<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogWarningParameter extends Model
{
    use HasFactory;
    protected $table = 'log_warning_parameter';
    protected $fillable = ['parameter_id','machine_id','value'];

    public static function checkParameter($request){
        $params = (array) $request;
        $scenarios = Scenario::all();
        $mark = [];
        foreach ($scenarios as $item) {
            $mark[$item->parameter_id] = $item;
        }
        foreach ($params as $key => $value) {
            if (isset($mark[$key])) {
                $tm = $mark[$key];
                $f1 = (float)$value > (float)$tm->tieu_chuan_kiem_soat_tren;
                $f2 = (float)$value < (float)$tm->tieu_chuan_kiem_soat_duoi;
                $f3 = $value != -1;
                $f4 = (float)$value <= (float)$tm->tieu_chuan_kiem_soat_tren;
                $f5 = (float)$value >= (float)$tm->tieu_chuan_kiem_soat_duoi;
                if (($f1 || $f2) && $f3) {
                    $check_log = LogWarningParameter::where('parameter_id',$key)->first();
                    if($check_log){
                        $check_monitor = Monitor::where('parameter_id',$key)->where('machine_id',$request->machine_id)->where('status', 0)->first();
                        if($check_monitor){
                            $check_monitor->update(['content'=>$tm->hang_muc.': '.$value]);
                        }else{
                            $monitor = new Monitor();
                            $monitor->type = 'cl';
                            $monitor->content =  $tm->hang_muc;
                            $monitor->value =  $value;
                            $monitor->parameter_id = $key;
                            $monitor->machine_id = $request->machine_id;
                            $monitor->status = 0;
                            $monitor->save();
                        }
                    }else{
                        $log = new LogWarningParameter();
                        $log->parameter_id = $key;
                        $log->value = $value;
                        $log->machine_id = $request->machine_id;
                        $log->save();
                    }
                }elseif($f4 && $f5 && $f3){
                    LogWarningParameter::where('parameter_id',$key)->where('machine_id',$request->machine_id)->delete();
                    Monitor::where('parameter_id',$key)->where('machine_id',$request->machine_id)->where('status', 0)->update(['status'=>1]);
                }
            }
        }
    }
}
