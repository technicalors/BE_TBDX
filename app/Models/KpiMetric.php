<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiMetric extends Model
{
    use HasFactory;
    protected $table="kpi_metrics";
    protected $fillable = ['id','name','code','unit'];
}
