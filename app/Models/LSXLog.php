<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LSXLog extends Model
{
    use HasFactory;
    use \Awobaz\Compoships\Compoships;
    protected $table = 'l_s_x_logs';
    protected $fillable = ['lo_sx', 'machine_id', 'mapping','params','thu_tu_uu_tien', 'info', 'map_time'];
    protected $casts=[
        "params"=>"json",
        "info"=>"json"
    ];
    public function plan(){
        return $this->hasOne(ProductionPlan::class,['lo_sx','machine_id'],['lo_sx','machine_id']);
    }
    public function tem(){
        return $this->hasOne(Tem::class,['lo_sx','machine_id'],['lo_sx','machine_id']);
    }
}
