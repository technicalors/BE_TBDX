<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tracking extends Model
{
    use HasFactory;
    protected $table = "tracking";
    protected $fillable = ['machine_id', 'timestamp', 'lo_sx', 'is_running', 'pre_counter', 'error_counter', 'so_ra', 'thu_tu_uu_tien', 'set_counter', 'sl_kh', 'parent_id', 'status', 'length_cut'];

    public static function createx($machine_id)

    {
        $res = new Tracking();
        $res->machine_id = $machine_id;
        $res->save();
        return $res;
    }

    public static function updateData($machine_id, $input=null, $output=null)
    {
        $res = Tracking::where("machine_id", $machine_id)->first();

        if (!$res) $res = self::createx($machine_id);

        if (isset($input)) {
            $res->input = $input;
        }
        if (isset($output)) {
            $res->output = $output;
        }

        // dd($input,$output,$res);

        $res->save();
        return $res;
    }

    public static function getData($machine_id)
    {
        $res = Tracking::where("machine_id", $machine_id)->first();
        if (!$res) $res = self::createx($machine_id);
        return $res;
    }
}
