<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LSX extends Model
{
    use HasFactory;
    use \Awobaz\Compoships\Compoships;
    protected $table = 'lo_sx';
    protected $fillable = ['id', 'so_luong', 'created_at', 'updated_at'];
    protected $casts = ['id'=>'string'];
}
