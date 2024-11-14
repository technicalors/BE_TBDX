<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class Color extends Model
{
    use HasFactory,UUID;
    protected $table = 'colors';
    protected $fillable = ['id','name'];

}
