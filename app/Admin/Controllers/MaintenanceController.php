<?php

namespace App\Admin\Controllers;

use App\Models\Maintenance;
use App\Models\MaintenanceDetail;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;

class MaintenanceController extends AdminController
{
    use API;

    public function getMaintenance(Request $request){
        $query = Maintenance::with('machine')->orderBy('created_at');
        if(isset($request->name)){
            $query->where('name', 'like', "%$request->name%");
        }
        $maintain = $query->get();
        return $this->success($maintain);
    }
    public function getMaintenanceDetail(Request $request){
        $maintain = Maintenance::with('detail', 'machine')->where('id', $request->id)->first();
        return $this->success($maintain);
    }
    public function updateMaintenance(Request $request){
        $input = $request->all();
        $maintain = Maintenance::where('id', $input['id'])->first();
        if($maintain){
            $validated = Maintenance::validateUpdate($input);
            if ($validated->fails()) {
                return $this->failure('', $validated->errors()->first());
            }
            $update = $maintain->update($input);
            if($update){
                foreach($input['detail'] as $key => $detail){
                    $detail['maintenance_id'] = $maintain->id;
                    $validated = MaintenanceDetail::validateUpdate($detail, false);
                    if ($validated->fails()) {
                        return $this->failure('', $validated->errors()->first());
                    }
                    if(isset($detail['id'])){
                        $maintain_detail = MaintenanceDetail::where('id', $detail['id'])->first();
                        if($maintain_detail){
                            $maintain_detail->update($detail);
                        }else{
                            continue;
                        }
                    }
                    else{
                        $maintain_detail = MaintenanceDetail::create($detail);
                    }
                }
                if(isset($input['delete_detail'])){
                    MaintenanceDetail::whereIn('id', $input['delete_detail'])->delete();
                }
                return $this->success($maintain);
            }else{
                return $this->failure('', 'Không thành công');
            }
        }
        else{
            return $this->failure('', 'Không tìm thấy công đoạn');
        }
    }

    public function createMaintenance(Request $request){
        $input = $request->all();
        $validated = Maintenance::validateUpdate($input, false);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $maintain = Maintenance::create($input);
        foreach($input['detail'] as $key => $detail){
            $detail['maintenance_id'] = $maintain->id;
            $input['detail'][$key]['maintenance_id'] = $maintain->id;
            $validated = MaintenanceDetail::validateUpdate($detail, false);
            if ($validated->fails()) {
                $maintain->delete();
                return $this->failure('', $validated->errors()->first());
            }
        }
        foreach($input['detail'] as $key => $detail){
            MaintenanceDetail::create($detail);
        }
        return $this->success($maintain, 'Tạo thành công');
    }

    public function deleteMaintenance(Request $request){
        $input = $request->all();
        Maintenance::whereIn('id', $input)->delete();
        MaintenanceDetail::whereIn('maintenance_id', $input)->delete();
        return $this->success('Xoá thành công');
    }

    public function exportMaintenance(Request $request){
        $query = Maintenance::with('machine')->orderBy('created_at');
        if(isset($request->name)){
            $query->where('name', 'like', "%$request->name%");
        }
        $maintain = $query->get();
        foreach($maintain as $detail){
            $detail->machine_name = $detail->machine->name;
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
        $header = ['Tên', 'Máy'];
        $table_key = [
            'A'=>'name',
            'B'=>'machine_name',
        ];
        foreach($header as $key => $cell){
            if(!is_array($cell)){
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            }else{
                $sheet->setCellValue([$start_col, $start_row], $key)->mergeCells([$start_col, $start_row, $start_col+count($cell)-1, $start_row])->getStyle([$start_col, $start_row, $start_col+count($cell)-1, $start_row])->applyFromArray($headerStyle);
                foreach($cell as $val){
                    $sheet->setCellValue([$start_col, $start_row+1], $val)->getStyle([$start_col, $start_row+1])->applyFromArray($headerStyle);
                    $start_col+=1;
                }
                continue;
            }
            $start_col+=1;
        }
        
        $sheet->setCellValue([1, 1], 'Quản lý kế hoạch bảo trì bảo dưỡng')->mergeCells([1, 1, $start_col-1, 1])->getStyle([1, 1, $start_col-1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row+1;
        foreach($maintain->toArray() as $key => $row){
            $table_col = 1;
            $row = (array)$row;
            foreach($table_key as $k=>$value){
                if(isset($row[$value])){
                    $sheet->setCellValue($k.$table_row,$row[$value])->getStyle($k.$table_row)->applyFromArray($centerStyle);
                }else{
                    continue;
                }
                $table_col+=1;
            }
            $table_row+=1;
        }
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            $sheet->getStyle($column->getColumnIndex().($start_row).':'.$column->getColumnIndex().($table_row-1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Bảo trì bảo dưỡng.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Bảo trì bảo dưỡng.xlsx');
        $href = '/exported_files/Bảo trì bảo dưỡng.xlsx';
        return $this->success($href);
    }

    public function importMaintenance(Request $request){
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
            if ($key > 2) {
                $input = [];
                $input['id'] = $row['A'];
                $input['name'] = $row['B'];
                $validated = Maintenance::validateUpdate($input);
                if ($validated->fails()) {
                    return $this->failure('', 'Lỗi dòng thứ '.($key).': '.$validated->errors()->first());
                }
                $data[] = $input;
            }
        }
        foreach ($data as $key => $input) {
            $cell = Maintenance::where('id', $input['id'])->first();
            if ($cell) {
                $cell->update($input);
            } else {
                Maintenance::create($input);
            }
        }
        return $this->success([], 'Upload thành công');
    }
}
