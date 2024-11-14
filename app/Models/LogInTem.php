<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogInTem extends Model
{
    use HasFactory;

    protected $table = "log_in_tem";
    protected $fillable=['lot_id','log', 'type'];
    protected $casts=[
        "log"=>"json"
    ];
}
