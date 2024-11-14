<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineLog extends Model
{
    use HasFactory;
    use \Awobaz\Compoships\Compoships;
    protected $fillable = ['machine_id', 'start_time', 'end_time', 'error_machine_id', 'user_id', 'lo_sx', 'handle_time'];
    public function machine()
    {
        return $this->belongsTo(Machine::class, "machine_id");
    }

    public function user()
    {
        return $this->belongsTo(CustomUser::class, "user_id");
    }

    public function error_machine()
    {
        return $this->belongsTo(ErrorMachine::class, "error_machine_id");
    }

    static public function getLatestRecord($machine_id)
    {
        return MachineLog::where("machine_id", $machine_id)->orderBy("created_at", "desc")->get()->first();
    }

    public function plan()
    {
        return $this->hasOne(ProductionPlan::class, ['lo_sx', 'machine_id'], ['lo_sx', 'machine_id']);
    }

    public static function UpdateStatus($request)
    {
        $isRun  = $request['status'];
        $res = self::getLatestRecord($request['machine_id']);
        if ($isRun == 1 && isset($res) && !isset($res->end_time)) {
            if (strtotime($request['timestamp']) -  strtotime($res->start_time) > 600) {
                $res->end_time = $request['timestamp'];
                $res->save();
                return $res;
            } else {
                $res->delete();
            }
        }
        if ($isRun != 1 && (!isset($res) || isset($res->end_time))) {
            $res = new MachineLog();
            $res->machine_id = $request['machine_id'];
            $res->start_time = $request['timestamp'];
            $res->lo_sx = $request['lo_sx'];
            $res->save();
            return $res;
        }
        return $res;
    }
}
