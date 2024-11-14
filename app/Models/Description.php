<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Description extends Model
{
    use HasFactory,UUID;
    protected $hidden = ['created_at', 'updated_at'];
    protected $fillable = ['name'];
}
