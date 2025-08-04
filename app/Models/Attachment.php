<?php
// app/Models/Attachment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'type',
    ];

    /**
     * Tin nhắn đính kèm
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
