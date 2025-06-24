<?php
// app/Models/ChatUser.php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MessageMention extends Pivot
{
    protected $table = 'message_mentions';

    protected $fillable = [
        'message_id',
        'user_id',
    ];
}
