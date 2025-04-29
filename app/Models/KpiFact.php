<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiFact extends Model
{
    use HasFactory;
    protected $table="kpi_metrics";
    protected $fillable = ['snapshot_date','kpi_metric_id','aging_range_id', 'value'];
}
