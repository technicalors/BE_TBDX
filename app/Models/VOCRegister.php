<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VOCRegister extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 0;
    public const STATUS_REPLIED = 1;
    public const STATUS_CANCLE = 2;

    protected $table = 'voc_registers';
    protected $fillable = [
        'voc_type_id',
        'no',
        'title',
        'content',
        'solution',
        'registered_by',
        'registered_at',
        'reply',
        'replied_by',
        'replied_at',
        'status',
    ];

    public function type(): HasOne
    {
        return $this->hasOne(VOCType::class, 'id', 'voc_type_id');
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(CustomUser::class, 'registered_by');
    }

    public function replier(): BelongsTo
    {
        return $this->belongsTo(CustomUser::class, 'replied_by');
    }
}
