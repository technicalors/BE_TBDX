<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineMap extends Model
{
    use HasFactory;
    protected $table = "machine_map";
    protected $fillable=['position_id', 'lo_sx', 'ma_vat_tu'];
}
