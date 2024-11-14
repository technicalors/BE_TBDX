<?php

namespace App\Console\Commands;

use App\Models\Machine;
use App\Models\MachineParameterLogs;
use App\Models\Shift as ModelsShift;
use Illuminate\Console\Command;

class Shift extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shift:cron';

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
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $shifts = ModelsShift::all();
        $machines = Machine::all();
        foreach($shifts as $key=>$shift){
            $check = MachineParameterLogs::whereDate('start_time',date('Y-m-d'))->whereTime('start_time','=',$shift->start_time)->first();
            if(strtotime(date('H:i:s')) > (strtotime($shift->start_time) -  7200) && strtotime(date('H:i:s')) < strtotime($shift->start_time) && !$check){
                $start_time = date('Y-m-d H:i:s',strtotime($shift->start_time));
                $end_time = strtotime($shift->start_time) > strtotime($shift->end_time) ? date('Y-m-d H:i:s',strtotime($shift->end_time. ' +1 day')) : date('Y-m-d H:i:s',strtotime($shift->end_time));
                while(strtotime($start_time) < strtotime($end_time)){
                    $end = date('Y-m-d H:i:s',strtotime($start_time ) + 7200);
                    foreach($machines as $key=>$machine){
                        MachineParameterLogs::create(['start_time'=>$start_time,'end_time'=>$end,'machine_id'=>$machine->code]);
                    }
                    $start_time = $end;
                }
            }
        }
    }
}
