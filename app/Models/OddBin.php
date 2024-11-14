<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OddBin extends Model
{
    use HasFactory;
    protected $table = "odd_bin";
    protected $fillable = ['product_id','lo_sx','so_luong'];
}
