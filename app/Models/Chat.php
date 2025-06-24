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

class Chat extends Model
{
    use HasFactory, UUID;

    protected $fillable = [
        'type',        // 'private' | 'group'
        'name',        // tên nhóm (nullable nếu private)
        'avatar',      // url/avatar nhóm
        'created_by',  // user_id người khởi tạo
    ];

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
                    ->withPivot(['last_read_message_id', 'last_read_at'])
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
}
