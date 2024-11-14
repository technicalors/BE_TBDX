<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VOCType extends Model
{
    use HasFactory;
    protected $table = 'voc_types';
    protected $fillable = [
        'name',
    ];
}
