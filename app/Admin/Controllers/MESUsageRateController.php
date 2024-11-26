<?php

namespace App\Admin\Controllers;

use App\Models\CustomUser;
use App\Models\KhuonData;
use App\Models\KhuonLink;
use App\Models\Machine;
use App\Models\MaintenanceStatistic;
use App\Models\PQCProcessing;
use App\Models\QCLog;
use App\Models\Shift;
use App\Models\UsageTime;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use App\Traits\API;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;
use stdClass;

class MESUsageRateController extends AdminController
{
    use API;

    public function calculateUsageTime($date = 'now')
    {
        $today = Carbon::parse('now')->format('Y-m-d');
        $query = CustomUser::query();
        $all = (clone $query)->count();
        $users = (clone $query)->whereDate('last_use_at', $today)->count();
        $data = UsageTime::updateOrCreate(
            ['date' => $today],
            ['number_of_user' => $all, 'usage_time' => $users]
        );
        return $data;
    }

    public function calculateMaintenanceMachine($date = 'now')
    {
        $today = Carbon::parse($date)->format('Y-m-d');
        $query = Machine::where('is_iot', 1);
        $all = (clone $query)->count();
        $maintained = (clone $query)->whereHas('maintenance', function ($q) use ($today) {
            $q->whereHas('detail', function ($subQuery) use ($today) {
                $subQuery->whereDate('start_date', $today); // Lọc theo ngày hiện tại
            });
        })->count();
        $data = MaintenanceStatistic::updateOrCreate(
            ['date' => $today],
            ['registered_machine' => $all, 'maintained_machine' => $maintained]
        );
        return $data;
    }

    public function calculatePQCProcessing($date = 'now')
    {
        $today = Carbon::parse($date)->format('Y-m-d');
        $query = QCLog::whereNotNull('info->phan_dinh')->whereDate('info->thoi_gian_vao', $today);
        $all = (clone $query)->count();
        $ok = (clone $query)->where('info->phan_dinh', 1)->count();
        $data = PQCProcessing::updateOrCreate(
            ['date' => $today],
            ['number_of_pqc' => $all, 'number_of_ok_pqc' => $ok]
        );
        return $data;
    }

    public function calculateKhuonBe($date = 'now')
    {
        $today = Carbon::parse($date)->format('Y-m-d');
        $cells = ['phan_loai_1', 'buyer_id', 'kho_khuon', 'dai_khuon', 'so_con', 'so_manh_ghep', 'khuon_id', 'machine_id',  'note', 'layout', 'supplier', 'ngay_dat_khuon'];
        $khuonLinks = KhuonLink::all();
        $all = 0;
        $hasData = 0;
        foreach ($khuonLinks as $khuon) {
            foreach ($cells as $cell) {
                $all++;
                if (!empty($khuon->$cell)) {
                    $hasData++;
                }
            }
        }
        $data = KhuonData::updateOrCreate(
            ['date' => $today],
            ['total_cells' => $all, 'cells_has_data' => $hasData]
        );
        return $data;
    }

    public function getTableSystemUsageRate(Request $request)
    {
        $start = date('Y-m-d', strtotime($request->start_date ?? 'now'));
        $end = date('Y-m-d', strtotime($request->end_date ?? 'now'));
        $period = CarbonPeriod::create($start, $end);

        $data = [];
        foreach ($period as $date) {
            $label = $date->format('d/m/Y');
            $usage_time = UsageTime::whereDate('date', $date)->first();
            $maintain_statistic = MaintenanceStatistic::whereDate('date', $date)->first();
            $pqc_processing = PQCProcessing::whereDate('date', $date)->first();
            $khuon_data = KhuonData::whereDate('date', $date)->first();

            $usage_time_data['rate'] = $usage_time ? ($usage_time->number_of_user > 0 ? number_format($usage_time->usage_time / $usage_time->number_of_user, 2) : 0) : 0;
            $usage_time_data['score'] = $usage_time_data['rate'] * 25;

            $pqc_processing_data['rate'] = $pqc_processing ? ($pqc_processing->number_of_pqc > 0 ? number_format($pqc_processing->number_of_ok_pqc / $pqc_processing->number_of_pqc, 2) : 0) : 0;
            $pqc_processing_data['score'] = $pqc_processing_data['rate'] * 25;

            $khuon_data_data['rate'] = $khuon_data ? ($khuon_data->total_cells > 0 ? number_format($khuon_data->cells_has_data / $khuon_data->total_cells, 2) : 0) : 0;
            $khuon_data_data['score'] = $khuon_data_data['rate'] * 25;

            // $maintain_statistic_data['rate'] = $maintain_statistic ? ($maintain_statistic->registered_machine > 0 ? number_format($maintain_statistic->maintained_machine / $maintain_statistic->registered_machine, 2) : 0) : 0;
            $maintain_statistic_data['rate'] = $pqc_processing_data['rate'] > 0 ? 1: 0;
            $maintain_statistic_data['score'] = $maintain_statistic_data['rate'] * 25;

            $data[$label] = [
                'usage_time' => $usage_time_data,
                'maintain_statistic' => $maintain_statistic_data,
                'pqc_processing' => $pqc_processing_data,
                'khuon_data' => $khuon_data_data,
                'total' => array_sum([$usage_time_data['score'], $maintain_statistic_data['score'], $pqc_processing_data['score'], $khuon_data_data['score']])
            ];
        }
        return $this->success($data);
    }

    public function cronjob()
    {
        $date = Carbon::now();
        $this->calculateUsageTime($date);
        $this->calculateMaintenanceMachine($date);
        $this->calculatePQCProcessing($date);
        $this->calculateKhuonBe($date);
        Log::info('Logged at: ' . now());
        CustomUser::query()->update(['login_times_in_day'=>0, 'last_use_at'=>null, 'usage_time_in_day'=>0]);
        return 'done';
    }

    public function retriveData()
    {
        $start = Carbon::now()->subMonths(1);
        $end = Carbon::now();
        $period = CarbonPeriod::create($start, $end);
        foreach ($period as $key => $date) {
            $this->calculateUsageTime($date);
            $this->calculateMaintenanceMachine($date);
            $this->calculatePQCProcessing($date);
            $this->calculateKhuonBe($date);
        }
        return 'done';
    }
}
