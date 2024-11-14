<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WareHouseMLT extends Model
{
    use HasFactory;
    protected $table = 'warehouse_mlt';
    protected $fillable = ['name'];
}
