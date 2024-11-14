<?php

namespace App\Console\Commands;

use App\Models\Insulation;
use App\Models\IOTLog;
use App\Models\LogWarningParameter;
use App\Models\Lot;
use App\Models\MachineIOT;
use App\Models\Machine;
use App\Models\MachineParameter;
use App\Models\MachineParameterLogs;
use App\Models\ThongSoMay;
use App\Models\Tracking;
use PhpMqtt\Client\Facades\MQTT;
use Illuminate\Console\Command;

class MqttListener extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    private $mqtt;

    public function __construct()
    {
        parent::__construct();
        $this->mqtt = MQTT::connection();
    }


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->mqtt->subscribe('TLP/Chatluong', function (string $topic, string $message) {
            // May want to comment out for production to keep logs clear
            $request = json_decode($message);
            $log = new IOTLog();
            $log->data = $request;
            $log->save();
            $log_iot = new MachineIOT();
            $log_iot->data = $request;
            $log_iot->save();
            if($request->machine_id == 'bao-on'){
                $insulation = Insulation::find(1);
                if($insulation){
                    $insulation->update(['t_ev'=>$request->t_ev,'e_hum'=>$request->e_hum]);
                }else{
                    Insulation::create(['t_ev'=>$request->t_ev,'e_hum'=>$request->e_hum]);
                }
            }
            $tracking = Tracking::where('machine_id', $request->machine_id)->first();
            LogWarningParameter::checkParameter($request);
            if (!$tracking) {
                $tracking = new Tracking();
                $tracking->machine_id = $request->machine_id;
                $tracking->timestamp = $request->timestamp;
                $tracking->save();
            }
            if (is_null($tracking->timestamp)) {
                $tracking->update(['timestamp' => $request->timestamp]);
            }
            if (!is_null($tracking->timestamp)) {
                if ($request->timestamp  >= ($tracking->timestamp +  300)) {
                    $start = $tracking->timestamp;
                    $end = $tracking->timestamp +  300;
                    $logs = MachineIOT::where('data->record_type', "cl")->where('data->machine_id', $request->machine_id)->where('data->timestamp', '>=', $start)->where('data->timestamp', '<=', $end)->pluck('data')->toArray();
                    $parameters = MachineParameter::where('machine_id', $request->machine_id)->where('is_if', 1)->pluck('parameter_id')->toArray();
                    $arr = [];
                    foreach ($parameters as $key => $parameter) {
                        $arr[$parameter] = 0;
                        foreach ((array) $logs as $key => $log) {
                            if (isset($log[$parameter])) {
                                if (in_array($parameter, ['uv1', 'uv2', 'uv3'])) {
                                    $arr[$parameter] = $log[$parameter];
                                } else {
                                    $arr[$parameter] = (float)$arr[$parameter] + (float)$log[$parameter];
                                }
                            }
                        }
                    }
                    foreach ($parameters as $key => $parameter) {
                        if (!in_array($parameter, ['uv1', 'uv2', 'uv3'])) {
                            $arr[$parameter] = $logs ? number_format($arr[$parameter] / count($logs), 2) : 0;
                        }
                    }
                    MachineIOT::where('data->record_type', "cl")->where('data->machine_id', $request->machine_id)->delete();
                    Tracking::where('machine_id', $request->machine_id)->update(['timestamp' => $request->timestamp]);
                    MachineParameterLogs::where('machine_id', $request->machine_id)->where('start_time', '<=', date('Y-m-d H:i:s', $request->timestamp))->where('end_time', '>=', date('Y-m-d H:i:s', $request->timestamp))->update(['data_if' => $arr]);
                    $machine = Machine::where('code',$request->machine_id)->first();
                    if ($machine) {
                        $line = $machine->line;
                        $updated_tracking = Tracking::where('machine_id', $machine->code)->first();
                        $lot = Lot::find($updated_tracking->lot_id);
                        if($lot){
                            $thong_so_may = new ThongSoMay();
                            $ca = (int)date('H', $request->timestamp);
                            $thong_so_may['ngay_sx'] = date('Y-m-d H:i:s');
                            $thong_so_may['ca_sx'] = ($ca >= 7 && $ca <= 17) ? 1 : 2;
                            $thong_so_may['xuong'] = '';
                            $thong_so_may['line_id'] = $line->id;
                            $thong_so_may['lot_id'] = $lot ? $lot->id : null;
                            $thong_so_may['lo_sx'] = $lot ? $lot->lo_sx : null;
                            $thong_so_may['machine_code'] = $machine->code;
                            $thong_so_may['data_if'] = $arr;
                            $thong_so_may['date_if'] = date('Y-m-d H:i:s', $request->timestamp);;
                            $thong_so_may->save();
                        }
                        
                    }
                }
            }
            $this->info("Topic: {$request->machine_id} | Message: {$message}!");       
        }, 2);
        $this->mqtt->loop(true,true);
    }
}
