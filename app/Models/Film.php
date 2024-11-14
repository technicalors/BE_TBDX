<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Film extends Model
{
    use HasFactory;
    protected $table = "films";
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['id', 'name'];
}
