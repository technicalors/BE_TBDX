<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Chat;
use App\Models\User;
use App\Models\Message;
use App\Events\MessageSent;
use App\Events\MessageRead;
use App\Events\ChatMemberAdded;
use App\Events\ChatMemberRemoved;
use App\Events\ChatUpdated;
use App\Models\Attachment;
use App\Models\CustomUser;
use App\Traits\API;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    use API;
    /**
     * GET /api/chats
     * Danh sách các chat của user hiện tại
     */
    public function index(Request $request)
    {
        $user = CustomUser::find($request->user()->id);

        $chats = $user->chats()
            ->with([
                'participants:id,name,avatar',
                'lastMessage.sender:id,name,avatar,username'
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($chat) use ($user) {
                if ($chat->type === 'private') {
                    // Tìm người chung phòng (không phải bản thân)
                    $otherParticipant = $chat->participants->first(function ($participant) use ($user) {
                        return $participant->id !== $user->id;
                    });
                    // Gán tên người chung phòng vào tên phòng
                    $chat->name = $otherParticipant->name;
                }
                if($chat->lastMessage){
                    $chat->timestamp = $chat->lastMessage->created_at;
                } else {
                    $chat->timestamp = $chat->created_at;
                }
                return $chat;
            })->sortByDesc('timestamp')->values();

        return $this->success($chats);
    }

    /**
     * POST /api/chats
     * Tạo nhóm mới
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type'      => 'required|in:private,group',
            'name'      => 'required_if:type,group|string|max:100',
            'avatar'    => 'nullable|string',
            'members'   => 'required_if:type,group|array',
            'members.*' => 'exists:users,id',
            'recipient_id' => 'required_if:type,private|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->failure('', $validator->errors()->first());
        }

        $data = $validator->validated();
        $me = $request->user()->id;

        $chat = DB::transaction(function () use ($data, $me) {
            // Private chat: lazy-create giữa A và B
            if ($data['type'] === 'private') {
                [$a, $b] = $me < $data['recipient_id']
                    ? [$me, $data['recipient_id']]
                    : [$data['recipient_id'], $me];

                $existing = Chat::where('type', 'private')
                    ->whereHas('participants', function ($q) use ($a, $b) {
                        $q->whereIn('user_id', [$a, $b]);
                    }, '=', 2)
                    ->first();

                if ($existing) {
                    return $existing;
                }

                $chat = Chat::create([
                    'type'       => 'private',
                    'created_by' => $me,
                ]);
                $chat->participants()->attach([$a, $b]);

                return $chat;
            }

            // Group chat:
            $chat = Chat::create([
                'type'       => 'group',
                'name'       => $data['name'],
                'avatar'     => $data['avatar'] ?? null,
                'created_by' => $me,
            ]);
            $members = array_unique(array_merge([$me], $data['members']));
            $chat->participants()->attach($members);

            return $chat;
        });

        // Phát event update chat list
        broadcast(new ChatUpdated($chat))->toOthers();

        return $this->success($chat);
    }

    /**
     * PATCH /api/chats/{chat}
     * Cập nhật tên/avatar nhóm
     */
    public function update(Request $request, Chat $chat)
    {
        $this->authorize('update', $chat);

        $validator = Validator::make($request->all(), [
            'name'   => 'sometimes|required_if:type,group|string|max:100',
            'avatar' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->failure('', $validator->errors()->first());
        }

        $data = $validator->validated();
        $chat->update($data);

        broadcast(new ChatUpdated($chat))->toOthers();

        return $this->success($chat);
    }

    /**
     * POST /api/chats/{chat}/members
     * Thêm thành viên vào nhóm
     */
    public function addMember(Request $request, Chat $chat)
    {
        $this->authorize('update', $chat);

        $validator = Validator::make($request->all(), [
            'user_ids'   => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->failure('', $validator->errors()->first());
        }

        $ids = $validator->validated()['user_ids'];
        $chat->participants()->syncWithoutDetaching($ids);

        foreach ($ids as $uid) {
            broadcast(new ChatMemberAdded($chat->id, $uid))->toOthers();
        }

        return $this->success([], 'Đã xoá');
    }

    /**
     * DELETE /api/chats/{chat}/members/{user}
     * Bớt thành viên khỏi nhóm
     */
    public function removeMember(Chat $chat, User $user)
    {
        $this->authorize('update', $chat);

        $chat->participants()->detach($user->id);

        broadcast(new ChatMemberRemoved($chat->id, $user->id))->toOthers();

        return response()->noContent();
    }

    /**
     * GET /api/chats/{chat}/messages
     * Lấy lịch sử message (cursor-based pagination)
     */
    public function messages(Request $request, $chat_id)
    {
        $limit  = $request->input('limit', 50);
        $before = $request->input('before');

        $chat = Chat::find($chat_id);

        if (!$chat) {
            return $this->failure($chat_id, 'Không tìm thấy dữ liệu');
        }

        $query = $chat->messages()->with(['sender:id,name,avatar,username', 'replyTo.sender:id,name', 'attachments']);

        if ($before) {
            $query->where('id', '<', $before);
        }

        $msgs = $query->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()  // để FE hiển thị từ cũ->mới
            ->values();

        return $this->success($msgs);
    }

    /**
     * POST /api/chats/{chat}/messages
     * Gửi message mới (cả text, file, reply…)
     */
    public function sendMessage(Request $request, $chat_id)
    {
        $chat = Chat::find($chat_id);
        if (!$chat) {
            return $this->failure($chat_id, 'Không tìm thấy đoạn chat');
        }
        $validator = Validator::make($request->all(), [
            'chat_id'             => 'required',
            'type'                => 'required|in:text,image,file,system',
            'content_text'             => 'nullable|string',
            'content_json'             => 'nullable|json',
            'metadata'            => 'nullable|array',
            'reply_to_message_id' => [
                'nullable',
                'integer',
                \Illuminate\Validation\Rule::exists('messages', 'id')
                    ->where('chat_id', $chat->id)
            ],
            'images.*'  => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120', // max 5MB mỗi ảnh,
            'mentions' => 'array',
            'mentions.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->failure('', $validator->errors()->first());
        }

        $data = $request->all();
        if(!empty($data['content_json'])){
            $data['content_json'] = json_decode($data['content_json'], true);
        }
        $msg = $chat->messages()->create([
            'chat_id'             => $data['chat_id'],
            'sender_id'           => $request->user()->id,
            'type'                => $data['type'],
            'content_text'        => $data['content_text'] ?? null,
            'content_json'        => $data['content_json'] ?? null,
            'metadata'            => $data['metadata'] ?? null,
            'reply_to_message_id' => $data['reply_to_message_id'] ?? null,
            'send_at'             => now()->getTimestampMs(),
        ]);

        // Nếu có ảnh upload
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                // lưu file, ví dụ: storage/app/public/chat_images
                $path = $file->store($data['type'] === 'image' ? 'chat_images' : 'chat_files', 'public');

                // tạo bản ghi attachment
                $msg->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_type' => $file->getMimeType(),
                ]);
            }
        }

        if ($request->filled('mentions')) {
            $msg->mentions()->sync($request->mentions);
        }

        // Load relationships before broadcasting
        $msg->load(['sender:id,name,avatar,username', 'replyTo.sender:id,name', 'attachments', 'mentions']);

        broadcast(new MessageSent($msg))->toOthers();

        return $this->success($msg);
    }

    /**
     * POST /api/chats/{chat}/messages
     * Gửi message mới (cả text, file, reply…)
     */
    public function uploadFiles(Request $request, $chat_id)
    {
        $chat = Chat::find($chat_id);
        if (!$chat) {
            return $this->failure($chat_id, 'Không tìm thấy đoạn chat');
        }
        $validator = Validator::make($request->all(), [
            'chat_id'             => 'required',
            'type'                => 'required|in:text,image,file,system',
            'content_text'             => 'nullable|string',
            'content_json'             => 'nullable|json',
            'metadata'            => 'nullable|array',
            'reply_to_message_id' => [
                'nullable',
                'integer',
                \Illuminate\Validation\Rule::exists('messages', 'id')
                    ->where('chat_id', $chat->id)
            ],
            'images.*'  => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120', // max 5MB mỗi ảnh
        ]);

        if ($validator->fails()) {
            return $this->failure('', $validator->errors()->first());
        }

        // Nếu có ảnh upload
        if ($request->hasFile('files')) {
            $messages = [];
            foreach ($request->file('files') as $file) {
                // lưu file, ví dụ: storage/app/public/chat_images
                $path = $file->store('chat_files', 'public');
                $data = $request->all();
                $msg = $chat->messages()->create([
                    'chat_id'             => $data['chat_id'],
                    'sender_id'           => $request->user()->id,
                    'type'                => 'file',
                    'send_at'             => now()->getTimestampMs(),
                ]);
                // tạo bản ghi attachment
                $msg->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_type' => $file->getMimeType(),
                ]);
                $msg->load(['sender:id,name,avatar,username', 'replyTo.sender:id,name', 'attachments']);
                $messages[] = $msg;
            }
        }
        broadcast(new MessageSent($messages))->toOthers();

        return $this->success($messages);
    }

    /**
     * POST /api/chats/{chat}/read
     * Đánh dấu đã đọc tin tới message_id
     */
    public function markAsRead(Request $request, Chat $chat)
    {
        $this->authorize('view', $chat);

        $validator = Validator::make($request->all(), [
            'message_id' => 'required|integer|exists:messages,id',
        ]);

        if ($validator->fails()) {
            return $this->failure('', $validator->errors()->first());
        }

        $msgId = $validator->validated()['message_id'];

        DB::table('chat_user')
            ->where(['chat_id' => $chat->id, 'user_id' => Auth::id()])
            ->update([
                'last_read_message_id' => $msgId,
                'last_read_at'         => now(),
            ]);

        broadcast(new MessageRead($chat->id, Auth::id(), $msgId))->toOthers();

        return $this->success([], 'Đã đọc');
    }

    public function downloadFile($file_name) {
        return $file_name;
        $att = Attachment::where('file_path', 'chat_files/' . $file_name)->first();
        if (!$att) {
            abort(404);
        }
        return response()->download(storage_path('app/public/' . $att->file_path));
    }
}
