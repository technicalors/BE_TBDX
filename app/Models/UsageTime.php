<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageTime extends Model
{
    use HasFactory;
    // public $timestamps = false;
    protected $fillable = ['number_of_user', 'date', 'usage_time'];
    public function user(){
        return $this->hasMany(CustomUser::class, 'user_id');
    }
}
