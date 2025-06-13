<?php
// app/Models/Message.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'sender_id',
        'type',                // 'text','image','file','system',...
        'content',
        'metadata',
        'read_at',
        'reply_to_message_id',
    ];

    protected $casts = [
        'metadata'            => 'array',
        'read_at'             => 'datetime',
    ];

    /**
     * Chat chứa message này
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Người gửi
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Tin nhắn này trả lời (reply to)
     */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_message_id');
    }

    /**
     * Những tin nhắn reply to this
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'reply_to_message_id');
    }

    /**
     * Attachments nếu có
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
