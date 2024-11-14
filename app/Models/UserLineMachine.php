<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLineMachine extends Model
{
    use HasFactory;
    protected $table = 'user_line_machine';
    protected $fillable = ['user_id','line_id','machine_id'];
    public function user(){
        return $this->hasOne(CustomUser::class, 'id', 'user_id');
    }
    public function line(){
        return $this->hasOne(Line::class, 'id', 'line_id');
    }
    public function machine(){
        return $this->hasOne(Machine::class, 'id', 'machine_id');
    }
}
