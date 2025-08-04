<?php
// app/Models/Chat.php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chat extends Model
{
    use HasFactory, UUID, SoftDeletes;

    protected $fillable = [
        'type',        // 'private' | 'group'
        'name',        // tên nhóm (nullable nếu private)
        'avatar',      // url/avatar nhóm
        'created_by',  // user_id người khởi tạo
    ];

    protected $appends = ['muted'];

    public function getMutedAttribute()
    {
        // Nếu quan hệ participants chưa load thì load để tránh null
        if (!$this->relationLoaded('participants')) {
            $this->load('participants');
        }

        $userId = auth()->id();
        $participant = $this->participants->firstWhere('id', $userId);

        return $participant ? $participant->pivot->muted : null;
    }

    public function getNameAttribute($value)
    {
        // Nếu là group thì trả về name có sẵn
        if ($this->type !== 'private') {
            return $value;
        }

        // Lấy id user hiện tại
        $userId = auth()->id(); // hoặc request()->user()->id

        // Nếu chỉ có 1 participant (chat với chính mình)
        if ($this->participants->count() <= 1) {
            return $this->participants->first()->name ?? '';
        }

        // Tìm người còn lại
        $otherParticipant = $this->participants->first(function ($participant) use ($userId) {
            return $participant->id !== $userId;
        });

        return $otherParticipant?->name ?? '';
    }

    public function getTimestampAttribute()
    {
        if ($this->lastMessage) {
            return $this->lastMessage->created_at;
        } else {
            return $this->updated_at;
        }
    }

    /**
     * Người tạo (chỉ group cần)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Thành viên trong chat
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(CustomUser::class, 'chat_user', 'chat_id', 'user_id')
            ->using(ChatUser::class)
            ->withPivot(['last_read_message_id', 'last_read_at', 'muted'])
            ->withTimestamps();
    }

    /**
     * Tin nhắn trong chat
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): HasOne
    {
        // Với Laravel 8+
        return $this->hasOne(Message::class)->orderBy('send_at', 'desc');

        // Nếu Laravel <8, dùng:
        // return $this->hasOne(Message::class)->orderBy('created_at', 'desc');
    }

    public function attachments()
    {
        // Attachment ← Message ← Chat
        return $this->hasManyThrough(
            Attachment::class,
            Message::class,
            'chat_id',      // FK trên messages
            'message_id',   // FK trên attachments
            'id',           // PK của chats
            'id'            // PK của messages
        );
    }
}
