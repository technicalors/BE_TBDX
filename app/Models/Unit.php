<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = ['id','name'];
    protected $hidden=['created_at',"updated_at"];
    use HasFactory,UUID;
}
