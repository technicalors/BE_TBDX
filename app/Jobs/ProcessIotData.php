<?php

namespace App\Jobs;

use App\Admin\Controllers\ApiController;
use App\Admin\Controllers\ApiUIController;
use App\Events\ProductionUpdated;
use App\Models\CustomUser;
use App\Models\Line;
use App\Models\Machine;
use App\Models\Tracking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessIotData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;
    public $apiController;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
        $this->apiController = new ApiController(new CustomUser(), new ApiUIController());
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $queueName = $this->job->getQueue();
        Log::info("Running job on queue: {$queueName}");
        $start = microtime(true);
        $request = $this->data;
        if (!isset($request['device_id'])) return 'Không có mã máy';
        $machine = Machine::with('line')->where('device_id', $request['device_id'])->first();
        $line = $machine->line;
        $tracking = Tracking::where('machine_id', $machine->id)->first();
        $broadcastData = [];
        switch ($line->id) {
            case Line::LINE_SONG:
                $broadcastData = $this->apiController->CorrugatingProduction($request, $tracking, $machine);
                break;
            case Line::LINE_IN:
                if ($machine->id === 'CH02' || $machine->id === 'CH03') {
                    $broadcastData = $this->apiController->TemPrintProductionCH($request, $tracking, $machine);
                } else {
                    $broadcastData = $this->apiController->TemPrintProduction($request, $tracking, $machine);
                }
                break;
            case Line::LINE_DAN:
                $broadcastData = $this->apiController->TemGluingProduction($request, $tracking, $machine);
                break;
            default:
                $broadcastData = [];
                break;
        }
        broadcast(new ProductionUpdated($broadcastData));
        $end = microtime(true);
        $executionTime = $end - $start;
        $formattedTime = number_format($executionTime, 3, '.', '');
        Log::info("Thời gian xử lý {$machine->id}: {$formattedTime} giây");
        return null;
    }
}
