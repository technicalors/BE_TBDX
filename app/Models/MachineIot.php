<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineIOT extends Model
{
    use HasFactory;

    protected $table = "machine_iot";

    protected $casts=[
        "data"=>"json"
    ];
}
