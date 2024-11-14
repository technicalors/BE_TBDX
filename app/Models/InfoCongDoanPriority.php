<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfoCongDoanPriority extends Model
{
    use HasFactory;
    protected $table = "info_cong_doan_priority";
    protected $fillable = ['info_cong_doan_id', 'priority'];
}
