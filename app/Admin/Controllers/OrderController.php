<?php

namespace App\Admin\Controllers;

use App\Models\Customer;
use App\Models\CustomerShort;
use App\Models\DRC;
use App\Models\ErrorLog;
use App\Models\Field;
use App\Models\FieldRole;
use App\Models\GroupPlanOrder;
use App\Models\KhuonLink;
use App\Models\LocatorMLTMap;
use App\Models\LSXPallet;
use App\Models\Material;
use App\Models\Order;
use App\Models\ProductionPlan;
use App\Models\Table;
use App\Models\WareHouse;
use App\Models\WarehouseFGLog;
use App\Models\WarehouseMLTLog;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Error;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends AdminController
{
    use API;

    public function updateData(Request $request)
    {
        $records = WarehouseFGLog::all();
        try {
            DB::beginTransaction();
            foreach ($records as $key => $record) {
                $data = LSXPallet::where('lo_sx', $record->lo_sx)->first();
                if ($data && $data->order_id) {
                    $record->update(['order_id' => $data->order_id]);
                }
            }
            DB::commit();
            return $this->success('Cập nhật thành công');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->failure('Cập nhật không thành công');
        }
    }

    public function fixBug(Request $request)
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
        Material::truncate();
        WarehouseMLTLog::truncate();
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 4
            if ($key > 8) {
                $inp['id'] = $row['B'];
                $inp['loai_giay'] = $row['D'];
                $inp['fsc'] = $row['E'] == 'X' ? 1 : 0;
                $inp['kho_giay'] = $row['F'];
                $inp['dinh_luong'] = $row['G'];
                $inp['so_kg'] = (int)str_replace(',', '', $row['Q']);
                $inp['so_kg_dau'] = (int)str_replace(',', '', $row['I']);
                $inp['ma_cuon_ncc'] = $row['M'];
                $inp['ma_vat_tu'] = $row['D'] . '(' . $row['G'] . ')' . $row['F'];;
                Material::create($inp);
                $input['material_id'] = $row['B'];
                $input['so_kg_nhap'] = (int)str_replace(',', '', $row['N']);
                $input['tg_nhap'] = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $row['J'])));
                $input['importer_id'] = 1;
                WarehouseMLTLog::create($input);
            }
        }
        return $this->success([], 'Upload thành công');
    }
    public function updateRole(Request $request)
    {
        $fields = Field::whereNotIn('id', ['25', '26'])->get();
        foreach ($fields as $key => $field) {
            $inp['table_id'] = 1;
            $inp['field_id'] = $field->id;
            $inp['role_id'] = 37;
            FieldRole::create($inp);
        }
        return true;
    }

    public function getOrders(Request $request)
    {
        $query = Order::with(['customer_specifications.drc', 'group_plan_order.plan', 'creator:id,name'])->orderBy('mdh', 'ASC')->orderBy('mql', 'ASC');
        if(isset($request->status)){
            if($request->status === 'all'){
                $query->withTrashed();
            }else if($request->status === 'deleted'){
                $query->onlyTrashed();
            }
        }
        if (isset($request->customer_id)) {
            $query->where('customer_id', 'like', "%" . $request->customer_id . "%");
        }
        if (isset($request->short_name)) {
            $query->where('short_name', 'like', "%" . $request->short_name . "%");
        }
        if (isset($request->ngay_dat_hang)) {
            $query->whereDate('ngay_dat_hang', date('Y-m-d', strtotime($request->ngay_dat_hang)));
        }
        if (isset($request->start_date) && isset($request->end_date)) {
            $query->whereDate('ngay_dat_hang', '>=', date('Y-m-d', strtotime($request->start_date)))->whereDate('ngay_dat_hang', '<=', date('Y-m-d', strtotime($request->end_date)));
        }
        if (isset($request->han_giao_sx)) {
            $query->whereDate('han_giao_sx', date('Y-m-d', strtotime($request->han_giao_sx)));
        }
        if (isset($request->ngay_kh)) {
            $plan_ids = ProductionPlan::whereDate('thoi_gian_bat_dau', date('Y-m-d', strtotime($request->ngay_kh)))->pluck('id')->toArray();
            $order_ids = GroupPlanOrder::whereIn('plan_id', $plan_ids)->pluck('order_id')->toArray();
            $query->whereIn('id', $order_ids);
        }
        if (isset($request->mdh)) {
            if (is_array($request->mdh)) {
                $query->where(function ($custom_query) use ($request) {
                    foreach ($request->mdh as $mdh) {
                        $custom_query->orWhere('orders.mdh', 'like', "%$mdh%");
                    }
                });
            } else {
                $query->where('orders.mdh', 'like', "%$request->mdh%");
            }
        }
        if (isset($request->order)) {
            $query->where('order', 'like', "%$request->order%");
        }
        if (isset($request->mql)) {
            if (is_array($request->mql)) {
                $query->where(function ($custom_query) use ($request) {
                    foreach ($request->mql as $mql) {
                        $custom_query->orWhere('orders.mql', $mql);
                    }
                });
            } else {
                $query->where('orders.mql', $request->mql);
            }
        }
        if (isset($request->kich_thuoc)) {
            $query->where('kich_thuoc', 'like', "%$request->kich_thuoc%");
        }
        if (isset($request->length)) {
            $query->where('length', 'like', $request->length);
        }
        if (isset($request->width)) {
            $query->where('width', 'like', $request->width);
        }
        if (isset($request->height)) {
            $query->where('height', 'like', $request->height);
        }
        if (isset($request->po)) {
            $query->where('po', 'like', "%$request->po%");
        }
        if (isset($request->style)) {
            $query->where('style', 'like', "%$request->style%");
        }
        if (isset($request->style_no)) {
            $query->where('style_no', 'like', "%$request->style_no%");
        }
        if (isset($request->color)) {
            $query->where('color', 'like', "%$request->color%");
        }
        if (isset($request->item)) {
            $query->where('item', 'like', "%$request->item%");
        }
        if (isset($request->rm)) {
            $query->where('rm', 'like', "%$request->rm%");
        }
        if (isset($request->size)) {
            $query->where('size', 'like', "%$request->size%");
        }
        if (isset($request->note_2)) {
            $query->where('note_2', 'like', "%$request->note_2%");
        }
        if (isset($request->han_giao)) {
            $query->whereDate('han_giao', date('Y-m-d', strtotime($request->han_giao)));
        }
        if (isset($request->dot)) {
            $query->where('dot', $request->dot);
        }
        if (isset($request->tmo)) {
            $query->where('tmo', $request->tmo);
        }
        if (isset($request->xuong_giao)) {
            $query->where('xuong_giao', $request->xuong_giao);
        }
        $count = $query->count();
        $totalPage = $count;
        $role_ids = $request->user()->roles()->pluck('id')->toArray();
        $table = Table::where('table', 'orders')->first();
        $field_query = FieldRole::where('table_id', $table->id);
        if($request->user()->username !== 'admin'){
            $field_query->whereIn('role_id', $role_ids);
        }
        $field_ids = $field_query->pluck('field_id')->toArray();
        $fields = DB::table('fields')->whereIn('id', $field_ids)->pluck('field')->toArray();
        if (isset($request->page) && isset($request->pageSize)) {
            $page = $request->page - 1;
            $pageSize = $request->pageSize;
            $query->offset($page * $pageSize)->limit($pageSize ?? 10);
        }
        $records = $query->select('*', 'sl as sl_dinh_muc')->get();
        $res = [
            "data" => $records,
            "totalPage" => $totalPage,
            "editableColumns" => $fields,
        ];
        return $this->success($res);
    }
    public function updateOrders(Request $request)
    {
        $input = $request->all();
        $order = Order::where('id', $input['id'])->first();
        if ($order) {
            try {
                DB::beginTransaction();
                if (isset($input['kich_thuoc_chuan']) && $input['kich_thuoc_chuan']) {
                    $kich_thuoc_chuan = [];
                    $kich_thuoc_chuan = explode('*', $input['kich_thuoc_chuan']);
                    if (count($kich_thuoc_chuan) > 1) {
                        $input['dai'] = $this->updateKichThuoc($kich_thuoc_chuan[0], $input['unit']);
                        $input['rong'] = $this->updateKichThuoc($kich_thuoc_chuan[1], $input['unit']);
                        if (count($kich_thuoc_chuan) > 2) {
                            $input['cao'] = $this->updateKichThuoc($kich_thuoc_chuan[2], $input['unit']);
                        } else {
                            $input['cao'] = null;
                        }
                    }
                }
                if (isset($input['quy_cach_drc'])) {
                    $quy_cach = DRC::where('id', $input['quy_cach_drc'])->first();
                    if ($quy_cach) {
                        $ct_dai = str_replace('[D]', $input['dai'], $quy_cach->ct_dai);
                        $ct_rong = str_replace('[R]', $input['rong'], $quy_cach->ct_rong);
                        $input['dai'] = eval($ct_dai) ?? $input['dai'];
                        $input['rong'] = eval($ct_rong) ?? $input['rong'];
                        if (isset($input['cao'])) {
                            $ct_cao = str_replace('[C]', $input['cao'], $quy_cach->ct_cao);
                            $input['cao'] = eval($ct_cao) ?? $input['cao'];
                        }
                    }
                }
                $input['so_luong'] = $input['sl'];
                if (isset($input['dai']) && isset($input['rong']) && isset($input['so_luong']) && (!($input['so_ra'] ?? "") || !($input['kho_tong'] ?? "") || !($input['kho'] ?? "") || !($input['dai_tam'] ?? ""))) {
                    $khuon_link = KhuonLink::with('khuon')
                        ->where('customer_id', $order->short_name)
                        ->where(DB::raw('CONCAT_WS("", dai, rong, cao)'), ($input['dai'] ?? "").($input['rong'] ?? "").($input['cao'] ?? ""))
                        // ->where('dai', $input['dai'] ?? null)
                        // ->where('rong', $input['rong'] ?? null)
                        // ->where('cao', $input['cao'] ?? null)
                        ->where('phan_loai_1', $input['phan_loai_1'] ?? null)
                        ->where('buyer_id', $input['buyer_id'] ?? null)
                        ->where('pad_xe_ranh', $input['note_3'] ?? null)
                        ->first();
                        // return [$input['dai'], $input['rong'], $input['cao']];
                        // return $khuon_link;
                    $input['khuon_id'] = $khuon_link->khuon_id ?? null;
                    $formula = DB::table('formulas')->where('phan_loai_1', $input['phan_loai_1'] ?? "")->where('phan_loai_2', $input['phan_loai_2'] ?? "")->first();
                    if ((!in_array($input['phan_loai_2'], ['thung-be', 'pad-be']) && $formula) || ($formula && in_array($input['phan_loai_2'], ['thung-be', 'pad-be']) && $khuon_link && $khuon_link->dai_khuon && $khuon_link->kho_khuon && $khuon_link->so_con)) {
                        $input['kho_giay_array'] = range(0, 200, 5);
                        $input['kho_giay_array'] = array_merge($input['kho_giay_array'], [88, 92]);
                        $input['n1_except'] = [5, 7, 10, 11, 13, 14, 17, 19, 22, 23, 25, 26, 29, 31, 33, 34, 35, 38, 39];
                        $function = str_replace('$input_dai', $input['dai'], $formula->function);
                        $function = str_replace('$input_cao', $input['cao'] ?? 0, $function);
                        $function = str_replace('$input_rong', $input['rong'], $function);
                        $function = str_replace('$input_so_luong', $input['so_luong'], $function);
                        $function = str_replace('$input_kho_giay', json_encode($input['kho_giay_array']), $function);
                        $function = str_replace('$input_n1_except', json_encode($input['n1_except']), $function);
                        if ($khuon_link && $khuon_link->dai_khuon && $khuon_link->kho_khuon && $khuon_link->so_con) {
                            $function = str_replace('$kho_khuon_input', $khuon_link->kho_khuon, $function);
                            $function = str_replace('$dai_khuon_input', $khuon_link->dai_khuon, $function);
                            $function = str_replace('$so_con_input', $khuon_link->so_con, $function);
                        }
                        try {
                            $input = array_merge($input, eval($function));
                            $input['so_met_toi'] = round($input['dai_tam'] * $input['so_dao'] / 100);
                        } catch (\Throwable $th) {
                            throw $th;
                        }
                    }
                }
                // return $input;
                $he_so = $input['he_so'] ?? 1;
                // if ($input['so_ra']) {
                //     $input['so_dao'] = ceil(($order->sl * $he_so) / $input['so_ra']);
                // }
                $update = $order->update($input);
                if (isset($input['ids'])) {
                    foreach ($input['ids'] as $key => $id) {
                        $record = Order::find($id);
                        if ($input['so_ra']) {
                            $input['so_dao'] = ceil(($record->sl * $he_so) / $input['so_ra']);
                            if ($input['dai_tam']) {
                                $input['so_met_toi'] = round($input['dai_tam'] * $input['so_dao'] / 100);
                            }
                        }

                        $input['tg_doi_model'] = isset($input['tg_doi_model']) ? $input['tg_doi_model'] : 0;
                        $input['han_giao_sx'] = $input['han_giao_sx'] ?? null;
                        $inp = [];
                        foreach ($input['listParams'] as $key => $param) {
                            $inp[$param] = $input[$param];
                        }
                        $order = Order::find($id);
                        if (!$order) continue;
                        if (isset($inp['kich_thuoc_chuan']) && $inp['kich_thuoc_chuan']) {
                            $kich_thuoc_chuan = [];
                            $kich_thuoc_chuan = explode('*', $inp['kich_thuoc_chuan']);
                            if (count($kich_thuoc_chuan) > 1) {
                                $inp['dai'] = $this->updateKichThuoc($kich_thuoc_chuan[0], $inp['unit'] ?? $order->unit);
                                $inp['rong'] = $this->updateKichThuoc($kich_thuoc_chuan[1], $inp['unit'] ?? $order->unit);
                                if (count($kich_thuoc_chuan) > 2) {
                                    $inp['cao'] = $this->updateKichThuoc($kich_thuoc_chuan[2], $inp['unit'] ?? $order->unit);
                                } else {
                                    $inp['cao'] = null;
                                }
                            }
                        }
                        if (isset($inp['quy_cach_drc']) && $inp['quy_cach_drc'] && isset($inp['dai']) && isset($inp['rong'])) {
                            $quy_cach = DRC::where('id', $inp['quy_cach_drc'])->first();
                            if ($quy_cach) {
                                $ct_dai = str_replace('[D]', $inp['dai'], $quy_cach->ct_dai);
                                $ct_rong = str_replace('[R]', $inp['rong'], $quy_cach->ct_rong);
                                $inp['dai'] = eval($ct_dai) ?? $inp['dai'];
                                $inp['rong'] = eval($ct_rong) ?? $inp['rong'];
                                if (isset($inp['cao'])) {
                                    $ct_cao = str_replace('[C]', $inp['cao'], $quy_cach->ct_cao);
                                    $inp['cao'] = eval($ct_cao) ?? $inp['cao'];
                                }
                            }
                        }
                        $order->update($inp);
                    }
                }
                DB::commit();
            } catch (\Throwable $th) {
                DB::rollBack();
                ErrorLog::saveError($request, $th);
                return $this->failure($th, 'Đã xảy ra lỗi');
            }
            return $this->success($order, 'Cập nhật thành công');
        } else {
            return $this->failure('', 'Không tìm thấy đơn hàng');
        }
    }

    function updateKichThuoc($value, $unit)
    {
        if (!is_numeric($value)) {
            return null;
        }
        switch (strtolower($unit)) {
            case 'mm':
                return $value / 10;
                break;
            case 'inch':
                return $value * 2.54;
                break;
            default:
                return $value;
                break;
        }
        return $value;
    }

    public function createOrder(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $input['id'] = isset($input['dot']) ? $input['mdh'] . '-' . $input['mql'] . '-' . $input['dot'] : $input['mdh'] . '-' . $input['mql'];
            $order = Order::create($input);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        return $this->success($order, 'Tạo thành công');
    }

    public function deleteOrders(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            Order::whereIn('id', $request->ids)->delete();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        return $this->success('Xoá thành công');
    }

    public function exportOrders(Request $request)
    {
        $query = Order::orderBy('mdh', 'ASC')->orderBy('mql', 'ASC');
        if (isset($request->customer_id)) {
            $query->where('customer_id', 'like', "%" . $request->customer_id . "%");
        }
        if (isset($request->short_name)) {
            $query->where('short_name', 'like', "%" . $request->short_name . "%");
        }
        if (isset($request->start_date) && isset($request->end_date)) {
            $query->whereDate('ngay_dat_hang', '>=', date('Y-m-d', strtotime($request->start_date)))->whereDate('ngay_dat_hang', '<=', date('Y-m-d', strtotime($request->end_date)));
        }
        if (isset($request->mdh)) {
            if (is_array($request->mdh)) {
                $query->where(function ($custom_query) use ($request) {
                    foreach ($request->mdh as $mdh) {
                        $custom_query->orWhere('mdh', 'like', "%$mdh%");
                    }
                });
            } else {
                $query->where('mdh', 'like', "%$request->mdh%");
            }
        }
        if (isset($request->order)) {
            $query->where('order', 'like', "%$request->order%");
        }
        if (isset($request->mql)) {
            $query->where('mql', $request->mql);
        }
        if (isset($request->kich_thuoc)) {
            $query->where('kich_thuoc', 'like', "%$request->kich_thuoc%");
        }
        if (isset($request->length)) {
            $query->where('length', 'like', $request->length);
        }
        if (isset($request->width)) {
            $query->where('width', 'like', $request->width);
        }
        if (isset($request->height)) {
            $query->where('height', 'like', $request->height);
        }
        if (isset($request->po)) {
            $query->where('po', 'like', "%$request->po%");
        }
        if (isset($request->dot)) {
            $query->where('dot', $request->dot);
        }
        if (isset($request->style)) {
            $query->where('style', 'like', "%$request->style%");
        }
        if (isset($request->style_no)) {
            $query->where('style_no', 'like', "%$request->style_no%");
        }
        if (isset($request->color)) {
            $query->where('color', 'like', "%$request->color%");
        }
        if (isset($request->item)) {
            $query->where('item', 'like', "%$request->item%");
        }
        if (isset($request->rm)) {
            $query->where('rm', 'like', "%$request->rm%");
        }
        if (isset($request->size)) {
            $query->where('size', 'like', "%$request->size%");
        }
        if (isset($request->note_2)) {
            $query->where('note_2', 'like', "%$request->note_2%");
        }
        if (isset($request->han_giao)) {
            $query->whereDate('han_giao', date('Y-m-d', strtotime($request->han_giao)));
        }
        $orders = $query->select(DB::raw('ROW_NUMBER() OVER(ORDER BY ID ASC) AS Row'), 'ngay_dat_hang', 'short_name', 'customer_id', 'nguoi_dat_hang', 'mdh', 'order', 'mql', 'length', 'width', 'height', 'kich_thuoc', 'unit', 'layout_type', 'sl', 'slg', 'slt', 'tmo', 'po', 'style', 'style_no', 'color', 'item', 'rm', 'size', 'price', 'into_money', 'dot', 'xuong_giao', 'note_1', 'han_giao', 'note_2', 'xuat_tai_kho', 'han_giao_sx')->get()->toArray();
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
        $header = [
            'STT',
            'Ngày ĐH',
            'Khách hàng',
            'Mã khách hàng',
            'Người ĐH',
            'MĐH',
            'Order',
            'MQL',
            'L',
            'W',
            'H',
            'Kích thước',
            'Đơn vị tính',
            'Máy + P8',
            'SL',
            'SLG',
            'SLT',
            'TMO',
            'PO',
            'Style',
            'Style no',
            'Color',
            'Item',
            'RM',
            'Size',
            'Giá thành',
            'Thành tiền',
            'Đợt',
            'Fac',
            'Ghi chú',
            'Hạn giao',
            'Ghi chú 2',
            'Xuất tại kho',
            'Ngày giao',
            'Xe giao',
            'Xuất hàng'
        ];
        foreach ($header as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
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
        $sheet->setCellValue([1, 1], 'ĐƠN HÀNG')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);

        $spreadsheet->getActiveSheet()->fromArray($orders, NULL, 'A3');
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Đơn hàng.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Đơn hàng.xlsx');
        $href = '/exported_files/Đơn hàng.xlsx';
        return $this->success($href);
    }

    function formarMDH($mdh)
    {
        $arr = explode('/', $mdh);
        $arr[0] = str_pad($arr[0], 4, '0', STR_PAD_LEFT);
        $arr[1] = str_pad($arr[1], 2, '0', STR_PAD_LEFT);
        if (isset($arr[2])) {
            $arr[2] = substr($arr[2], -2);
        } else {
            $arr[2] = '23';
        }
        return $arr[0] . '/' . $arr[1] . '/' . $arr[2];
    }

    function formatDate($date)
    {
        // $timestamp = strtotime($date);
        // if ($timestamp === FALSE) {
        //     return null;
        // }
        $timestamp = strtotime(str_replace('/', '-', $date));
        $date = date('Y-m-d', $timestamp);
        return $date;
    }
    public function importOrders(Request $request)
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
        $orderData = [];
        $customer_array = Customer::pluck('id')->toArray();
        foreach ($allDataInSheet as $key => $row) {
            if (!$row['O'] && !$row['F'] && !$row['D']) {
                break;
            }
            if ($key > 1 && $row['B']) {
                if (!$row['O']) {
                    return $this->failure([], 'Hàng số ' . $key . ': Thiếu thông tin số lượng');
                }
                if (!$row['F']) {
                    return $this->failure([], 'Hàng số ' . $key . ': Thiếu thông tin mdh');
                }
                if (!$row['D']) {
                    return $this->failure([], 'Hàng số ' . $key . ': Thiếu thông mã khách hàng');
                }
                if(!in_array($row['D'], $customer_array)){
                    // return $this->failure([], 'Hàng số ' . $key . ': Mã khách hàng không tồn tại'); 
                    Customer::updateOrCreate(['id'=>$row['D']], ['name'=>$row['C'], 'namp_input'=>$row['C']]);
                    CustomerShort::updateOrCreate(['customer_id'=>$row['D'], 'short_name'=>$row['C']]);
                }
                $id = $row['F'] . '-' . $row['H'];
                $check = Order::where('id', $id)->withTrashed()->forceDelete();
                // if ($check) {
                //     $check->forceDelete();
                // }
            }
            $orderData[$key] = $row;
        }
        try {
            DB::beginTransaction();
            foreach ($orderData as $key => $row) {
                //Lấy dứ liệu từ dòng thứ 3
                if (!$row['O'] && !$row['F'] && !$row['D']) {
                    break;
                }
                if ($key > 1 && $row['B']) {
                    // $row['F'] = $this->formarMDH($row['F']);
                    $row = array_map('trim', $row);
                    $input['id'] = $row['F'] . '-' . $row['H'];
                    $input['ngay_dat_hang'] = $this->formatDate($row['B']);
                    $input['short_name'] = $row['C'];
                    $input['customer_id'] = $row['D'];
                    $input['nguoi_dat_hang'] = $row['E'];
                    $input['order'] = $row['G'];
                    $input['mdh'] = $row['F'];
                    $input['mql'] = $row['H'];
                    $input['length'] = $row['I'];
                    $input['width'] = $row['J'];
                    $input['height'] = $row['K'];
                    $input['unit'] = $row['M'];
                    if (is_numeric($input['length']) && is_numeric($input['width'])) {
                        $input['kich_thuoc_chuan'] =  $row['K'] ? $row['I'] . '*' . $row['J'] . '*' . $row['K'] : $row['I'] . '*' . $row['J'];
                        if ($input['unit']) {
                            $input['dai'] = $this->updateKichThuoc($row['I'], $row['M']);
                            $input['rong'] = $this->updateKichThuoc($row['J'], $row['M']);
                            if ($row['K']) {
                                $input['cao'] = $this->updateKichThuoc($row['K'], $row['M']);
                            }
                        }
                    }
                    $input['kich_thuoc'] = $row['L'];
                    $input['layout_type'] = $row['N'];
                    $input['sl'] = $row['O'] ? str_replace(',', '', $row['O']) : null;
                    $input['slg'] = $row['P'] ? str_replace(',', '', $row['P']) : null;
                    $input['slt'] = $row['Q'] ? str_replace(',', '', $row['Q']) : null;
                    $input['tmo'] = $row['R'];
                    $input['po'] = $row['S'];
                    $input['style'] = $row['T'];
                    $input['style_no'] = $row['U'];
                    $input['color'] = $row['V'];
                    $input['item'] = $row['W'];
                    $input['rm'] = $row['X'];
                    $input['size'] = $row['Y'];
                    $input['price'] = $row['Z'] ? str_replace(',', '', $row['Z']) : null;
                    $input['into_money'] = $row['AA'] ? str_replace(',', '', $row['AA']) : null;
                    $input['dot'] = $row['AB'];
                    $input['han_giao'] = $row['AE'] ? $this->formatDate($row['AE']) : null;
                    $input['han_giao_sx'] = $row['AE'] ? $this->formatDate($row['AE']) : null;
                    $input['xuong_giao'] = $row['AC'] ?? "";
                    $input['note_1'] = $row['AD'] ?? "";
                    $input['note_2'] = $row['AF'] ?? "";
                    $input['xuat_tai_kho'] = $row['AG'] ?? "";
                    $input['tg_doi_model'] = 0;
                    $input['toc_do'] = 80;

                    // $input['layout_id'] = $row['AH'];
                    $check = Order::find($input['id']);
                    if ($check) {
                        continue;
                    }
                    if (isset($input['mdh']) && isset($input['ngay_dat_hang'])) {
                        $input['created_by'] = $request->user()->id;
                        Order::create($input);
                    }
                    unset($input);
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'File import có vấn đề vui lòng kiểm tra lại');
        }
        return $this->success([], 'Upload thành công');
    }

    public function splitOrders(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $order = Order::find($input['id']);
            $count = Order::where('mdh', $order->mdh)->where('mql', $order->mql)->count();
            $he_so = floor(($order->so_dao * $order->so_ra) / $order->sl) <= 0 ? 1 : floor(($order->so_dao * $order->so_ra) / $order->sl);
            $sum = 0;
            foreach ($input['inputData'] as $key => $value) {
                $sum = $sum + $value['so_luong'];
                $count = $count + 1;
                $arr = (clone $order)->toArray();
                $arr['dot'] = $value['dot'] ? $value['dot'] : $count;
                $arr['xuong_giao'] = $value['xuong_giao'];
                $arr['sl'] = $value['so_luong'];
                $arr['slt'] = 0;
                $arr['han_giao'] = date('Y-m-d', strtotime($value['ngay_giao']));
                $arr['han_giao_sx'] = date('Y-m-d', strtotime($value['ngay_giao']));
                $arr['id'] = $arr['mdh'] . '-' . $arr['mql'] . '-' . $arr['dot'];
                if ($arr['so_ra'] > 0) {
                    $arr['so_dao'] = ceil(($arr['sl'] * $he_so) / $arr['so_ra']);
                    if ($arr['dai_tam']) {
                        $arr['so_met_toi'] = round($arr['dai_tam'] * $arr['so_dao'] / 100);
                    }
                }
                Order::create($arr);
            }
            Order::find($input['id'])->update(['sl' => ($order->sl - $sum)]);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        return $this->success([], 'Tách đơn hàng thành công');
    }

    public function num_to_letters($n)
    {
        $n -= 1;
        for ($r = ""; $n >= 0; $n = intval($n / 26) - 1)
            $r = chr($n % 26 + 0x41) . $r;
        return $r;
    }

    public function importOrdersFromPlan(Request $request)
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
            //Lấy dứ liệu từ dòng thứ 3
            if ($key > 3) {
                if (!$row['L'] || !$row['E'] || !$row['D']) {
                    continue;
                }
                $input['customer_id'] = $row['D'];
                $input['mdh'] = $row['E'];
                $input['mql'] = $row['F'];
                $input['length'] = $row['G'];
                $input['width'] = $row['H'];
                $input['height'] = $row['I'];
                $input['sl'] = $row['L'];
                $input['slg'] = $row['L'];
                $input['so_ra'] = $row['W'];
                $input['kho'] = $row['X'];
                $input['dai_tam'] = $row['Y'];
                $input['kho_tong'] = $row['Z'];
                $input['dai'] = $row['AD'];
                $input['rong'] = $row['AE'];
                $input['cao'] = $row['AF'];
                $input['buyer_id'] = $row['AP'];
                $input['toc_do'] = $row['AC'];
                $input['tg_doi_model'] = $row['AB'];
                $input['so_met_toi'] = $row['AI'];
                $input['phan_loai_1'] = Str::slug($row['S']);
                $input['phan_loai_2'] = Str::slug($row['V']);
                $data[] = $input;
            }
        }
        try {
            DB::beginTransaction();
            $plans = [];
            foreach ($data as $input) {
                $plan = Order::where('mql', $input['mql'])->where('mdh', $input['mdh'])->update($input);
                $plans[] = $plan;
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        return $this->success($plans, 'Upload thành công');
    }

    public function restoreOrders(Request $request){
        try {
            DB::beginTransaction();
            $restore = Order::withTrashed()->where('id', $request->id)->restore();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        return $this->success('', 'Đã khôi phục đơn hàng');
    }
}
