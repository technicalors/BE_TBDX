<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TieuChuanNCC extends Model
{
    use HasFactory;
    protected $table = "tieu_chuan_ncc";
    protected $fillable = ['ma_ncc', 'requirements'];
    protected $casts = ['requirements' => 'json'];
}
