<?php

namespace App\Admin\Controllers;

use App\Models\Cell;
use App\Models\Sheft;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;

class CellController extends AdminController
{
    use API;

    public function getCells(Request $request){
        $query = Cell::orderBy('id');
        if(isset($request->id)){
            $query->where('id', 'like', "%$request->id%");
        }
        if(isset($request->name)){
            $query->where('name', 'like', "%$request->name%");
        }
        $warehouses = $query->get();
        return $this->success($warehouses);
    }
    public function updateCell(Request $request){
        $input = $request->all();
        $material = Cell::where('id', $input['id'])->first();
        if($material){
            $validated = Cell::validateUpdate($input);
            if ($validated->fails()) {
                return $this->failure('', $validated->errors()->first());
            }
            $update = $material->update($input);
            if($update){
                return $this->success($material);
            }else{
                return $this->failure('', 'Không thành công');
            }  
        }
        else{
            return $this->failure('', 'Không tìm thấy công đoạn');
        }
    }

    public function createCell(Request $request){
        $input = $request->all();
        $validated = Cell::validateUpdate($input, false);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $warehouse = Cell::create($input);
        return $this->success($warehouse, 'Tạo thành công');
    }

    public function deleteCells(Request $request){
        $input = $request->all();
        Cell::whereIn('id', $input)->delete();
        return $this->success('Xoá thành công');
    }

    public function exportCells(Request $request){
        $query = Cell::orderBy('id');
        if(isset($request->id)){
            $query->where('id', 'like', "%$request->id%");
        }
        if(isset($request->name)){
            $query->where('name', 'like', "%$request->name%");
        }
        $warehouses = $query->get();
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
        $header = ['ID', 'Tên', 'Kệ', 'Number of bin', 'Mã hàng'];
        $table_key = [
            'A'=>'id',
            'B'=>'name',
            'C'=>'sheft_id',
            'D'=>'number_of_bin',
            'E'=>'product_id',
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
        
        $sheet->setCellValue([1, 1], 'Quản lý vị trí kho')->mergeCells([1, 1, $start_col-1, 1])->getStyle([1, 1, $start_col-1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row+1;
        foreach($warehouses->toArray() as $key => $row){
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
        header('Content-Disposition: attachment;filename="Vị trí kho.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Vị trí kho.xlsx');
        $href = '/exported_files/Vị trí kho.xlsx';
        return $this->success($href);
    }

    public function importCells(Request $request){
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
                // $input = [];
                // $input['id'] = $row['A'];
                // $input['name'] = $row['B'];
                // $input['sheft_id'] = $row['C'];
                // $input['number_of_bin'] = $row['D'];
                // $input['product_id'] = $row['E'];
                // $validated = Cell::validateUpdate($input);
                // if ($validated->fails()) {
                //     return $this->failure('', 'Lỗi dòng thứ '.($key).': '.$validated->errors()->first());
                // }
                $kgc = [];
                $kgc['id'] = $row['B'];
                if($kgc['id']){
                    $kgc['name'] = $row['B'];
                    $kgc['warehouse_id'] = 'KGC';
                    $data[] = $kgc;
                }
                $kbtp = [];
                $kbtp['id'] = $row['D'];
                if($kbtp['id']){
                    $kbtp['name'] = $row['B'];
                    $kbtp['warehouse_id'] = 'KBTP';
                    $data[] = $kbtp;
                }
                $ktpc = [];
                $ktpc['id'] = $row['F'];
                if($ktpc['id']){
                    $ktpc['name'] = $row['B'];
                    $ktpc['warehouse_id'] = 'KTPC';
                    $data[] = $ktpc;
                }
                $ktp = [];
                $ktp['id'] = $row['H'];
                if($ktp['id']){
                    $ktp['name'] = $row['B'];
                    $ktp['warehouse_id'] = 'KTP';
                    $data[] = $ktp;
                }
            }
        }
        foreach ($data as $key => $input) {
            $cell = Cell::where('id', $input['id'])->first();
            if ($cell) {
                $cell->update($input);
            } else {
                Cell::create($input);
            }
        }
        return $this->success([], 'Upload thành công');
    }
}
