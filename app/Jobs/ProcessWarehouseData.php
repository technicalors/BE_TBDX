<?php

namespace App\Jobs;

use App\Models\LSXPallet;
use App\Models\Pallet;
use App\Models\WarehouseFGLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWarehouseData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $palletIds = WarehouseFGLog::where('type', 1)->distinct()->pluck('pallet_id');
        $palletIds->chunk(100)->each(function ($chunkedPalletIds) {
            $logs = WarehouseFGLog::with('order')->where('type', 1)
                ->whereIn('pallet_id', $chunkedPalletIds)
                ->get();
        
            foreach ($logs->groupBy('pallet_id') as $palletId => $groupedLogs) {
                // Tính toán và xử lý như trên
                $totalQuantity = $groupedLogs->sum('so_luong');
                $uniqueLSX = $groupedLogs->unique('lo_sx')->count();
        
                $pallet = Pallet::updateOrCreate(
                    ['id' => $palletId],
                    [
                        'so_luong' => $totalQuantity,
                        'number_of_lot' => $uniqueLSX,
                        'updated_at' => now(),
                    ]
                );
        
                foreach ($groupedLogs as $log) {
                    if(empty($log->order)){
                        continue;
                    }
                    LSXPallet::updateOrCreate(
                        ['pallet_id' => $palletId, 'lo_sx' => $log->lo_sx],
                        [
                            'so_luong' => $log->so_luong,
                            'mdh' => $log->order->mdh ?? null,
                            'mql' => $log->order->mql ?? null,
                            'customer_id' => $log->order->customer_id ?? null,
                            'order_id' => $log->order_id,
                        ]
                    );
                }
            }
        });
    }
}
