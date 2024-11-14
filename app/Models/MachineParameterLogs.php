<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineParameterLogs extends Model
{
    use HasFactory;
    use \Awobaz\Compoships\Compoships;
    protected $fillable = ['id', 'machine_id', 'lo_sx', 'user_id','info'];
    protected $casts = ['info' => 'json'];

    public function plan(){
        return $this->hasOne(ProductionPlan::class,['lo_sx','machine_id'],['lo_sx','machine_id']);
    }
}
