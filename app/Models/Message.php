<?php
// app/Models/Message.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'sender_id',
        'type',                // 'text','image','file','system',...
        'content_text',
        'content_json',
        'metadata',
        'read_at',
        'reply_to_message_id',
        'send_at'
    ];

    protected $appends = ['from_now'];

    protected $casts = [
        'metadata'            => 'array',
        'read_at'             => 'datetime',
        'content_json' => 'array'
    ];

    /**
     * Get human readable time from send_at
     */
    public function getFromNowAttribute()
    {
        return $this->send_at ? \Carbon\Carbon::createFromTimestampMs($this->send_at)->locale('vi')->diffForHumans() : null;
    }

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

    public function mentions(): BelongsToMany
    {
        return $this->belongsToMany(CustomUser::class, 'message_mentions', 'message_id', 'user_id')
        ->using(MessageMention::class)
        ->withTimestamps();
    }
}
