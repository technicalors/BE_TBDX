<?php
// app/Models/ChatUser.php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ChatUser extends Pivot
{
    protected $table = 'chat_user';

    protected $fillable = [
        'chat_id',
        'user_id',
        'last_read_message_id',
        'last_read_at',
        'muted',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }
}
