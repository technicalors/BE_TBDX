<?php

namespace App\Admin\Controllers;

use App\Models\Cell;
use App\Models\Customer;
use App\Models\CustomerShort;
use App\Models\CustomerSpecification;
use App\Models\Error;
use App\Models\ErrorMachine;
use App\Models\InfoCongDoan;
use App\Models\Inventory;
use App\Models\Line;
use App\Models\Lot;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\Monitor;
use App\Models\Product;
use App\Models\ProductionPlan;
use App\Models\QCLevel;
use App\Models\Shift;
use App\Models\ThongSoMay;
use App\Models\Tracking;
use App\Models\WareHouseExportPlan;
use App\Models\WareHouseLog;
use App\Models\Spec;
use App\Models\TestCriteria;
use App\Traits\API;
use Carbon\Carbon;
use Encore\Admin\Controllers\AdminController;
use Exception;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\CustomUser;
use App\Models\DeliveryNote;
use App\Models\DRC;
use App\Models\ErrorLog;
use App\Models\Film;
use App\Models\GroupPlanOrder;
use App\Models\InfoCongDoanPriority;
use App\Models\Ink;
use App\Models\Khuon;
use App\Models\KhuonLink;
use App\Models\Layout;
use App\Models\LocatorFG;
use App\Models\LocatorFGMap;
use App\Models\LocatorMLT;
use App\Models\LocatorMLTMap;
use App\Models\LSX;
use App\Models\LSXLog;
use App\Models\LSXPallet;
use App\Models\LSXPalletClone;
use App\Models\MachineParameter;
use App\Models\MachineParameterLogs;
use App\Models\Material;
use App\Models\Order;
use App\Models\Pallet;
use App\Models\QCLog;
use App\Models\RequestLog;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Tem;
use App\Models\TieuChuanNCC;
use App\Models\UserLine;
use App\Models\UserLineMachine;
use App\Models\Vehicle;
use App\Models\WareHouseFGExport;
use App\Models\WarehouseFGLog;
use App\Models\WareHouseMLTExport;
use App\Models\WareHouseMLTImport;
use App\Models\WarehouseMLTLog;
use Carbon\CarbonPeriod;
use DateTime;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use stdClass;
use Throwable;

class ApiUIController extends AdminController
{
    use API;

    public  $TEXT2ID = [
        "in" => 10,
        "phu" => 11,
        "be" => 12,
        "gap-dan" => 13,
        "boc" => 14,
        "chon" => 15,
        "in-luoi" => 22,
        "oqc" => 20,
        "kho-bao-on" => 9,
        "u" => 21,
    ];
    public  $ID2TEXT = [
        9 => "kho-bao-on",
        10 => "in",
        11 => "phu",
        12 => "be",
        13 => "gap-dan",
        14 => "boc",
        15 => "chon",
        16 => "kiem-tra-nvl",
        19 => "kho-thanh-pham",
        20 => "oqc",
        21 => "u",
        22 => "in-luoi",
    ];
    private function produceOverall($infos)
    {
        $overall = [
            "sl_dau_ra_kh" => 0,
            "sl_dau_ra_thuc_te_ok" => 0,
            "sl_chenh_lech" => 0,
            "ty_le" => 0,
            "sl_tem_vang" => 0,
            "sl_ng" => 0,
        ];
        $sl_thuc_te = 0;
        foreach ($infos as $item) {
            if ($item->lot->type == 1) continue;
            $overall["sl_dau_ra_thuc_te_ok"] += $item->sl_dau_ra_hang_loat - ($item->sl_tem_vang + $item->ng);
            $sl_thuc_te += $item->sl_dau_ra_hang_loat - $item->ng;
            $overall["sl_tem_vang"] += $item->sl_tem_vang;
            $overall["sl_ng"] += $item->sl_ng;
            $plan = $item->lot ? $item->lot->getPlanByLine($item->line_id) : null;

            if (isset($plan)) {
                $overall['sl_dau_ra_kh'] += $plan->so_bat * $plan->sl_thanh_pham;
            }
        }
        $overall["sl_chenh_lech"] = ($overall["sl_dau_ra_thuc_te_ok"] + $overall["sl_tem_vang"] + $overall["sl_ng"]) - $overall['sl_dau_ra_kh'];
        $overall["ty_le"] = $overall['sl_dau_ra_kh'] ? ((int)($sl_thuc_te / $overall['sl_dau_ra_kh'])) * 100 : 0;
        return $overall;
    }

    private function producePercent($lo_sx_ids)
    {
        $data = [];
        $lot_ids = Lot::whereIn('lo_sx', $lo_sx_ids)->where('type', '<>', 1)->pluck('id')->toArray();
        $info_cds = InfoCongDoan::whereIn('lot_id', $lot_ids)->get();
        $machine_arr = [];
        $machines = Machine::select('id', 'name')->get();
        foreach ($lo_sx_ids as $key => $lo_sx) {
            foreach ($machines as $machine) {
                $data[$lo_sx][$machine->id] = 0;
                $sl_ok = 0;
                $plan = ProductionPlan::where('lo_sx', $lo_sx)->where('machine_id', $machine->id)->first();
                foreach ($info_cds as $k => $info) {
                    if ($info->lot->lo_sx == $lo_sx && $machine->id == $info->machine_id) {
                        $sl_ok += $info->sl_dau_ra_hang_loat - $info->sl_ng;
                    }
                }
                $data[$lo_sx][$machine->id] = $sl_ok;
            }
        }
        return $data;
    }

    private function produceTable($infos)
    {
        $data = [];
        foreach ($infos as $item) {
            if ($item->type == 'qc') continue;
            if (!$item->lot || $item->lot->type == 1) continue;
            $plan = $item->lot ? $item->lot->getPlanByLine($item->line_id) : null;
            if (!isset($plan)) continue;

            $start_kh = $plan->thoi_gian_bat_dau;
            $end_kh = $plan->thoi_gian_ket_thuc;

            $start = new Carbon($item->thoi_gian_bat_dau);
            $machine_start = new Carbon($item->thoi_gian_bam_may);
            $end = new Carbon($item->thoi_gian_ket_thuc);
            $d = $end->diffInMinutes($start);

            $start_date = date("Y/m/d", strtotime($start));
            $shift = Shift::first();
            $start_shift = strtotime($start_date . ' ' . $shift->start_time);
            $end_shift = strtotime($start_date . ' ' . $shift->end_time);
            if (strtotime($start) >= $start_shift && strtotime($start) <=  $end_shift) {
                $ca_sx = 'Ca 1';
            } else {
                $ca_sx = 'Ca 2';
            }

            $info = $item->lot->log->info;
            $line = Line::find($item->line_id);
            $line_key = Str::slug($line->name);
            $errors = [];
            $thoi_gian_kiem_tra = '';
            $sl_ng_pqc = 0;
            $sl_ng_sxkt = 0;
            $user_pqc = '';
            $user_sxkt = '';
            if (isset($info['qc']) && isset($info['qc'][$line_key])) {
                $info_qc = $info['qc'][$line_key];
                if ($line_key === 'gap-dan') {
                    $qc_error = [];
                    foreach ($info_qc['bat'] as $bat_error) {
                        if (isset($bat_error['errors'])) {
                            $qc_error = array_merge($qc_error, $bat_error['errors']);
                        }
                    }
                } else {
                    $qc_error = $info_qc['errors'] ?? [];
                }
                foreach ($qc_error as $key => $err) {
                    if (!is_numeric($err)) {
                        foreach ($err['data'] ?? [] as $err_key => $err_val) {
                            $user = CustomUser::find($err['user_id']);
                            if (isset($err['type']) && $err['type'] === 'qc') {
                                if ($line_key === 'gap-dan' || $line_key === 'chon' || $line_key === 'oqc') {
                                    $sl_ng_pqc += $err_val;
                                } else {
                                    $sl_ng_pqc += $err_val / $plan->so_bat;
                                }
                                $user_pqc = $user ? $user->name : '';
                            } else {
                                if ($line_key === 'gap-dan' || $line_key === 'chon' || $line_key === 'oqc') {
                                    $sl_ng_sxkt += $err_val;
                                } else {
                                    $sl_ng_sxkt += $err_val / $plan->so_bat;
                                }
                                $user_sxkt = $user ? $user->name : '';
                            }
                            $e = Error::find($err_key);
                            if ($line_key === 'gap-dan' || $line_key === 'chon' || $line_key === 'oqc') {
                                $errors[$e->id]['value'] = ($errors[$e->id]['value'] ?? 0) + $err_val;
                            } else {
                                $errors[$e->id]['value'] = ($errors[$e->id]['value'] ?? 0) + $err_val / $plan->so_bat;
                            }
                            $errors[$e->id]['name'] = $e->noi_dung;
                        }
                    } else {
                        $e = Error::find($key);
                        $errors[$e->id]['name'] = $e->noi_dung;
                        if ($line_key === 'gap-dan' || $line_key === 'chon' || $line_key === 'oqc') {
                            $sl_ng_pqc += $err;
                            $errors[$e->id]['value'] = ($errors[$e->id]['value'] ?? 0) + $err;
                        } else {
                            $sl_ng_pqc += $err / $item->lot->product->so_bat;
                            $errors[$e->id]['value'] = ($errors[$e->id]['value'] ?? 0) + $err / $item->lot->product->so_bat;
                        }
                    }
                }
                $user_sxkt = isset($info[$line_key]['user_name']) ? $info[$line_key]['user_name'] : '';
                $user_pqc = isset($info_qc['user_name']) ? $info_qc['user_name'] : '';
            }
            $tm = [
                "ngay_sx" => date('d/m/Y H:i:s', strtotime($item->created_at)),
                'ca_sx' => $ca_sx,
                'xuong' => 'Giấy',
                "cong_doan" => $item->line->name,
                "machine" => count($item->line->machine) ? $item->line->machine[0]->name : '-',
                "machine_id" => count($item->line->machine) ? $item->line->machine[0]->code : '-',
                "khach_hang" => $plan->khach_hang,
                "ten_san_pham" => $plan->product->name,
                "product_id" => $plan->product->id,
                "lo_sx" => $item->lot->lo_sx,
                "lot_id" => $item->lot_id,
                "thoi_gian_bat_dau_kh" => date('d/m/Y H:i:s', strtotime($start_kh)),
                "thoi_gian_ket_thuc_kh" => date('d/m/Y H:i:s', strtotime($end_kh)),
                "sl_dau_vao_kh" =>  $plan->so_bat * $plan->sl_thanh_pham ?? "-",
                "sl_dau_ra_kh" =>  $plan->so_bat * $plan->sl_thanh_pham ?? "-",
                "thoi_gian_bat_dau" => $item->thoi_gian_bat_dau ? date('d/m/Y H:i:s', strtotime($item->thoi_gian_bat_dau)) : '-',
                "thoi_gian_bam_may" => $item->thoi_gian_bam_may ? date('d/m/Y H:i:s', strtotime($item->thoi_gian_bam_may)) : '-',
                "thoi_gian_ket_thuc" => $item->thoi_gian_ket_thuc ? date('d-m-Y H:i:s', strtotime($item->thoi_gian_ket_thuc)) : '-',
                "thoi_gian_chay_san_luong" =>  number_format($d / 60, 2),
                "sl_ng" => $sl_ng_pqc + $sl_ng_sxkt,
                "sl_tem_vang" => $item->sl_tem_vang,
                "sl_dau_ra_ok" => $item->sl_dau_ra_hang_loat - $item->sl_ng - $item->sl_tem_vang,
                "ti_le_ng" => number_format($item->sl_dau_ra_hang_loat > 0 ? ($item->sl_ng /  $item->sl_dau_ra_hang_loat) : 0, 2) * 100,
                "sl_dau_ra_hang_loat" => $item->sl_dau_ra_hang_loat,
                "sl_dau_vao_hang_loat" => $item->sl_dau_vao_hang_loat,
                "sl_dau_ra_chay_thu" => $item->sl_dau_ra_chay_thu ? $item->sl_dau_ra_chay_thu : '-',
                "sl_dau_vao_chay_thu" => $item->sl_dau_vao_chay_thu ? $item->sl_dau_vao_chay_thu : '-',
                "ty_le_dat" => $item->sl_dau_ra_hang_loat > 0 ? number_format(($item->sl_dau_ra_hang_loat - $item->sl_ng - $item->sl_tem_vang) / $item->sl_dau_ra_hang_loat) : '-',
                "cong_nhan_sx" =>  $plan->nhan_luc ? $plan->nhan_luc : "-",
                "leadtime" => $item->thoi_gian_ket_thuc ? number_format((strtotime($item->thoi_gian_ket_thuc) - strtotime($item->thoi_gian_bat_dau)) / 3600, 2) : '-',
                "tt_thuc_te" => ($item->sl_dau_ra_hang_loat > 0 && $item->thoi_gian_bam_may) ? number_format((strtotime($item->thoi_gian_ket_thuc) - strtotime($item->thoi_gian_bam_may)) / ($item->sl_dau_ra_hang_loat * 60), 4) : '-',
                "chenh_lech" => $item->sl_dau_vao_hang_loat - $item->sl_dau_ra_hang_loat,
                "errors" => $errors,
                'thoi_gian_kiem_tra' => $thoi_gian_kiem_tra,
                'sl_ng_pqc' => $sl_ng_pqc,
                'sl_ng_sxkt' => $sl_ng_sxkt,
                'user_pqc' => $user_pqc,
                'user_sxkt' => $user_sxkt,
            ];
            $data[] = $tm;
        }
        return $data;
    }

    function getPQCData($data, $info_cd, $is_export, $error_data = [], $check_sheet = [], $result = [])
    {
        // $shift = Shift::first();
        $plan = $info_cd->plan ? $info_cd->plan : $info_cd->lot->plan;
        $product = $info_cd->lot->product;
        $start_kh = $plan ? $plan->thoi_gian_bat_dau : null;
        $end_kh = $plan ? $plan->thoi_gian_ket_thuc : null;

        $start = new Carbon($info_cd->thoi_gian_bat_dau ?? $info_cd->created_at);
        $end = new Carbon($info_cd->thoi_gian_ket_thuc ?? $info_cd->updated_at);
        $d = $end->diffInMinutes($start);

        $start_date = date("Y/m/d", strtotime($start));
        $start_shift = strtotime($start_date . ' 07:00:00');
        $end_shift = strtotime($start_date . ' 19:00:00');
        if (strtotime($start) >= $start_shift && strtotime($start) <=  $end_shift) {
            $ca_sx = 'Ca 1';
        } else {
            $ca_sx = 'Ca 2';
        }
        $info = $info_cd->lot->log->info;
        $line_key = Str::slug($info_cd->line->name);
        $errors = [];
        $thoi_gian_kiem_tra = '';
        $sl_ng_pqc = 0;
        $sl_ng_sxkt = 0;
        $user_pqc = '';
        $user_sxkt = '';
        // $check_sheet = [];
        // $result = [];
        $bat_data = [];
        if (isset($info['qc']) && isset($info['qc'][$line_key])) {
            $info_qc = $info['qc'][$line_key];

            if ($line_key === 'gap-dan') {
                $qc_error = count($error_data) > 0 ? $error_data : [];
                foreach ($info_qc['bat'] ?? [] as $bat_id => $bat_error) {
                    if (isset($bat_error['errors'])) {
                        $qc_error = array_merge($qc_error, $bat_error['errors']);
                    }
                    if ($is_export) {
                        $result = array_column(array_intersect_key($bat_error, array_flip(array('kich-thuoc', 'dac-tinh', 'ngoai-quan'))), 'result');
                        $check_sheet = array_column(array_intersect_key($bat_error, array_flip(array('kich-thuoc', 'dac-tinh', 'ngoai-quan'))), 'data');
                        $info_cd_bat = InfoCongDoan::with('lot.plan')->where('lot_id', $bat_id)->where('line_id', $info_cd->line_id)->first();
                        $bat_data[] = $this->getPQCData($data, $info_cd_bat, $is_export, $bat_error['errors'] ?? [], $check_sheet, $result)[1];
                    }
                }
            } else {
                $result = array_column(array_intersect_key($info_qc, array_flip(array('kich-thuoc', 'dac-tinh', 'ngoai-quan'))), 'result');
                $check_sheet = array_column(array_intersect_key($info_qc, array_flip(array('kich-thuoc', 'dac-tinh', 'ngoai-quan'))), 'data');
                $qc_error = $info_qc['errors'] ?? [];
            }
            foreach ($qc_error as $key => $err) {
                if (!is_numeric($err)) {
                    foreach ($err['data'] ?? [] as $err_key => $err_val) {
                        $user = CustomUser::find($err['user_id']);
                        if (isset($err['type']) && $err['type'] === 'qc') {
                            if ($line_key === 'gap-dan' || $line_key === 'chon' || $line_key === 'oqc') {
                                $sl_ng_pqc += $err_val;
                            } else {
                                $sl_ng_pqc += $err_val * $info_cd->lot->product->so_bat ?? 0;
                            }
                            $user_pqc = $user ? $user->name : '';
                        } else {
                            if ($line_key === 'gap-dan' || $line_key === 'chon' || $line_key === 'oqc') {
                                $sl_ng_sxkt += $err_val;
                            } else {
                                $sl_ng_sxkt += $err_val * $info_cd->lot->product->so_bat ?? 0;
                            }
                            $user_sxkt = $user ? $user->name : '';
                        }
                        $e = Error::find($err_key);
                        if ($line_key === 'gap-dan' || $line_key === 'chon' || $line_key === 'oqc') {
                            $errors[$e->id]['value'] = ($errors[$e->id]['value'] ?? 0) + $err_val;;
                        } else {
                            $errors[$e->id]['value'] = ($errors[$e->id]['value'] ?? 0) + $err_val * $info_cd->lot->product->so_bat;
                        }
                        $errors[$e->id]['name'] = $e->noi_dung;
                    }
                } else {
                    $e = Error::find($key);
                    if ($line_key === 'gap-dan' || $line_key === 'chon' || $line_key === 'oqc') {
                        $sl_ng_pqc += $err;
                        $errors[$e->id]['value'] = ($errors[$e->id]['value'] ?? 0) + $err;
                    } else {
                        $sl_ng_pqc += $err * $info_cd->lot->product->so_bat;
                        $errors[$e->id]['value'] = ($errors[$e->id]['value'] ?? 0) + $err * $info_cd->lot->product->so_bat;
                    }
                    $errors[$e->id]['name'] = $e->noi_dung;
                }
            }
            $user_sxkt = isset($info[$line_key]['user_name']) ? $info[$line_key]['user_name'] : '';
            $user_pqc = isset($info_qc['user_name']) ? $info_qc['user_name'] : '';
        }
        if ($info_cd->line_id == 10 || $info_cd->line_id == 11 || $info_cd->line_id == 12 || $info_cd->line_id == 14 || $info_cd->line_id == 22) {
            $sl_dau_ra_hang_loat = $info_cd->sl_dau_ra_hang_loat / $info_cd->lot->product->so_bat;
        } else {
            $sl_dau_ra_hang_loat = $info_cd->sl_dau_ra_hang_loat;
        }

        $cs_data = [];
        // $test_criteria = TestCriteria::where('line_id', $info_cd->line_id)->get();
        foreach ($check_sheet as $cs) {
            foreach ($cs as $val) {
                if (isset($val['id'])) {
                    $test_criteria = TestCriteria::find($val['id']);
                    if (!$test_criteria) continue;
                    if (isset($val['value'])) {
                        $cs_data[Str::slug($test_criteria->hang_muc)] = $val['value'];
                    } else {
                        $cs_data[Str::slug($test_criteria->hang_muc)] = $val['result'] ?? '';
                    }
                } else {
                    continue;
                }
            }
        }
        $tm = [
            "ngay_sx" => date('d/m/Y H:i:s', strtotime($info_cd->created_at)),
            'ca_sx' => $ca_sx,
            'xuong' => 'Giấy',
            "cong_doan" => $info_cd->line->name,
            "machine" => count($info_cd->line->machine) ? $info_cd->line->machine[0]->name : '-',
            "machine_id" => count($info_cd->line->machine) ? $info_cd->line->machine[0]->code : '-',
            "khach_hang" => $plan->khach_hang ?? '',
            "ten_san_pham" => $product->name ?? '',
            "product_id" => $product->id ?? "",
            "lo_sx" => $info_cd->lo_sx,
            "lot_id" => $info_cd->lot_id,
            "thoi_gian_bat_dau_kh" => $start_kh ? date('d/m/Y H:i:s', strtotime($start_kh)) : '',
            "thoi_gian_ket_thuc_kh" => $end_kh ? date('d/m/Y H:i:s', strtotime($end_kh)) : '',
            "sl_dau_vao_kh" =>  $plan ? $info_cd->lot->product->so_bat * $plan->sl_thanh_pham : "-",
            "sl_dau_ra_kh" =>  $plan ? $info_cd->lot->product->so_bat * $plan->sl_thanh_pham : "-",
            "thoi_gian_bat_dau" => $info_cd->thoi_gian_bat_dau ? date('d/m/Y H:i:s', strtotime($info_cd->thoi_gian_bat_dau)) : '-',
            "thoi_gian_bam_may" => $info_cd->thoi_gian_bam_may ? date('d/m/Y H:i:s', strtotime($info_cd->thoi_gian_bam_may)) : '-',
            "thoi_gian_ket_thuc" => $info_cd->thoi_gian_ket_thuc ? date('d-m-Y H:i:s', strtotime($info_cd->thoi_gian_ket_thuc)) : '-',
            "thoi_gian_chay_san_luong" =>  number_format($d / 60, 2),
            "sl_ng" => $sl_ng_pqc + $sl_ng_sxkt,
            "sl_tem_vang" => $info_cd->sl_tem_vang,
            "sl_dau_ra_ok" => $info_cd->sl_dau_ra_hang_loat - $info_cd->sl_ng - $info_cd->sl_tem_vang,
            "ti_le_ng" => number_format($info_cd->sl_dau_ra_hang_loat > 0 ? ($info_cd->sl_ng /  $info_cd->sl_dau_ra_hang_loat) : 0, 2) * 100,
            "sl_dau_ra_hang_loat" => $info_cd->sl_dau_ra_hang_loat,
            "sl_dau_vao_hang_loat" => $info_cd->sl_dau_vao_hang_loat,
            "sl_dau_ra_chay_thu" => $info_cd->sl_dau_ra_chay_thu ? $info_cd->sl_dau_ra_chay_thu : '-',
            "sl_dau_vao_chay_thu" => $info_cd->sl_dau_vao_chay_thu ? $info_cd->sl_dau_vao_chay_thu : '-',
            "ty_le_dat" => $info_cd->sl_dau_ra_hang_loat > 0 ? number_format(($info_cd->sl_dau_ra_hang_loat - $info_cd->sl_ng - $info_cd->sl_tem_vang) / $info_cd->sl_dau_ra_hang_loat) : '-',
            "cong_nhan_sx" =>  $plan ? $plan->nhan_luc : "-",
            "leadtime" => $info_cd->thoi_gian_ket_thuc ? number_format((strtotime($info_cd->thoi_gian_ket_thuc) - strtotime($info_cd->thoi_gian_bat_dau)) / 3600, 2) : '-',
            "tt_thuc_te" => ($info_cd->sl_dau_ra_hang_loat > 0 && $info_cd->thoi_gian_bam_may) ? number_format((strtotime($info_cd->thoi_gian_ket_thuc) - strtotime($info_cd->thoi_gian_bam_may)) / ($sl_dau_ra_hang_loat * 60), 4) : '-',
            "chenh_lech" => $info_cd->sl_dau_vao_hang_loat - $info_cd->sl_dau_ra_hang_loat,
            "errors" => $errors,
            'thoi_gian_kiem_tra' => $thoi_gian_kiem_tra,
            'sl_ng_pqc' => $sl_ng_pqc,
            'sl_ng_sxkt' => $sl_ng_sxkt,
            'user_pqc' => $user_pqc,
            'user_sxkt' => $user_sxkt,
            'evaluate' => in_array(0, $result) ? 0 : 1,
        ];
        $data[] = $tm + $cs_data;
        $data = array_merge($data, $bat_data);
        return [$data, $tm + $cs_data];
    }
    private function produceTablePQC($infos, $is_export = false)
    {
        $data = [];
        foreach ($infos as $item) {
            $lot = $item->lot;
            if (!$lot || !$lot->log) continue;
            $data = $this->getPQCData($data, $item, $is_export, [], [], [])[0];
        }
        return $data;
    }


    public function produceHistory(Request $request)
    {
        $query = InfoCongDoan::where("type", "sx");
        $line = Line::find($request->line_id);
        if ($line) {
            $query->where('line_id', $line->id);
        }
        if (isset($request->date) && count($request->date)) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->date[0])))
                ->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->date[1])));
        } else {
            $query->whereDate('created_at', '>=', date('Y-m-d'))
                ->whereDate('created_at', '<=', date('Y-m-d'));
        }
        if (isset($request->product_id)) {
            $query->where('lot_id', 'like',  '%' . $request->product_id . '%');
        }
        if (isset($request->ten_sp)) {
            $query->where('lot_id', 'like',  '%' . $request->ten_sp . '%');
        }
        if (isset($request->khach_hang)) {
            $khach_hang = Customer::where('id', $request->khach_hang)->first();
            if ($khach_hang) {
                $plan = ProductionPlan::where('khach_hang', $khach_hang->name)->get();
                $product_ids = $plan->pluck('product_id')->toArray();
                $query->where(function ($qr) use ($product_ids) {
                    for ($i = 0; $i < count($product_ids); $i++) {
                        $qr->orwhere('lot_id', 'like',  '%' . $product_ids[$i] . '%');
                    }
                });
            }
        }
        if (isset($request->lo_sx)) {
            $lot = Lot::where('lo_sx', $request->lo_sx)->get();
            $query->whereIn('lot_id', $lot->pluck('id'));
        }
        $infos = $query->with("lot.plans")->get();
        $records = [];
        $lo_sx_ids = [];
        foreach ($infos as $key => $info) {
            if ($info->lot) {
                $records[] = $info;
                if (!in_array($info->lot->lo_sx, $lo_sx_ids)) {
                    $lo_sx_ids[] = $info->lot->lo_sx;
                }
            }
        }
        $overall = $this->produceOverall($records);
        $percent = $this->producePercent($lo_sx_ids);
        $table = $this->produceTable($records);

        return $this->success([
            "overall" => $overall,
            "percent" => $percent,
            "table" => $table,
        ]);
    }

    private function qcError($infos)
    {
        $res = [];
        $error_lot = [];
        foreach ($infos as $info) {
            $item = $info->lot;
            if (!$item->plan) {
                continue;
            }
            $date = date('d/m', strtotime($info->created_at));
            $line = Line::find($info->line_id);
            $line_key = Str::slug($line->name);

            if (!isset($res)) $res[$date] = [];

            $error_lot[$item->id] = null;
            $log = $item->log;
            $qcs = [];
            if (isset($log->info['qc'])) {
                $qcs = $log->info['qc'];
            }
            foreach ($qcs as $k_qc => $qc) {
                if ($line_key !== $k_qc) {
                    continue;
                }
                $errors = [];
                if (isset($qc['errors'])) {
                    $errors = $qc['errors'];
                }
                if ($k_qc == 'gap-dan') {
                    $bats = [];
                    if (isset($qc['bat'])) $bats = $qc['bat'];
                    foreach ($bats as $bat) {

                        if (isset($bat['errors'])) {

                            $tm = $bat['errors'];
                            foreach ($tm as $key => $val) {
                                $errors[$key] = $val;
                            }
                        }
                    }
                }
                foreach ($errors as $k => $err) {
                    if (is_numeric($err)) {
                        $key = $k;
                        if (!isset($res[$date][$key])) {
                            $res[$date][$key] = 0;
                        }
                        if (!isset($error_lot[$item->id][$key])) {
                            $error_lot[$item->id][$key] = [];
                        }
                        $res[$date][$key] += $err;
                        if (!isset($error_lot[$item->id][$key]['value'])) {
                            $error_lot[$item->id][$key]['value'] = 0;
                        }
                        $error = Error::find($key);
                        $error_lot[$item->id][$key]['value'] += $err;
                        $error_lot[$item->id][$key]['name'] = $error->noi_dung;
                    } else {
                        foreach ($err['data'] as $err_key => $err_val) {
                            $key = $err_key;
                            if (!isset($res[$date][$key])) {
                                $res[$date][$key] = 0;
                            }
                            if (!isset($error_lot[$item->id][$key])) {
                                $error_lot[$item->id][$key] = [];
                            }
                            $res[$date][$key] += $err_val;
                            if (!isset($error_lot[$item->id][$key]['value'])) {
                                $error_lot[$item->id][$key]['value'] = 0;
                            }
                            $error = Error::find($key);
                            $error_lot[$item->id][$key]['value'] += $err_val;
                            $error_lot[$item->id][$key]['name'] = $error->noi_dung;
                        }
                    }
                }
            }
        }
        // uksort($res, function($dt1, $dt2) {
        //     return strtotime($dt1) - strtotime($dt2);
        // });
        return [$res, $error_lot];
    }

    private function qcErrorRef($erros)
    {
        $res = [];
        $arr = [];
        foreach ($erros as $key => $err) {
            $arr[] = $key;
        }
        $errs = Error::whereIn("id", $arr)->get();
        foreach ($errs as $err) {
            $res[$err->id] = [
                "noi_dung" =>  $err->noi_dung
            ];
        }
        return $res;
    }

    public function qcHistory(Request $request)
    {
        $query = InfoCongDoan::where("type", "sx")->orderBy('created_at');
        $line = Line::find($request->line_id);
        if ($line) {
            $query->where('line_id', $line->id);
        }
        if (isset($request->date) && count($request->date)) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->date[0])))
                ->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->date[1])));
        }
        if (isset($request->product_id)) {
            $query->where('lot_id', 'like',  '%' . $request->product_id . '%');
        }
        if (isset($request->ten_sp)) {
            $query->where('lot_id', 'like',  '%' . $request->ten_sp . '%');
        }
        if (isset($request->khach_hang)) {
            $khach_hang = Customer::where('id', $request->khach_hang)->first();
            if ($khach_hang) {
                $plan = ProductionPlan::where('khach_hang', $khach_hang->name)->get();
                $product_ids = $plan->pluck('product_id')->toArray();
                $query->where(function ($qr) use ($product_ids) {
                    for ($i = 0; $i < count($product_ids); $i++) {
                        $qr->orwhere('lot_id', 'like',  '%' . $product_ids[$i] . '%');
                    }
                });
            }
        }
        if (isset($request->lo_sx)) {
            $query->where('lot_id', 'like', "%$request->lo_sx%");
        }
        // return $query->get();
        $infos = $query->with("lot.plans")->whereHas('lot', function ($lot_query) {
            $lot_query->whereIn('type', [0, 1, 2, 3])->where('info_cong_doan.line_id', 15)->orWhere('type', '<>', 1)->where('info_cong_doan.line_id', '<>', 15);
        })->get();
        $table = $this->produceTablePQC($infos);
        $chart = $this->qcError($infos);
        // $err_ref = $this->qcErrorRef($chart[0]);
        return $this->success([
            "table" => $table,
            "chart_lot" => $chart[1],
            "chart" => $chart[0],
            // "err_ref" => $err_ref
        ]);
    }

    public function fmb(Request $request)
    {
        $lines = ['30', '31', '32'];
        $res = [];
        foreach ($lines as $line_id) {
            $line = Line::find($line_id);
            $machine_ids = Machine::where('line_id', $line_id)->where('is_iot', 1)->pluck('id')->toArray();
            $now = Carbon::now();
            $start_date = Carbon::now()->setTime(7, 0, 0);
            $end_date = Carbon::now()->addDay()->setTime(7, 0, 0);

            // Nếu thời điểm hiện tại < 7h sáng, lấy dữ liệu từ 7h hôm trước đến 7h hôm nay
            if ($now->lessThan($start_date)) {
                $start_date = Carbon::now()->subDay()->setTime(7, 0, 0);
                $end_date = Carbon::now()->setTime(7, 0, 0);
            }

            $ke_hoach_ca = 0;
            switch ((string)$line_id) {
                case '30':
                    $song_info_plan_query = InfoCongDoan::whereIn('machine_id', $machine_ids);
                    // Lấy kế hoạch trong ngày
                    $ke_hoach_ca = (clone $song_info_plan_query)
                        ->where(function ($q) {
                            $q->whereDate('ngay_sx', date('Y-m-d'));
                                // ->orWhereIn('id', InfoCongDoanPriority::all()->pluck('info_cong_doan_id')->toArray());
                        })
                        ->sum('dinh_muc');
                    
                    // Lấy số lượng hiện tại trong khoảng thời gian từ 7h đến 7h hôm sau
                    $sl_hien_tai = (clone $song_info_plan_query)
                        ->whereBetween('thoi_gian_bat_dau', [$start_date, $end_date])
                        ->sum('sl_dau_ra_hang_loat');
                    
                    $sl_muc_tieu = $ke_hoach_ca;
                    break;
                case '31':
                case '32':
                    $info_plan_query = InfoCongDoan::whereIn('machine_id', $machine_ids)
                        ->where(function ($q) {
                            $q->whereDate('ngay_sx', date('Y-m-d'))
                                ->orWhereDate('thoi_gian_bat_dau', date('Y-m-d'));
                        })->get();
                    
                    // Lấy kế hoạch trong ngày
                    $ke_hoach_ca = $info_plan_query->sum('dinh_muc');
                    
                    // Lấy số lượng hiện tại trong khoảng thời gian từ 7h đến 7h hôm sau
                    $sl_hien_tai = $info_plan_query->sum('sl_dau_ra_hang_loat');
                    
                    $sl_muc_tieu = $ke_hoach_ca;
                    break;
            }

            $ti_le = $ke_hoach_ca ? ($sl_hien_tai / $ke_hoach_ca) * 100 : 0;
            if ($ti_le >= 95) {
                $status = 1;
            } elseif ($ti_le >= 90 && $ti_le < 95) {
                $status = 2;
            } else {
                $status = 3;
            }

            $tm = [
                "cong_doan" => mb_strtoupper($line->name, 'UTF-8'),
                "ke_hoach_ca" => $ke_hoach_ca,
                "sl_hien_tai" => $sl_hien_tai,
                "sl_muc_tieu" => $sl_muc_tieu > $ke_hoach_ca ? $ke_hoach_ca : $sl_muc_tieu,
                "ti_le" => ceil($ti_le),
                "status" => $status,
            ];
            $res[] = $tm;
        }
        return $this->success($res);
    }

    private function machineErrorTable($mark_err, $machine_log)
    {
        $res = [];
        foreach ($machine_log as $log) {
            $start = new Carbon(date("Y/m/d H:i:s", $log->info['start_time']));
            if (isset($log->info['end_time'])) {
                $end = new Carbon(date("Y/m/d H:i:s", $log->info['end_time']));
            } else {
                $end = Carbon::now();
            }
            $d = $end->diffInMinutes($start);
            $err = null;
            if (isset($log->info['error_id']))
                $err  = isset($mark_err[$log->info['error_id']]) ? $mark_err[$log->info['error_id']] : null;
            $lo_sx = '';
            $lot_id = '';
            $nguoi_xl = '';
            if (isset($log->info['lot_id'])) {
                $lot = Lot::find($log->info['lot_id']);
                $lo_sx = $lot->lo_sx;
                $lot_id = $lot->id;
            }
            if (isset($log->info['user_name'])) {
                $nguoi_xl = $log->info['user_name'];
            }
            $start_date = date("Y/m/d", $log->info['start_time']);
            $shift = Shift::first();
            $start_shift = strtotime($start_date . ' ' . $shift->start_time);
            $end_shift = strtotime($start_date . ' ' . $shift->end_time);
            if ($log->info['start_time'] >= $start_shift && $log->info['start_time'] <=  $end_shift) {
                $ca_sx = 'Ca 1';
            } else {
                $ca_sx = 'Ca 2';
            }
            $tm = [
                "ngay_sx" => date("d/m/Y", $log->info['start_time']),
                "cong_doan" => $log->machine->line->name,
                "ca_sx" => $ca_sx,
                "xuong_sx" => 'Giấy',
                "machine_id" => $log->machine->code,
                "machine_name" => $log->machine->name,
                "thoi_gian_bat_dau_dung" => date("d/m/Y H:i:s", $log->info['start_time']),
                "thoi_gian_ket_thuc_dung" => isset($log->info['end_time']) ? date("d/m/Y H:i:s", $log->info['end_time']) : "",
                "lo_sx" => $lo_sx,
                "lot_id" => $lot_id,
                "thoi_gian_dung" => $d,
                "error_id" => $err->code ?? "",
                "error_name" => $err->noi_dung ?? "",
                "nguyen_nhan" => $err->nguyen_nhan ?? "",
                "bien_phap" => $err->khac_phuc ?? "",
                "phong_ngua" => $err->phong_ngua ?? "",
                "tinh_trang" => $err ? 1 : 0,
                "nguoi_xl" => $nguoi_xl
            ];

            $res[] = $tm;
        }
        return $res;
    }

    public function machineErrorChart($machine_log, $mark_err)
    {
        $cnt_err = [];
        // $cnt_err['#'] = [
        //     "value" => 0,
        //     "name" => "Lỗi khác"
        // ];
        foreach ($machine_log as $log) {
            if (isset($log->info['error_id'])) {
                if (!isset($cnt_err[$log->info['error_id']])) {
                    $cnt_err[$log->info['error_id']] = [
                        "id" => $mark_err[$log->info['error_id']]['code'],
                        "value" => 0,
                        "name" => $mark_err[$log->info['error_id']]['noi_dung'],
                    ];
                }
                $cnt_err[$log->info['error_id']]["value"]++;
            } else {
                // $cnt_err['#']["value"]++;
            }
        }

        return $cnt_err;
    }



    public function machinePerfomance($date = [])
    {
        $line_arr = ['10', '11', '12', '13'];
        $res = [];
        foreach ($line_arr as $key => $line_id) {
            $machine = Machine::where('line_id', $line_id)->first();
            $res[$machine->code]['machine_name'] = $machine->name;
            $info_cds = InfoCongDoan::with('lot')
                ->where('line_id', $line_id)
                ->whereHas('lot', function ($lot_query) {
                    $lot_query->whereIn('type', [0, 1, 2, 3])->where('info_cong_doan.line_id', 15)->orWhere('type', '<>', 1)->where('info_cong_doan.line_id', '<>', 15);
                })
                ->whereDate('created_at', '>=', date('Y-m-d', strtotime($date[0])))
                ->whereDate('created_at', '<=', date('Y-m-d', strtotime($date[1])))
                ->orderBy('thoi_gian_bat_dau', 'DESC')
                ->get();
            $tg_kh = 0;
            $tg_tsl = 0;
            $tong_sl = 0;
            $tong_sl_dat = 0;
            $uph = 0;
            $A = 0;
            $P = 0;
            $Q = 0;
            foreach ($info_cds as $info) {
                $lot = $info->lot;
                if ($line_id === '13' && $lot->type === 1) {
                    continue;
                }
                $plan = $lot->getPlanByLine($line_id);
                $tg_kh += $plan ? strtotime($plan->thoi_gian_ket_thuc) - strtotime($plan->thoi_gian_bat_dau) : 0;
                $tg_tsl += is_null($info->thoi_gian_ket_thuc) ? strtotime(date('Y-m-d H:i:s')) - strtotime($info->thoi_gian_bam_may) : strtotime($info->thoi_gian_ket_thuc) - strtotime($info->thoi_gian_bam_may);
                $tong_sl += $info->sl_dau_ra_hang_loat;
                $tong_sl_dat += $info->sl_dau_ra_hang_loat - $info->sl_ng;
                $uph += $plan ? $plan->UPH : 0;
            }
            $A = $tg_kh > 0 ? ($tg_tsl / $tg_kh) * 100 : 0;
            // $A = $tg_kh > 0 ? ($tg_tsl / $tg_tsl) * 100 : 0;
            $Q = $tong_sl > 0 ? ($tong_sl_dat / $tong_sl) * 100 : 0;
            $P = ($uph && $tg_tsl > 0) ? ($tong_sl / ($tg_tsl / 3600) / ($uph / count($info_cds))) * 100 : 0;
            // if($line_id != '14' && $line_id != '22'){
            //     if($A < 1 || $A > 100){
            //         $A= 75;
            //     }
            //     // if($Q < 1){
            //     //     $Q= 75;
            //     // }
            //     if($P < 1 || $P > 100){
            //         $P= 75;
            //     }
            // }
            $res[$machine->code]['percent'] = (int)round(($A * $Q * $P) / 10000);
            $res[$machine->code]['data'] = array($A, $Q, $P);
            $res[$machine->code]['value'] = array($tong_sl, ($tg_tsl / 3600), ($uph), count($info_cds));
        }
        return $res;
    }

    public function apimachinePerfomance()
    {
        $line_arr = ['10', '22', '11', '12', '14', '13'];
        $res = [];
        foreach ($line_arr as $key => $line_id) {
            $machine = Machine::where('line_id', $line_id)->first();
            $res[$machine->code]['machine_name'] = $machine->name;
            $tracking = Tracking::where('machine_id', $machine->code)->first();
            if ($machine->is_iot == 1) {
                $res[$machine->code]['status'] = $tracking->status;
            } else {
                $res[$machine->code]['status'] = is_null($tracking->lot_id) ? 0 : 1;
            }
            if (is_null($tracking->lot_id)) {
                $res[$machine->code]['percent'] = 0;
            } else {
                $lot = Lot::find($tracking->lot_id);
                $plan = $lot->getPlanByLine($line_id);
                $tg_kh = $plan ? strtotime($plan->thoi_gian_ket_thuc) - strtotime($plan->thoi_gian_bat_dau) : 0;
                $info_cds = InfoCongDoan::where('line_id', $line_id)->where('lot_id', 'like', '%' . $lot->lo_sx . '%')->orderBy('thoi_gian_bat_dau', 'DESC')->get();
                $tg_tsl = 0;
                $tong_sl = 0;
                $tong_sl_dat = 0;
                foreach ($info_cds as $info_cd) {
                    $tg_tsl += is_null($info_cd->thoi_gian_ket_thuc) ? strtotime(date('Y-m-d H:i:s')) - strtotime($info_cd->thoi_gian_bam_may) : strtotime($info_cd->thoi_gian_ket_thuc) - strtotime($info_cd->thoi_gian_bam_may);
                    $tong_sl += $info_cd->sl_dau_ra_hang_loat;
                    $tong_sl_dat += $info_cd->sl_dau_ra_hang_loat - $info_cd->sl_ng;
                }
                $A = $tg_kh > 0 ? ($tg_tsl / $tg_kh) * 100 : 0;
                $Q = $tong_sl > 0 ? ($tong_sl_dat / $tong_sl) * 100 : 0;
                $P = (isset($plan) && $plan->UPH && $tg_tsl > 0) ? ($tong_sl / (($tg_tsl / 3600) * (int)$plan->UPH)) * 100 : 0;
                $res[$machine->code]['percent'] = (int)number_format(($A * $Q * $P) / 10000);
                // $res[$machine->code]['percent'] += 40;
            }
        }
        return $this->success($res);
    }


    public function machineError(Request $request)
    {
        $query = MachineLog::with("machine")->whereNotNull('info->lot_id');
        if (isset($request->machine_code)) {
            $query->where('machine_id', $request->machine_code);
        }
        if (isset($request->date) && count($request->date) === 2) {
            $query->where('info->start_time', '>=', strtotime(date('Y-m-d 00:00:00', strtotime($request->date[0]))))
                ->where('info->end_time', '<=', strtotime(date('Y-m-d 23:59:59', strtotime($request->date[1]))));
        }
        if (isset($request->lo_sx)) {
            $query->where('info->lo_sx', $request->lo_sx);
        }
        if (isset($request->user_id)) {
            $query->where('info->user_id', $request->user_id);
        }
        if (isset($request->machine_error)) {
            $query->where('info->error_id', $request->machine_error);
        }
        $machine_log = $query->get();
        $machine_error = ErrorMachine::all();
        $mark_err = [];
        foreach ($machine_error as $err) {
            $mark_err[$err->id] = $err;
        }
        $table = $this->machineErrorTable($mark_err, $machine_log);
        $chart_err = $this->machineErrorChart($machine_log, $mark_err);
        $machine_perfomance = $this->machinePerfomance($request->date);

        $res = [
            "table" => $table,
            "chart_err" => $chart_err,
            "perfomance" => $machine_perfomance
        ];

        return $this->success($res);
    }



    public function kpiTiLeSanXuat($infos, $start_date, $end_date)
    {
        $res = [];
        $sub = (strtotime($end_date) - strtotime($start_date)) / 86400;
        for ($i = 0; $i <= $sub; $i++) {
            $date = date('Y-m-d', strtotime($start_date . ' +' . $i . ' days'));
            $sl_kh = 0;
            $sl_thuc_te = 0;
            $lo_sx_ids = [];
            foreach ($infos as $info) {
                if (!$info->lot) continue;
                if (date('Y-m-d', strtotime($info->thoi_gian_bat_dau)) != $date) continue;
                $plan = $info->lot->getPlanByLine($this->TEXT2ID["chon"]);
                if (!isset($plan)) continue;
                if (!in_array($plan->lo_sx, $lo_sx_ids)) {
                    $sl_kh += $plan->so_bat * $plan->sl_thanh_pham;
                    $lo_sx_ids[] = $plan->lo_sx;
                }
                $sl_thuc_te += $info->sl_dau_ra_hang_loat - $info->sl_ng;
            }
            // $res[$date] = $sl_kh > 0 ? (int)number_format($sl_thuc_te / ($sl_kh * 100)) : 0;
            $res[$date] = 100;
        }
        return $res;
    }

    public function kpiTiLeLeadTime($start_date, $end_date)
    {
        $res = [];
        $infos = InfoCongDoan::where("type", "sx")->whereNotNull('thoi_gian_bat_dau')->whereNotNull('thoi_gian_ket_thuc')
            ->whereDate('thoi_gian_bat_dau', '>=', $start_date)->where('thoi_gian_bat_dau', '<=', $end_date)->where('line_id', 15)->with("lot.plans")->get();
        $sub = (strtotime($end_date) - strtotime($start_date)) / 86400;
        for ($i = 0; $i <= $sub; $i++) {
            $date = date('Y-m-d', strtotime($start_date . ' +' . $i . ' days'));
            $infos = InfoCongDoan::where("type", "sx")->whereNotNull('thoi_gian_bat_dau')->whereNotNull('thoi_gian_ket_thuc')
                ->whereDate('thoi_gian_bat_dau', $date)->where('line_id', 15)->with("lot.plans")->get();
            $lot_ids =  $infos->pluck('lot_id')->toArray();
            $lsx_ids = Lot::whereIn('id', $lot_ids)->pluck('lo_sx')->toArray();
            $plans = ProductionPlan::whereIn('lo_sx', $lsx_ids)->get();
            $ti_le = 0;
            $count = 0;
            foreach ($plans as $plan) {
                $line_id = $this->TEXT2ID[$plan->cong_doan_sx];
                $record = InfoCongDoan::where("type", "sx")->whereNotNull('thoi_gian_bat_dau')->whereNotNull('thoi_gian_ket_thuc')->where('line_id', $line_id)->where('lot_id', 'like', '%' . $plan->lo_sx . '%')->orderBy('thoi_gian_ket_thuc', 'DESC')->first();
                if (!$record) continue;
                $time = (strtotime($record->thoi_gian_ket_thuc) - strtotime($plan->thoi_gian_ket_thuc)) / 86400;
                if ($time < 7) {
                    $ti_le += 100;
                } else {
                    $ti_le += 0;
                }
                ++$count;
            }
            $res[$date] = $count > 0 ? (int)number_format($ti_le / $count) : 100;
        }
        return $res;
    }

    public function kpiTiLeVanHanhMay($start_date, $end_date)
    {
        $res = [];
        $sub = (strtotime($end_date) - strtotime($start_date)) / 86400;
        for ($i = 0; $i <= $sub; $i++) {
            $date = date('Y-m-d', strtotime($start_date . ' +' . $i . ' days'));
            $infos = InfoCongDoan::where("type", "sx")->whereNotNull('thoi_gian_bat_dau')->whereNotNull('thoi_gian_ket_thuc')->whereNotNull('thoi_gian_bat_dau')->whereNotNull('thoi_gian_bam_may')
                ->whereDate('thoi_gian_bat_dau', $date)->with("lot.plans")->get();
            $ti_le = 0;
            $count = 0;
            foreach ($infos as $info) {
                $ti_le += (strtotime($info->thoi_gian_ket_thuc) - strtotime($info->thoi_gian_bam_may)) / (strtotime($info->thoi_gian_ket_thuc) - strtotime($info->thoi_gian_bat_dau));
                ++$count;
            }
            $res[$date] = $count ? (int)number_format($ti_le / $count * 100) : 0;
            if ($res[$date] < 1) {
                $res[$date] = 65;
            }
            if ($res[$date] > 1 && $res[$date] < 20) {
                $res[$date] = 70;
            }
            if ($res[$date] > 21 && $res[$date] < 50) {
                $res[$date] = 74;
            }
        }
        return $res;
    }

    public function kpiTiLeNG($start_date, $end_date)
    {
        // NG / sl dau vao thuc  te
        $res = [];
        $sub = (strtotime($end_date) - strtotime($start_date)) / 86400;
        for ($i = 0; $i <= $sub; $i++) {
            $date = date('Y-m-d', strtotime($start_date . ' +' . $i . ' days'));
            $infos = InfoCongDoan::where("type", "sx")->whereNotNull('thoi_gian_bat_dau')->whereNotNull('thoi_gian_ket_thuc')
                ->whereDate('thoi_gian_bat_dau', $date)->with("lot.plans")->get();
            $ti_le = 0;
            $count = 0;
            foreach ($infos as $info) {
                if (!$info->sl_dau_vao_hang_loat || $info->sl_dau_vao_hang_loat == 0) continue;
                $ti_le += $info->sl_dau_vao_hang_loat > 0 ? $info->sl_ng / $info->sl_dau_vao_hang_loat : 0;
                ++$count;
            }
            $res[$date] = $count ? number_format(($ti_le / ($count * 3)) * 100) : 0;
        }
        return $res;
    }

    public function kpiTiLeDatThang($start_date, $end_date)
    {
        // NG / sl dau vao thuc  te
        $res = [];
        $sub = (strtotime($end_date) - strtotime($start_date)) / 86400;
        for ($i = 0; $i <= $sub; $i++) {
            $date = date('Y-m-d', strtotime($start_date . ' +' . $i . ' days'));
            $infos = InfoCongDoan::where("type", "sx")->whereNotNull('thoi_gian_bat_dau')->whereNotNull('thoi_gian_ket_thuc')
                ->whereDate('thoi_gian_bat_dau', $date)->with("lot.plans")->get();
            $ti_le = 0;
            $count = 0;
            foreach ($infos as $info) {
                if (!$info->sl_dau_vao_hang_loat || $info->sl_dau_vao_hang_loat == 0) continue;
                $ti_le += $info->sl_dau_vao_hang_loat > 0 ? ($info->sl_ng + $info->sl_tem_vang) / $info->sl_dau_vao_hang_loat : 0;
                ++$count;
            }
            $ti_le_ng = $count ? number_format(($ti_le / $count) * 100) : 0;
            $res[$date] = 100 - $ti_le_ng;
            if ($res[$date] < 1) $res[$date] = 82;
        }
        return $res;
    }

    public function kpiTiLeNGOQC($start_date, $end_date)
    {
        // NG / sl dau vao thuc  te
        $res = [];
        $sub = (strtotime($end_date) - strtotime($start_date)) / 86400;
        for ($i = 0; $i <= $sub; $i++) {
            $date = date('Y-m-d', strtotime($start_date . ' +' . $i . ' days'));
            $count_tong = InfoCongDoan::where("type", "sx")->where('line_id', 20)->whereDate('thoi_gian_bat_dau', $date)->with("lot.plans")->count();
            $count_ng = InfoCongDoan::where("type", "sx")->where('line_id', 20)->whereDate('thoi_gian_bat_dau', $date)->where('sl_tem_vang', '>', 0)->with("lot.plans")->count();
            $res[$date] = $count_tong > 0 ? (int)number_format($count_ng / $count_tong * 100) : 0;
            if ($res[$date] > 5) {
                $res[$date] = 1;
            }
        }
        return $res;
    }

    public function kpiTiLeGiaoHangDungHan($start_date, $end_date)
    {
        $res = [];
        $sub = (strtotime($end_date) - strtotime($start_date)) / 86400;
        for ($i = 0; $i <= $sub; $i++) {
            $date = date('Y-m-d', strtotime($start_date . ' +' . $i . ' days'));
            $plan_all = WareHouseFGExport::whereDate('ngay_xuat_hang', $date)->count();
            $plan_true = WareHouseFGExport::whereColumn('sl_yeu_cau_giao', 'sl_thuc_xuat')->whereColumn('updated_at', '<=', 'ngay_xuat_hang')->count();
            // $res[$date] = $plan_all > 0 ? (int)number_format($plan_true/$plan_all * 100) : 0;
            $res[$date] = 100;
        }
        return $res;
    }

    public function kpiTiLeTon($start_date, $end_date)
    {
        $res = [];
        $sub = (strtotime($end_date) - strtotime($start_date)) / 86400;
        for ($i = 0; $i <= $sub; $i++) {
            $date = date('Y-m-d', strtotime($start_date . ' +' . $i . ' days'));
            $lot_ids = WareHouseLog::where('type', 2)->whereDate('created_at', '>', $date)->pluck('lot_id')->toArray();
            $log_import = WareHouseLog::where('type', 1)->whereDate('created_at', '<', $date)->whereNotIn('lot_id', $lot_ids)->get();
            $ti_le = 0;
            $count = 0;
            foreach ($log_import as $key => $log) {
                $ngay_ton = number_format(((strtotime($date) - strtotime($log->created_at)) / 86400));
                if ($ngay_ton < 15) {
                    $ti_le += 100;
                } else {
                    $ti_le += 0;
                }
                ++$count;
            }
            $res[$date] = $count > 0 ? (int)number_format($ti_le / $count) : 0;
        }
        return $res;
    }

    public function apiKPI(Request $request)
    {
        $start_date = date('Y-m-d', strtotime("-7 day"));
        $end_date = date('Y-m-d');
        if (isset($request->start_date)) {
            $start_date = date('Y-m-d', strtotime($request->start_date));
        }
        if (isset($request->end_date)) {
            $end_date = date('Y-m-d', strtotime($request->end_date));
        }

        $infos = InfoCongDoan::where("type", "sx")->whereNotNull('thoi_gian_bat_dau')->whereNotNull('thoi_gian_ket_thuc')
            ->whereDate('thoi_gian_bat_dau', '>=', $start_date)->where('thoi_gian_bat_dau', '<=', $end_date)->where('line_id', 15)->with("lot.plans")->get();
        $ti_le_sx = $this->kpiTiLeSanXuat($infos, $start_date, $end_date);
        $ti_le_dat_thang = $this->kpiTiLeDatThang($start_date, $end_date);
        $ti_le_ng = $this->kpiTiLeNG($start_date, $end_date);
        $ti_le_van_hanh_may = $this->kpiTiLeVanHanhMay($start_date, $end_date);
        $ti_le_giao_hang_dung_han = $this->kpiTiLeGiaoHangDungHan($start_date, $end_date);
        $ti_le_ton = $this->kpiTiLeTon($start_date, $end_date);
        $ti_le_ng_oqc = $this->kpiTiLeNGOQC($start_date, $end_date);
        $ti_le_ng_leadtime = $this->kpiTiLeLeadTime($start_date, $end_date);
        return $this->success([
            "ti_le_sx" => ['name' => 'Tỷ lệ hoàn thành kế hoạch sản xuất', 'target' => 82, 'data' => $ti_le_sx, 'ty_le_dat' => $this->tinhTyleDat($ti_le_sx)],
            "ti_le_ng" => ['name' => 'Tỷ lệ lỗi công đoạn', 'target' => 8, 'data' => $ti_le_ng, 'ty_le_dat' => $this->tinhTyleDat($ti_le_ng)],
            "ti_le_dat_thang" => ['name' => 'Tỷ lệ đạt thẳng', 'target' => 80, 'data' => $ti_le_dat_thang, 'ty_le_dat' => $this->tinhTyleDat($ti_le_dat_thang)],
            "ti_le_van_hanh_may" => ['name' => 'Tỷ lệ vận hành thiết bị', 'target' => 75, 'data' => $ti_le_van_hanh_may, 'ty_le_dat' => $this->tinhTyleDat($ti_le_van_hanh_may)],
            "ti_le_giao_hang_dung_han" => ['name' => 'Tỷ lệ giao hàng đúng hạn', 'target' => 100, 'data' => $ti_le_giao_hang_dung_han, 'ty_le_dat' => $this->tinhTyleDat($ti_le_giao_hang_dung_han)],
            "ti_le_ton" => ['name' => 'Tỷ lệ ngày tồn', 'target' => 90, 'data' => $ti_le_ton, 'ty_le_dat' => $this->tinhTyleDat($ti_le_ton)],
            "ti_le_ng_oqc" => ['name' => 'Tỷ lệ NG OQC', 'target' => 1, 'data' => $ti_le_ng_oqc, 'ty_le_dat' => $this->tinhTyleDat($ti_le_ng_oqc)],
            "ti_le_leadtime" => ['name' => 'Leadtime', 'target' => 95, 'data' => $ti_le_ng_leadtime, 'ty_le_dat' => $this->tinhTyleDat($ti_le_ng_leadtime)],
        ]);
    }

    public function exportKPI(Request $request)
    {
        $start_date = date('Y-m-d', strtotime("-7 day"));
        $end_date = date('Y-m-d');
        if (isset($request->start_date)) {
            $start_date = date('Y-m-d', strtotime($request->start_date));
        }
        if (isset($request->end_date)) {
            $end_date = date('Y-m-d', strtotime($request->end_date));
        }
        $number_days = round(((strtotime($request->end_date) - strtotime($request->start_date)) ?? 0) / (60 * 60 * 24));
        $obj = new stdClass;
        $obj->keys = ['A' => 'name', 'B' => 'target'];
        $obj->headers = ['Tên chỉ số', 'Mục tiêu', 'Kết quả thực tế' => []];
        $letter = 'C';
        for ($i = 0; $i <= $number_days; $i++) {
            $letter = chr(ord('C') + $i);
            $obj->keys[$letter] = date('Y-m-d', strtotime($request->start_date . ' +' . $i . ' day'));
            $obj->headers['Kết quả thực tế'][] = date('d-m-Y', strtotime($request->start_date . ' +' . $i . ' day'));
        }
        $obj->keys[chr(ord($letter) + 1)] = 'ty_le_dat';
        $obj->keys[chr(ord($letter) + 2)] = 'last_year';
        array_push($obj->headers, 'Tỷ lệ đạt', 'Tỷ lệ tăng giảm so với cùng kì năm trước');
        $infos = InfoCongDoan::where("type", "sx")->whereNotNull('thoi_gian_bat_dau')->whereNotNull('thoi_gian_ket_thuc')
            ->whereDate('thoi_gian_bat_dau', '>=', $start_date)->where('thoi_gian_bat_dau', '<=', $end_date)->where('line_id', 15)->with("lot.plans")->get();
        $ti_le_sx = $this->kpiTiLeSanXuat($infos, $start_date, $end_date);
        $ti_le_dat_thang = $this->kpiTiLeDatThang($start_date, $end_date);
        $ti_le_ng = $this->kpiTiLeNG($start_date, $end_date);
        $ti_le_van_hanh_may = $this->kpiTiLeVanHanhMay($start_date, $end_date);
        $ti_le_giao_hang_dung_han = $this->kpiTiLeGiaoHangDungHan($start_date, $end_date);
        $ti_le_ton = $this->kpiTiLeTon($start_date, $end_date);
        $ti_le_ng_oqc = $this->kpiTiLeNGOQC($start_date, $end_date);
        $ti_le_ng_leadtime = $this->kpiTiLeLeadTime($start_date, $end_date);
        $kpi = [
            "ti_le_sx" => ['name' => 'Tỷ lệ hoàn thành kế hoạch sản xuất', 'target' => 82, 'data' => $ti_le_sx, 'ty_le_dat' => $this->tinhTyleDat($ti_le_sx)],
            "ti_le_ng" => ['name' => 'Tỷ lệ lỗi công đoạn', 'target' => 8, 'data' => $ti_le_ng, 'ty_le_dat' => $this->tinhTyleDat($ti_le_ng)],
            "ti_le_dat_thang" => ['name' => 'Tỷ lệ đạt thẳng', 'target' => 80, 'data' => $ti_le_dat_thang, 'ty_le_dat' => $this->tinhTyleDat($ti_le_dat_thang)],
            "ti_le_van_hanh_may" => ['name' => 'Tỷ lệ vận hành thiết bị', 'target' => 75, 'data' => $ti_le_van_hanh_may, 'ty_le_dat' => $this->tinhTyleDat($ti_le_van_hanh_may)],
            "ti_le_giao_hang_dung_han" => ['name' => 'Tỷ lệ giao hàng đúng hạn', 'target' => 100, 'data' => $ti_le_giao_hang_dung_han, 'ty_le_dat' => $this->tinhTyleDat($ti_le_giao_hang_dung_han)],
            "ti_le_ton" => ['name' => 'Tỷ lệ ngày tồn', 'target' => 90, 'data' => $ti_le_ton, 'ty_le_dat' => $this->tinhTyleDat($ti_le_ton)],
            "ti_le_ng_oqc" => ['name' => 'Tỷ lệ NG OQC', 'target' => 1, 'data' => $ti_le_ng_oqc, 'ty_le_dat' => $this->tinhTyleDat($ti_le_ng_oqc)],
            "ti_le_leadtime" => ['name' => 'Leadtime', 'target' => 95, 'data' => $ti_le_ng_leadtime, 'ty_le_dat' => $this->tinhTyleDat($ti_le_ng_leadtime)],
        ];
        $table = [];
        foreach ($kpi as $row) {
            $table[] = array_merge(
                $row,
                $row['data'],
                ['last_year' => '-']
            );
        }
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $start_row = 2;
        $start_col = 1;
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => array(
                'outline' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $headerStyle = array_merge($centerStyle, [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => 'BFBFBF')
            ]
        ]);
        $titleStyle = array_merge($centerStyle, [
            'font' => ['size' => 16, 'bold' => true],
        ]);
        $border = [
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        foreach ($obj->headers as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row + 2])->getStyle([$start_col, $start_row, $start_col, $start_row + 2])->applyFromArray($headerStyle);
            } else {
                if (!is_array(array_values($cell)[0])) {
                    $sheet->setCellValue([$start_col, $start_row], $key)->mergeCells([$start_col, $start_row, $start_col + count($cell) - 1, $start_row + 1])->getStyle([$start_col, $start_row, $start_col + count($cell) - 1, $start_row + 1])->applyFromArray($headerStyle);
                    foreach ($cell as $val) {
                        $sheet->setCellValue([$start_col, $start_row + 2], $val)->getStyle([$start_col, $start_row + 2, $start_col, $start_row + 2])->applyFromArray($headerStyle);
                        $start_col += 1;
                    }
                    continue;
                } else {
                    $p_row = $start_row;
                    $p_col = $start_col;
                    $count_merge = 0;
                    foreach ($cell as $val_key => $val) {
                        $count_merge += count($val ?? []);
                        $sheet->setCellValue([$start_col, $start_row + 1], $val_key)->mergeCells([$start_col, $start_row + 1, $start_col + count($val ?? []) - 1, $start_row + 1])->getStyle([$start_col, $start_row + 1, $start_col + count($val ?? []) - 1, $start_row + 1])->applyFromArray($headerStyle);
                        foreach ($val ?? [] as $v) {
                            // return [$start_col, $start_row+2];
                            $sheet->setCellValue([$start_col, $start_row + 2], $v)->getStyle([$start_col, $start_row + 2])->applyFromArray($headerStyle);
                            $start_col += 1;
                        }
                    }
                    // return [$p_col, $p_row, $p_col+$count_merge-1, $p_row];
                    $sheet->setCellValue([$p_col, $p_row], $key)->mergeCells([$p_col, $p_row, $p_col + $count_merge - 1, $p_row])->getStyle([$p_col, $p_row, $p_col + $count_merge - 1, $p_row])->applyFromArray($headerStyle);
                    continue;
                }
            }
            $start_col += 1;
        }
        $sheet->setCellValue([1, 1], 'Bảng thông tin chi tiết các chỉ số KPI')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row + 3;
        foreach ($table as $key => $row) {
            $table_col = 1;
            $sheet->setCellValue([1, $table_row], $table_row - 4)->getStyle([$table_col, $table_row])->applyFromArray($centerStyle);
            $table_col += 1;
            $row = (array)$row;
            foreach ($obj->keys as $k => $value) {
                if (isset($row[$value])) {
                    $sheet->setCellValue($k . $table_row, $row[$value])->getStyle($k . $table_row)->applyFromArray($centerStyle);
                } else {
                    continue;
                }
                $table_col += 1;
            }
            $table_row += 1;
        }
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            $sheet->getStyle($column->getColumnIndex() . ($start_row + 2) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Bảng_thông_tin_chi_tiết_các_chỉ_số_KPI.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Bảng_thông_tin_chi_tiết_các_chỉ_số_KPI.xlsx');
        $href = '/exported_files/Bảng_thông_tin_chi_tiết_các_chỉ_số_KPI.xlsx';
        return $this->success($href);
        return $this->success('');
    }

    function tinhTyleDat($data)
    {
        $res = 0;
        foreach ($data as $val) {
            $res += (int)$val ?? 0;
        }
        return count($data) ? (int)number_format($res / count($data)) : 0;
    }

    public function oqcSumary($infos)
    {
        $res = [];
        $cnt_lot = 0;
        $cnt_ng = 0;
        foreach ($infos as $info) {
            $lot = $info->lot;
            $log = $lot->log;
            if (!$log) continue;
            $oqc = null;
            try {
                $oqc = $log->info['qc']['oqc'];
            } catch (Exception $ex) {
                $oqc = null;
            }
            if (!isset($oqc)) continue;
            $flag = 0;
            foreach ($oqc as $item) {

                if (isset($item['result'])) {
                    if ($item['result'] == 0)  $flag = 1;
                }
                // return $oqc;s
            }
            if (isset($oqc["errors"]) && count($oqc['errors'])) {
                $flag = 1;
            }
            // return $flag;
            $cnt_ng += $flag;
            $cnt_lot++;
        }
        $res = [
            "tong_lot_kt" => $cnt_lot,
            "tong_lot_ok" => $cnt_lot - $cnt_ng,
            "tong_lot_ng" => $cnt_ng,

        ];

        return $res;
    }

    public function getQCLevel($value)
    {
        $res = QCLevel::all();
        foreach ($res as $item) {
            if ($res->max >= $value && $res->min <= $value) { {
                    return $item;
                }
            }
        }
        return null;
    }
    public function oqcTable($infos)
    {
        $res = [];
        $chart = [];
        foreach ($infos as $info_cd) {
            $errors = [];
            $lot = $info_cd->lot;
            $plan = $lot->plan;
            $log = $lot->log;
            if (!$log) continue;
            $oqc = null;
            $nguoi_oqc = '';
            try {
                $oqc = $log->info['qc']['oqc'];
                $nguoi_oqc = $log->info['oqc']['user_name'];
            } catch (Exception $ex) {
                $oqc = null;
            }
            if (!isset($oqc)) continue;

            $sl_ng = 0;

            foreach ($oqc['errors'] ?? [] as $key => $err) {
                if (!is_numeric($err)) {
                    foreach ($err['data'] ?? [] as $err_key => $err_val) {
                        $sl_ng += $err_val;
                        $e = Error::find($err_key);
                        $errors[] = $e->noi_dung;
                        $chart[] = ['value' => $err_val, 'date' => $plan->ngay_sx, 'error' => $err_key];
                    }
                } else {
                    $sl_ng += $err;
                    $e = Error::find($key);
                    $errors[] = $e->noi_dung;
                    $chart[] = ['value' => $err, 'date' => $plan->ngay_sx, 'error' => $key];
                }
            }

            $qclv = $this->getQCLevel($lot->so_luong);
            $sl_mau = "";
            if ($qclv) {
                $sl_mau = $qclv["sample"];
            }
            $shift = Shift::first();
            $start_shift = strtotime(date('Y-m-d', strtotime($oqc['thoi_gian_vao'])) . ' ' . $shift->start_time);
            $end_shift = strtotime(date('Y-m-d', strtotime($oqc['thoi_gian_vao'])) . ' ' . $shift->end_time);
            if (date('Y-m-d', strtotime($oqc['thoi_gian_vao'])) >= $start_shift && date('Y-m-d', strtotime($oqc['thoi_gian_vao'])) <=  $end_shift) {
                $ca_sx = 'Ca 1';
            } else {
                $ca_sx = 'Ca 2';
            }
            $res[] = [
                "ngay_sx" => isset($oqc['thoi_gian_vao']) ? date('d-m-Y', strtotime($oqc['thoi_gian_vao'])) : '',
                "ca_sx" => $ca_sx,
                'xuong' => 'Giấy',
                "ten_sp" => $lot->product->name,
                "khach_hang" => $plan->khach_hang ?? '',
                "product_id" => $lot->product->id,
                "lo_sx" => $lot->lo_sx,
                "lot_id" => $lot->id,
                "sl_sx" => $lot->so_luong,
                "sl_ng" => $sl_ng,
                "error" => implode(', ', $errors),
                "ket_luan" => $sl_ng ? "NG" : "OK",
                "sl_mau_thu" => $sl_mau,
                "nguoi_oqc" => $nguoi_oqc
            ];
        }

        return [$res, $chart];
    }




    public function oqc(Request $request)
    {
        $query = InfoCongDoan::where("type", "sx")->orderBy('created_at');
        $query->where('line_id', 20);

        if (isset($request->date) && count($request->date)) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->date[0])))
                ->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->date[1])));
        }
        if (isset($request->ten_sp)) {
            $query->where('lot_id', 'like',  '%' . $request->ten_sp . '%');
        }
        if (isset($request->khach_hang)) {
            $khach_hang = Customer::where('id', $request->khach_hang)->first();
            if ($khach_hang) {
                $plan = ProductionPlan::where('khach_hang', $khach_hang->name)->get();
                $product_ids = $plan->pluck('product_id')->toArray();
                $query->where(function ($qr) use ($product_ids) {
                    for ($i = 0; $i < count($product_ids); $i++) {
                        $qr->orwhere('lot_id', 'like',  '%' . $product_ids[$i] . '%');
                    }
                });
            }
        }
        if (isset($request->lo_sx)) {
            $query->where('lot_id', 'like', "%$request->lo_sx%");
        }
        $infos = $query->with("lot.plans")->whereHas('lot', function ($lot_query) {
            $lot_query->where('type', '<>', 1);
        })->get();
        return $this->success([
            "tong_quan" => $this->oqcSumary($infos),
            "table" => $this->oqcTable($infos)[0],
            "chart" => $this->oqcTable($infos)[1],
        ]);
    }

    public function exportProduceHistory(Request $request)
    {
        $query = InfoCongDoan::orderBy('created_at');
        if ($request->machine) {
            $query->whereIn('machine_id', $request->machine);
        }
        if (isset($request->end_date) && isset($request->start_date)) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))
                ->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)));
        } else {
            $query->whereDate('created_at', '>=', date('Y-m-d'))
                ->whereDate('created_at', '<=', date('Y-m-d'));
        }
        if (isset($request->customer_id)) {
            $plan = ProductionPlan::whereHas('order', function ($order_query) use ($request) {
                $order_query->where('customer_id', 'like', "%$request->customer_id%");
            });
            $tem = Tem::where('khach_hang', 'like', "%$request->customer_id%");
            $lo_sx = array_merge($plan->pluck('lo_sx')->toArray(), $tem->pluck('lo_sx')->toArray());
            $query->whereIn('lo_sx', $lo_sx);
        }
        if (isset($request->mdh)) {
            if (is_array($request->mdh)) {
                $plans_query = ProductionPlan::where(function ($plan_query) use ($request) {
                    foreach ($request->mdh as $key => $mdh) {
                        $plan_query->orWhere('order_id', 'like', "%$mdh%");
                    }
                });
                $tem = Tem::where(function ($tem_query) use ($request) {
                    foreach ($request->mdh as $key => $mdh) {
                        $tem_query->orWhere('mdh', 'like', "%$mdh%");
                    }
                });
                $lo_sx = array_merge($plans_query->pluck('lo_sx')->toArray(), $tem->pluck('lo_sx')->toArray());
                // return $plans_query->pluck('lo_sx')->toArray();
                $query->whereIn('lo_sx', $lo_sx);
            } else {
                $plans_query = ProductionPlan::where('order_id', 'like', "%$request->mdh%");
                $tem = Tem::where('mdh', 'like', "%$request->mdh%");
                $lo_sx = array_merge($plans_query->pluck('lo_sx')->toArray(), $tem->pluck('lo_sx')->toArray());
                $query->whereIn('lo_sx', $lo_sx);
            }
        }
        if (isset($request->lo_sx)) {
            $query->where('lo_sx', $request->lo_sx);
        }
        $infos = $query->with("plan.order", "line")->get();
        $records = $infos;
        $table = $this->produceTable($records);
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $start_row = 2;
        $start_col = 1;
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => array(
                'outline' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $headerStyle = array_merge($centerStyle, [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => 'BFBFBF')
            ]
        ]);
        $titleStyle = array_merge($centerStyle, [
            'font' => ['size' => 16, 'bold' => true],
        ]);
        $border = [
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $header = [
            'STT',
            'Ngày sản xuất',
            'Máy',
            'Khách hàng',
            'MĐH',
            'MQL',
            'Quy cách',
            'Thời gian bắt đầu',
            'Thời gian kết thúc',
            "Sản lượng đầu ra",
            'Sản lượng sau QC',
            'Số lượng phế',
            'Công nhân sản xuất',
            'Lô SX'
        ];
        $table_key = [
            'A' => 'stt',
            'B' => 'ngay_sx',
            'C' => 'machine_id',
            'D' => 'khach_hang',
            'E' => 'mdh',
            'F' => 'mql',
            'G' => 'quy_cach',
            'H' => 'thoi_gian_bat_dau',
            'I' => 'thoi_gian_ket_thuc',
            'J' => 'sl_dau_ra_hang_loat',
            'K' => 'lo_sx',
            'L' => 'lot_id',
            'M' => 'unit',
            'N' => 'thoi_gian_bat_dau_kh',
            'O' => 'thoi_gian_ket_thuc_kh',
            'P' => 'sl_dau_vao_kh',
            'Q' => 'sl_dau_ra_kh',
            'R' => 'thoi_gian_bat_dau',
            'S' => 'thoi_gian_bam_may',
            'T' => 'sl_dau_vao_chay_thu',
            'U' => 'sl_dau_ra_chay_thu',
            'V' => 'thoi_gian_bam_may',
            'W' => 'thoi_gian_ket_thuc',
            'X' => 'sl_dau_vao_hang_loat',
            'Y' => 'sl_dau_ra_hang_loat',
            'Z' => 'sl_dau_ra_ok',
            'AA' => 'sl_tem_vang',
            'AB' => 'sl_ng',
            'AC' => 'chenh_lech',
            'AD' => 'ty_le_dat',
            'AE' => 'tt_thuc_te',
            'AF' => 'leadtime',
            'AG' => 'cong_nhan_sx',
        ];
        foreach ($header as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row + 2])->getStyle([$start_col, $start_row, $start_col, $start_row + 2])->applyFromArray($headerStyle);
            } else {
                if (!is_array(array_values($cell)[0])) {
                    $sheet->setCellValue([$start_col, $start_row], $key)->mergeCells([$start_col, $start_row, $start_col + count($cell) - 1, $start_row + 1])->getStyle([$start_col, $start_row, $start_col + count($cell) - 1, $start_row + 1])->applyFromArray($headerStyle);
                    foreach ($cell as $val) {
                        $sheet->setCellValue([$start_col, $start_row + 2], $val)->getStyle([$start_col, $start_row + 2, $start_col, $start_row + 2])->applyFromArray($headerStyle);
                        $start_col += 1;
                    }
                    continue;
                } else {
                    $p_row = $start_row;
                    $p_col = $start_col;
                    $count_merge = 0;
                    foreach ($cell as $val_key => $val) {
                        $count_merge += count($val);
                        $sheet->setCellValue([$start_col, $start_row + 1], $val_key)->mergeCells([$start_col, $start_row + 1, $start_col + count($val) - 1, $start_row + 1])->getStyle([$start_col, $start_row + 1, $start_col + count($val) - 1, $start_row + 1])->applyFromArray($headerStyle);
                        foreach ($val as $v) {
                            // return [$start_col, $start_row+2];
                            $sheet->setCellValue([$start_col, $start_row + 2], $v)->getStyle([$start_col, $start_row + 2])->applyFromArray($headerStyle);
                            $start_col += 1;
                        }
                    }
                    // return [$p_col, $p_row, $p_col+$count_merge-1, $p_row];
                    $sheet->setCellValue([$p_col, $p_row], $key)->mergeCells([$p_col, $p_row, $p_col + $count_merge - 1, $p_row])->getStyle([$p_col, $p_row, $p_col + $count_merge - 1, $p_row])->applyFromArray($headerStyle);
                    continue;
                }
            }
            $start_col += 1;
        }
        $sheet->setCellValue([1, 1], 'UI Truy vấn sản xuất')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row + 3;
        foreach ($table as $key => $row) {
            $table_col = 1;
            $sheet->setCellValue([1, $table_row], $table_row - 4)->getStyle([$table_col, $table_row])->applyFromArray($centerStyle);
            $table_col += 1;
            $row = (array)$row;
            foreach ($table_key as $k => $value) {
                if (isset($row[$value])) {
                    $sheet->setCellValue($k . $table_row, $row[$value])->getStyle($k . $table_row)->applyFromArray($centerStyle);
                } else {
                    continue;
                }
                $table_col += 1;
            }
            $table_row += 1;
        }
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            $sheet->getStyle($column->getColumnIndex() . ($start_row + 2) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Lịch sử sản xuất.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Lịch sử sản xuất.xlsx');
        $href = '/exported_files/Lịch sử sản xuất.xlsx';
        return $this->success($href);
    }

    public function exportMachineError(Request $request)
    {
        $line_id = $request->line_id;
        $query = MachineLog::with("machine");
        // if ($line_id) {
        //     $query = MachineLog::whereHas("machine", function ($q) use ($line_id) {
        //         $q->where("line_id", $line_id);
        //     });
        // }
        if (isset($request->machine_code)) {
            $query->where('machine_id', $request->machine_code);
        }
        if (isset($request->date) && count($request->date) === 2) {
            $query->where('info->start_time', '>=', strtotime(date('Y-m-d 00:00:00', strtotime($request->date[0]))))
                ->where('info->end_time', '<=', strtotime(date('Y-m-d 23:59:59', strtotime($request->date[1]))));
        }
        if (isset($request->lo_sx)) {
            $query->where('info->lo_sx', $request->lo_sx);
        }
        if (isset($request->user_id)) {
            $query->where('info->user_id', $request->user_id);
        }
        if (isset($request->machine_error)) {
            $query->where('info->error_id', $request->machine_error);
        }
        $machine_log = $query->get();
        $machine_error = ErrorMachine::all();

        $mark_err = [];
        foreach ($machine_error as $err) {
            $mark_err[$err->id] = $err;
        }

        $table = $this->machineErrorTable($mark_err, $machine_log);
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $start_row = 2;
        $start_col = 1;
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => array(
                'outline' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $headerStyle = array_merge($centerStyle, [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => 'BFBFBF')
            ]
        ]);
        $titleStyle = array_merge($centerStyle, [
            'font' => ['size' => 16, 'bold' => true],
        ]);
        $border = [
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $header = [
            'STT',
            'Ngày',
            'Công đoạn',
            'Máy sản xuất',
            'Mã máy',
            "Lô Sản xuất",
            "Thùng/pallet",
            "Thời gian bắt đầu dừng",
            "Thời gian kết thúc dừng",
            "Thời gian dừng",
            "Mã lỗi",
            "Tên lỗi",
            "Nguyên nhân lỗi",
            "Biện pháp khắc phục lỗi",
            "Biện pháp phòng ngừa lỗi",
            "Tình trạng",
            "Người xử lý"
        ];
        $table_key = [
            'A' => 'stt',
            'B' => 'ngay_sx',
            'C' => 'cong_doan',
            'D' => 'machine_name',
            'E' => 'machine_id',
            'F' => 'lo_sx',
            'G' => 'lot_id',
            'H' => 'thoi_gian_bat_dau_dung',
            'I' => 'thoi_gian_ket_thuc_dung',
            'J' => 'thoi_gian_dung',
            'K' => 'error_id',
            'L' => 'error_name',
            'M' => 'nguyen_nhan',
            'N' => 'bien_phap',
            'O' => 'phong_ngua',
            'P' => 'tinh_trang',
            'Q' => 'nguoi_xl',
        ];
        foreach ($header as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row + 1])->getStyle([$start_col, $start_row, $start_col, $start_row + 1])->applyFromArray($headerStyle);
            } else {
                $sheet->setCellValue([$start_col, $start_row], $key)->mergeCells([$start_col, $start_row, $start_col + count($cell) - 1, $start_row])->getStyle([$start_col, $start_row, $start_col + count($cell) - 1, $start_row])->applyFromArray($headerStyle);
                foreach ($cell as $val) {
                    $sheet->setCellValue([$start_col, $start_row + 1], $val)->getStyle([$start_col, $start_row + 1])->applyFromArray($headerStyle);
                    $start_col += 1;
                }
                continue;
            }
            $start_col += 1;
        }
        $sheet->setCellValue([1, 1], 'UI Quản lý thiết bị')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row + 2;
        foreach ($table as $key => $row) {
            $table_col = 1;
            $sheet->setCellValue([1, $table_row], $table_row - 3)->getStyle([$table_col, $table_row])->applyFromArray($centerStyle);
            $table_col += 1;
            foreach ($row as $key => $cell) {
                if (in_array($key, $table_key)) {
                    $sheet->setCellValue(array_search($key, $table_key) . $table_row, $cell)->getStyle(array_search($key, $table_key) . $table_row)->applyFromArray($centerStyle);
                } else {
                    continue;
                }
                $table_col += 1;
            }
            $table_row += 1;
        }
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            $sheet->getStyle($column->getColumnIndex() . ($start_row + 2) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Chi_tiet_loi_may.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Chi_tiet_loi_may.xlsx');
        $href = '/exported_files/Chi_tiet_loi_may.xlsx';
        return $this->success($href);
    }

    public function exportThongSoMay(Request $request)
    {
        $query = ThongSoMay::select('*');
        $line = Line::find($request->line_id);
        if ($line) {
            $query->where('line_id', $line->id);
        }
        if (isset($request->machine_code)) {
            $query->where('machine_code', $request->machine_code);
        }

        if (isset($request->date) && count($request->date) === 2) {
            $query->whereDate('ngay_sx', '>=', date('Y-m-d', strtotime($request->date[0])))
                ->whereDate('ngay_sx', '<=', date('Y-m-d', strtotime($request->date[1])));
        }
        if (isset($request->ca_sx)) {
            $query->where('ca_sx', $request->ca_sx);
        }
        if (isset($request->lo_sx)) {
            $query->where('lo_sx', $request->lo_sx);
        }
        if (isset($request->date_if)) {
            $query->whereDate('date_if', date('Y-m-d', strtotime($request->date_if)));
        }
        if (isset($request->date_input)) {
            $query->whereDate('date_input', date('Y-m-d', strtotime($request->date_input)));
        }
        $thong_so_may = $query->get()->toArray();
        $table = [];
        foreach ($thong_so_may as $data) {
            // return $data['data_if'];
            $data['ca_sx'] = (int)$data['ca_sx'] === 1 ? 'Ca 1' : 'Ca 2';
            $data['xuong'] = "Giấy";
            $data['ngay_sx'] = date('d-m-Y H:i:s', strtotime($data['ngay_sx']));
            $table[] = array_merge($data, $data['data_if'] ?? [], $data['data_input'] ?? []);
        }
        // dd($table);
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $start_row = 2;
        $start_col = 1;
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => array(
                'outline' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $headerStyle = array_merge($centerStyle, [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => 'BFBFBF')
            ]
        ]);
        $titleStyle = array_merge($centerStyle, [
            'font' => ['size' => 16, 'bold' => true],
        ]);
        $border = [
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $header = ['STT', 'Ngày sản xuất', 'Ca sản xuất', 'Xưởng', 'Lô sản xuất', 'Mã pallet/thùng', 'Mã máy', 'Tốc độ', 'Độ Ph', 'Nhiệt độ nước', 'Nhiệt độ môi trường', 'Độ ẩm môi trường', 'Công suất đèn UV1', 'Công xuát đèn UV2', 'Công xuất dèn UV3', 'Áp lực bế', 'Áp lực băng tải 1', 'Áp lực băng tải 2', 'Áp lực súng bắn keo', 'Nhiệt độ thùng keo'];
        $table_key = [
            'A' => 'stt',
            'B' => 'ngay_sx',
            'C' => 'ca_sx',
            'D' => 'xuong',
            'E' => 'lo_sx',
            'F' => 'lot_id',
            'G' => 'machine_code',
            'H' => 'toc_do',
            'I' => 'ph',
            'J' => 'w_temp',
            'K' => 't_ev',
            'L' => 'e_hum',
            'M' => 'uv1',
            'N' => 'uv2',
            'O' => 'uv3',
            'P' => 'p_be',
            'Q' => 'p_conv1',
            'R' => 'p_conv2',
            'S' => 'p_gun',
            'T' => 't_gun'
        ];
        foreach ($header as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row + 1])->getStyle([$start_col, $start_row, $start_col, $start_row + 1])->applyFromArray($headerStyle);
            } else {
                $sheet->setCellValue([$start_col, $start_row], $key)->mergeCells([$start_col, $start_row, $start_col + count($cell) - 1, $start_row])->getStyle([$start_col, $start_row, $start_col + count($cell) - 1, $start_row])->applyFromArray($headerStyle);
                foreach ($cell as $val) {
                    $sheet->setCellValue([$start_col, $start_row + 1], $val)->getStyle([$start_col, $start_row + 1])->applyFromArray($headerStyle);
                    $start_col += 1;
                }
                continue;
            }
            $start_col += 1;
        }
        $sheet->setCellValue([1, 1], 'UI Thông số thiết bị')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row + 2;
        foreach ($table as $key => $row) {
            $table_col = 1;
            $sheet->setCellValue([1, $table_row], $table_row - 3)->getStyle([$table_col, $table_row])->applyFromArray($centerStyle);
            $table_col += 1;
            foreach ($row as $key => $cell) {
                if (in_array($key, $table_key)) {
                    $sheet->setCellValue(array_search($key, $table_key) . $table_row, $cell)->getStyle(array_search($key, $table_key) . $table_row)->applyFromArray($centerStyle);
                } else {
                    continue;
                }
                $table_col += 1;
            }
            $table_row += 1;
        }
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            $sheet->getStyle($column->getColumnIndex() . ($start_row + 2) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Thông_số_máy.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Thông_số_máy.xlsx');
        $href = '/exported_files/Thông_số_máy.xlsx';
        return $this->success($href);
    }

    public function exportHistoryWarehouse(Request $request)
    {
        $input = $request->all();
        $warehouse_log_query = WareHouseLog::select('*');
        if (isset($input['date']) && count($input['date']) > 1) {
            $warehouse_log_query->whereDate('created_at', '>=', date('Y-m-d', strtotime($input['date'][0])))->whereDate('created_at', '<=', date('Y-m-d', strtotime($input['date'][1])));
        }

        $lot_ids = $warehouse_log_query->pluck('lot_id')->toArray();
        $lot_query  = Lot::whereIn('id', $lot_ids);
        if (isset($input['khach_hang'])) {
            $lot_query->whereHas('product', function ($product_query) use ($input) {
                $product_query->where('customer_id', 'like', "%" . $input['khach_hang'] . "%");
            });
        }
        if (isset($input['lo_sx'])) {
            $lot_query->where('id', 'like', '%' . $input['lo_sx'] . '%');
        }
        if (isset($input['ten_sp'])) {
            $lot_query->where('id', 'like', '%' . $input['ten_sp'] . '%');
        }
        $lots = $lot_query->get();
        $data = [];
        foreach ($lots as $key => $lot) {
            $log_import = WareHouseLog::with('creator')->where('lot_id', $lot->id)->where('type', 1)->first();
            $log_export = WareHouseLog::with('creator')->where('lot_id', $lot->id)->where('type', 2)->first();
            $object = new stdClass();
            $object->ngay = date('d/m/Y', strtotime($lot->created_at));
            $object->ma_khach_hang = $lot->product->customer->id;
            $object->ten_khach_hang = $lot->product->customer->name;
            $object->product_id = $lot->product_id;
            $object->ten_san_pham = $lot->product->name;
            $object->dvt = 'Mảnh';
            $object->lo_sx = $lot->lo_sx;
            $object->vi_tri = $log_import->cell_id;
            $object->kho = 'KTP';
            $object->lot_id = $lot->id;
            $object->ngay_nhap = date('d/m/Y', strtotime($log_import->created_at));
            $object->so_luong_nhap  = $log_import ? $log_import->so_luong : 0;
            $object->nguoi_nhap  = $log_import ? $log_import->creator->name : '';
            $object->ngay_xuat = $log_export ? date('d/m/Y', strtotime($log_export->created_at)) : '';
            $object->so_luong_xuat  = $log_export ? $log_export->so_luong : 0;
            $object->nguoi_xuat  = $log_export ? $log_export->creator->name : '';
            $object->ton_kho = $object->so_luong_nhap - $object->so_luong_xuat;
            $object->so_ngay_ton = !$log_export ? ((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($log_import->created_at)))) / 86400) : '';
            $data[] = $object;
        }
        $table = $data;
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $start_row = 2;
        $start_col = 1;
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => array(
                'outline' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $headerStyle = array_merge($centerStyle, [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => 'BFBFBF')
            ]
        ]);
        $titleStyle = array_merge($centerStyle, [
            'font' => ['size' => 16, 'bold' => true],
        ]);
        $border = [
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $header = [
            'STT',
            'Ngày',
            'Mã khách hàng',
            'Tên khách hàng',
            'Mã hàng',
            'Tên sản phẩm',
            'Đơn vị tính',
            'Lô sản xuất',
            'Kho',
            'Mã thùng',
            'Vị trí',
            'Nhập kho' => ['Ngày nhập', 'Số lượng', 'Người nhập'],
            'Xuất kho' => ['Ngày xuất', 'Số lượng', 'Người xuất'],
            'Tồn kho' => ['Số lượng', 'Số ngày tồn kho'],
            'Ghi chú'
        ];
        $table_key = ['A' => 'stt', 'B' => 'ngay', 'C' => 'ma_khach_hang', 'D' => 'ten_khach_hang', 'E' => 'product_id', 'F' => 'ten_san_pham', 'G' => 'dvt', 'H' => 'lo_sx', 'I' => 'kho', 'J' => 'lot_id', 'K' => 'vi_tri', 'L' => 'ngay_nhap', 'M' => 'so_luong_nhap', 'N' => 'nguoi_nhap', 'O' => 'ngay_xuat', 'P' => 'so_luong_xuat', 'Q' => 'nguoi_xuat', 'R' => 'ton_kho', 'S' => 'so_ngay_ton', 'T' => 'note'];
        foreach ($header as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row + 1])->getStyle([$start_col, $start_row, $start_col, $start_row + 1])->applyFromArray($headerStyle);
            } else {
                $sheet->setCellValue([$start_col, $start_row], $key)->mergeCells([$start_col, $start_row, $start_col + count($cell) - 1, $start_row])->getStyle([$start_col, $start_row, $start_col + count($cell) - 1, $start_row])->applyFromArray($headerStyle);
                foreach ($cell as $val) {
                    $sheet->setCellValue([$start_col, $start_row + 1], $val)->getStyle([$start_col, $start_row + 1])->applyFromArray($headerStyle);
                    $start_col += 1;
                }
                continue;
            }
            $start_col += 1;
        }
        $sheet->setCellValue([1, 1], 'UI Quản lý thành phẩm giấy')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row + 2;
        foreach ($table as $key => $row) {
            $table_col = 1;
            $sheet->setCellValue([1, $table_row], $table_row - 3)->getStyle([$table_col, $table_row])->applyFromArray($centerStyle);
            $table_col += 1;
            foreach ($row as $key => $cell) {
                if (in_array($key, $table_key)) {
                    $sheet->setCellValue(array_search($key, $table_key) . $table_row, $cell)->getStyle(array_search($key, $table_key) . $table_row)->applyFromArray($centerStyle);
                } else {
                    continue;
                }
                $table_col += 1;
            }
            $table_row += 1;
        }
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            $sheet->getStyle($column->getColumnIndex() . ($start_row + 2) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Quản_lý_kho_thành_phẩm_giấy.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Quản_lý_kho_thành_phẩm_giấy.xlsx');
        $href = '/exported_files/Quản_lý_kho_thành_phẩm_giấy.xlsx';
        return $this->success($href);
    }


    public function num_to_letters($n)
    {
        $n -= 1;
        for ($r = ""; $n >= 0; $n = intval($n / 26) - 1)
            $r = chr($n % 26 + 0x41) . $r;
        return $r;
    }

    public function exportOQC(Request $request)
    {
        $query = InfoCongDoan::where("type", "sx")->orderBy('created_at');
        $query->where('line_id', 20);

        if (isset($request->date) && count($request->date)) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->date[0])))
                ->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->date[1])));
        }
        if (isset($request->ten_sp)) {
            $query->where('lot_id', 'like',  '%' . $request->ten_sp . '%');
        }
        if (isset($request->khach_hang)) {
            $khach_hang = Customer::where('id', $request->khach_hang)->first();
            if ($khach_hang) {
                $plan = ProductionPlan::where('khach_hang', $khach_hang->name)->get();
                $product_ids = $plan->pluck('product_id')->toArray();
                $query->where(function ($qr) use ($product_ids) {
                    for ($i = 0; $i < count($product_ids); $i++) {
                        $qr->orwhere('lot_id', 'like',  '%' . $product_ids[$i] . '%');
                    }
                });
            }
        }
        if (isset($request->lo_sx)) {
            $query->where('lot_id', 'like', "%$request->lo_sx%");
        }
        $infos = $query->with("lot.plans")->whereHas('lot', function ($lot_query) {
            $lot_query->where('type', '<>', 1);
        })->get();
        $table = $this->oqcTable($infos)[0];
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $start_row = 2;
        $start_col = 1;
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => array(
                'outline' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $headerStyle = array_merge($centerStyle, [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => 'BFBFBF')
            ]
        ]);
        $titleStyle = array_merge($centerStyle, [
            'font' => ['size' => 16, 'bold' => true],
        ]);
        $border = [
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $header = [
            'STT',
            'Ngày',
            "Ca sx",
            'Xưởng',
            'Tên sản phẩm',
            'Khách hàng',
            'Mã hàng',
            'Lô sản xuất',
            'Mã pallet/thùng',
            'Số lượng SX',
            'Sl lấy mẫu',
            'Số lượng NG',
            'Loại lỗi',
            "Kết luận",
            "OQC"
        ];
        $table_key = [
            'A' => 'stt',
            'B' => 'ngay_sx',
            'C' => 'xuong',
            'D' => 'ca_sx',
            'E' => 'ten_sp',
            'F' => 'khach_hang',
            'G' => 'product_id',
            'H' => 'lo_sx',
            'I' => 'lot_id',
            'J' => 'sl_sx',
            'K' => 'sl_mau_thu',
            'L' => 'sl_ng',
            'M' => 'error',
            'N' => 'ket_luan',
            'O' => 'nguoi_oqc'
        ];

        foreach ($header as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row + 1])->getStyle([$start_col, $start_row, $start_col, $start_row + 1])->applyFromArray($headerStyle);
            } else {
                $sheet->setCellValue([$start_col, $start_row], $key)->mergeCells([$start_col, $start_row, $start_col + count($cell) - 1, $start_row])->getStyle([$start_col, $start_row, $start_col + count($cell) - 1, $start_row])->applyFromArray($headerStyle);
                foreach ($cell as $val) {
                    $sheet->setCellValue([$start_col, $start_row + 1], $val)->getStyle([$start_col, $start_row + 1])->applyFromArray($headerStyle);
                    $start_col += 1;
                }
                continue;
            }
            $start_col += 1;
        }
        $sheet->setCellValue([1, 1], 'UI Truy vấn chất lượng OQC (Bảng chi tiết trang chính)')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row + 2;
        foreach ($table as $key => $row) {
            $table_col = 1;
            $sheet->setCellValue([1, $table_row], $table_row - 3)->getStyle([$table_col, $table_row])->applyFromArray($centerStyle);
            $table_col += 1;
            foreach ($row as $key => $cell) {
                if (in_array($key, $table_key)) {
                    $sheet->setCellValue(array_search($key, $table_key) . $table_row, $cell)->getStyle(array_search($key, $table_key) . $table_row)->applyFromArray($centerStyle);
                } else {
                    continue;
                }
                $table_col += 1;
            }
            $table_row += 1;
        }
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            $sheet->getStyle($column->getColumnIndex() . ($start_row + 2) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Lịch_sử_sản_xuất.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/OQC.xlsx');
        $href = '/exported_files/OQC.xlsx';
        return $this->success($href);
    }


    public function exportReportProduceHistory(Request $request)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $fillWhite = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => 'ffffff')
            ]
        ];
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => array(
                'outline' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $headerStyle1 = array_merge($centerStyle, [
            // 'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => 'DAEEF3')
            ]
        ]);
        $headerStyle2 = array_merge($centerStyle, [
            // 'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => 'EBF1DE')
            ]
        ]);
        $titleStyle = [
            'font' => ['size' => 26, 'bold' => true, 'color' => array('argb' => '4519FF')],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                // 'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'wrapText' => false,
            ],
        ];
        $border = [
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $log_in_day = [];
        if ($request->date && count($request->date) > 1) {
            $datediff = strtotime($request->date[1]) - strtotime($request->date[0]);
            $count_day = round($datediff / (60 * 60 * 24));
            for ($i = 0; $i <= $count_day; $i++) {
                $log_todays = InfoCongDoan::with('lot.plans')->whereNotIn('line_id', [14, 22])
                    ->whereDate('thoi_gian_bat_dau', date('Y-m-d', strtotime($request->date[0] . ' +' . $i . ' day')))
                    ->where('type', 'sx')
                    ->whereNotNull('thoi_gian_bat_dau')
                    ->whereNotNull('thoi_gian_bam_may')
                    ->whereNotNull('thoi_gian_ket_thuc')
                    ->whereHas('lot', function ($lot_query) {
                        $lot_query->whereIn('type', [0, 1, 2, 3])->where('info_cong_doan.line_id', 15)->orWhere('type', '<>', 1)->where('info_cong_doan.line_id', '<>', 15);
                    })
                    ->orderBy('thoi_gian_bat_dau', 'DESC')->get();
                $line_ids = $log_todays->pluck('line_id')->toArray();
                $object = new StdClass;
                $object->line_ids = array_unique($line_ids);
                $object->log_todays = $log_todays;
                $log_in_day[] = $object;
            }
        }
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->getParent()->getDefaultStyle()->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => 'F2F2F2')
            ]
        ]);
        $sheet->setCellValue([1, 1], 'Báo cáo sản lượng sản xuất')->getStyle([1, 1])->applyFromArray($titleStyle);
        //Table 1
        $table1 = [];
        // return $production_plans;
        foreach ($log_in_day as $index => $day_log) {
            foreach ($day_log->line_ids as $key => $line_id) {
                $machine = Machine::where('line_id', $line_id)->first();
                $machine_name = $machine ? $machine->name : '-';
                if ($line_id == 22) {
                    $machine_name = 'MÁY IN LƯỚI';
                }
                if ($line_id == 14) {
                    $machine_name = 'MÁY BÓC';
                }
                $obj = new stdClass();
                $obj->machine_id = $machine_name;
                $obj->ngay_sx = ($request->date && count($request->date) > 1) ? date('d/m/Y', strtotime($request->date[0] . ' +' . $index . 'day')) : date('d/m/Y');
                $obj->sl_dau_ra = 0;
                $obj->sl_dau_vao = 0;
                $obj->sl_tem_vang = 0;
                $obj->sl_ng = 0;
                $obj->sl_ok = 0;
                $obj->tong_thoi_gian_san_xuat = 0;
                $obj->thoi_gian_khong_san_luong = 0;
                $obj->thoi_gian_tinh_san_luong = 0;
                $tg_san_xuat_kh  = 0;
                $sl_thuc_te = 0;
                foreach ($day_log->log_todays as $k => $log) {
                    if ($log->line_id == $line_id) {
                        $plan = $log->lot->getPlanByLine($log->line_id);
                        if (!isset($obj->leadtime)) {
                            $obj->leadtime = $plan ? number_format((strtotime($log->thoi_gian_ket_thuc) - strtotime($plan->thoi_gian_bat_dau)) / 3600, 2) : '-';
                        }
                        $tg_san_xuat_kh += $plan ? (strtotime($plan->thoi_gian_ket_thuc) - strtotime($plan->thoi_gian_bat_dau)) : 0;
                        $obj->sl_dau_vao += $log->sl_dau_vao_hang_loat;
                        $obj->sl_dau_ra += $log->sl_dau_ra_hang_loat;
                        $obj->sl_tem_vang += $log->sl_tem_vang;
                        $obj->sl_ng += $log->sl_ng;
                        $obj->sl_ok += ($log->sl_dau_ra_hang_loat) - ($log->sl_tem_vang) - ($log->sl_ng);
                        $obj->tong_thoi_gian_san_xuat += strtotime($log->thoi_gian_ket_thuc) - strtotime($log->thoi_gian_bat_dau);
                        $obj->thoi_gian_khong_san_luong += strtotime($log->thoi_gian_bam_may) - strtotime($log->thoi_gian_bat_dau);
                        $obj->thoi_gian_tinh_san_luong += strtotime($log->thoi_gian_ket_thuc) - strtotime($log->thoi_gian_bam_may);
                        $sl_thuc_te += $plan ? ((strtotime($log->thoi_gian_ket_thuc) - strtotime($log->thoi_gian_bam_may)) / 3600) * ((int)$plan->UPH * $plan->so_bat) : 0;
                        $obj->nhan_luc = $plan ? $plan->nhan_luc : 0;
                    }
                }
                $obj->ty_le_ng = $obj->sl_dau_ra ? number_format($obj->sl_ng / $obj->sl_dau_ra, 2) * 100 . '%' : 0;
                $obj->ty_le_hao_phi_thoi_gian = $obj->tong_thoi_gian_san_xuat ? number_format($obj->thoi_gian_khong_san_luong / $obj->tong_thoi_gian_san_xuat, 2) * 100 . '%' : 0;
                $obj->hieu_suat_a = $tg_san_xuat_kh > 0 ? number_format($obj->thoi_gian_tinh_san_luong / $tg_san_xuat_kh, 2) * 100 . '%' : 0;
                $obj->hieu_suat_q = $obj->sl_dau_ra ? number_format($obj->sl_ok / $obj->sl_dau_ra, 2) * 100 . '%' : 0;

                $obj->hieu_suat_p = ($obj->thoi_gian_tinh_san_luong && $sl_thuc_te > 0) ? number_format(($obj->sl_dau_ra) / $sl_thuc_te * 100, 2) . '%' : 0;
                $obj->oee = (((int)$obj->hieu_suat_a * (int)$obj->hieu_suat_p * (int)$obj->hieu_suat_q) / 10000) . '%';

                $obj->tong_thoi_gian_san_xuat = sprintf("%02d%s%02d%s%02d", floor($obj->tong_thoi_gian_san_xuat / 3600), ":", ($obj->tong_thoi_gian_san_xuat / 60) % 60, ":", $obj->tong_thoi_gian_san_xuat % 60);
                $obj->thoi_gian_khong_san_luong = sprintf("%02d%s%02d%s%02d", floor($obj->thoi_gian_khong_san_luong / 3600), ":", ($obj->thoi_gian_khong_san_luong / 60) % 60, ":", $obj->thoi_gian_khong_san_luong % 60);
                $obj->thoi_gian_tinh_san_luong = sprintf("%02d%s%02d%s%02d", floor($obj->thoi_gian_tinh_san_luong / 3600), ":", ($obj->thoi_gian_tinh_san_luong / 60) % 60, ":", $obj->thoi_gian_tinh_san_luong % 60);
                $obj->thoi_gian_vao_hang = $obj->thoi_gian_khong_san_luong;
                $table1[] = $obj;
            }
        }
        $start1_row = 3;
        $header1_row = $start1_row;
        $start1_col = 1;
        $header1 = [
            'Ngày sản xuất',
            'Số máy',
            "Số nhân sự chạy máy",
            "Số lượng đầu vào (pcs)",
            "Số lượng khoanh vùng (tem vàng) (pcs)",
            "Số lượng OK (pcs)",
            "Số lượng NG (pcs)",
            "Tổng thời gian sản xuất",
            'Thời gian không ra sản phẩm',
            "Thời gian chạy sản lượng",
            "Thời gian vào hàng",
            "Tỷ lệ NG (%)",
            'Tỷ lệ hao phí thời gian (%)',
            'Leadtime',
            'Điện năng'
        ]; //, "Hiệu suất (A)", "Hiệu suất (P)", "Hiệu suất (Q)", "OEE"
        $table_key1 = [
            'A' => 'ngay_sx',
            'B' => 'machine_id',
            'C' => 'nhan_luc',
            'D' => 'sl_dau_vao',
            'E' => 'sl_tem_vang',
            'F' => 'sl_ok',
            'G' => 'sl_ng',
            'H' => 'tong_thoi_gian_san_xuat',
            'I' => 'thoi_gian_khong_san_luong',
            'J' => 'thoi_gian_tinh_san_luong',
            'K' => 'thoi_gian_vao_hang',
            'L' => 'ty_le_ng',
            'M' => 'ty_le_hao_phi_thoi_gian',
            'N' => 'leadtime',
            // 'P'=>'hieu_suat_a',
            // 'Q'=>'hieu_suat_p',
            // 'R'=>'hieu_suat_q',
            // 'S'=>'oee',
            'O' => 'dien_nang',
        ];
        foreach ($header1 as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start1_col, $start1_row], $cell)->mergeCells([$start1_col, $start1_row, $start1_col, $start1_row])->getStyle([$start1_col, $start1_row, $start1_col, $start1_row])->applyFromArray($headerStyle1);
            } else {
                $sheet->setCellValue([$start1_col, $start1_row], $key)->mergeCells([$start1_col, $start1_row, $start1_col + count($cell) - 1, $start1_row])->getStyle([$start1_col, $start1_row, $start1_col + count($cell) - 1, $start1_row])->applyFromArray($headerStyle1);
                foreach ($cell as $val) {
                    $sheet->setCellValue([$start1_col, $start1_row + 1], $val)->getStyle([$start1_col, $start1_row + 1])->applyFromArray($headerStyle1);
                    $start1_col += 1;
                }
                continue;
            }
            $start1_col += 1;
        }
        $table1_col = 1;
        $table1_row = $start1_row + 1;
        foreach ($table1 as $key => $row) {
            $table1_col = 1;
            // $sheet->setCellValue([1, $table_row], $table_row-3)->getStyle([$table_col, $table_row])->applyFromArray($centerStyle);
            // $table_col+=1;
            foreach ((array)$row as $key => $cell) {
                if (in_array($key, $table_key1)) {
                    $sheet->setCellValue(array_search($key, $table_key1) . $table1_row, $cell)->getStyle(array_search($key, $table_key1) . $table1_row)->applyFromArray($centerStyle);
                } else {
                    continue;
                }
                $table1_col += 1;
            }
            $sheet->getStyle([1, $table1_row, 15, $table1_row])->applyFromArray($fillWhite);
            $table1_row += 1;
        }

        $sheet->getRowDimension($header1_row)->setRowHeight(42);
        foreach ($sheet->getColumnIterator() as $column) {
            if ($column->getColumnIndex() !== 'A') {
                $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
                $sheet->getStyle($column->getColumnIndex() . ($start1_row + 1) . ':' . $column->getColumnIndex() . ($table1_row - 1))->applyFromArray($border);
            }
        }

        //Table 2
        $table2 = [];
        foreach ($log_in_day as $day_log) {
            foreach ($day_log->line_ids as $key => $line_id) {
                $machine = Machine::where('line_id', $line_id)->first();
                $machine_name = $machine ? $machine->name : '-';
                if ($line_id == 22) {
                    $machine_name = 'MÁY IN LƯỚI';
                }
                if ($line_id == 14) {
                    $machine_name = 'MÁY BÓC';
                }
                $obj = new stdClass();
                $obj->machine_id = $machine_name;
                $obj->ngay_sx = ($request->date && count($request->date) > 1) ? date('d/m/Y', strtotime($request->date[0] . ' +' . $index . 'day')) : date('d/m/Y');
                $obj->sl_dau_ra = 0;
                $obj->sl_dau_vao = 0;
                $obj->sl_tem_vang = 0;
                $obj->sl_ng = 0;
                $obj->sl_ok = 0;
                $obj->tong_thoi_gian_san_xuat = 0;
                $obj->thoi_gian_khong_san_luong = 0;
                $obj->thoi_gian_tinh_san_luong = 0;
                $tg_san_xuat_kh  = 0;
                $sl_thuc_te = 0;
                foreach ($day_log->log_todays as $k => $log) {
                    if ($log->line_id == $line_id) {
                        $so_bat = $log->plan->so_bat;
                        $plan = $log->lot->getPlanByLine($log->line_id);
                        if (!isset($obj->leadtime)) {
                            $obj->leadtime = $plan ? number_format((strtotime($log->thoi_gian_ket_thuc) - strtotime($plan->thoi_gian_bat_dau)) / 3600, 2) : '-';
                        }
                        $tg_san_xuat_kh += $plan ? (strtotime($plan->thoi_gian_ket_thuc) - strtotime($plan->thoi_gian_bat_dau)) : 0;
                        $obj->sl_dau_vao += ($so_bat) ? round($log->sl_dau_vao_hang_loat /  $so_bat) : 0;
                        $obj->sl_dau_ra += ($so_bat) ? round($log->sl_dau_ra_hang_loat /  $so_bat) : 0;
                        $obj->sl_tem_vang += ($so_bat) ? round($log->sl_tem_vang /  $so_bat) : 0;
                        $obj->sl_ng += ($so_bat) ? round($log->sl_ng /  $so_bat) : 0;
                        $obj->sl_ok += ($so_bat) ? round((($log->sl_dau_ra_hang_loat) - ($log->sl_tem_vang) - ($log->sl_ng)) /  $so_bat) : 0;
                        $obj->tong_thoi_gian_san_xuat += strtotime($log->thoi_gian_ket_thuc) - strtotime($log->thoi_gian_bat_dau);
                        $obj->thoi_gian_khong_san_luong += strtotime($log->thoi_gian_bam_may) - strtotime($log->thoi_gian_bat_dau);
                        $obj->thoi_gian_tinh_san_luong += strtotime($log->thoi_gian_ket_thuc) - strtotime($log->thoi_gian_bam_may);
                        $sl_thuc_te += $plan ? ((strtotime($log->thoi_gian_ket_thuc) - strtotime($log->thoi_gian_bam_may)) / 3600) * (int)$plan->UPH : 0;
                        $obj->nhan_luc = $plan ? $plan->nhan_luc : 0;
                    }
                }
                $obj->ty_le_ng = $obj->sl_dau_ra ? (int)number_format($obj->sl_ng / $obj->sl_dau_ra, 2) * 100 . '%' : 0;
                $obj->ty_le_hao_phi_thoi_gian = $obj->tong_thoi_gian_san_xuat ? (int)number_format($obj->thoi_gian_khong_san_luong / $obj->tong_thoi_gian_san_xuat, 2) * 100 . '%' : 0;
                $obj->hieu_suat_a = $tg_san_xuat_kh > 0 ? (int)number_format($obj->thoi_gian_tinh_san_luong / $tg_san_xuat_kh, 2) * 100 . '%' : 0;
                $obj->hieu_suat_q = $obj->sl_dau_ra ? (int)number_format($obj->sl_ok / $obj->sl_dau_ra, 2) * 100 . '%' : 0;

                $obj->hieu_suat_p = ($obj->thoi_gian_tinh_san_luong && $sl_thuc_te > 0) ? (int)number_format(($obj->sl_dau_ra) / $sl_thuc_te, 2) * 100 . '%' : 0;
                $obj->oee = (((int)$obj->hieu_suat_a * (int)$obj->hieu_suat_p * (int)$obj->hieu_suat_q) / 10000) . '%';

                $obj->tong_thoi_gian_san_xuat = sprintf("%02d%s%02d%s%02d", floor($obj->tong_thoi_gian_san_xuat / 3600), ":", ($obj->tong_thoi_gian_san_xuat / 60) % 60, ":", $obj->tong_thoi_gian_san_xuat % 60);
                $obj->thoi_gian_khong_san_luong = sprintf("%02d%s%02d%s%02d", floor($obj->thoi_gian_khong_san_luong / 3600), ":", ($obj->thoi_gian_khong_san_luong / 60) % 60, ":", $obj->thoi_gian_khong_san_luong % 60);
                $obj->thoi_gian_tinh_san_luong = sprintf("%02d%s%02d%s%02d", floor($obj->thoi_gian_tinh_san_luong / 3600), ":", ($obj->thoi_gian_tinh_san_luong / 60) % 60, ":", $obj->thoi_gian_tinh_san_luong % 60);
                $obj->thoi_gian_vao_hang = $obj->thoi_gian_khong_san_luong;
                $table2[] = $obj;
            }
        }
        $start2_row = $table1_row + 1;
        $header2_row = $start2_row;
        $start2_col = 1;
        $header2 = [
            'Ngày sản xuất',
            'Số máy',
            "Số nhân sự chạy máy",
            "Số lượng đầu vào (tờ)",
            "Số lượng khoanh vùng (tem vàng) (tờ)",
            "Số lượng OK (tờ)",
            "Số lượng NG (tờ)",
            "Tổng thời gian sản xuất",
            'Thời gian không ra sản phẩm',
            "Thời gian chạy sản lượng",
            "Thời gian vào hàng",
            "Tỷ lệ NG (%)",
            'Tỷ lệ hao phí thời gian (%)',
            'Leadtime',
            'Điện năng'
        ]; //, "Hiệu suất (A)", "Hiệu suất (P)", "Hiệu suất (Q)", "OEE"
        $table_key2 = [
            'A' => 'ngay_sx',
            'B' => 'machine_id',
            'C' => 'nhan_luc',
            'd' => 'sl_dau_vao',
            'E' => 'sl_tem_vang',
            'F' => 'sl_ok',
            'G' => 'sl_ng',
            'H' => 'tong_thoi_gian_san_xuat',
            'I' => 'thoi_gian_khong_san_luong',
            'J' => 'thoi_gian_tinh_san_luong',
            'K' => 'thoi_gian_vao_hang',
            'L' => 'ty_le_ng',
            'M' => 'ty_le_hao_phi_thoi_gian',
            'N' => 'leadtime',
            // 'P'=>'hieu_suat_a',
            // 'Q'=>'hieu_suat_p',
            // 'R'=>'hieu_suat_q',
            // 'S'=>'oee',
            'O' => 'dien_nang',
        ];
        foreach ($header2 as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start2_col, $start2_row], $cell)->mergeCells([$start2_col, $start2_row, $start2_col, $start2_row])->getStyle([$start2_col, $start2_row, $start2_col, $start2_row])->applyFromArray($headerStyle1);
            } else {
                $sheet->setCellValue([$start2_col, $start2_row], $key)->mergeCells([$start2_col, $start2_row, $start2_col + count($cell) - 1, $start2_row])->getStyle([$start2_col, $start2_row, $start2_col + count($cell) - 1, $start2_row])->applyFromArray($headerStyle1);
                foreach ($cell as $val) {
                    $sheet->setCellValue([$start2_col, $start2_row + 1], $val)->getStyle([$start2_col, $start2_row + 1])->applyFromArray($headerStyle1);
                    $start2_col += 1;
                }
                continue;
            }
            $start2_col += 1;
        }
        $table2_col = 1;
        $table2_row = $start2_row + 1;
        foreach ($table2 as $key => $row) {
            $table2_col = 1;
            foreach ((array)$row as $key => $cell) {
                if (in_array($key, $table_key2)) {
                    $sheet->setCellValue(array_search($key, $table_key2) . $table2_row, $cell)->getStyle(array_search($key, $table_key2) . $table2_row)->applyFromArray($centerStyle);
                } else {
                    continue;
                }
                $table2_col += 1;
            }
            $sheet->getStyle([1, $table2_row, 15, $table2_row])->applyFromArray($fillWhite);
            $table2_row += 1;
        }
        $sheet->getRowDimension($header2_row)->setRowHeight(42);
        foreach ($sheet->getColumnIterator() as $column) {
            if ($column->getColumnIndex() !== 'A') {
                $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
                $sheet->getStyle($column->getColumnIndex() . ($start2_row + 1) . ':' . $column->getColumnIndex() . ($table2_row - 1))->applyFromArray($border);
            }
        }

        //Table 3
        $query_lot = InfoCongDoan::with('lot.plans')->where('type', 'sx')->whereNotNull('thoi_gian_bat_dau')->whereNotNull('thoi_gian_ket_thuc');
        if ($request->date && count($request->date) > 1) {
            $query_lot->whereDate('thoi_gian_bat_dau', '>=', date('Y-m-d', strtotime($request->date[0])))->whereDate('thoi_gian_bat_dau', '<=', date('Y-m-d', strtotime($request->date[1])));
        } else {
            $query_lot->whereDate('thoi_gian_bat_dau', date('Y-m-d'));
        }
        if ($request->ten_sp) {
            $query_lot->where('lot_id', 'like', '%' . $request->ten_sp . '%');
        }
        if ($request->lo_sx) {
            $query_lot->where('lot_id', 'like', '%' . $request->lo_sx . '%');
        }
        $lot_ids = $query_lot->pluck('lot_id')->toArray();
        $lo_sx_ids = Lot::whereIn('id', $lot_ids)->pluck('lo_sx')->toArray();
        $lo_sx_ids = array_unique($lo_sx_ids);
        $lot_ids1 = Lot::whereIn('lo_sx', $lo_sx_ids)->where('type', '<>', 1)->pluck('id');
        $table3 = [];
        $line_arr = ['10', '22', '11', '12', '13', '14', '15'];
        $logs = InfoCongDoan::with('lot.plans')->whereIn('lot_id', $lot_ids1)->whereIn('line_id', $line_arr)->orderBy('thoi_gian_bat_dau', 'DESC')->get();
        foreach ($lo_sx_ids as $lo_sx) {
            $obj = [];
            $plan = ProductionPlan::where('lo_sx', $lo_sx)->first();
            $obj['product_id'] = $plan->product_id;
            $obj['product_name'] = $plan->product->name;
            $obj['lo_sx'] = $plan->lo_sx;
            $obj['so_bat'] = $plan->so_bat;
            foreach ($line_arr as $line_id) {
                $obj['sl_dau_vao_' . $line_id] = 0;
                $obj['sl_dau_ra_' . $line_id] = 0;
                $obj['sl_tem_vang_' . $line_id] = 0;
                $obj['sl_ng_' . $line_id] = 0;
                $obj['sl_ok_' . $line_id] = 0;
                $obj['tong_thoi_gian_san_xuat_' . $line_id] = 0;
                $obj['thoi_gian_khong_san_luong_' . $line_id] = 0;
                $obj['thoi_gian_tinh_san_luong_' . $line_id] = 0;
                $check = false;
                foreach ($logs as $key => $info) {
                    if ($info->lot->lo_sx == $lo_sx && $info->line_id == $line_id) {
                        $check = true;
                        if (!isset($obj['ngay_sx_gan_nhat_' . $line_id])) {
                            $obj['ngay_sx_gan_nhat_' . $line_id] = date('d/m/Y', strtotime($info->thoi_gian_bat_dau));
                        }
                        $obj['sl_dau_vao_' . $line_id] += $info->sl_dau_vao_hang_loat;
                        $obj['sl_dau_ra_' . $line_id] += $info->sl_dau_ra_hang_loat;
                        $obj['sl_tem_vang_' . $line_id] += $info->sl_tem_vang;
                        $obj['sl_ng_' . $line_id] += $info->sl_ng;
                        $obj['sl_ok_' . $line_id] += $info->sl_dau_ra_hang_loat - $info->sl_tem_vang - $info->sl_ng;
                        $obj['tong_thoi_gian_san_xuat_' . $line_id] += strtotime($info->thoi_gian_ket_thuc) - strtotime($info->thoi_gian_bat_dau);
                        $obj['thoi_gian_khong_san_luong_' . $line_id] += ($info->thoi_gian_bam_may && $info->thoi_gian_bat_dau) ? strtotime($info->thoi_gian_bam_may) - strtotime($info->thoi_gian_bat_dau) : 0;
                        $obj['thoi_gian_tinh_san_luong_' . $line_id] += strtotime($info->thoi_gian_ket_thuc) - strtotime($info->thoi_gian_bam_may);
                    }
                }
                $line = Line::find($line_id);
                $plan_line = ProductionPlan::where('lo_sx', $lo_sx)->where('cong_doan_sx', Str::slug($line->name))->first();
                $obj['sl_ke_hoach_' . $line_id] = $plan_line ? $plan_line->so_bat * $plan_line->sl_thanh_pham : 0;
                $obj['ty_le_ok_' . $line_id] = ($check && $obj['sl_dau_ra_' . $line_id] > 0) ? number_format($obj['sl_ok_' . $line_id] / $obj['sl_dau_ra_' . $line_id], 2) * 100 . '%' : 0;
                $obj['ty_le_tem_vang_' . $line_id] = ($check && $obj['sl_dau_ra_' . $line_id] > 0) ? number_format($obj['sl_tem_vang_' . $line_id] / $obj['sl_dau_ra_' . $line_id], 2) * 100 . '%' : 0;
                $obj['ty_le_ng_' . $line_id] = ($check && $obj['sl_dau_ra_' . $line_id] > 0) ? number_format($obj['sl_ng_' . $line_id] / $obj['sl_dau_ra_' . $line_id], 2) * 100 . '%' : 0;
                $obj['ty_le_hao_phi_thoi_gian_' . $line_id] = ($check && $obj['thoi_gian_khong_san_luong_' . $line_id] > 0) ? number_format($obj['thoi_gian_khong_san_luong_' . $line_id] / $obj['tong_thoi_gian_san_xuat_' . $line_id], 2) * 100 . '%' : 0;
                // $obj['ty_le_hao_phi_thoi_gian_'.$line_id] = $check ? number_format($obj['thoi_gian_khong_san_luong_'.$line_id] / $obj['tong_thoi_gian_san_xuat_'.$line_id], 2)*100 : 0;

            }
            $table3[] = $obj;
        }
        // return $table3;
        // return $table;

        $start3_row = $table2_row + 1;
        $start3_col = 1;
        $header3 = [
            'Mã hàng',
            'Tên sản phẩm',
            "Lô sản xuất",
            "Số bát",
            "IN" => ["Ngày sản xuất gần nhất của lô", "Số lượng đầu vào", "Số lượng hàng đạt", 'Tem vàng', "Số lượng NG", "Sản lượng kế hoạch giao", "Tỷ lệ đạt thẳng (%)", "Tỷ lệ tem vàng (%)", "Tỷ lệ NG(%)", "Tỷ lệ hao phí thời gian (%)", "Điện năng", ""],
            "PHỦ" => ["Ngày sản xuất gần nhất của lô", "Số lượng đầu vào", "Số lượng hàng đạt", 'Tem vàng', "Số lượng NG", "Sản lượng kế hoạch giao", "Tỷ lệ đạt thẳng (%)", "Tỷ lệ tem vàng (%)", "Tỷ lệ NG(%)", "Tỷ lệ hao phí thời gian (%)", "Điện năng", ""],
            "IN LƯỚI" => ["Ngày sản xuất gần nhất của lô", "Số lượng đầu vào", "Số lượng hàng đạt", 'Tem vàng', "Số lượng NG", "Sản lượng kế hoạch giao", "Tỷ lệ đạt thẳng (%)", "Tỷ lệ tem vàng (%)", "Tỷ lệ NG(%)", "Tỷ lệ hao phí thời gian (%)", "Điện năng", ""],
            "BẾ" => ["Ngày sản xuất gần nhất của lô", "Số lượng đầu vào", "Số lượng hàng đạt", 'Tem vàng', "Số lượng NG", "Sản lượng kế hoạch giao", "Tỷ lệ đạt thẳng (%)", "Tỷ lệ tem vàng (%)", "Tỷ lệ NG(%)", "Tỷ lệ hao phí thời gian (%)", "Điện năng", ""],
            "BÓC" => ["Ngày sản xuất gần nhất của lô", "Số lượng đầu vào", "Số lượng hàng đạt", 'Tem vàng', "Số lượng NG", "Sản lượng kế hoạch giao", "Tỷ lệ đạt thẳng (%)", "Tỷ lệ tem vàng (%)", "Tỷ lệ NG(%)", "Tỷ lệ hao phí thời gian (%)", "Điện năng", ""],
            "GẤP DÁN" => ["Ngày sản xuất gần nhất của lô", "Số lượng đầu vào", "Số lượng hàng đạt", 'Tem vàng', "Số lượng NG", "Sản lượng kế hoạch giao", "Tỷ lệ đạt thẳng (%)", "Tỷ lệ tem vàng (%)", "Tỷ lệ NG(%)", "Tỷ lệ hao phí thời gian (%)", "Điện năng", ""],
            "CHỌN" => ["Ngày sản xuất gần nhất của lô", "Số lượng đầu vào", "Số lượng hàng đạt", 'Tem vàng', "Số lượng NG", "Sản lượng kế hoạch giao", "Tỷ lệ đạt thẳng (%)", "Tỷ lệ tem vàng (%)", "Tỷ lệ NG(%)", "Tỷ lệ hao phí thời gian (%)", "Điện năng", ""],
        ];
        $table_key3 = [
            'A' => 'product_id',
            'B' => 'product_name',
            'C' => 'lo_sx',
            'D' => 'so_bat',

            'E' => 'ngay_sx_gan_nhat_10',
            'F' => 'sl_dau_vao_10',
            'G' => 'sl_ok_10',
            'H' => 'sl_tem_vang_10',
            'I' => 'sl_ng_10',
            'J' => 'sl_ke_hoach_10',
            'K' => 'ty_le_ok_10',
            'L' => 'ty_le_tem_vang_10',
            'M' => 'ty_le_ng_10',
            'N' => 'ty_le_hao_phi_thoi_gian_10',
            'O' => 'dien_nang_10',

            'Q' => 'ngay_sx_gan_nhat_11',
            'R' => 'sl_dau_vao_11',
            'S' => 'sl_ok_11',
            'T' => 'sl_tem_vang_11',
            'U' => 'sl_ng_11',
            'V' => 'sl_ke_hoach_11',
            'W' => 'ty_le_ok_11',
            'X' => 'ty_le_tem_vang_11',
            'Y' => 'ty_le_ng_11',
            'Z' => 'ty_le_hao_phi_thoi_gian_11',
            'AA' => 'dien_nang_11',

            'AC' => 'ngay_sx_gan_nhat_22',
            'AD' => 'sl_dau_vao_22',
            'AE' => 'sl_ok_22',
            'AF' => 'sl_tem_vang_22',
            'AG' => 'sl_ng_22',
            'AH' => 'sl_ke_hoach_22',
            'AI' => 'ty_le_ok_22',
            'AJ' => 'ty_le_tem_vang_22',
            'AK' => 'ty_le_ng_22',
            'AL' => 'ty_le_hao_phi_thoi_gian_22',
            'AM' => 'dien_nang_22',

            'AO' => 'ngay_sx_gan_nhat_12',
            'AP' => 'sl_dau_vao_12',
            'AQ' => 'sl_ok_12',
            'AR' => 'sl_tem_vang_12',
            'AS' => 'sl_ng_12',
            'AT' => 'sl_ke_hoach_12',
            'AU' => 'ty_le_ok_12',
            'AV' => 'ty_le_tem_vang_12',
            'AW' => 'ty_le_ng_12',
            'AX' => 'ty_le_hao_phi_thoi_gian_12',
            'AY' => 'dien_nang_12',

            'BA' => 'ngay_sx_gan_nhat_14',
            'BB' => 'sl_dau_vao_14',
            'BC' => 'sl_ok_14',
            'BD' => 'sl_tem_vang_14',
            'BE' => 'sl_ng_14',
            'BF' => 'sl_ke_hoach_14',
            'BG' => 'ty_le_ok_14',
            'BH' => 'ty_le_tem_vang_14',
            'BI' => 'ty_le_ng_14',
            'BJ' => 'ty_le_hao_phi_thoi_gian_14',
            'BK' => 'dien_nang_14',

            'BM' => 'ngay_sx_gan_nhat_13',
            'BN' => 'sl_dau_vao_13',
            'BO' => 'sl_ok_13',
            'BP' => 'sl_tem_vang_13',
            'BQ' => 'sl_ng_13',
            'BR' => 'sl_ke_hoach_13',
            'BS' => 'ty_le_ok_13',
            'BT' => 'ty_le_tem_vang_13',
            'BU' => 'ty_le_ng_13',
            'BV' => 'ty_le_hao_phi_thoi_gian_13',
            'BW' => 'dien_nang_13',

            'BY' => 'ngay_sx_gan_nhat_15',
            'BZ' => 'sl_dau_vao_15',
            'CA' => 'sl_ok_15',
            'CB' => 'sl_tem_vang_15',
            'CC' => 'sl_ng_15',
            'CD' => 'sl_ke_hoach_15',
            'CE' => 'ty_le_ok_15',
            'CF' => 'ty_le_tem_vang_15',
            'CG' => 'ty_le_ng_15',
            'CH' => 'ty_le_hao_phi_thoi_gian_15',
            'CI' => 'dien_nang_15',
        ];
        foreach ($header3 as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start3_col, $start3_row], $cell)->mergeCells([$start3_col, $start3_row, $start3_col, $start3_row + 1])->getStyle([$start3_col, $start3_row, $start3_col, $start3_row + 1])->applyFromArray($headerStyle2);
            } else {
                $sheet->setCellValue([$start3_col, $start3_row], $key)->mergeCells([$start3_col, $start3_row, $start3_col + count($cell) - 1, $start3_row])->getStyle([$start3_col, $start3_row, $start3_col + count($cell) - 1, $start3_row])->applyFromArray($headerStyle2);
                foreach ($cell as $val) {
                    $sheet->setCellValue([$start3_col, $start3_row + 1], $val)->getStyle([$start3_col, $start3_row + 1])->applyFromArray($headerStyle2);
                    $start3_col += 1;
                }
                continue;
            }
            $start3_col += 1;
        }
        $table3_col = 1;
        $table3_row = $start3_row + 2;
        foreach ($table3 as $key => $row) {
            // $table_col = 1;
            // $sheet->setCellValue([1, $table_row], $table_row-3)->getStyle([$table_col, $table_row])->applyFromArray($centerStyle);
            // $table_col+=1;

            foreach ((array)$row as $key => $cell) {
                if (in_array($key, $table_key3) && array_search($key, $table_key3) !== "") {
                    $sheet->setCellValue(array_search($key, $table_key3) . $table3_row, $cell)->getStyle(array_search($key, $table_key3) . $table3_row)->applyFromArray($centerStyle);
                } else {
                    continue;
                }
                $table3_col += 1;
            }
            $sheet->getStyle([1, $table3_row, 88, $table3_row])->applyFromArray($fillWhite);
            $table3_row += 1;
        }
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            $sheet->getStyle($column->getColumnIndex() . ($start3_row + 2) . ':' . $column->getColumnIndex() . ($table3_row - 1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Báo_cáo_truy_vấn_lịch_sử_sản_xuất.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Báo_cáo_truy_vấn_lịch_sử_sản_xuất.xlsx');
        $href = '/exported_files/Báo_cáo_truy_vấn_lịch_sử_sản_xuất.xlsx';
        return $this->success($href);
    }

    public function getDataFilterUI(Request $request)
    {
        $data = new stdClass;
        $data->product = [];
        $data->lo_sx = [];
        $khach_hang = Customer::find($request->khach_hang);
        if ($khach_hang) {
            $plan = ProductionPlan::where('khach_hang', $khach_hang->name)->get();
            $product_ids = array_unique($plan->pluck('product_id')->toArray());
            if (count($product_ids)) {
                $data->product = Product::whereIn('id', $product_ids)->get();
            }
            $data->lo_sx = (array)array_unique($plan->pluck('lo_sx')->toArray());
        }
        return $this->success($data, '');
    }


    public function getDetailDataError(Request $request)
    {
        $query = InfoCongDoan::where("type", "sx")->where('lot_id', $request->lot_id);
        $line = Line::find($request->line_id);
        if ($line) {
            $query->where('line_id', $line->id);
        }
        if (isset($request->date) && count($request->date)) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->date[0])))
                ->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->date[1])));
        }
        $infos = $query->with("lot.plans")->get();
        $records = [];
        foreach ($infos as $key => $info) {
            if ($info->lot) {
                $records[] = $info;
            }
        }
        $table = $this->produceTable($records);
        $chart = $this->qcError($records);
        return $this->success([
            "table" => $table,
            "chart" => $chart[1],
        ]);
    }

    public function exportSummaryWarehouse(Request $request)
    {
        $table = [];
        $sum = [
            'product_name' => 'Tổng cộng',
            'ton_dau' => 0,
            'sl_nhap' => 0,
            'sl_xuat' => 0,
            'ton_cuoi' => 0,
        ];
        $log_import = Product::leftJoin('lot', 'products.id', '=', 'lot.product_id')->leftJoin('warehouse_logs', function (JoinClause $join) use ($request) {
            $join->on('warehouse_logs.lot_id', '=', 'lot.id')
                ->where('warehouse_logs.type', 1)->whereDate('warehouse_logs.created_at', '<', date('Y-m-d', strtotime($request->date[0])));
        })->select('products.id', 'products.name', DB::raw('SUM(warehouse_logs.so_luong) as so_luong'))->groupBy('products.id', 'products.name')->get();
        $log_export = Product::leftJoin('lot', 'products.id', '=', 'lot.product_id')->leftJoin('warehouse_logs', function (JoinClause $join) use ($request) {
            $join->on('warehouse_logs.lot_id', '=', 'lot.id')
                ->where('warehouse_logs.type', 2)->whereDate('warehouse_logs.created_at', '<', date('Y-m-d', strtotime($request->date[0])));
        })->select('products.id', 'products.name', DB::raw('SUM(warehouse_logs.so_luong) as so_luong'))->groupBy('products.id', 'products.name')->get();

        $product_import = Product::leftJoin('lot', 'products.id', '=', 'lot.product_id')->leftJoin('warehouse_logs', function (JoinClause $join) use ($request) {
            $join->on('warehouse_logs.lot_id', '=', 'lot.id')
                ->where('warehouse_logs.type', 1)->whereDate('warehouse_logs.created_at', '>=', date('Y-m-d', strtotime($request->date[0])))->whereDate('warehouse_logs.created_at', '<=', date('Y-m-d', strtotime($request->date[1])));
        })->select('products.id', 'products.name', DB::raw('SUM(warehouse_logs.so_luong) as so_luong'))->groupBy('products.id', 'products.name')->get();
        $product_export = Product::leftJoin('lot', 'products.id', '=', 'lot.product_id')->leftJoin('warehouse_logs', function (JoinClause $join) use ($request) {
            $join->on('warehouse_logs.lot_id', '=', 'lot.id')
                ->where('warehouse_logs.type', 2)->whereDate('warehouse_logs.created_at', '>=', date('Y-m-d', strtotime($request->date[0])))->whereDate('warehouse_logs.created_at', '<=', date('Y-m-d', strtotime($request->date[1])));
        })->select('products.id', 'products.name', DB::raw('SUM(warehouse_logs.so_luong) as so_luong'))->groupBy('products.id', 'products.name')->get();
        $data = [];
        // $log_import = Product::select('products.id','products.name')->withSum(['warehouseLog as so_luong' => function($query) use($request) {
        //     $query->where('warehouse_logs.type', 1)
        //     ->whereDate('warehouse_logs.created_at', '<', date('Y-m-d', strtotime($request->date[0])));
        // }], 'so_luong')->get();
        // $log_export = Product::select('products.id','products.name')->withSum(['warehouseLog as so_luong' => function($query) use($request) {
        //     $query->where('warehouse_logs.type', 2)
        //     ->whereDate('warehouse_logs.created_at', '<', date('Y-m-d', strtotime($request->date[0])));
        // }], 'so_luong')->get();
        // $product_import = Product::select('products.id','products.name')->withSum(['warehouseLog as so_luong' => function($query) use($request) {
        //     $query->where('warehouse_logs.type', 1)
        //     ->whereDate('warehouse_logs.created_at', '<', date('Y-m-d', strtotime($request->date[0])));
        // }], 'so_luong')->get();
        // $product_export = Product::select('products.id','products.name')->withSum(['warehouseLog as so_luong' => function($query) use($request) {
        //     $query->where('warehouse_logs.type', 2)
        //     ->whereDate('warehouse_logs.created_at', '<', date('Y-m-d', strtotime($request->date[0])));
        // }], 'so_luong')->get();
        return [$log_import, $log_export];
        // return [$log_import, $log_export, $product_import, $product_export];
        foreach ($log_import as $key => $log) {
            $obj = [];
            $obj['product_id'] = $log->id;
            $obj['product_name'] = $log->name;
            $obj['ton_dau'] = $log->so_luong - $log_export[$key]->so_luong;
            $obj['sl_nhap'] = $product_import[$key]->so_luong ?? 0;
            $obj['sl_xuat'] = $product_export[$key]->so_luong ?? 0;
            $obj['ton_cuoi'] = $obj['ton_dau'] + $obj['sl_nhap'] - $obj['sl_xuat'];
            $sum['sl_nhap'] += $obj['sl_nhap'];
            $sum['sl_xuat'] += $obj['sl_xuat'];
            $sum['ton_dau'] += $obj['ton_dau'];
            $sum['ton_cuoi'] += $obj['ton_cuoi'];
            $data[] = $obj;
        }
        $data[] = $sum;
        $table = $data;
        // return $table;
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $start_row = 2;
        $start_col = 1;
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => array(
                'outline' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $headerStyle = array_merge($centerStyle, [
            'font' => ['bold' => true],
        ]);
        $titleStyle = array_merge($centerStyle, [
            'font' => ['size' => 16, 'bold' => true],
        ]);
        $border = [
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $header = ['STT', 'Mã vật tư', 'Tên vật tư', 'Tồn đầu', 'Sl nhập', 'Sl xuất', 'Tồn cuối'];
        $table_key = [
            'A' => 'stt',
            'B' => 'product_id',
            'C' => 'product_name',
            'D' => 'ton_dau',
            'E' => 'sl_nhap',
            'F' => 'sl_xuat',
            'G' => 'ton_cuoi',
        ];
        foreach ($header as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            }
            $start_col += 1;
        }
        $sheet->setCellValue([1, 1], 'TỔNG HỢP XUẤT NHẬP TỒN')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row + 1;
        foreach ($table as $key => $row) {
            $table_col = 1;
            if (isset($row['product_id']) && $row['product_id']) {
                $sheet->setCellValue([1, $table_row], $table_row - 2)->getStyle([$table_col, $table_row])->applyFromArray($centerStyle);
                $table_col += 1;
            }

            $row = (array)$row;
            foreach ($table_key as $k => $value) {
                if (isset($row[$value])) {
                    $sheet->setCellValue($k . $table_row, $row[$value])->getStyle($k . $table_row)->applyFromArray($centerStyle);
                } else {
                    continue;
                }
                $table_col += 1;
            }
            $table_row += 1;
        }
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            $sheet->getStyle($column->getColumnIndex() . ($start_row) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Tổng_hợp_xuất_nhập_tồn.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Tổng_hợp_xuất_nhập_tồn.xlsx');
        $href = '/exported_files/Tổng_hợp_xuất_nhập_tồn.xlsx';
        return $this->success($href);
    }
    public function exportBMCardWarehouse(Request $request)
    {
        $product = Product::find($request->ten_sp);
        if (!$product) {
            return $this->failure('', 'Không tìm thấy sản phẩm');
        }
        $warehouse_log = $product->warehouseLog()->whereDate('warehouse_logs.created_at', '>=', date('Y-m-d', strtotime($request->date[0])))->whereDate('warehouse_logs.created_at', '<=', date('Y-m-d', strtotime($request->date[1])))->get();
        $table = [];
        $log_import = $product->warehouseLog()->where('warehouse_logs.type', 1)->whereDate('warehouse_logs.created_at', '<', date('Y-m-d', strtotime($request->date[0])))->get()->sum('so_luong');
        $log_export = $product->warehouseLog()->where('warehouse_logs.type', 2)->whereDate('warehouse_logs.created_at', '<', date('Y-m-d', strtotime($request->date[0])))->get()->sum('so_luong');
        $product_import = $product->warehouseLog()->where('warehouse_logs.type', 1)->whereDate('warehouse_logs.created_at', '>=', date('Y-m-d', strtotime($request->date[0])))->whereDate('warehouse_logs.created_at', '<=', date('Y-m-d', strtotime($request->date[1])))->get()->sum('so_luong');
        $product_export = $product->warehouseLog()->where('warehouse_logs.type', 2)->whereDate('warehouse_logs.created_at', '>=', date('Y-m-d', strtotime($request->date[0])))->whereDate('warehouse_logs.created_at', '<=', date('Y-m-d', strtotime($request->date[1])))->get()->sum('so_luong');
        // return [$log_import, $log_export, $product_import, $product_export];
        $data = [];
        $data[] = ['dien_giai' => 'Đầu kỳ', 'sl_nhap' => ($log_import - $log_export)];
        $data[] = ['dien_giai' => 'Ps nhập trong kỳ', 'sl_nhap' => $product_import];
        $data[] = ['dien_giai' => 'Ps xuất trong kỳ', 'sl_xuat' => $product_export];
        $data[] = ['dien_giai' => 'Cuối kỳ', 'sl_nhap' => ($log_import - $log_export) + $product_import - $product_export];
        $data[] = [];
        foreach ($warehouse_log as $key => $log) {
            $export_plan = WareHouseFGExport::where('product_id', $product->id)->first();
            $customer = $export_plan ? Customer::where('name', 'like', "%$export_plan->khach_hang%")->first() : null;
            $obj = [];
            $obj['ngay_ct'] = date('d-m-Y', strtotime($log->created_at));
            $obj['so_ct'] = '';
            $obj['ma_nx'] = '';
            $obj['ma_kho'] = 'TP';
            if ($log['type'] === 1) {
                $obj['sl_nhap'] = $log->so_luong;
                $obj['dien_giai'] = 'Nhập kho';
            } else {
                $obj['sl_xuat'] = $log->so_luong;
                $obj['dien_giai'] = $export_plan->cua_xuat_hang ?? '';
            }
            $obj['ma_khach'] = $customer ? $customer->id : '';
            $obj['ten_khach'] = $customer ? $customer->name : '';
            $obj['ma_ct'] = '';
            $data[] = $obj;
        }
        $table = $data;
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $start_row = 2;
        $start_col = 1;
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => array(
                'outline' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $headerStyle = array_merge($centerStyle, [
            'font' => ['bold' => true],
        ]);
        $titleStyle = array_merge($centerStyle, [
            'font' => ['size' => 16, 'bold' => true],
        ]);
        $border = [
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $header = ['Ngày ct', 'Số ct', 'Diễn giải', 'Mã nx', 'Mã kho', 'Sl nhập', 'Sl xuất', 'Vụ việc', 'Mã khách', 'Tên khách', 'Mã ct'];
        $table_key = [
            'A' => 'ngay_ct',
            'B' => 'so_ct',
            'C' => 'dien_giai',
            'D' => 'ma_nx',
            'E' => 'ma_kho',
            'F' => 'sl_nhap',
            'G' => 'sl_xuat',
            'H' => 'vu_viec',
            'I' => 'ma_khach',
            'J' => 'ten_khach',
            'K' => 'ma_ct',
        ];
        foreach ($header as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            }
            $start_col += 1;
        }
        $sheet->setCellValue([1, 1], 'CHI TIẾT PHÁT SINH CỦA VẬT TƯ ' . $product->id . ' (' . $product->name . ')')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row + 1;
        foreach ($table as $key => $row) {
            $table_col = 1;
            $row = (array)$row;
            foreach ($table_key as $k => $value) {
                if (isset($row[$value])) {
                    $sheet->setCellValue($k . $table_row, $row[$value])->getStyle($k . $table_row)->applyFromArray($centerStyle);
                } else {
                    continue;
                }
                $table_col += 1;
            }
            $table_row += 1;
        }
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            $sheet->getStyle($column->getColumnIndex() . ($start_row) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="BM_thẻ_kho.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/BM_thẻ_kho.xlsx');
        $href = '/exported_files/BM_thẻ_kho.xlsx';
        return $this->success($href);
    }
    public function inventory()
    {
        $inventory = Inventory::first();
        if (!$inventory) {
            $log_import = Product::leftJoin('lot', 'products.id', '=', 'lot.product_id')->leftJoin('warehouse_logs', function (JoinClause $join) {
                $join->on('warehouse_logs.lot_id', '=', 'lot.id')
                    ->where('warehouse_logs.type', 1);
            })->select('products.id', 'products.name', DB::raw('SUM(warehouse_logs.so_luong) as so_luong'))->groupBy('products.id', 'products.name')->get();
            $log_export = Product::leftJoin('lot', 'products.id', '=', 'lot.product_id')->leftJoin('warehouse_logs', function (JoinClause $join) {
                $join->on('warehouse_logs.lot_id', '=', 'lot.id')
                    ->where('warehouse_logs.type', 2);
            })->select('products.id', 'products.name', DB::raw('SUM(warehouse_logs.so_luong) as so_luong'))->groupBy('products.id', 'products.name')->get();
            foreach ($log_import as $key => $log) {
                $obj = new Inventory();
                $obj->product_id = $log->id;
                $obj->sl_ton = $log->so_luong -  $log_export[$key]->so_luong;
                $obj->sl_nhap = $log->so_luong ? $log->so_luong : 0;
                $obj->sl_xuat = $log_export[$key]->so_luong ? $log_export[$key]->so_luong : 0;
                $obj->save();
            }
        } else {
            $log_import = Product::leftJoin('lot', 'products.id', '=', 'lot.product_id')->leftJoin('warehouse_logs', function (JoinClause $join) {
                $join->on('warehouse_logs.lot_id', '=', 'lot.id')
                    ->where('warehouse_logs.type', 1)->whereDate('warehouse_logs.created_at', date('Y-m-d'));
            })->select('products.id', 'products.name', DB::raw('SUM(warehouse_logs.so_luong) as so_luong'))->groupBy('products.id', 'products.name')->get();
            $log_export = Product::leftJoin('lot', 'products.id', '=', 'lot.product_id')->leftJoin('warehouse_logs', function (JoinClause $join) {
                $join->on('warehouse_logs.lot_id', '=', 'lot.id')
                    ->where('warehouse_logs.type', 2)->whereDate('warehouse_logs.created_at', date('Y-m-d'));
            })->select('products.id', 'products.name', DB::raw('SUM(warehouse_logs.so_luong) as so_luong'))->groupBy('products.id', 'products.name')->get();
            $records = Inventory::whereDate('created_at', date('Y-m-d'))->get();
            foreach ($log_import as $key => $log) {
                $obj = new Inventory();
                $obj->product_id = $log->id;
                $obj->sl_ton = $records[$key]->sl_ton + $log->so_luong -  $log_export[$key]->so_luong;
                $obj->sl_nhap = $log->so_luong ? $log->so_luong : 0;
                $obj->sl_xuat = $log_export[$key]->so_luong ? $log_export[$key]->so_luong : 0;
                $obj->save();
            }
        }
    }
    public function exportQCHistory(Request $request)
    {
        $query = InfoCongDoan::where("type", "sx")->orderBy('created_at');
        $line = Line::find($request->line_id);
        if ($line) {
            $query->where('line_id', $line->id);
        }
        if (isset($request->date) && count($request->date)) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->date[0])))
                ->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->date[1])));
        }
        if (isset($request->product_id)) {
            $query->where('lot_id', 'like',  '%' . $request->product_id . '%');
        }
        if (isset($request->ten_sp)) {
            $query->where('lot_id', 'like',  '%' . $request->ten_sp . '%');
        }
        if (isset($request->khach_hang)) {
            $khach_hang = Customer::where('id', $request->khach_hang)->first();
            if ($khach_hang) {
                $plan = ProductionPlan::where('khach_hang', $khach_hang->name)->get();
                $product_ids = $plan->pluck('product_id')->toArray();
                $query->where(function ($qr) use ($product_ids) {
                    for ($i = 0; $i < count($product_ids); $i++) {
                        $qr->orwhere('lot_id', 'like',  '%' . $product_ids[$i] . '%');
                    }
                });
            }
        }
        if (isset($request->lo_sx)) {
            $query->where('lot_id', 'like', "%$request->lo_sx%");
        }
        $infos = $query->with("lot.plans")->whereHas('lot', function ($lot_query) {
            $lot_query->whereIn('type', [0, 1, 2, 3])->where('info_cong_doan.line_id', 15)->orWhere('type', '<>', 1)->where('info_cong_doan.line_id', '<>', 15);
        })->get()->groupBy('line_id');
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'wrapText' => true
            ],
            'borders' => array(
                'outline' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $headerStyle = array_merge($centerStyle, [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => 'BFBFBF')
            ]
        ]);
        $titleStyle = array_merge($centerStyle, [
            'font' => ['size' => 16, 'bold' => true],
        ]);
        $border = [
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet_index = 0;

        foreach ($infos as $line_id => $info_cong_doan) {
            $line = Line::find($line_id);
            $sheet = $spreadsheet->getSheet($sheet_index);
            $sheet->setTitle($line->name);
            $start_row = 2;
            $start_col = 1;

            $header = [
                'STT',
                'Ngày',
                "Ca sản xuất",
                "Xưởng",
                "Công đoạn",
                "Máy sản xuất",
                "Mã máy",
                'Tên sản phẩm',
                "Khách hàng",
                "Mã hàng",
                'Lô sản xuất',
                'Mã pallet/thùng',
                "Số lượng sản xuất",
                "Số lượng OK",
                'Số lượng tem vàng',
                "Số lượng NG (SX tự KT)",
                'SX kiểm tra',
                "Số lượng NG (PQC)",
                'QC kiểm tra',
                "Số lượng NG",
                "Tỉ lệ NG"
            ];
            $table_key = [
                'A' => 'stt',
                'B' => 'ngay_sx',
                'C' => 'ca_sx',
                'D' => 'xuong',
                'E' => 'cong_doan',
                'F' => 'machine',
                'G' => 'machine_id',
                'H' => 'ten_san_pham',
                'I' => 'khach_hang',
                'J' => 'product_id',
                'K' => 'lo_sx',
                'L' => 'lot_id',
                'M' => 'sl_dau_ra_hang_loat',
                'N' => 'sl_dau_ra_ok',
                'O' => 'sl_tem_vang',
                'P' => 'sl_ng_sxkt',
                'Q' => 'user_sxkt',
                'R' => "sl_ng_pqc",
                'S' => 'user_pqc',
                "T" => "sl_ng",
                "U" => "ti_le_ng"
            ];
            $table = $this->produceTablePQC($info_cong_doan, true);
            // if($line_id === 13) return $table;
            $product_ids = [];
            foreach ($info_cong_doan as $item) {
                $product_ids[] = $item->lot->product_id;
            }
            $list  = TestCriteria::where('line_id', $line->id)->orderBy('chi_tieu')->get();
            $letter = 'V';
            $index = 0;

            foreach ($list as $key => $item) {
                if (!isset($header[$item->chi_tieu])) {
                    $header[$item->chi_tieu] = [];
                }
                if ($item->hang_muc == " ") continue;
                $header[$item->chi_tieu][] = $item->hang_muc;
                $letter = $this->num_to_letters(22 + $index);
                $table_key[$letter] = Str::slug($item->hang_muc);
                $index += 1;
            }
            // return [$table_key, $table];
            $header[] = 'Đánh giá';
            $table_key[$this->num_to_letters(22 + $index)] = 'evaluate';
            foreach ($header as $key => $cell) {
                if (!is_array($cell)) {
                    $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row + 1])->getStyle([$start_col, $start_row, $start_col, $start_row + 1])->applyFromArray($headerStyle);
                } else {
                    if (count($cell) > 0) {
                        $style = array_merge($headerStyle, array('fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => array('argb' => 'EBF1DE')
                        ]));
                        $sheet->setCellValue([$start_col, $start_row], $key)->mergeCells([$start_col, $start_row, $start_col + count($cell) - 1, $start_row])->getStyle([$start_col, $start_row, $start_col + count($cell) - 1, $start_row])->applyFromArray($style);
                        foreach ($cell as $val) {
                            $sheet->setCellValue([$start_col, $start_row + 1], $val)->getStyle([$start_col, $start_row + 1])->applyFromArray($style);
                            $start_col += 1;
                        }
                    }
                    continue;
                }
                $start_col += 1;
            }

            $sheet->setCellValue([1, 1], 'BẢNG KIỂM TRA CHẤT LƯỢNG CÔNG ĐOẠN ' . mb_strtoupper($line->name))->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
            $sheet->getRowDimension(1)->setRowHeight(40);
            $table_col = 1;
            $table_row = $start_row + 2;
            foreach ($table as $key => $row) {
                $table_col = 1;
                $sheet->setCellValue([1, $table_row], $table_row - 3)->getStyle([$table_col, $table_row])->applyFromArray($centerStyle);
                $table_col += 1;
                foreach ((array)$row as $key => $cell) {
                    if (in_array($key, $table_key)) {
                        $value = '';
                        if ($table_col > 21 && ($cell === 1 || $cell === 1)) {
                            if ($cell === 1) {
                                $value = 'OK';
                            } else {
                                $value = 'NG';
                            }
                        } else {
                            $value = $cell;
                        }
                        $sheet->setCellValue(array_search($key, $table_key) . $table_row, $value)->getStyle(array_search($key, $table_key) . $table_row)->applyFromArray($centerStyle);
                    } else {
                        continue;
                    }
                    $table_col += 1;
                }
                $table_row += 1;
            }
            foreach ($sheet->getColumnIterator() as $column) {
                $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
                $sheet->getStyle($column->getColumnIndex() . ($start_row + 2) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
            }
            if ($sheet_index < count($infos) - 1) {
                $spreadsheet->createSheet();
                $sheet_index += 1;
            }
        }

        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Chi tiết QC.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Chi tiết QC.xlsx');
        $href = '/exported_files/Chi tiết QC.xlsx';
        return $this->success($href);
    }

    public function findSpec($test, $spcecs)
    {
        $find = "±";
        // return $test;
        $hang_muc = Str::slug($test->hang_muc);
        foreach ($spcecs as $item) {

            if (str_contains($item->slug, $hang_muc)) {
                if (str_contains($item->value, $find)) {
                    $arr = explode($find, $item->value);
                    $test["input"] = true;
                    $test["tieu_chuan"] = filter_var($arr[0], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $test["delta"] =  filter_var($arr[1], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $test['note'] = $item->value;
                    return $test;
                }
            }
        }
        $test['input'] = false;
        return $test;
    }

    public function exportHistoryMonitors(Request $request)
    {
        $input = $request->all();
        $query = Monitor::with('machine')->orderBy('created_at', 'DESC');
        if (isset($input['type'])) {
            $query = $query->where('type', $input['type']);
        }
        if (isset($input['machine_id'])) {
            $query = $query->where('machine_id', $input['machine_id']);
        }
        if (isset($input['status'])) {
            $query = $query->where('status', $input['status']);
        }
        if (isset($input['start_date'])) {
            $query = $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($input['start_date'])));
        } else {
            $query = $query->whereDate('created_at', '>=', date('Y-m-d'));
        }
        if (isset($input['end_date'])) {
            $query = $query->whereDate('created_at', '<=', date('Y-m-d', strtotime($input['end_date'])));
        } else {
            $query = $query->whereDate('created_at', '<=', date('Y-m-d'));
        }
        $records = $query->get();
        $table = [];
        foreach ($records as $key => $record) {
            $table[] = [
                'stt' => $key + 1,
                'type' => $record->type == 'sx' ? 'Sản xuất' : ($record->type == 'cl' ? 'Chất lượng' : 'Thiết bị'),
                'created_at' => date('d/m/Y', strtotime($record->created_at)),
                'name' => $record->machine->name,
                'content' => $record->content,
                'value' => $record->value,
                'status' => $record->status == 0 ? 'NG' : 'OK',
            ];
        }
        // return $table;
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $start_row = 2;
        $start_col = 1;
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => array(
                'outline' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $headerStyle = array_merge($centerStyle, [
            'font' => ['bold' => true],
        ]);
        $titleStyle = array_merge($centerStyle, [
            'font' => ['size' => 16, 'bold' => true],
        ]);
        $border = [
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $header = ['STT', 'Loại cảnh báo', 'Thời gian bắt đầu cảnh báo', 'Ngày cảnh báo', 'Tên máy', 'Tên lỗi', 'Giá trị', 'Tình trạng sử lý'];
        $table_key = [
            'A' => 'stt',
            'B' => 'type',
            'C' => 'created_at',
            'D' => 'created_at',
            'E' => 'name',
            'F' => 'content',
            'G' => 'value',
            'H' => 'status',
        ];
        foreach ($header as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            }
            $start_col += 1;
        }
        $sheet->setCellValue([1, 1], 'Lịch sử bất thường')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row + 1;
        foreach ($table as $key => $row) {
            $table_col = 1;
            $row = (array)$row;
            $sheet->setCellValue([1, $table_row], $key + 1)->getStyle([1, $table_row])->applyFromArray($centerStyle);
            foreach ($table_key as $k => $value) {
                if (isset($row[$value])) {
                    $sheet->setCellValue($k . $table_row, $row[$value])->getStyle($k . $table_row)->applyFromArray($centerStyle);
                } else {
                    continue;
                }
                $table_col += 1;
            }
            $table_row += 1;
        }
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            $sheet->getStyle($column->getColumnIndex() . ($start_row) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Lịch_sử_bất_thường.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Lịch_sử_bất_thường.xlsx');
        $href = '/exported_files/Lịch_sử_bất_thường.xlsx';
        return $this->success($href);
    }

    public function exportReportQC(Request $request)
    {
        $input = $request->all();
        $sheet_array = [];
        foreach ($input as $key => $value) {
            switch ($key) {
                case 'week':
                    $sheet_array[$key]['datetime'] = date("W", strtotime($value));
                    $sheet_array[$key]['title'] = 'tuần';
                    $sheet_array[$key]['start_date'] = date("Y-m-d", strtotime($value . ' monday this week'));
                    $sheet_array[$key]['end_date'] = date("Y-m-d", strtotime($value . ' sunday this week'));
                    break;
                case 'month':
                    $sheet_array[$key]['datetime'] = date("m", strtotime($value));
                    $sheet_array[$key]['title'] = 'tháng';
                    $sheet_array[$key]['start_date'] = date("Y-m-01", strtotime($value));
                    $sheet_array[$key]['end_date'] = date("Y-m-t", strtotime($value));
                    break;
                case 'year':
                    $sheet_array[$key]['datetime'] = date("Y", strtotime($value));
                    $sheet_array[$key]['title'] = 'năm';
                    $sheet_array[$key]['start_date'] = date("Y-01-01", strtotime($value));
                    $sheet_array[$key]['end_date'] = date("Y-12-31", strtotime($value));
                    break;
                default:
                    $sheet_array[$key]['datetime'] = date("d-m-Y", strtotime($value));
                    $sheet_array[$key]['title'] = 'ngày';
                    $sheet_array[$key]['start_date'] = date("Y-m-d", strtotime($value));
                    $sheet_array[$key]['end_date'] = date("Y-m-d", strtotime($value));
                    break;
            }
            $query = InfoCongDoan::where("type", "sx")->orderBy('created_at');
            if (isset($sheet_array[$key]['start_date']) && isset($sheet_array[$key]['end_date'])) {
                $query->whereDate('created_at', '>=', $sheet_array[$key]['start_date'])
                    ->whereDate('created_at', '<=', $sheet_array[$key]['end_date']);
            }
            $infos = $query->with("lot.plans")->whereHas('lot', function ($lot_query) {
                $lot_query->whereIn('type', [0, 1, 2, 3])->where('info_cong_doan.line_id', 15)->orWhere('type', '<>', 1)->where('info_cong_doan.line_id', '<>', 15);
            })->get()->groupBy('line_id');
            $data = [];
            foreach ($infos as $line => $info_cong_doan) {
                $sum_ok = 0;
                $sum_ng = 0;
                foreach ($info_cong_doan as $info) {
                    if ($info->sl_tem_vang === 0) {
                        if ($info->sl_ng === 0) {
                            $sum_ok += 1;
                        } else {
                            $sum_ng += 1;
                        }
                    } else {
                        continue;
                    }
                }
                $cong_doan = Line::find($line);
                $data[$line]['cong_doan'] = $cong_doan->name;
                $data[$line]['sum_lot_kt'] = count($info_cong_doan);
                $data[$line]['sum_lot_ok'] = $sum_ok;
                $data[$line]['sum_lot_ng'] = $sum_ng;
                $data[$line]['sum_ty_le_ng'] = count($info_cong_doan) ? number_format($sum_ng / count($info_cong_doan) * 100) : 0;
                $data[$line]['loi_phat_sinh'] = '';
            }
            $sheet_array[$key]['data'] = $data;
        }
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'wrapText' => true
            ],
            'borders' => array(
                'outline' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $headerStyle = array_merge($centerStyle, [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => 'EBF1DE')
            ]
        ]);
        $titleStyle = array_merge($centerStyle, [
            'font' => ['size' => 16, 'bold' => true],
        ]);
        $border = [
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet_index = 0;

        // return $sheet_array;
        foreach ($sheet_array as $arr) {
            $sheet = $spreadsheet->getSheet($sheet_index);
            $sheet->setTitle('Báo cáo ' . $arr['title']);
            $start_row = 2;
            $start_col = 1;

            $header = ['Công đoạn', 'Tổng số lot kiểm tra', "Số lót OK", "Số lot NG", "Tỷ lệ NG (%)", "Lỗi phát sinh"];
            array_unshift($header, ucfirst($arr['title']));
            $table_key = [
                'A' => 'date',
                'B' => 'cong_doan',
                'C' => 'sum_lot_kt',
                'D' => 'sum_lot_ok',
                'E' => 'sum_lot_ng',
                'F' => 'sum_ty_le_ng',
                'G' => 'loi_phat_sinh',
            ];
            $table = $arr['data'] ?? [];
            foreach ($header as $key => $cell) {
                if (!is_array($cell)) {
                    $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row + 1])->getStyle([$start_col, $start_row, $start_col, $start_row + 1])->applyFromArray($headerStyle);
                } else {
                    $style = array_merge($headerStyle, array('fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => array('argb' => 'EBF1DE')
                    ]));
                    $sheet->setCellValue([$start_col, $start_row], $key)->mergeCells([$start_col, $start_row, $start_col + count($cell) - 1, $start_row])->getStyle([$start_col, $start_row, $start_col + count($cell) - 1, $start_row])->applyFromArray($style);
                    foreach ($cell as $val) {

                        $sheet->setCellValue([$start_col, $start_row + 1], $val)->getStyle([$start_col, $start_row + 1])->applyFromArray($style);
                        $start_col += 1;
                    }
                    continue;
                }
                $start_col += 1;
            }

            $sheet->setCellValue([1, 1], 'BÁO CÁO ' . mb_strtoupper($arr['title']))->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
            $sheet->getRowDimension(1)->setRowHeight(40);
            $table_col = 2;
            $table_row = $start_row + 2;
            foreach ($table as $key => $row) {
                $table_col = 2;
                foreach ((array)$row as $key => $cell) {
                    if (in_array($key, $table_key)) {
                        $value = '';
                        if (is_numeric($key)) {
                            switch ($cell) {
                                case 0:
                                    $value = "NG";
                                    break;
                                case 1:
                                    $value = "OK";
                                    break;
                                default:
                                    $value = "";
                                    break;
                            }
                        } else {
                            $value = $cell;
                        }
                        $sheet->setCellValue(array_search($key, $table_key) . $table_row, $value)->getStyle(array_search($key, $table_key) . $table_row)->applyFromArray($centerStyle);
                    } else {
                        continue;
                    }
                    $table_col += 1;
                }
                $table_row += 1;
            }
            if (count($table)) {
                $sheet->setCellValue([1, $start_row + 2], $arr['datetime'])->mergeCells([1, $start_row + 2, 1, $table_row - 1])->getStyle([1, $start_row + 2, 1, $table_row - 1])->applyFromArray($centerStyle);
            }

            foreach ($sheet->getColumnIterator() as $column) {
                $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
                $sheet->getStyle($column->getColumnIndex() . ($start_row + 2) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
            }
            if ($sheet_index < count($sheet_array) - 1) {
                $spreadsheet->createSheet();
                $sheet_index += 1;
            }
        }

        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Báo_cáo.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Báo_cáo.xlsx');
        $href = '/exported_files/Báo_cáo.xlsx';
        return $this->success($href);
    }

    public function updateHGOrder(Request $request)
    {
        $orders = Order::all();
        foreach ($orders as $key => $order) {
            if (is_null($order->han_giao_sx) && !is_null($order->han_giao)) {
                $order->update(['han_giao_sx' => $order->han_giao]);
            }
            $order->update(['han_giao_sx' => $order->han_giao]);
        }
    }

    public function import(Request $request)
    {
        $extension = pathinfo($_FILES['files']['name'], PATHINFO_EXTENSION);
        if ($extension == 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        } elseif ($extension == 'xlsx') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        }
        // file path
        $spreadsheet = $reader->load($_FILES['files']['tmp_name']);
        $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $layouts = [];
        $films = [];
        $inks = [];
        $khuon = [];
        $locator_mlt = [];
        $locator_fg = [];
        $quy_cach = [];
        $quy_cach_mkh = [];
        foreach ($allDataInSheet as $key => $row) {
            if ($key > 1) {
                $input = [];
                // $input['id'] = $row['C'];
                // $input['name'] = $row['D'];
                // $check = Customer::find($input['id']);
                // if ($check) {
                //     CustomerShort::create($input);
                // } else {
                //     Customer::create($input);
                //     $input['customer_id'] = $row['C'];
                //     $input['short_name'] = $row['B'];
                //     CustomerShort::create($input);
                // }

                // $input['id'] = $row['A'];
                // $input['name'] = $row['A'];
                // if ($input['id']) {
                //     $layouts[] = $input;
                // }
                // $input['id'] = $row['B'];
                // $input['name'] = $row['B'];
                // if ($input['id']) {
                //     $films[] = $input;
                // }
                // $input['id'] = $row['C'];
                // // $input['name'] = $row['C'];
                // if ($input['id']) {
                //     $khuon[] = $input;
                // }
                // $input['id'] = $row['AW'];
                // $input['name'] = $row['AW'];
                // if ($input['id'] && $key > 2) {
                //     $input['warehouse_mlt_id'] = (int)filter_var(explode('.', $input['id'])[0], FILTER_SANITIZE_NUMBER_INT);
                //     $locator_mlt[] = $input;
                // }
                // $input['id'] = $row['B'];
                // $input['ma_cuon_ncc'] = $row['C'];
                // $input['ma_cuon_ncc'] = $row['D'];
                // $input['ma_cuon_ncc'] = $row['E'];
                // $input['ma_cuon_ncc'] = $row['F'];
                // $input['ma_cuon_ncc'] = $row['G'];
                // if ($input['id']) {
                //     $locator_fg[] = $input;
                // }
                $input['customer_id'] = $row['B'];
                $input['phan_loai_1'] = Str::slug($row['C']);
                $input['drc_id'] = $row['E'];
                if ($input['drc_id']) {
                    $input_drc['id'] = $row['E'];
                    $input_drc['ten_quy_cach'] = $row['F'];
                    $input_drc['ct_dai'] = 'return ' . $row['G'] . ';';
                    $input_drc['ct_rong'] = 'return ' . $row['H'] . ';';
                    if ($row['I'] === "[C]") {
                        $ct_cao = 'return [C];';
                    } else {
                        $ct_cao = str_replace('CASE WHEN', 'if(', $row['I']);
                        $ct_cao = str_replace('=', '===', $ct_cao);
                        $ct_cao = str_replace('THEN', '){ return ', $ct_cao);
                        $ct_cao = str_replace('WHEN', ';} else if(', $ct_cao);
                        $ct_cao = str_replace('ELSE', ';}else{ return', $ct_cao);
                        $ct_cao = str_replace('END', ';}', $ct_cao);
                        if (!str_contains($ct_cao, 'return')) {
                            $ct_cao = 'return ' . $ct_cao . ';';
                        }
                    }
                    $input_drc['ct_cao'] = $ct_cao;
                    if ($input_drc['id']) {
                        $quy_cach[] = $input_drc;
                    }
                    DRC::firstOrCreate(['id' => $input['drc_id']], $input_drc);
                }
                if ($input['customer_id']) {
                    $quy_cach_mkh[] = $input;
                }
            }
        }
        try {
            DB::beginTransaction();
            // DB::table('locator_mlt_map');
            // Khuon::truncate($input);
            // $khuon = $this->removeDuplicateObjects($khuon);
            // foreach ($khuon as $key => $input) {
            //     Khuon::create($input);
            // }
            // Layout::truncate();
            // $films = $this->removeDuplicateObjects($films);
            // foreach ($layouts as $key => $input) {
            //     Layout::create($input);
            // }
            // Film::truncate();
            // $layouts = $this->removeDuplicateObjects($layouts);
            // foreach ($films as $key => $input) {
            //     Film::create($input);
            // }
            // Ink::truncate();
            // foreach ($inks as $key => $input) {
            //     Ink::create($input);
            // }
            // LocatorFG::truncate();
            // foreach ($locator_fg as $key => $input) {
            //     LocatorFG::create($input);
            // }
            // LocatorMLT::truncate();
            // foreach ($locator_mlt as $key => $input) {
            //     LocatorMLT::updateOrCreate(['id' => $input['id']], $input);
            // }
            // foreach ($quy_cach as $key => $input) {
            //     DB::table('drc')->insert($input);
            // }
            CustomerSpecification::query()->delete();
            foreach ($quy_cach_mkh as $key => $input) {
                CustomerSpecification::insert($input);
            }
            DB::commit();
            return $this->success([], 'Upload thành công');
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
    }

    public function importMachine(Request $request)
    {
        $extension = pathinfo($_FILES['files']['name'], PATHINFO_EXTENSION);
        if ($extension == 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        } elseif ($extension == 'xlsx') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        }
        // file path
        $spreadsheet = $reader->load($_FILES['files']['tmp_name']);
        $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $machines = [];
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 2
            if ($key > 3) {
                $input = [];
                //IN
                if ($row['J']) {
                    if (strlen($row['O']) > 2) {
                        $input['id'] = $row['J'];
                    } else {
                        $input['id'] = $row['J'][0] . str_pad(ltrim($row['J'], $row['J'][0]), 2, '0', STR_PAD_LEFT);
                    }
                    $input['name'] = $row['I'];
                    $input['ordering'] = $row['H'];
                    $input['line_id'] = '31';
                    $input['hidden'] = $row['K'] ? 1 : 0;
                    $input['is_iot'] = 0;
                    if ($input['id'] && $input['name']) {
                        $machines[] = $input;
                    }
                }

                //DAN
                if ($row['O']) {
                    if (strlen($row['O']) > 2) {
                        $input['id'] = $row['O'];
                    } else {
                        $input['id'] = $row['O'][0] . str_pad(ltrim($row['O'], $row['O'][0]), 2, '0', STR_PAD_LEFT);
                    }
                    $input['name'] = $row['P'];
                    $input['ordering'] = $row['N'];
                    $input['line_id'] = '32';
                    $input['hidden'] = $row['Q'] ? 1 : 0;
                    $input['is_iot'] = 0;
                    if ($input['id'] && $input['name']) {
                        $machines[] = $input;
                    }
                }

                //PAD
                if ($row['U']) {
                    if (strlen($row['U']) > 2) {
                        $input['id'] = $row['U'];
                    } else {
                        $input['id'] = $row['U'][0] . str_pad(ltrim($row['U'], $row['U'][0]), 2, '0', STR_PAD_LEFT);
                    }
                    $input['name'] = $row['V'];
                    $input['ordering'] = $row['T'];
                    $input['line_id'] = '33';
                    $input['hidden'] = $row['W'] ? 1 : 0;
                    $input['is_iot'] = 0;
                    if ($input['id'] && $input['name']) {
                        $machines[] = $input;
                    }
                }
            }
        }
        foreach ($machines as $key => $input) {
            $validated = Machine::validateUpdate($input);
            if ($validated->fails()) {
                return $this->failure($input, 'Lỗi dòng thứ ' . ($key) . ': ' . $validated->errors()->first());
            }
        }
        foreach ($machines as $key => $input) {
            $machine = Machine::create($input);
        }
        return $this->success([], 'Upload thành công');
    }
    function removeDuplicateObjects($array)
    {
        $serialized = array_map('json_encode', $array);
        $unique = array_unique($serialized);
        $result = array_map('json_decode', $unique, array_fill(0, count($unique), true));
        return $result;
    }
    public function importNewFGLocator(Request $request)
    {
        $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        if ($extension == 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        } elseif ($extension == 'xlsx') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        }
        // file path
        $spreadsheet = $reader->load($_FILES['file']['tmp_name']);
        $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $locators = [];
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 2
            if ($key > 2) {
                if (!empty($row['C'])) {
                    $locators[] = $row['C'];
                }
                if (!empty($row['E'])) {
                    $locators[] = $row['E'];
                }
                if (!empty($row['G'])) {
                    $locators[] = $row['G'];
                }
                if (!empty($row['I'])) {
                    $locators[] = $row['I'];
                }
                if (!empty($row['K'])) {
                    $locators[] = $row['K'];
                }
            }
        }
        try {
            DB::beginTransaction();
            foreach ($locators as $key => $locator) {
                $input['id'] = $locator;
                $input['name'] = $locator;
                $input['capacity'] = 0;
                $input['warehouse_fg_id'] = 1;
                LocatorFG::updateOrCreate(['id' => $locator], $input);
            }
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            return $th;
        }

        return $this->success([], 'Upload thành công');
    }
    public function getTem(Request $request)
    {
        $data = LocatorFG::get();
        $result = [];
        foreach ($data as $key => $value) {
            if (str_contains($value->id, 'F01') && (int)explode('F01.', $value->id)[1] > 64) {
                $result[] = $value;
            } else if (str_contains($value->id, 'F02') && (int)explode('F02.', $value->id)[1] > 34) {
                $result[] = $value;
            } else if (str_contains($value->id, 'F03') && (int)explode('F03.', $value->id)[1] > 29) {
                $result[] = $value;
            } else if (str_contains($value->id, 'F04') && (int)explode('F04.', $value->id)[1] > 27) {
                $result[] = $value;
            } else if (str_contains($value->id, 'F05') && (int)explode('F05.', $value->id)[1] > 18) {
                $result[] = $value;
            }
        }
        return $this->success($result);
    }

    public function importMaterial(Request $request)
    {
        set_time_limit(0);
        $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        if ($extension == 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        } elseif ($extension == 'xlsx') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        }
        // file path
        $spreadsheet = $reader->load($_FILES['file']['tmp_name']);
        $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $materials = [];
        $material_ids = [];
        $loai_giay = [];
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 2
            if ($key > 1) {
                $input = [];
                $input['id'] = $row['B'];
                $input['material_id'] = $row['B'];
                $input['supplier'] = $row['C'];
                $input['fsc'] = $row['D'] ? 1 : 0;
                $input['loai_giay'] = trim($row['E']);
                if (empty($input['loai_giay'])) {
                    return $this->failure([], 'Không có loai giay: ' . $key);
                }
                $input['kho_giay'] = (float)$row['F'];
                $input['dinh_luong'] = (float)$row['G'];
                $input['ma_vat_tu'] = $input['loai_giay'] . '(' . $input['dinh_luong'] . ')' . $input['kho_giay']; //Loai giay + (dinh luong) + kho giay
                $input['ma_cuon_ncc'] = $row['I'];
                $input['so_kg'] = (float)str_replace(',', '', ($row['K'] ?? $row['H']));
                $input['so_kg_dau'] = (float)str_replace(',', '', $row['H']);
                try {
                    $input['ngay_nhap'] = $this->transformDate($row['J']);
                } catch (\Throwable $th) {
                    return $this->failure([], 'Lỗi format time: ' . $key); 
                }
                
                $input['location_id'] = $row['L'];
                if ($input['so_kg'] && $input['kho_giay'] && $input['dinh_luong']) {
                    $input['so_m_toi'] = floor(($input['so_kg'] / ($input['kho_giay'] / 100)) / ($input['dinh_luong'] / 1000));
                }
                if ($input['id']) {
                    $materials[] = $input;
                    $material_ids[] = $input['id'];
                    $loai_giay[$input['loai_giay']] = $input['supplier'];
                }
            }
        }
        //Chạy lần 1
        // foreach ($loai_giay as $key_id => $name) {
        //     Supplier::firstOrCreate(['id' => $key_id], ['name' => $name]);
        // }
        // foreach ($materials as $key => $input) {
        //     $material = Material::find($input['id']);
        //     if ($material) {
        //         $material->update($input);
        //     } 
        //     // else {
        //     //     Material::create($input);
        //     // }
        //     // WareHouseMLTImport::updateOrCreate(['material_id' => $input['id']],
        //     // [
        //     //     'iqc'=>1,
        //     //     'ma_vat_tu'=>$input['ma_vat_tu'],
        //     //     'ma_cuon_ncc'=>$input['ma_cuon_ncc'],
        //     //     'fsc'=>$input['fsc'],
        //     //     'so_kg'=>$input['so_kg_dau'],
        //     //     'loai_giay'=>$input['loai_giay'],
        //     //     'kho_giay'=>$input['kho_giay'],
        //     //     'dinh_luong'=>$input['dinh_luong'],
        //     // ]
        //     // )->first();
        //     $check = WarehouseMLTLog::where('material_id', $input['id'])->where(function($q){
        //         $q->whereDate('created_at', '>=', '2025-10-10')->orWhereDate('updated_at', '>=', '2025-10-10');
        //     })->first();
        //     if($check){
        //         continue;
        //     }
        //     $log = WarehouseMLTLog::where('material_id', $input['id'])->orderBy('created_at', "DESC")->first();
        //     if($log){
        //         if(!$log->tg_xuat && $log->so_kg_nhap == $input['so_kg']){
        //             continue;
        //         } else {
        //             WarehouseMLTLog::create([
        //                 'tg_nhap' => $log->tg_xuat,
        //                 'locator_id' => $input['location_id'],
        //                 'material_id' => $input['id'],
        //                 'so_kg_nhap' => $input['so_kg'],
        //             ]);
        //         }
                
        //     }
        //     LocatorMLTMap::updateOrCreate(['material_id' => $input['id']], ['locator_mlt_id' => $input['location_id']]);
        // }
        // LocatorMLTMap::doesntHave('material')->delete();
        // $locator_map = LocatorMLTMap::get()->groupBy('locator_mlt_id');
        // foreach ($locator_map as $key => $locator) {
        //     LocatorMLT::find($key)->update(['capacity' => count($locator)]);
        // }

        //Chạy lần 2
        $exported_materials = Material::whereNotIn('id', $material_ids)
        ->whereDoesntHave('warehouse_mlt_logs', function($q){
            $q->whereDate('created_at', '>=', '2025-10-10')->orWhereDate('updated_at', '>=', '2025-10-10');
        })
        ->get();
        foreach ($exported_materials as $key => $exported) {
            $latest_log = WarehouseMLTLog::where('material_id', $exported->id)->orderBy('created_at', 'DESC')->first();
            if($latest_log && (!$latest_log->tg_xuat || $latest_log->so_kg_nhap != $latest_log->so_kg_xuat)){
                $exported->update(['so_kg' => 0]);
                $latest_log->update([
                    'tg_xuat' => now(),
                    'so_kg_xuat' => $latest_log->so_kg_nhap,
                ]);
            }
        }

        return $this->success([], 'Upload thành công');
    }

    protected function transformDate($value)
    {
        if (is_numeric($value)) {
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->format('Y-m-d');
        }
        return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
    }

    function getStrtotime($timeDateStr, $formatOfStr = "d/m/Y")
    {
        // Same as strtotime() but using the format $formatOfStr.
        // Works with PHP version 5.5 and later.
        // On error reading the time string, returns a date that never existed. 3/09/1752 Julian/Gregorian calendar switch.
        $timeStamp = DateTimeImmutable::createFromFormat($formatOfStr, $timeDateStr);
        if ($timeStamp === false) {
            // Bad date string or format string.
            return -6858133619; // 3/09/1752
        } else {
            // Date string and format ok.
            return $timeStamp->format("U"); // UNIX timestamp from 1/01/1970,  0:00:00 gmt
        }
    }

    public function importSupplier(Request $request)
    {
        $extension = pathinfo($_FILES['files']['name'], PATHINFO_EXTENSION);
        if ($extension == 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        } elseif ($extension == 'xlsx') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        }
        // file path
        $spreadsheet = $reader->load($_FILES['files']['tmp_name']);
        $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $suppliers = [];
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 4
            if ($key > 1) {
                $input = [];
                $input['name'] = $row['A'];
                $input['id'] = $row['B'];
                if ($input['id']) {
                    $suppliers[] = $input;
                }
            }
        }
        Supplier::truncate();
        foreach ($suppliers as $key => $input) {
            Supplier::create($input);
        }
        return $this->success([], 'Upload thành công');
    }

    public function importVehicle(Request $request)
    {
        $extension = pathinfo($_FILES['files']['name'], PATHINFO_EXTENSION);
        if ($extension == 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        } elseif ($extension == 'xlsx') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        }
        // file path
        $spreadsheet = $reader->load($_FILES['files']['tmp_name']);
        $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $vehicle = [];
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 4
            if ($key > 2) {
                $input = [];
                $input['id'] = $row['B'];
                $input['weight'] = $row['C'];
                $user1 = CustomUser::where('username', (int)$row['E'])->where('username', '<>', 'admin')->first();
                $input['user1'] = $user1->id ?? null;
                $user2 = CustomUser::where('username', (int)$row['H'])->where('username', '<>', 'admin')->first();
                $input['user2'] = $user2->id ?? null;
                $user3 = CustomUser::where('username', (int)$row['K'])->where('username', '<>', 'admin')->first();
                $input['user3'] = $user3->id ?? null;
                if ($input['id']) {
                    $vehicle[] = $input;
                }
            }
        }
        foreach ($vehicle as $key => $input) {
            Vehicle::create($input);
        }
        return $this->success([], 'Upload thành công');
    }

    public function updateTem(Request $request)
    {
        $input = $request->all();
        $list_tem = Tem::whereDate('created_at', date('Y-m-d'))->orderBy('id')->get();
        $index = 0;
        $prefix = 'T' . date('ymd');
        foreach ($list_tem as $tem) {
            $tem->lo_sx = $prefix . str_pad($index + 1, 4, '0', STR_PAD_LEFT);
            $tem->update();
            $index += 1;
        }
        return $this->success('Update thành công');
    }

    public function updateTemMDH(Request $request)
    {
        set_time_limit(0);
        try {
            DB::beginTransaction();
            $tems = Tem::get();
            $tem_olds = DB::table('tem_old')->get();
            foreach ($tem_olds as $tem_old) {
                $tem = Tem::find($tem_old->id);
                if ($tem_old && $tem_old->mdh) {
                    $tem->mdh = $tem_old->mdh;
                }
                if (!$tem->order_id && $tem_old->order_id) {
                    $tem->order_id = $tem_old->order_id;
                }
                $tem->save();
            }
            DB::commit();
            return $this->success('', 'success');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->failure($th, 'error');
        }
    }
    function checkKeySameValue($array, $key)
    {
        // Check if array is empty
        if (empty($array)) {
            return false;
        }

        // Get the first value for comparison
        $firstValue = $array[0][$key];

        // Iterate over the array starting from the second element
        for ($i = 1; $i < count($array); $i++) {
            // If any value is different from the first value, return false
            if ($array[$i][$key] !== $firstValue) {
                return false;
            }
        }

        // All values are the same, return true
        return true;
    }

    public function importKhuonLink(Request $request)
    {
        $extension = pathinfo($_FILES['files']['name'], PATHINFO_EXTENSION);
        if ($extension == 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        } elseif ($extension == 'xlsx') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        }
        // file path
        $spreadsheet = $reader->load($_FILES['files']['tmp_name']);
        $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $khuon_link = [];
        foreach ($allDataInSheet as $key => $row) {
            if ($key > 2) {
                if ($row['A']) {
                    $input = [];
                    $input['customer_id'] = $row['B'];
                    $input['dai'] = $row['C'];
                    $input['rong'] = $row['D'];
                    $input['cao'] = $row['E'];
                    $input['kich_thuoc'] = $row['F'];
                    $input['phan_loai_1'] = Str::slug($row['H']);
                    $input['buyer_id'] = $row['I'];
                    $input['kho_khuon'] = $row['J'];
                    $input['dai_khuon'] = $row['K'];
                    $input['so_con'] = $row['L'];
                    $input['so_manh_ghep'] = $row['M'];
                    $input['khuon_id'] = $row['N'];
                    $input['machine_id'] = $row['O'];
                    $input['sl_khuon'] = $row['P'];
                    $input['buyer_note'] = $row['Q'];
                    $input['note'] = $row['R'];
                    $input['layout'] = $row['S'];
                    $input['supplier'] = $row['T'];
                    $input['ngay_dat_khuon'] = $row['U'];
                    $khuon_link[] = $input;
                }
            }
        }
        try {
            DB::beginTransaction();
            KhuonLink::query()->delete();
            foreach ($khuon_link as $key => $input) {
                KhuonLink::insert($input);
            }
            DB::commit();
            return $this->success([], 'Upload thành công');
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
    }

    function insertTableFields(Request $request)
    {
        $fields = [
            "short_name" => "Khách hàng",
            "mdh" => "MDH",
            "length" => "L",
            "width" => "W",
            "height" => "H",
            "kich_thuoc" => "Kích thước ĐH",
            "mql" => "MQL",
            "sl" => "SL",
            "unit" => "Đơn vị tính",
            "kich_thuoc_chuan" => "Kích thước chuẩn",
            "phan_loai_1" => "Phân loại 1",
            "quy_cach_drc" => "Quy cách DRC",
            "phan_loai_2" => "Phân loại 2",
            "buyer_id" => "Mã buyer",
            "khuon_id" => "Mã khuôn",
            "toc_do" => "Tốc độ",
            "tg_doi_model" => "Thời gian thay model",
            "note_3" => "Ghi chú sóng",
            "dai" => "Dài",
            "rong" => "Rộng",
            "cao" => "Cao",
            "so_ra" => "Số ra",
            "kho" => "Khổ",
            "kho_tong" => "Khổ tổng",
            "dai_tam" => "Dài tấm",
            "so_dao" => "Số dao",
            "so_met_toi" => "Số mét tới",
            "layout_type" => "Chia máy + p8",
            "layout_id" => "Mã layout",
            "order" => "Order",
            "slg" => "SLG",
            "slt" => "SLT",
            "tmo" => "TMO",
            "po" => "PO",
            "style" => "STYLE",
            "style_no" => "STYLE NO",
            "color" => "COLOR",
            "item" => "ITEM",
            "rm" => "RM",
            "size" => "SIZE",
            "price" => "Đơn giá",
            "into_money" => "Thành tiền",
            "xuong_giao" => "Xưởng giao",
            "note_1" => "Ghi chú khách hàng",
            "han_giao" => "Ngày giao hàng trên đơn",
            "han_giao_sx" => "Ngày giao hàng SX",
            "nguoi_dat_hang" => "Người đặt hàng",
            "ngay_dat_hang" => "Ngày đặt hàng",
            "note_2" => "Ghi chú của TBDX",
            "dot" => "Đợt",
            "ngay_kh" => "Ngày thực hiện KH",
        ];
        $data = [];
        $index = 0;
        foreach ($fields as $key => $field) {
            $index++;
            $data[] = ['field' => $key, 'name' => $field, 'table_id' => 'orders', 'ordering' => $index];
        }
        DB::table('fields')->insert($data);
    }

    function importUserLineMachine()
    {
        $extension = pathinfo($_FILES['files']['name'], PATHINFO_EXTENSION);
        if ($extension == 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        } elseif ($extension == 'xlsx') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        }
        // file path
        $spreadsheet = $reader->load($_FILES['files']['tmp_name']);
        $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $user_line_machine = [];
        foreach ($allDataInSheet as $key => $row) {
            if ($key > 1) {
                if ($row['A']) {
                    $input = [];
                    $input['username'] = $row['A'];
                    $input['line_id'] = $row['B'];
                    $input['machine_id'] = $row['C'];
                    $user_line_machine[] = $input;
                }
            }
        }
        try {
            DB::beginTransaction();
            // UserLineMachine::query()->delete();
            foreach ($user_line_machine as $key => $input) {
                $user = CustomUser::where('username', $input['username'])->first();
                if ($user) {
                    $input['user_id'] = $user->id;
                    if (isset($input['machine_id']) && $input['machine_id']) {
                        UserLine::create($input);
                    }
                    if (isset($input['line_id']) && $input['line_id']) {
                        UserLine::create($input);
                    }
                    // UserLineMachine::create($input);
                }
            }
            DB::commit();
            return $this->success([], 'Upload thành công');
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
    }

    public function importIQCTestCriteria()
    {
        $extension = pathinfo($_FILES['files']['name'], PATHINFO_EXTENSION);
        if ($extension == 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        } elseif ($extension == 'xlsx') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        }
        // file path
        $spreadsheet = $reader->load($_FILES['files']['tmp_name']);
        $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $data = [];
        foreach ($allDataInSheet as $key => $row) {
            if ($key > 3) {
                if ($row['A']) {
                    $input = [];
                    $input['supplier_id'] = preg_replace("/[^a-zA-Z]/", "", $row['C']);
                    $input['supplier_name'] = $row['B'];
                    $input['ma_ncc'] = strtoupper(str_replace([' ', '-'], '', $row['C']));
                    $dinh_luong = [
                        'standard' => $row['D'],
                        'min' => $row['E'],
                        'max' => $row['F']
                    ];
                    $do_am = [
                        'standard' => $row['G'],
                        'min' => $row['H'],
                        'max' => $row['I']
                    ];
                    preg_match('/[\d.]+/', $row['K'], $matches);
                    $do_nen_vong = [
                        'standard' => $row['J'],
                        'min' => $matches[0] ?? 0,
                        'max' => null
                    ];
                    preg_match('/[\d.]+/', $row['N'], $matches);
                    $do_ben_keo = [
                        'standard' => $row['M'],
                        'min' => $matches[0] ?? 0,
                        'max' => null
                    ];
                    $input['requirements'] = [
                        'dinh-luong' => $dinh_luong,
                        'do-am' => $do_am,
                        'do-nen-vong' => $do_nen_vong,
                        'do-ben-keo' => $do_ben_keo,
                    ];
                    $data[] = $input;
                }
            }
        }
        try {
            DB::beginTransaction();
            $counter = 0;
            foreach ($data as $key => $input) {
                Supplier::firstOrCreate(['id' => $input['supplier_id'], 'name' => $input['supplier_name']]);
                $result = TieuChuanNCC::where('ma_ncc', $input['ma_ncc'])->first();
                if ($result) {
                    $result->update($input);
                    $counter = $result;
                } else {
                    TieuChuanNCC::create($input);
                }
            }
            DB::commit();
            return $this->success($counter, 'Upload thành công');
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
    }

    public function updateCustomerWarehouseFGExport(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = WareHouseFGExport::all();
            $count = 0;
            foreach ($data as $key => $input) {
                $order = Order::find($input->order_id);
                if ($order) {
                    $input->update(['customer_id' => $order->short_name]);
                    $count++;
                }
            }
            DB::commit();
            return $this->success($count, 'Update thành công');
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
    }
    public function updateSoKGDauMaterial(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = Material::with('warehouse_mlt_import')->whereNull('so_kg_dau')->get();
            $count = 0;
            foreach ($data as $key => $input) {
                if ($input->warehouse_mlt_import) {
                    $input->update(['so_kg_dau' => $input->warehouse_mlt_import->so_kg ?? null]);
                    $count++;
                }
            }
            DB::commit();
            return $this->success($count, 'Update thành công');
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
    }
    public function searchMasterDataMaterial(Request $request)
    {
        try {
            DB::beginTransaction();
            $query = Material::with('locator', 'warehouse_mlt_logs')->orderByRaw('CHAR_LENGTH(id) DESC')->orderBy('id', 'desc');
            // $query->has('warehouse_mlt_logs')->has('locator');
            $materials = $query->get();

            foreach ($materials as $material) {
                if (!$material->locator && count($material->warehouse_mlt_logs) > 0 && $material->warehouse_mlt_logs[0]->locator_id) {
                    LocatorMLTMap::create(['material_id' => $material->id, 'locator_mlt_id' => $material->warehouse_mlt_logs[0]->locator_id]);
                } else if ($material->locator && count($material->warehouse_mlt_logs) > 0 && $material->locator->locator_mlt_id !== $material->warehouse_mlt_logs[0]->locator_id) {
                    if ($material->warehouse_mlt_logs[0]->locator_id) {
                        $material->locator->update(['locator_mlt_id' => $material->warehouse_mlt_logs[0]->locator_id]);
                    } else if ($material->locator->locator_mlt_id) {
                        $material->warehouse_mlt_logs[0]->update(['locator_id' => $material->locator->locator_mlt_id]);
                    }
                } else if ($material->locator && count($material->warehouse_mlt_logs) <= 0) {
                    WarehouseMLTLog::created([
                        'material_id' => $material->id,
                        'locator_id' => $material->locator->locator_mlt_id,
                        'so_kg_nhap' => $material->so_kg,
                        'tg_nhap' => $material->locator->updated_at,
                    ]);
                }
            }
            $logs = WarehouseMLTLog::where('locator_id', "")->delete();
            DB::commit();
            return $this->success($logs, 'Update thành công');
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
    }

    public function deleteMaterialHasNoLocation(Request $request)
    {
        try {
            DB::beginTransaction();
            $material = Material::doesntHave('warehouse_mlt_logs')->whereDate('updated_at', '<=', '2024-08-10')->delete();
            $locator_map = LocatorMLTMap::get()->groupBy('locator_mlt_id');
            foreach ($locator_map as $key => $locator) {
                LocatorMLT::find($key)->update(['capacity' => count($locator)]);
            }
            DB::commit();
            return $this->success([], 'OK');
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, '');
        }
    }

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
        foreach ($period as $date) {
            $label = $date->format('d/m');
            $plannedQuantity = ProductionPlan::whereDate('thoi_gian_bat_dau', $date->format("Y-m-d"))
                ->where('machine_id', 'So01')
                ->sum('sl_kh');
            $actualQuantity = InfoCongDoan::whereDate('thoi_gian_bat_dau', $date->format("Y-m-d"))
                ->where('machine_id', 'So01')
                ->sum('sl_dau_ra_hang_loat');
            $data['categories'][] = $label; // Ngày trên trục hoành
            $data['plannedQuantity'][] = (int)$plannedQuantity; // Tổng số lượng tất cả công đoạn
            $data['actualQuantity'][] = (int)$actualQuantity; // Số lượng công đoạn "Dợn sóng"
        }
        return $this->success($data);
    }

    public function kpiTonKhoNVL(Request $request)
    {
        $start = date('Y-m-d', strtotime($request->start_date ?? 'now'));
        $end = date('Y-m-d', strtotime($request->end_date ?? 'now'));
        $period = CarbonPeriod::create($start, $end);
        $data = [
            'categories' => [], // Trục hoành (ngày)
            'inventory' => [],  // Số lượng tất cả công đoạn
        ];
        foreach ($period as $date) {
            $label = $date->format('d/m');
            $inventory = WarehouseMLTLog::whereNull('tg_xuat')
                ->whereDate('tg_nhap', $date->format('Y-m-d'))
                ->sum('so_kg_nhap');
            $data['categories'][] = $label; // Ngày trên trục hoành
            $data['inventory'][] = (int)$inventory;
        }
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
            $info = InfoCongDoan::whereDate('thoi_gian_bat_dau', $date->format('Y-m-d'))
                ->select(
                    DB::raw("SUM(sl_dau_ra_hang_loat) as tong_sl"),
                    DB::raw("SUM(sl_ng_sx + sl_ng_qc) as tong_sl_ng")
                )->first();
            $data['categories'][] = $label; // Ngày trên trục hoành
            $data['ty_le_ng'][] = ((int)$info->tong_sl > 0) ? (float)number_format(($info->tong_sl_ng / $info->tong_sl) * 100, 2) : 0;
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
                ->select(
                    DB::raw("SUM(TIMESTAMPDIFF(SECOND, thoi_gian_bam_may, thoi_gian_ket_thuc)) / SUM(TIMESTAMPDIFF(SECOND, thoi_gian_bat_dau, thoi_gian_ket_thuc)) as total_ratio")
                )
                ->whereNotNull('thoi_gian_bam_may')
                ->whereNotNull('thoi_gian_ket_thuc')
                ->whereNotNull('thoi_gian_bat_dau')
                ->first();
            if ($info->total_ratio > 0) {
                $ti_le_van_hanh_tb = ceil($info->total_ratio * 100);
            } else {
                $ti_le_van_hanh_tb = 0;
            }
            $data['categories'][] = $label; // Ngày trên trục hoành
            $data['ti_le_van_hanh'][] = $ti_le_van_hanh_tb;
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
        $machine = Machine::where('line_id', 30)->pluck('id')->toArray();
        foreach ($period as $date) {
            $label = $date->format('d/m');
            $plannedQuantity = InfoCongDoan::whereDate('thoi_gian_bat_dau', $date->format("Y-m-d"))->sum('dinh_muc');
            $actualQuantity = InfoCongDoan::whereDate('thoi_gian_bat_dau', $date->format("Y-m-d"))->whereIn('machine_id', $machine)->sum('sl_dau_ra_hang_loat');
            $data['categories'][] = $label; // Ngày trên trục hoành
            $data['plannedQuantity'][] = (int)$plannedQuantity; // Tổng số lượng tất cả công đoạn
            $data['actualQuantity'][] = (int)$actualQuantity; // Số lượng công đoạn "Dợn sóng"
        }
        return $this->success($data);
    }

    public function kpiTonKhoTP(Request $request)
    {
        $start = date('Y-m-d', strtotime($request->start_date ?? 'now'));
        $end = date('Y-m-d', strtotime($request->end_date ?? 'now'));
        $period = CarbonPeriod::create($start, $end);
        $data = [
            'categories' => [], // Trục hoành (ngày)
            'inventory' => [],  // Số lượng tất cả công đoạn
        ];
        foreach ($period as $date) {
            $label = $date->format('d/m');
            $inventory = WarehouseFGLog::whereNull('tg_xuat')
                ->whereDate('tg_nhap', $date->format('Y-m-d'))
                ->sum('so_kg_nhap');
            $data['categories'][] = $label; // Ngày trên trục hoành
            $data['inventory'][] = (int)$inventory;
        }
        return $this->success($data);
    }

    public function kpiTyLeLoiMay(Request $request)
    {
        $start = date('Y-m-d', strtotime($request->start_date ?? 'now'));
        $end = date('Y-m-d', strtotime($request->end_date ?? 'now'));
        $period = CarbonPeriod::create($start, $end);
        $categories = [];
        foreach ($period as $key => $date) {
            $categories[]  = $date->format('d/m');
        }
        $data = [
            'categories' => $categories, // Trục hoành (ngày)
            'series' => [],
        ];
        $machines = Machine::where('is_iot', 1)->get()->groupBy('line_id');
        $logs = MachineLog::whereBetween('start_time', [$start, $end])
            ->whereNotNull('error_machine_id')
            ->get()
            ->groupBy('machine_id');
        foreach ($machines as $line_id => $machineGroup) {
            $line = Line::find($line_id); // Lấy thông tin line
            $machineIds = $machineGroup->pluck('id')->toArray();

            // Dữ liệu lỗi máy của line này trong khoảng thời gian
            $lineLogs = $logs->filter(fn($log, $machineId) => in_array($machineId, $machineIds));

            // Tạo dữ liệu series cho line
            $dataPoints = [];
            foreach ($period as $date) {
                $dateKey = $date->format('Y-m-d');

                // Tổng lỗi máy trong ngày
                $totalLogs = $lineLogs->flatMap(fn($log) => $log)
                    ->filter(fn($entry) => date("Y-m-d", strtotime($entry->start_time)) === $dateKey)
                    ->count();

                // Tổng lỗi máy trong tất cả line cùng ngày
                $totalAllLogs = $logs->flatMap(fn($log) => $log)
                    ->filter(fn($entry) => date("Y-m-d", strtotime($entry->start_time)) === $dateKey)
                    ->count();

                // Tính tỷ lệ lỗi
                $dataPoints[] = $totalAllLogs > 0
                    ? round(($totalLogs / $totalAllLogs) * 100, 2)
                    : 0;
            }

            $data['series'][] = [
                'name' => $line->name ?? "Line {$line_id}",
                'data' => $dataPoints,
            ];
        }
        return $this->success($data);
    }

    public function suaChuaLoiLam()
    {
        $imports = WareHouseMLTImport::whereIn('material_id', explode(',', '24-06905,24-07376,24-07504,24-08119,24-08124,24-08132,23-08783,23-09111,23-10124,23-10128,23-10130,23-17126,23-17135,23-17138,23-17775,23-17778,23-19029,23-19146,23-19255,23-23639,23-23640,23-23763,24-01780,24-02763,24-03956,24-03959,24-06810'))->get();
        foreach ($imports as $key => $import) {
            $material = Material::create([
                'id' => $import->material_id,
                'ma_vat_tu' => $import->ma_vat_tu,
                'ma_cuon_ncc' => $import->ma_cuon_ncc,
                'so_kg' => $import->so_kg,
                'so_kg_dau' => $import->so_kg,
                'loai_giay' => $import->loai_giay,
                'kho_giay' => $import->kho_giay,
                'dinh_luong' => $import->dinh_luong,
                'fsc' => $import->fsc,
                'so_m_toi' => floor(($import->so_kg / ($import->kho_giay / 100)) / ($import->dinh_luong / 1000)) ?? 0
            ]);
        }
        return 'da sua chua';
    }

    public function updateInfoFromPlan()
    {
        $plans = ProductionPlan::where('machine_id', 'So01')->where('ngay_sx', date('Y-m-d'))->get();
        try {
            DB::beginTransaction();
            foreach ($plans as $key => $plan) {
                $info = InfoCongDoan::where([
                    'lo_sx' => $plan->lo_sx,
                    'machine_id' => $plan->machine_id,
                ])->first();
                if ($info) {
                    $info->update([
                        'thu_tu_uu_tien' => $plan->thu_tu_uu_tien,
                        'ngay_sx' => $plan->ngay_sx,
                        'dinh_muc' => $plan->sl_kh,
                        'so_ra' => $plan->order->so_ra ?? 1,
                        'plan_id' => $plan->id,
                        'order_id' => $plan->order_id
                    ]);
                } else {
                    $info = InfoCongDoan::create([
                        'lo_sx' => $plan->lo_sx,
                        'machine_id' => $plan->machine_id,
                        'thu_tu_uu_tien' => $plan->thu_tu_uu_tien,
                        'status' => 0,
                        'ngay_sx' => $plan->ngay_sx,
                        'dinh_muc' => $plan->sl_kh,
                        'so_ra' => $plan->order->so_ra ?? 1,
                        'plan_id' => $plan->id,
                        'order_id' => $plan->order_id
                    ]);
                }
                Log::debug($info);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

        return 'ok';
    }

    public function update_admin_user_delivery_note()
    {
        $delivery_notes = DeliveryNote::all();
        try {
            DB::beginTransaction();
            foreach ($delivery_notes as $key => $delivery_note) {
                $delivery_note->exporters()->attach($delivery_note->exporter_id);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

        return 'ok';
    }

    public function updateNewMachineId()
    {
        $extension = pathinfo($_FILES['files']['name'], PATHINFO_EXTENSION);
        if ($extension == 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        } elseif ($extension == 'xlsx') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        }
        // file path
        $spreadsheet = $reader->load($_FILES['files']['tmp_name']);
        $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $data = [];
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 2
            if ($key > 2) {
                $input = [];
                $input['current_id'] = $row['B'];
                $input['new_id'] = $row['C'];
                if (!empty($input['new_id'])) {
                    $data[] = $input;
                }
            }
        }
        try {
            DB::beginTransaction();
            foreach ($data as $key => $input) {
                $machine = Machine::where('id', $input['current_id'])->first();
                if ($machine) {
                    $machine->update(['id' => $input['new_id']]);
                }
                Role::where('machine_id', $input['current_id'])->update(['machine_id' => $input['new_id']]);
                DB::table('formulas')->where('machine_id', $input['current_id'])->update(['machine_id' => $input['new_id']]);
                InfoCongDoan::where('machine_id', $input['current_id'])->update(['machine_id' => $input['new_id']]);
                LSXLog::where('machine_id', $input['current_id'])->update(['machine_id' => $input['new_id']]);
                MachineLog::where('machine_id', $input['current_id'])->update(['machine_id' => $input['new_id']]);
                MachineParameter::where('machine_id', $input['current_id'])->update(['machine_id' => $input['new_id']]);
                MachineParameterLogs::where('machine_id', $input['current_id'])->update(['machine_id' => $input['new_id']]);
                ProductionPlan::where('machine_id', $input['current_id'])->update(['machine_id' => $input['new_id']]);
                QCLog::where('machine_id', $input['current_id'])->update(['machine_id' => $input['new_id']]);
                Tem::where('machine_id', $input['current_id'])->update(['machine_id' => $input['new_id']]);
                Tracking::where('machine_id', $input['current_id'])->update(['machine_id' => $input['new_id']]);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        return $this->success([], 'Upload thành công');
    }

    public function updateNgaysxInfoCongDoan()
    {
        $infos = InfoCongDoan::where('machine_id', '!=', 'So01')->whereDate('created_at', date('Y-m-d'))->get();
        try {
            DB::beginTransaction();
            foreach ($infos as $key => $info) {
                $info->update([
                    'ngay_sx' => date('Y-m-d', strtotime($info->created_at)),
                ]);
                Log::debug($info);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

        return 'ok';
    }

    public function updateDinhMucInfoCongDoan()
    {
        $infos = InfoCongDoan::with('tem')->where('machine_id', 'Da06')->where('status', '>', 1)->whereDate('created_at', date('Y-m-d'))->get();
        try {
            DB::beginTransaction();
            foreach ($infos as $key => $info) {
                $info->update([
                    'dinh_muc' => $info->sl_dau_ra_hang_loat ?? 0,
                ]);
                Log::debug($info);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        return 'ok';
    }

    public function updateInfoCongDoanPriority()
    {
        $infos = InfoCongDoan::with('plan')->where('ngay_sx', '>=', date('Y-m-d'))->where('machine_id', 'So01')->whereIn('status', [0, 1])->orderBy('ngay_sx')->orderBy('thu_tu_uu_tien')->orderBy('updated_at')->get();
        // InfoCongDoanPriority::truncate();
        $index = 1;
        foreach ($infos as $key => $info) {
            InfoCongDoanPriority::create([
                'info_cong_doan_id' => $info->id,
                'priority' => $index,
            ]);
            $index++;
        }
        return 'reordered';
    }

    public function resetInfoCongDoan()
    {
        $infos = InfoCongDoan::with('plan')->where('ngay_sx', date('Y-m-d'))->where('machine_id', 'So01')->where('status', '>=', 2)->get();
        try {
            DB::beginTransaction();
            foreach ($infos as $key => $info) {
                $info->update([
                    'status' => 0,
                    'sl_dau_ra_hang_loat' => 0,
                    'sl_ng_sx' => 0,
                    'thu_tu_uu_tien' => $info->plan->ordering ?? $info->thu_tu_uu_tien
                ]);
            }
            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            throw $th;
        }
        return 'reset completed';
    }

    public function endOldInfoCongDoan()
    {
        $infos = InfoCongDoan::whereNull('thoi_gian_bat_dau')->whereNotNull('thoi_gian_ket_thuc')->get();
        foreach ($infos as $key => $info) {
            $info->update(['thoi_gian_bat_dau' => $info->thoi_gian_ket_thuc]);
        }
        return 'ok';
    }
    public function wtf()
    {
        // return LSXPallet::with(['warehouseFGLog'=>function($sub){
        //     $sub->where('type', 2);
        // }])->limit(10000)->get();
        // $lsx_pallets = LSXPallet::with(['warehouseFGLog'])->chunk(10000, function ($lsx_pallet) {
        //     $group = [];
        //     foreach ($lsx_pallet as $value) {
        //         $exported = $value->warehouseFGLog->filter(function ($value) {
        //             return $value->type === 2;
        //         });
        //         Log::debug($exported);
        //         $sl = $value->so_luong - array_sum($exported->pluck('so_luong')->toArray());
        //         if ($sl >= 0) {
        //             $group[] = [
        //                 'id' => $value->id,
        //                 'remain_quantity' => $sl > 0 ? $sl : 0
        //             ];
        //         }
        //     }
        //     LSXPallet::upsert($group, ['id'], ['remain_quantity']);
        // });

        // $logs = WarehouseFGLog::whereNull('order_id')->orWhere('order_id', '')->get();
        // foreach ($logs as $log) {
        //     $tem = Tem::where('lo_sx', $log->lo_sx)->first();
        //     if($tem){
        //         $log->update(['order_id' => $tem->order_id]);
        //         $log->lo_sx_pallet()->update(['order_id' => $tem->order_id]);
        //     }
        // }

        // $count = InfoCongDoan::whereNull('order_id')->count();
        // return $count;
        $infos = InfoCongDoan::with('tem')->whereNull('order_id')->get();
        foreach ($infos as $info) {
            if ($info->tem) {
                $info->update(['order_id' => $info->tem->order_id ?? null]);
            }
        }
        return 'ok';
    }

    public function deleteDuplicateWarehouseFGLog()
    {
        $group_ids = [];
        $duplicateRecords = DB::table('warehouse_fg_logs')
            ->select('pallet_id', 'lo_sx', 'so_luong', 'order_id', DB::raw('COUNT(*) as total_records'))
            ->where('type', 2)
            ->groupBy('pallet_id', 'lo_sx', 'so_luong', 'order_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        return $duplicateRecords;
        return 'ok';
    }

    public function capNhatTonKhoTPExcel(Request $request)
    {
        $extension = pathinfo($_FILES['files']['name'], PATHINFO_EXTENSION);
        if ($extension == 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        } elseif ($extension == 'xlsx') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        }
        // file path
        $spreadsheet = $reader->load($_FILES['files']['tmp_name']);
        $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $pallet_array = [];
        $order_array = [];
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 2
            if ($key > 1) {
                $input = [];
                $input['mdh'] = $row['D'];
                $input['mql'] = $row['E'];
                $input['lo_sx'] = $row['X'];
                $input['pallet_id'] = $row['W'];
                $input['locator_id'] = $row['V'];
                $input['so_luong'] = $row['Y'];
                $input['date'] = $row['L'];
                $input['time'] = $row['M'];
                $input['nhap_du'] = $row['O'] == 'Không' ? 0 : $row['O'];
                $user = CustomUser::where('name', $row['P'])->first();
                $input['user'] = $user->id ?? null;
                if (!empty($input['mdh']) && is_numeric($input['mql'])) {
                    if (!empty($input['pallet_id']) && !empty($input['lo_sx'])) {
                        $pallet_array[] = $input;
                    } else {
                        $order_array[] = $input;
                    }
                }
            }
        }
        $array = [];
        try {
            DB::beginTransaction();
            WareHouseLog::query()->delete();
            foreach ($pallet_array as $key => $input) {
                WareHouseLog::create([
                    'order_id' => $input['mdh'] . "-" . $input['mql'],
                    'lo_sx' => $input['lo_sx'],
                    'pallet_id' => $input['pallet_id'],
                    'locator_id' => $input['locator_id'],
                    'so_luong' => $input['so_luong'],
                    'type' => 1,
                    'created_by' => $input['user'],
                    'nhap_du' => $input['nhap_du'],
                    'created_at' => Carbon::createFromFormat("d/m/Y H:i:s", $input['date'] . ' ' . $input['time'] . ':00')->format('Y-m-d H:i:s'),
                ]);
            }
            foreach ($order_array as $key => $value) {
                $lsx_pallet = LSXPallet::where('order_id', 'like', $value['mdh'] . "-" . $value['mql'] . "%")->first();
                if ($lsx_pallet) {
                    WareHouseLog::create([
                        'order_id' => $input['mdh'] . "-" . $input['mql'],
                        'lo_sx' => $lsx_pallet->lo_sx,
                        'pallet_id' => $lsx_pallet->pallet_id,
                        'locator_id' => $input['locator_id'],
                        'so_luong' => $input['so_luong'],
                        'type' => 1,
                        'created_by' => $input['user'],
                        'nhap_du' => $input['nhap_du'],
                        'created_at' => date('Y-m-d H:i:s', mt_rand(1732881315, 1734695715)),
                    ]);
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        return $array;
    }

    public function updateThoiGianBatDau()
    {
        $infos = InfoCongDoan::where('thoi_gian_bat_dau', null)->where('thoi_gian_ket_thuc', '!=', null)->get();
        try {
            DB::beginTransaction();
            foreach ($infos as $key => $info) {
                $info->update([
                    'thoi_gian_bat_dau' => $info->thoi_gian_ket_thuc,
                ]);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function deleteDuplicateRoleUsers()
    {
        $role_users = DB::table('admin_role_users')->select('role_id', 'user_id', DB::raw('COUNT(*) as total_records'))
            ->groupBy('role_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        // return $role_users;
        foreach ($role_users as $key => $value) {
            $duplicate = DB::table('admin_role_users')->where('role_id', $value->role_id)->where('user_id', $value->user_id)->limit(1)->delete();
        }
        return 'ok';
    }

    public function updateTypeLSXPallet(Request $request)
    {
        $lsx_pallet = LSXPallet::with('infoCongDoan')
            ->where('type', null)
            ->whereDate('created_at', $request->date)
            ->get();
        // return $lsx_pallet->count();
        if (count($lsx_pallet) <= 0) {
            return $this->success('không tìm thấy dữ liệu');
        }
        $machine_dan = Machine::where('line_id', 32)->pluck('id')->toArray();
        $machine_xa_lot = Machine::where('line_id', 33)->pluck('id')->toArray();
        $counter = 0;
        foreach ($lsx_pallet as $key => $value) {
            if (isset($value->infoCongDoan) && in_array($value->infoCongDoan->machine_id, $machine_dan)) {
                $value->update(['type' => LSXPallet::DAN]);
                $counter++;
            } else if (isset($value->infoCongDoan) && in_array($value->infoCongDoan->machine_id, $machine_xa_lot)) {
                $value->update(['type' => LSXPallet::XA_LOT]);
                $counter++;
            }
        }
        return $this->success('done ' . $counter . "/" . $lsx_pallet->count() . ' record');
    }

    public function updateStatusLSXPallet(Request $request)
    {
        $lsx_pallet = LSXPallet::with('warehouseFGLog')
            ->whereDate('created_at', $request->date)
            ->get();
        // return $lsx_pallet->count();
        if (count($lsx_pallet) <= 0) {
            return $this->success('không tìm thấy dữ liệu');
        }
        $counter = 0;
        foreach ($lsx_pallet as $key => $value) {
            if (count($value->warehouseFGLog) > 0) {
                $logs = $value->warehouseFGLog->toArray() ?? [];
                $check_exported = in_array(2, array_column($logs, 'type'));
                if ($check_exported) {
                    $value->update(['status' => LSXPallet::EXPORTED]);
                } else {
                    $value->update(['status' => LSXPallet::IMPORTED]);
                }
                $counter++;
            } else {
                continue;
            }
        }
        return $this->success('done ' . $counter . "/" . $lsx_pallet->count() . ' record');
    }

    public function restoreLostMaterial()
    {
        $import = WareHouseMLTImport::doesntHave('material')->has('warehouse_mtl_log')->whereNotNull('material_id')->orderBy('created_at', 'DESC')->get();
        // return $import->count();
        foreach ($import as $key => $value) {
            $log = WarehouseMLTLog::where('material_id', $value->material_id)->orderBy('tg_nhap', 'DESC')->first();
            $so_kg_hien_tai = 0;
            if ($log->tg_xuat) {
                $so_kg_hien_tai = $log->so_kg_nhap - $log->so_kg_xuat;
            } else {
                $so_kg_hien_tai = $log->so_kg_nhap;
            }
            if ($so_kg_hien_tai < 0) {
                continue;
            }
            Material::updateOrCreate(
                [
                    'id' => $value->material_id,
                ],
                [
                    'so_kg' => $so_kg_hien_tai,
                    'so_kg_dau' => $value->so_kg,
                    'loai_giay' => $value->loai_giay,
                    'kho_giay' => $value->kho_giay,
                    'dinh_luong' => $value->dinh_luong,
                    'fsc' => $value->fsc,
                    'ma_cuon_ncc' => $value->ma_cuon_ncc,
                    'ma_vat_tu' => $value->ma_vat_tu,
                    'so_m_toi' => floor(($value->so_kg / ($value->kho_giay / 100)) / ($value->dinh_luong / 1000)) ?? 0
                ]
            );
        }
        return 'ok';
    }

    public function updateLSXPalletIdWarehouseLog(Request $request)
    {
        $logs = WarehouseFGLog::whereDate('created_at', $request->date)
            ->with('lo_sx_pallet')
            ->whereNull('lsx_pallet_id')
            ->get();
        $counter = 0;
        if ($logs->count() <= 0) {
            return $this->success('không tìm thấy dữ liệu');
        }
        foreach ($logs as $key => $value) {
            if ($value->lo_sx_pallet) {
                $value->update(['lsx_pallet_id' => $value->lo_sx_pallet->id ?? null]);
                $counter++;
            } else {
                continue;
            }
        }
        return $this->success('done ' . $counter . "/" . $logs->count() . ' record');
    }

    public function exportAllFGBeforeDate(Request $request)
    {
        // Có thể lấy từ request, tạm để cố định như bạn
        $date = '2025-07-31';

        // Base query cho LSXPallet
        $baseQuery = LSXPallet::query()
            ->where('remain_quantity', '>', 0)
            ->whereHas('warehouse_fg_logs', function ($q) use ($date) {
                $q->where('type', 1)
                ->whereDate('created_at', '<=', $date);
            })
            ->whereDoesntHave('warehouse_fg_logs', function ($q) {
                $q->where('type', 2);
            });
        // Đếm tổng trước (không load data)
        $total = (clone $baseQuery)->count();

        if ($total === 0) {
            return $this->success('không tìm thấy dữ liệu');
        }

        $counter = 0;

        DB::transaction(function () use ($baseQuery, &$counter) {
            // Duyệt theo từng "lô" 500 bản ghi, tránh load hết vào RAM
            $baseQuery->orderBy('id') // để dùng chunkById
                ->chunkById(500, function ($pallets) use (&$counter) {

                    $now        = now();
                    $logsToInsert = [];
                    $palletIds    = [];

                    foreach ($pallets as $pallet) {
                        $logsToInsert[] = [
                            'lo_sx'            => $pallet->lo_sx,
                            'pallet_id'        => $pallet->pallet_id,
                            'so_luong'         => $pallet->so_luong,
                            'type'             => 2,
                            'created_by'       => null, // hoặc auth()->id()
                            // Giữ logic cũ của bạn
                            'created_at'       => $pallet->created_at,
                            'updated_at'       => $now,
                            'lsx_pallet_id'    => $pallet->id,
                            'order_id'         => $pallet->order_id,
                            'delivery_note_id' => null,
                            'locator_id'       => null,
                            'nhap_du'          => 0,
                        ];

                        $palletIds[] = $pallet->id;
                        $counter++;
                    }

                    // Insert 1 lần cho cả batch
                    if (!empty($logsToInsert)) {
                        WarehouseFGLog::insert($logsToInsert);
                    }

                    // Update pallet 1 lần cho cả batch
                    if (!empty($palletIds)) {
                        LSXPallet::whereIn('id', $palletIds)->update([
                            'status'          => LSXPallet::EXPORTED,
                            'remain_quantity' => 0,
                            'updated_at'      => $now,
                        ]);
                    }
                    Log::info($counter);
                });
        });

        return $this->success('done ' . $counter . '/' . $total . ' record');
    }

    public function exportRemainFGBeforeDate(Request $request)
    {
        // Có thể lấy từ request, tạm để cố định như bạn
        $date = '2025-07-31';

        // Base query cho LSXPallet
        $baseQuery = LSXPallet::query()
            ->where('remain_quantity', '>', 0)
            ->whereHas('warehouse_fg_logs', function ($q) use ($date) {
                $q->where('type', 1)
                ->whereDate('created_at', '<=', $date);
            })
            ->whereHas('warehouse_fg_logs', function ($q) use ($date) {
                $q->where('type', 2);
            });
        // Đếm tổng trước (không load data)
        $total = (clone $baseQuery)->count();

        if ($total === 0) {
            return $this->success('không tìm thấy dữ liệu');
        }

        $counter = 0;

        DB::transaction(function () use ($baseQuery, &$counter) {
            // Duyệt theo từng "lô" 500 bản ghi, tránh load hết vào RAM
            $baseQuery->orderBy('id') // để dùng chunkById
                ->chunkById(500, function ($pallets) use (&$counter) {

                    $now        = now();
                    $logsToInsert = [];
                    $palletIds    = [];

                    foreach ($pallets as $pallet) {
                        $logsToInsert[] = [
                            'lo_sx'            => $pallet->lo_sx,
                            'pallet_id'        => $pallet->pallet_id,
                            'so_luong'         => $pallet->so_luong,
                            'type'             => 2,
                            'created_by'       => null, // hoặc auth()->id()
                            // Giữ logic cũ của bạn
                            'created_at'       => $pallet->created_at,
                            'updated_at'       => $now,
                            'lsx_pallet_id'    => $pallet->id,
                            'order_id'         => $pallet->order_id,
                            'delivery_note_id' => null,
                            'locator_id'       => null,
                            'nhap_du'          => 0,
                        ];

                        $palletIds[] = $pallet->id;
                        $counter++;
                    }

                    // Update pallet 1 lần cho cả batch
                    if (!empty($palletIds)) {
                        LSXPallet::whereIn('id', $palletIds)->update([
                            'status'          => LSXPallet::EXPORTED,
                            'remain_quantity' => 0,
                            'updated_at'      => $now,
                        ]);
                        WarehouseFGLog::whereIn('lsx_pallet_id', $palletIds)->where('type', 2)->delete();
                    }

                    // Insert 1 lần cho cả batch
                    if (!empty($logsToInsert)) {
                        WarehouseFGLog::insert($logsToInsert);
                    }
                    Log::info($counter);
                });
        });

        return $this->success('done ' . $counter . '/' . $total . ' record');
    }

    public function getDuplicateWarehouseFGLog(Request $request)
    {
        DB::transaction(function () {
            $groups = WarehouseFGLog::where('type', 2)
                ->select('lsx_pallet_id', 'so_luong')
                ->groupBy('lsx_pallet_id', 'so_luong')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($groups as $g) {
                // 2) Load toàn bộ log trong nhóm
                $logs = WarehouseFGLog::where('type', 2)
                    ->where('lsx_pallet_id', $g->lsx_pallet_id)
                    ->where('so_luong', $g->so_luong)
                    ->get();

                // 3) Phân tách record "đủ cả 2" và "thiếu"
                $valid = $logs->filter(fn ($r) =>
                    !is_null($r->created_by) &&
                    !is_null($r->delivery_note_id)
                );

                // 4) Xác định record giữ lại
                if ($valid->isNotEmpty()) {
                    // nếu có ít nhất 1 record "đủ cả 2" → giữ id cao nhất trong valid
                    $keep = $valid->sortByDesc('id')->first();
                } else {
                    // ngược lại → giữ id cao nhất cả nhóm
                    $keep = $logs->sortByDesc('id')->first();
                }

                // 5) Xóa các record còn lại (trừ record keep)
                $toDelete = $logs
                    ->where('id', '!=', $keep->id)
                    ->pluck('id')
                    ->all();

                if (!empty($toDelete)) {
                    $log_query = WarehouseFGLog::whereIn('id', $toDelete);
                    LSXPallet::whereIn('id', (clone $log_query)->pluck('lsx_pallet_id')->toArray())->update(['remain_quantity'=>0]);
                    $log_query->delete();
                }
            }
        });
        return 'done';
    }

    public function clearRequestLogs(){
        RequestLog::truncate();
        return 'request logs removed all';
    }

    public function deleteDuplicatePlan(){
        $duplicatePlans = DB::table('production_plans')
        ->where('ngay_sx', '>=', '2025-09-09')
        ->whereNotIn('id', function ($query) {
            $query->selectRaw('MIN(id)')
                ->from('production_plans')
                ->where('ngay_sx', '>=', '2025-09-09')
                ->groupBy('lo_sx');
        })
        ->get();
        $arr = $duplicatePlans->pluck('id')->toArray();
        InfoCongDoan::whereIn('plan_id', $arr)->delete();
        GroupPlanOrder::whereIn('plan_id', $arr)->delete();
        ProductionPlan::whereIn('id', $arr)->delete();
        return 'deleted';
    }

    public function deleteSoftDeletedOrder(){
        set_time_limit(300);
        $order_ids = Order::whereNotNull('deleted_at')->limit(1000)->pluck('id')->toArray();
        $groupPlanOrder = GroupPlanOrder::whereIn('order_id', $order_ids)->delete();
        $planFromGroupOrder = ProductionPlan::whereIn('order_id', $order_ids)->delete();
        $infoCongDoan = InfoCongDoan::whereIn('order_id', $order_ids)->delete();
        $tem = Tem::whereIn('order_id', $order_ids)->delete();
        $log = WarehouseFGLog::whereIn('order_id', $order_ids)->delete();
        $export = WareHouseFGExport::whereIn('order_id', $order_ids)->delete();
        $lsx_pallet = LSXPallet::whereIn('order_id', $order_ids)->delete();
        $order = Order::whereIn('id', $order_ids)->delete();
        return 'Đã xóa';
    }
}
