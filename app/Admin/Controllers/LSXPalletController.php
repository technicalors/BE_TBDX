<?php

namespace App\Admin\Controllers;

use App\Models\LSXPallet;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\WarehouseFGLog;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use stdClass;

class LSXPalletController extends AdminController
{
    use API;

    public function getLSXPallet(Request $request){
        $query = LSXPallet::orderBy('created_at')->orderBy('pallet_id')->orderBy('mdh')->orderBy('mql');
        if(isset($request->start_date) || isset($request->end_date)){
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)));
        }else{
            $query->whereDate('created_at', date('Y-m-d'));
        }
        if(isset($request->mdh)){
            if (is_array($request->mdh)) {
                $query->whereIn('mdh', $request->mdh);
            } else {
                $query->where('mdh', $request->mdh);
            }
        }
        if(isset($request->mql)){
            if (is_array($request->mql)) {
                $query->whereIn('mql', $request->mql);
            } else {
                $query->where('mql', $request->mql);
            }
        }
        if(isset($request->lo_sx)){
            $query->where('lo_sx', $request->lo_sx);
        }
        if(isset($request->pallet_id)){
            $query->where('pallet_id', 'like', $request->pallet_id);
        }
        if(isset($request->locator_id)){
            $query->whereHas('locator_fg_map', function($q)use($request){
                $q->where('locator_id', 'like', "%".$request->locator_id."%");
            });
        }
        if(isset($request->status)){
            $loggedLoSX = WarehouseFGLog::pluck('pallet_id')->unique()->toArray();
            if($request->status == 0){
                $query->whereNotIn('pallet_id', $loggedLoSX);
            }else{
                $query->whereIn('pallet_id', $loggedLoSX);
            }
        }
        //search by order
        if(isset($request->length) || isset($request->width) || isset($request->height) || isset($request->kich_thuoc)){
            $query->whereHas('order', function($q)use($request){
                if(isset($request->length)) $q->where('length', 'like', "%".$request->length."%");
                if(isset($request->width)) $q->where('width', 'like', "%".$request->width."%");
                if(isset($request->height)) $q->where('height', 'like', "%".$request->height."%");
                if(isset($request->kich_thuoc)) $q->where('kich_thuoc', 'like', "%".$request->kich_thuoc."%");
            });
        }
        $count = $query->count();
        if(isset($request->page) || isset($request->pageSize)){
            $query->offset(($request->page - 1) * $request->pageSize)->limit($request->pageSize);
        }
        $records = $query->with('order', 'locator_fg_map', 'warehouseFGLog')->get();
        foreach ($records as $key => $record) {
            $record->mdh = $record->order->mdh ?? "";
            $record->mql = $record->order->mql ?? "";
            $record->length = $record->order->length ?? "";
            $record->width = $record->order->width ?? "";
            $record->height = $record->order->height ?? "";
            $record->kich_thuoc = $record->order->kich_thuoc ?? "";
            $record->locator_id = $record->locator_fg_map->locator_id ?? "";
            $record->status = count($record->warehouseFGLog) > 0 ? 'Đã nhập kho' : 'Chưa nhập kho';
        }
        return $this->success(['data'=>$records, 'totalPage'=>$count]);
    }

    public function exportLSXPallet(Request $request){
        $query = LSXPallet::orderBy('created_at')->orderBy('pallet_id')->orderBy('mdh')->orderBy('mql');
        if(isset($request->start_date) || isset($request->end_date)){
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)));
        }else{
            $query->whereDate('created_at', date('Y-m-d'));
        }
        if(isset($request->mdh)){
            if (is_array($request->mdh)) {
                $query->whereIn('mdh', $request->mdh);
            } else {
                $query->where('mdh', $request->mdh);
            }
        }
        if(isset($request->mql)){
            if (is_array($request->mql)) {
                $query->whereIn('mql', $request->mql);
            } else {
                $query->where('mql', $request->mql);
            }
        }
        if(isset($request->lo_sx)){
            $query->where('lo_sx', $request->lo_sx);
        }
        if(isset($request->pallet_id)){
            $query->where('pallet_id', 'like', $request->pallet_id);
        }
        if(isset($request->locator_id)){
            $query->whereHas('locator_fg_map', function($q)use($request){
                $q->where('locator_id', 'like', "%".$request->locator_id."%");
            });
        }
        if(isset($request->status)){
            $loggedLoSX = WarehouseFGLog::pluck('pallet_id')->unique()->toArray();
            if($request->status == 0){
                $query->whereNotIn('pallet_id', $loggedLoSX);
            }else{
                $query->whereIn('pallet_id', $loggedLoSX);
            }
        }
        //search by order
        if(isset($request->length) || isset($request->width) || isset($request->height) || isset($request->kich_thuoc)){
            $query->whereHas('order', function($q)use($request){
                if(isset($request->length)) $q->where('length', 'like', "%".$request->length."%");
                if(isset($request->width)) $q->where('width', 'like', "%".$request->width."%");
                if(isset($request->height)) $q->where('height', 'like', "%".$request->height."%");
                if(isset($request->kich_thuoc)) $q->where('kich_thuoc', 'like', "%".$request->kich_thuoc."%");
            });
        }
        $records = $query->with('order', 'locator_fg_map', 'warehouseFGLog')->get();
        $data = [];
        foreach ($records as $key => $record) {
            $obj = new stdClass;
            $obj->lo_sx = $record->lo_sx;
            $obj->pallet_id = $record->pallet_id;
            $obj->mdh = $record->order->mdh ?? "";
            $obj->mql = $record->order->mql ?? "";
            $obj->length = $record->order->length ?? "";
            $obj->width = $record->order->width ?? "";
            $obj->height = $record->order->height ?? "";
            $obj->kich_thuoc = $record->order->kich_thuoc ?? "";
            $obj->so_luong = $record->so_luong ?? "";
            $obj->locator_id = $record->locator_fg_map->locator_id ?? "";
            $record->status = count($record->warehouseFGLog) > 0 ? 'Đã nhập kho' : 'Chưa nhập kho';
            $data[] = (array)$obj;
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
            'font' => ['size'=>16, 'bold' => true],
        ]);
        $border = [
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $header = ['Lô SX', 'Mã tem (pallet)', 'MDH', 'MQL', 'L', 'W', 'H', 'Kích thước', 'Số lượng', 'Vị trí', 'Trạng thái'];
        foreach($header as $key => $cell){
            if(!is_array($cell)){
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            }
            $start_col+=1;
        }
        $table_col = 1;
        $table_row = $start_row + 1;
        $sheet->setCellValue([1, 1], 'Quản lý tem gộp')->mergeCells([1, 1, $start_col-1, 1])->getStyle([1, 1, $start_col-1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->fromArray($data, null, 'A3');
        $sheet->getStyle([1, $table_row, $start_col - 1, count($data) + $table_row - 1])->applyFromArray(
            array_merge(
                $centerStyle,
                array(
                    'borders' => array(
                        'allBorders' => array(
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => array('argb' => '000000'),
                        ),
                    )
                )
            )
        );
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            $sheet->getStyle($column->getColumnIndex() . ($start_row) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Quản lý tem gộp.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Quản lý tem gộp.xlsx');
        $href = '/exported_files/Quản lý tem gộp.xlsx';
        return $this->success($href);
    }

    public function importRoles(Request $request){
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
                $input['name'] = $row['A'];
                $input['quyen'] = $row['B'];
                $validated = Role::validateUpdate($input);
                if ($validated->fails()) {
                    return $this->failure('', 'Lỗi dòng thứ '.($key).': '.$validated->errors()->first());
                }
                $data[] = $input;
            }
        }
        foreach ($data as $key => $input) {
            $role = Role::where('name', 'like', $input['name'])->first();
            if($role) {
                $role->update($input);
                $role_permission = RolePermission::where('role_id', $role->id)->delete();
                foreach(explode(', ', $input['quyen']) as $quyen){
                    $permission = Permission::where('name', 'like', trim($quyen))->first();
                    if($permission) {
                        $role_permission = RolePermission::create(['role_id'=>$role->id, 'permission_id' => $permission->id]);
                    }
                }
            }else{
                $input['slug'] = Str::slug($input['name']);
                $role = Role::create($input);
                foreach(explode(', ', $input['quyen']) as $quyen){
                    $permission = Permission::where('name', 'like', trim($quyen))->first();
                    if($permission) {
                        $role_permission = RolePermission::create(['role_id'=>$role->id, 'permission_id' => $permission->id]);
                    }
                }
            }
        }
        return $this->success([], 'Upload thành công');
    }

    public function printPallet(Request $request){
        $lsx_pallet = LSXPallet::where('pallet_id', $request->pallet_id)->get()->sortBy('order_id', SORT_NATURAL)->values();
        if(empty($lsx_pallet)){
            return $this->failure('', 'Không tìm thấy pallet');
        }
        return $this->success($lsx_pallet);
    }
}
