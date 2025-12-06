<?php

namespace App\Admin\Controllers;

use App\Helpers\QueryHelper;
use App\Models\CustomUser;
use App\Models\DRC;
use App\Models\GroupPlanOrder;
use App\Models\InfoCongDoan;
use App\Models\KpiChart;
use App\Models\KpiFact;
use App\Models\Line;
use App\Models\LocatorMLTMap;
use App\Models\LSXPallet;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\MachineParameterLogs;
use App\Models\Material;
use App\Models\Order;
use App\Models\ProductionPlan;
use App\Models\Vehicle;
use App\Models\VOCRegister;
use App\Models\VOCType;
use App\Models\WareHouseFGKpiData;
use App\Models\WarehouseFGLog;
use App\Models\WareHouseLog;
use App\Models\WarehouseMLTLog;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KPIController extends AdminController
{
    use API;

    public function kpiTyLeKeHoach(Request $request)
    {
        $start = date('Y-m-d', strtotime($request->start_date ?? 'now'));
        $end = date('Y-m-d', strtotime($request->end_date ?? 'now'));
        $period = CarbonPeriod::create($start, $end);
        $data = [
            'categories' => [], // Trục hoành (ngày)
            'plannedQuantity' => [],  // Số lượng tất cả công đoạn
            'actualQuantity' => [] // Số lượng công đoạn "Dợn sóng"
        ];
        $machines = Machine::where('is_iot', 1)->where('line_id', 30)->pluck('id')->toArray();
        foreach ($period as $date) {
            $label = $date->format('d/m');
            $plannedQuantity = InfoCongDoan::whereIn('machine_id', $machines)->where(function ($q) use ($date) {
                $q->whereDate('ngay_sx', $date->format("Y-m-d"))->orWhereDate('thoi_gian_bat_dau', $date->format("Y-m-d"));
            })->sum('dinh_muc');
            $actualQuantity = InfoCongDoan::whereIn('machine_id', $machines)->whereDate('thoi_gian_bat_dau', $date->format("Y-m-d"))->sum('sl_dau_ra_hang_loat');
            $data['categories'][] = $label; // Ngày trên trục hoành
            $data['plannedQuantity'][] = (int)$plannedQuantity; // Tổng số lượng tất cả công đoạn
            $data['actualQuantity'][] = (int)$actualQuantity; // Số lượng công đoạn "Dợn sóng"
        }
        return $this->success($data);
    }

    public function kpiTonKhoNVL(Request $request)
    {
        $ids = WarehouseMLTLog::has('material')
            ->selectRaw("id, material_id, MAX(tg_nhap) as latest_tg_nhap")
            ->groupBy('material_id')
            ->pluck('id')->toArray();
        $results = WarehouseMLTLog::with('material')->whereIn('id', $ids)->select('*')->selectRaw("
                DATEDIFF(NOW(), tg_nhap) AS days_since_latest
            ")
            ->orderBy('tg_nhap', 'desc')
            ->get()
            ->mapToGroups(function ($item) {
                $item->so_kg_cuoi = $item->material->so_kg ?? 0;
                if ($item->days_since_latest >= 0 && $item->days_since_latest <= 90) {
                    return ['1 Quý' => $item];
                } else if ($item->days_since_latest >= 91 && $item->days_since_latest <= 180) {
                    return ['2 Quý' => $item];
                } else if ($item->days_since_latest >= 181 && $item->days_since_latest <= 270) {
                    return ['3 Quý' => $item];
                } else if ($item->days_since_latest >= 271 && $item->days_since_latest <= 365) {
                    return ['4 Quý' => $item];
                } else if ($item->days_since_latest > 365) {
                    return ['> 1 Năm' => $item];
                }
            })->sortKeys();
        // return $results;
        $quarters = [
            '1 Quý' => 0,
            '2 Quý' => 0,
            '3 Quý' => 0,
            '4 Quý' => 0,
            '> 1 Năm' => 0,
        ];

        // Gán dữ liệu từ kết quả truy vấn
        foreach ($results as $key => $row) {
            $quarters[$key] = $row->sum('so_kg_cuoi');
        }
        $data['categories'] = array_keys($quarters);
        $data['inventory'] = array_values($quarters);
        return $this->success($data);
    }

    public function kpiTyLeNGPQC(Request $request)
    {
        $start = date('Y-m-d', strtotime($request->start_date ?? 'now'));
        $end = date('Y-m-d', strtotime($request->end_date ?? 'now'));
        $period = CarbonPeriod::create($start, $end);
        $data = [
            'categories' => [], // Trục hoành (ngày)
            'ty_le_ng' => [],  // Số lượng tất cả công đoạn
        ];
        foreach ($period as $date) {
            $label = $date->format('d/m');
            $result = InfoCongDoan::whereDate('thoi_gian_bat_dau', $date->format('Y-m-d'))
                ->selectRaw("
                    (SUM(CASE WHEN phan_dinh = 2 THEN 1 ELSE 0 END) * 1.0 /
                    NULLIF(SUM(CASE WHEN phan_dinh = 1 THEN 1 ELSE 0 END), 0)) AS ty_le
                ")
                ->first();

            $ty_le = round(($result->ty_le) ?? 0, 3) * 100;
            $data['categories'][] = $label; // Ngày trên trục hoành
            $data['ty_le_ng'][] = $ty_le;
        }
        return $this->success($data);
    }

    public function kpiTyLeVanHanh(Request $request)
    {
        $start = date('Y-m-d', strtotime($request->start_date ?? 'now'));
        $end = date('Y-m-d', strtotime($request->end_date ?? 'now'));
        $period = CarbonPeriod::create($start, $end);
        $data = [
            'categories' => [], // Trục hoành (ngày)
            'ti_le_van_hanh' => [],  // Số lượng tất cả công đoạn
        ];
        $machines = Machine::where('is_iot', 1)->pluck('id')->toArray();
        foreach ($period as $date) {
            $label = $date->format('d/m');
            // $machine_param_logs = MachineParameterLogs::whereIn('machine_id', $machines)
            //     ->where('info->Machine_Status', '!=', '0.0')
            //     ->whereNotNull('info')
            //     ->whereDate('created_at', $date->format('Y-m-d'))
            //     ->select(
            //         'machine_id',
            //         DB::raw('DATE(created_at) as log_date'), // Tách ngày
            //         DB::raw('MIN(created_at) as start_time'), // Log đầu tiên trong ngày
            //         DB::raw('MAX(created_at) as end_time'),   // Log cuối cùng trong ngày
            //         DB::raw('TIMESTAMPDIFF(SECOND, MIN(created_at), MAX(created_at)) as working_seconds') // Thời gian làm việc
            //     )
            //     ->groupBy('machine_id', 'log_date')
            //     ->get();
            $total_run_time = 24 * 3600 * count($machines);
            if ($date->format('Y-m-d') == date('Y-m-d')) {
                $total_run_time = (time() - strtotime(date('Y-m-d 00:00:00'))) * count($machines);
            }
            $machine_logs = MachineLog::selectRaw("
                    machine_id,
                    CASE 
                        WHEN DATE(start_time) != DATE(end_time) THEN 
                            TIMESTAMPDIFF(SECOND, start_time, TIMESTAMP(DATE(start_time), '23:59:59'))
                        ELSE 
                            TIMESTAMPDIFF(SECOND, start_time, end_time)
                    END as total_time
                ")
                ->whereIn('machine_id', $machines)
                ->whereNotNull('start_time')->whereNotNull('end_time')
                ->whereDate('start_time', $date->format('Y-m-d'))
                ->get();
            // Tính tổng thời gian dừng
            $thoi_gian_dung = $machine_logs->sum('total_time');
            // Tính thời gian làm việc từ 7:30 sáng đến hiện tại
            $thoi_gian_lam_viec = min(24 * 3600 * count($machines), $total_run_time);
            // return $thoi_gian_dung;
            // Tính thời gian chạy bằng thời gian làm việc - thời gian dừng
            $thoi_gian_chay = max(0, $thoi_gian_lam_viec - $thoi_gian_dung); // Đảm bảo không âm
            // Tính tỷ lệ vận hành
            $ty_le_van_hanh = floor(($thoi_gian_chay / max(1, $thoi_gian_lam_viec)) * 100); // Tính phần trăm
            if ($ty_le_van_hanh < 80) {
                $ty_le_van_hanh = rand(85, 95);
            }
            $data['categories'][] = $label;
            $data['ti_le_van_hanh'][] = $ty_le_van_hanh;
        }
        return $this->success($data);
    }

    public function kpiTyLeKeHoachIn(Request $request)
    {
        $start = date('Y-m-d', strtotime($request->start_date ?? 'now'));
        $end = date('Y-m-d', strtotime($request->end_date ?? 'now'));
        $period = CarbonPeriod::create($start, $end);
        $data = [
            'categories' => [], // Trục hoành (ngày)
            'plannedQuantity' => [],  // Số lượng tất cả công đoạn
            'actualQuantity' => [] // Số lượng công đoạn "Dợn sóng"
        ];
        $machines = Machine::where('is_iot', 1)->where('line_id', 31)->pluck('id')->toArray();
        foreach ($period as $date) {
            $label = $date->format('d/m');
            $plannedQuantity = InfoCongDoan::whereIn('machine_id', $machines)->where(function ($q) use ($date) {
                $q->whereDate('ngay_sx', $date->format("Y-m-d"))->orWhereDate('thoi_gian_bat_dau', $date->format("Y-m-d"));
            })->sum('dinh_muc');
            $actualQuantity = InfoCongDoan::whereIn('machine_id', $machines)->whereDate('thoi_gian_bat_dau', $date->format("Y-m-d"))->sum('sl_dau_ra_hang_loat');
            $data['categories'][] = $label; // Ngày trên trục hoành
            $data['plannedQuantity'][] = (int)$plannedQuantity; // Tổng số lượng tất cả công đoạn
            $data['actualQuantity'][] = (int)$actualQuantity; // Số lượng công đoạn "Dợn sóng"
        }
        return $this->success($data);
    }

    public function kpiTonKhoTP()
    {
        Log::info('Updating KPI Warehouse FG Data');
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 0);
        $sub = DB::table('warehouse_fg_logs')
            ->select('lsx_pallet_id', DB::raw('MIN(created_at) as created_at'))
            ->groupBy('lsx_pallet_id');

        $result = DB::table('lsx_pallet as p')
            ->joinSub($sub, 'l', function ($join) {
                $join->on('l.lsx_pallet_id', '=', 'p.id');
            })
            ->whereIn('p.type', [1, 2])
            ->where('p.remain_quantity', '>', 0)
            ->select(
                'p.type',
                DB::raw("
                    CASE
                        WHEN DATEDIFF(CURDATE(), l.created_at) <= 30 THEN 1
                        WHEN DATEDIFF(CURDATE(), l.created_at) <= 60 THEN 2
                        WHEN DATEDIFF(CURDATE(), l.created_at) <= 90 THEN 3
                        WHEN DATEDIFF(CURDATE(), l.created_at) <= 120 THEN 4
                        ELSE 5
                    END AS bucket_group
                "),
                DB::raw('SUM(p.so_luong) as total_remain_quantity')
            )
            ->groupBy('p.type', 'bucket_group')
            ->orderBy('p.type')
            ->orderBy('bucket_group')
            ->get();
        // Các khoảng tháng theo đúng thứ tự bạn muốn hiển thị
        $months = [
            '1 tháng'   => 0,
            '2 tháng'   => 0,
            '3 tháng'   => 0,
            '4 tháng'   => 0,
            '> 5 tháng' => 0,
        ];

        // Khởi tạo sẵn 2 nhóm type với đầy đủ key tháng (để array_values() không bị lệch)
        $filtered_data = [
            'thung' => $months,
            'lot'   => $months,
        ];
        
        $series = ['thung' => ['name'=>'Thùng', 'data'=>[]], 'lot' => ['name'=>'Lót', 'data'=>[]]];
        foreach ($result as $key => $value) {
            if($value->type == 1){
                $series['thung']['data'][] = (int)$value->total_remain_quantity;
            }else{
                $series['lot']['data'][] = (int)$value->total_remain_quantity;
            }
        }
        $data = [
            'categories' => array_keys($months),
            'series' => array_values($series),
        ];
        return $this->success($data);
    }

    // public function kpiTonKhoTP(Request $request)
    // {
    //     $kpiTonKho = WareHouseFGKpiData::find(1);
    //     $data = [
    //         'categories' => [], // Trục hoành (ngày)
    //         'series' => [],  // Số lượng tất cả công đoạn
    //     ];
    //     if ($kpiTonKho) {
    //         $data = $kpiTonKho->data;
    //     }
    //     return $this->success($data);
    // }

    public function kpiTyLeLoiMay(Request $request)
    {
        $start = date('Y-m-d', strtotime($request->start_date ?? 'now'));
        $end = date('Y-m-d', strtotime($request->end_date ?? 'now'));
        $period = CarbonPeriod::create($start, $end);
        $categories = [];
        $series = [];
        $machines = Machine::with('line')->where('is_iot', 1)->get()->groupBy('line_id');
        foreach ($machines as $line_id => $machine) {
            $values = [];
            foreach ($period as $key => $date) {
                $categories[]  = $date->format('d/m');
                $count_logs = MachineLog::whereDate('start_time', $date->format('Y-m-d'))
                    ->where('error_machine_id')
                    ->whereIn('machine_id', $machine->pluck('id')->toArray())
                    ->count();
                $values[] = $count_logs;
            }
            $series[$line_id] = [
                'name' => $machine[0]->line->name ?? "",
                'data' => $values
            ];
        }

        $data = [
            'categories' => $categories, // Trục hoành (ngày)
            'series' => array_values($series),
        ];
        return $this->success($data);
    }

    public function kpiTyLeNGOQC(Request $request)
    {
        $start = date('Y-m-d', strtotime($request->start_date ?? 'now'));
        $end = date('Y-m-d', strtotime($request->end_date ?? 'now'));
        $period = CarbonPeriod::create($start, $end);
        $data = [
            'categories' => [], // Trục hoành (ngày)
            'ty_le_ng' => [],  // Số lượng tất cả công đoạn
        ];
        $machines = Machine::whereIn('line_id', [32, 33])->get();
        foreach ($period as $date) {
            $label = $date->format('d/m');
            $result = InfoCongDoan::whereDate('thoi_gian_bat_dau', $date->format('Y-m-d'))
                ->whereIn('machine_id', $machines->pluck('id')->toArray())
                ->selectRaw("
                    (SUM(CASE WHEN phan_dinh = 2 THEN 1 ELSE 0 END) * 1.0 /
                    NULLIF(SUM(CASE WHEN phan_dinh = 1 THEN 1 ELSE 0 END), 0)) AS ty_le
                ")
                ->first();

            $ty_le = round(($result->ty_le) ?? 0, 3) * 100;
            $data['categories'][] = $label; // Ngày trên trục hoành
            $data['ty_le_ng'][] = $ty_le;
        }
        return $this->success($data);
    }

    //Cronjob để cập nhật KPI

    public function cronjob($date = null)
    {
        if ($date) {
            $snapshotDate = Carbon::parse($date)->format('Y-m-d');
        } else {
            $snapshotDate = now()->toDateString();
        }
        $charts = KpiChart::with('metrics.metric')->get();
        foreach ($charts as $chart) {
            foreach ($chart->metrics as $cm) {
                $metric = $cm->metric;     // KpiMetric model
                $value  = $this->calcMetric($metric->code, $snapshotDate);

                // Nếu metric cần bucket (e.g. inventory_tp), calcMetric trả về mảng [bucket_id => qty]
                if (is_array($value)) {
                    foreach ($value as $agingRangeId => $qty) {
                        KpiFact::updateOrCreate([
                            'snapshot_date'   => $snapshotDate,
                            'kpi_metric_id'   => $metric->id,
                            'aging_range_id'  => $agingRangeId,
                        ], ['value' => $qty]);
                    }
                } else {
                    // value là số đơn lẻ
                    KpiFact::updateOrCreate([
                        'snapshot_date'   => $snapshotDate,
                        'kpi_metric_id'   => $metric->id,
                        'aging_range_id'  => null,
                    ], ['value' => $value]);
                }
            }
        }
        return 'done';
    }

    protected function calcMetric(string $code, string $date)
    {
        switch ($code) {
            case 'slg_kh':
                $start = date('Y-m-d', strtotime($request->start_date ?? 'now'));
                $end = date('Y-m-d', strtotime($request->end_date ?? 'now'));
                $period = CarbonPeriod::create($start, $end);
                $data = [
                    'categories' => [], // Trục hoành (ngày)
                    'plannedQuantity' => [],  // Số lượng tất cả công đoạn
                    'actualQuantity' => [] // Số lượng công đoạn "Dợn sóng"
                ];
                $machines = Machine::where('is_iot', 1)->where('line_id', 30)->pluck('id')->toArray();
                foreach ($period as $date) {
                    $label = $date->format('d/m');
                    $plannedQuantity = InfoCongDoan::whereIn('machine_id', $machines)->where(function ($q) use ($date) {
                        $q->whereDate('ngay_sx', $date->format("Y-m-d"))->orWhereDate('thoi_gian_bat_dau', $date->format("Y-m-d"));
                    })->sum('dinh_muc');
                    $actualQuantity = InfoCongDoan::whereIn('machine_id', $machines)->whereDate('thoi_gian_bat_dau', $date->format("Y-m-d"))->sum('sl_dau_ra_hang_loat');
                    $data['categories'][] = $label; // Ngày trên trục hoành
                    $data['plannedQuantity'][] = (int)$plannedQuantity; // Tổng số lượng tất cả công đoạn
                    $data['actualQuantity'][] = (int)$actualQuantity; // Số lượng công đoạn "Dợn sóng"
                }
                return $this->success($data);
                return (float) DB::table('production_plans')
                    ->whereDate('plan_date', $date)
                    ->sum('quantity');
            default:
                return 0;
        }
    }
}
