<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QCLog extends Model
{
    use \Awobaz\Compoships\Compoships;
    use HasFactory;
    protected $table = "qc_logs";
    protected $fillable = ['lo_sx', 'info', 'machine_id'];
    protected $casts = ['info' => 'json'];
}
