<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DRC extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'drc';
    protected $fillable = ['id', 'ten_quy_cach', 'ct_dai', 'ct_rong', 'ct_cao', 'description'];
    public $incrementing = false;
    protected $casts = [
        "id" => "string"
    ];
}
