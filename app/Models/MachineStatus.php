<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineStatus extends Model
{
    use HasFactory;
    protected $table = "machine_status";

    public static function create($machine_id, $status)
    {
        $res = new MachineStatus();
        $res->machine_id = $machine_id;
        $res->status = $status;
        $res->save();
        return $res;
    }

    public static function setValue($machine_id, $value)
    {
        $res =  MachineStatus::where("machine_id", $machine_id)->first();
        if (!$res) $res = self::create($machine_id, $value);

        $res->status  = $value;
        $res->save();
        return true;
    }
    public static function active($machine_id)
    {
        return self::setValue($machine_id, 1);
    }
    public static function reset($machine_id)
    {
        return self::setValue($machine_id, 0);
    }
    public static function deactive($machine_id)
    {
        return self::setValue($machine_id, -1);
    }

    public static function getStatus($machine_id)
    {
        $res =  MachineStatus::where("machine_id", $machine_id)->first();
        if (!$res) $res =  self::create($machine_id, -1);
        return $res->status;
    }
    public static function getRecord($machine_id)
    {
        $res =  MachineStatus::where("machine_id", $machine_id)->first();
        if (!$res) $res =  self::create($machine_id, -1);
        return $res;
    }
}
