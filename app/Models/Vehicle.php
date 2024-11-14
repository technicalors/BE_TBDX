<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;
    protected $fillable = ['id','weight','user1','user2','user3'];
    protected $casts = ['id'=>'string'];
    public function driver(){
        return $this->hasOne(CustomUser::class, 'id', 'user1');
    }
    public function assistant_driver1(){
        return $this->hasOne(CustomUser::class, 'id', 'user2');
    }
    public function assistant_driver2(){
        return $this->hasOne(CustomUser::class, 'id', 'user3');
    }
}
