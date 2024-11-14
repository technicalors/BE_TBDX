<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Film extends Model
{
    use HasFactory;
    protected $table = "formulas";
    protected $fillable = ['phan_loai_1', 'phan_loai_2', 'machine_id', 'he_so', 'function'];
}
