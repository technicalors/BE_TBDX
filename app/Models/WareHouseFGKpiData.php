<?php

namespace App\Models;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WareHouseFGKpiData extends Model
{
    use HasFactory;
    protected $table = 'warehouse_fg_kpi_data';
    protected $fillable = ['id', 'data', 'created_at', 'updated_at'];
    protected $casts = [
        'data' => 'json'
    ];
}
