<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineSpeed extends Model
{
    use HasFactory;
    protected $table = "machine_speed";
    protected $fillable = ['id', 'machine_id', 'speed', 'created_at', 'updated_at'];
}
