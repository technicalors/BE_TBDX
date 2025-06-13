<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Chat;
use App\Models\User;
use App\Models\Message;
use App\Events\MessageSent;
use App\Events\MessageRead;
use App\Events\ChatMemberAdded;
use App\Events\ChatMemberRemoved;
use App\Events\ChatUpdated;
use App\Models\CustomUser;
use App\Traits\API;

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
                'lastMessage.sender:id,name,avatar'
            ])
            ->orderBy('updated_at', 'desc')
            ->get();

        return $this->success($chats);
    }

    /**
     * GET /api/chats/{chat}/messages
     * Lấy lịch sử message (cursor-based pagination)
     */
    public function messages(Request $request, Chat $chat)
    {
        $this->authorize('view', $chat);

        $limit  = $request->input('limit', 50);
        $before = $request->input('before');

        $query = $chat->messages()->with(['sender:id,name,avatar','replyTo.sender:id,name']);

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
     * POST /api/chats
     * Tạo nhóm mới
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'type'      => 'required|in:private,group',
            'name'      => 'required_if:type,group|string|max:100',
            'avatar'    => 'nullable|string',
            'members'   => 'required_if:type,group|array',
            'members.*' => 'exists:users,id',
            'recipient_id' => 'required_if:type,private|exists:users,id',
        ]);

        $me = Auth::id();

        $chat = DB::transaction(function() use($data, $me) {
            // Private chat: lazy-create giữa A và B
            if ($data['type'] === 'private') {
                [$a, $b] = $me < $data['recipient_id']
                    ? [$me, $data['recipient_id']]
                    : [$data['recipient_id'], $me];

                $existing = Chat::where('type','private')
                    ->whereHas('participants', fn($q)=> $q->whereIn('user_id', [$a,$b]), fn($q)=> $q->havingRaw('COUNT(*)=2'))
                    ->first();

                if ($existing) {
                    return $existing;
                }

                $chat = Chat::create([
                    'type'       => 'private',
                    'created_by' => $me,
                ]);
                $chat->participants()->attach([$a,$b]);

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

        $data = $request->validate([
            'name'   => 'sometimes|required_if:type,group|string|max:100',
            'avatar' => 'nullable|string',
        ]);

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

        $ids = $request->validate([
            'user_ids'   => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ])['user_ids'];

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
     * POST /api/chats/{chat}/messages
     * Gửi message mới (cả text, file, reply…)
     */
    public function sendMessage(Request $request, Chat $chat)
    {
        $this->authorize('view', $chat);

        $data = $request->validate([
            'type'                => 'required|in:text,image,file,system',
            'content'             => 'nullable|string',
            'metadata'            => 'nullable|array',
            'reply_to_message_id' => [
                'nullable','integer',
                \Illuminate\Validation\Rule::exists('messages','id')
                    ->where('chat_id', $chat->id)
            ],
        ]);

        $msg = $chat->messages()->create([
            'sender_id'           => Auth::id(),
            'type'                => $data['type'],
            'content'             => $data['content'] ?? null,
            'metadata'            => $data['metadata'] ?? null,
            'reply_to_message_id' => $data['reply_to_message_id'] ?? null,
        ]);

        // Broadcast message mới
        broadcast(new MessageSent($msg))->toOthers();

        return $this->success($msg);
    }

    /**
     * POST /api/chats/{chat}/read
     * Đánh dấu đã đọc tin tới message_id
     */
    public function markAsRead(Request $request, Chat $chat)
    {
        $this->authorize('view', $chat);

        $msgId = $request->validate([
            'message_id' => 'required|integer|exists:messages,id',
        ])['message_id'];

        DB::table('chat_user')
          ->where(['chat_id'=>$chat->id,'user_id'=>Auth::id()])
          ->update([
            'last_read_message_id' => $msgId,
            'last_read_at'         => now(),
          ]);

        broadcast(new MessageRead($chat->id, Auth::id(), $msgId))->toOthers();

        return $this->success([], 'Đã đọc');
    }
}
