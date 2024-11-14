<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insulation extends Model
{
    use HasFactory;
    protected $table = 'insulation';
    protected $fillable = ['t_ev','e_hum'];
}
