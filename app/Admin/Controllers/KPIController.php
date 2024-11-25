<?php

namespace App\Admin\Controllers;

use App\Helpers\QueryHelper;
use App\Models\CustomUser;
use App\Models\DRC;
use App\Models\GroupPlanOrder;
use App\Models\InfoCongDoan;
use App\Models\Line;
use App\Models\LocatorMLTMap;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\Material;
use App\Models\Order;
use App\Models\ProductionPlan;
use App\Models\Vehicle;
use App\Models\VOCRegister;
use App\Models\VOCType;
use App\Models\WarehouseFGLog;
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
        $machines = Machine::where('line_id', 30)->pluck('id')->toArray();
        foreach ($period as $date) {
            $label = $date->format('d/m');
            $plannedQuantity = InfoCongDoan::whereIn('machine_id', $machines)->whereDate('ngay_sx', $date->format("Y-m-d"))->sum('dinh_muc');
            $actualQuantity = InfoCongDoan::whereIn('machine_id', $machines)->whereDate('ngay_sx', $date->format("Y-m-d"))->sum('sl_dau_ra_hang_loat');
            $data['categories'][] = $label; // Ngày trên trục hoành
            $data['plannedQuantity'][] = (int)$plannedQuantity; // Tổng số lượng tất cả công đoạn
            $data['actualQuantity'][] = (int)$actualQuantity; // Số lượng công đoạn "Dợn sóng"
        }
        return $this->success($data);
    }

    public function kpiTonKhoNVL(Request $request)
    {
        $results = WarehouseMLTLog::whereNotNull('tg_nhap')
            ->whereNull('tg_xuat')
            ->selectRaw("
                CASE 
                    WHEN DATEDIFF(NOW(), tg_nhap) BETWEEN 1 AND 90 THEN '1 Quý'
                    WHEN DATEDIFF(NOW(), tg_nhap) BETWEEN 91 AND 180 THEN '2 Quý'
                    WHEN DATEDIFF(NOW(), tg_nhap) BETWEEN 181 AND 270 THEN '3 Quý'
                    WHEN DATEDIFF(NOW(), tg_nhap) BETWEEN 271 AND 365 THEN '4 Quý'
                    WHEN DATEDIFF(NOW(), tg_nhap) > 365 THEN '> 1 năm'
                END AS period,
                COUNT(*) AS so_luong_ton
            ")
            ->groupBy('period')
            ->get();
        $quarters = [
            '1 Quý' => 0,
            '2 Quý' => 0,
            '3 Quý' => 0,
            '4 Quý' => 0,
            '> 1 năm' => 0,
        ];

        // Gán dữ liệu từ kết quả truy vấn
        foreach ($results as $row) {
            $quarters[$row->period] = (int)$row->so_luong_ton;
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

            $ty_le = round(($result->ty_le) ?? 0, 1);
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
        foreach ($period as $date) {
            $label = $date->format('d/m');
            $info = InfoCongDoan::whereDate('thoi_gian_bat_dau', $date->format('Y-m-d'))
                ->whereNotNull('thoi_gian_ket_thuc')
                ->whereNotNull('thoi_gian_bat_dau')
                ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, thoi_gian_bat_dau, thoi_gian_ket_thuc)) AS production_time')
                ->first();
            $logs = MachineLog::whereDate('start_time', $date->format('Y-m-d'))
                ->whereNotNull('lo_sx')
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) AS stop_time')
                ->first();
            $machine_array = array_unique($info->pluck('machine_id')->toArray());
            $total_time = count($machine_array) * 8 * 3600;
            $thoi_gian_van_hanh = (($info->production_time ?? 0) - ($logs->stop_time ?? 0)) ?? 0;
            $ti_le_van_hanh = $total_time > 0 ? round($thoi_gian_van_hanh / $total_time, 2) : 0;
            $data['categories'][] = $label;
            $data['ti_le_van_hanh'][] = $ti_le_van_hanh * 100;
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
        $machines = Machine::where('line_id', 31)->pluck('id')->toArray();
        foreach ($period as $date) {
            $label = $date->format('d/m');
            $plannedQuantity = InfoCongDoan::whereIn('machine_id', $machines)->whereDate('ngay_sx', $date->format("Y-m-d"))->sum('dinh_muc');
            $actualQuantity = InfoCongDoan::whereIn('machine_id', $machines)->whereDate('ngay_sx', $date->format("Y-m-d"))->sum('sl_dau_ra_hang_loat');
            $data['categories'][] = $label; // Ngày trên trục hoành
            $data['plannedQuantity'][] = (int)$plannedQuantity; // Tổng số lượng tất cả công đoạn
            $data['actualQuantity'][] = (int)$actualQuantity; // Số lượng công đoạn "Dợn sóng"
        }
        return $this->success($data);
    }

    public function kpiTonKhoTP(Request $request)
    {
        $inventories = WarehouseFGLog::select('pallet_id', 'lo_sx')
            ->selectRaw('SUM(CASE WHEN type = 1 THEN so_luong ELSE 0 END) AS tong_nhap')
            ->selectRaw('SUM(CASE WHEN type = 2 THEN so_luong ELSE 0 END) AS tong_xuat')
            ->selectRaw('(SUM(CASE WHEN type = 1 THEN so_luong ELSE 0 END) - SUM(CASE WHEN type = 2 THEN so_luong ELSE 0 END)) AS ton_kho')
            ->selectRaw("
                CASE 
                    WHEN DATEDIFF(NOW(), created_at) BETWEEN 1 AND 30 THEN '1 tháng'
                    WHEN DATEDIFF(NOW(), created_at) BETWEEN 31 AND 60 THEN '2 tháng'
                    WHEN DATEDIFF(NOW(), created_at) BETWEEN 61 AND 90 THEN '3 tháng'
                    WHEN DATEDIFF(NOW(), created_at) BETWEEN 91 AND 120 THEN '4 tháng'
                    WHEN DATEDIFF(NOW(), created_at) > 120 THEN '> 5 tháng'
                END AS thoi_gian_ton
            ")
            ->groupBy('pallet_id', 'lo_sx', 'thoi_gian_ton')
            ->havingRaw('ton_kho > 0') // Chỉ lấy tồn dương
            ->get();
        $months = [
            '1 tháng' => 0,
            '2 tháng' => 0,
            '3 tháng' => 0,
            '4 tháng' => 0,
            '> 5 tháng' => 0,
        ];

        // Gán dữ liệu từ kết quả truy vấn
        foreach ($inventories as $row) {
            $months[$row->thoi_gian_ton] = (int)$row->ton_kho;
        }
        $data['categories'] = array_keys($months);
        $data['inventory'] = array_values($months);
        return $this->success($data);
    }

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
        foreach ($period as $date) {
            $label = $date->format('d/m');
            $machines = Machine::whereIn('line_id', [32, 33])->get();
            $result = InfoCongDoan::whereDate('thoi_gian_bat_dau', $date->format('Y-m-d'))
                ->whereIn('machine_id', $machines->pluck('id')->toArray())
                ->selectRaw("
                    (SUM(CASE WHEN phan_dinh = 2 THEN 1 ELSE 0 END) * 1.0 /
                    NULLIF(SUM(CASE WHEN phan_dinh = 1 THEN 1 ELSE 0 END), 0)) AS ty_le
                ")
                ->first();

            $ty_le = round(($result->ty_le) ?? 0, 1);
            $data['categories'][] = $label; // Ngày trên trục hoành
            $data['ty_le_ng'][] = $ty_le;
        }
        return $this->success($data);
    }
}
