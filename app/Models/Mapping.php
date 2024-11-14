<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mapping extends Model
{
    protected $table = 'mapping';
    protected $fillable = ['id', 'machine_id', 'lo_sx', 'position', 'user_id', 'info', 'map_time'];
    protected $casts = ['info' => 'json'];
}
