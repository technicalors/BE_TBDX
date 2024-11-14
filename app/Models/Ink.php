<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Ink extends Model
{
    use HasFactory;
    protected $table = "inks";
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['id', 'name'];
}
