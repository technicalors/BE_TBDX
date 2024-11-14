<?php

namespace App\Admin\Controllers;

use App\Models\Jig;
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

class JigController extends AdminController
{
    use API;

    public function getJig(Request $request){
        $query = Jig::orderBy('id');
        if(isset($request->id)){
            $query->where('id', 'like', "%$request->id%");
        }
        if(isset($request->name)){
            $query->where('name', 'like', "%$request->name%");
        }
        $Jig = $query->get();
        return $this->success($Jig);
    }
    public function updateJig(Request $request){
        $input = $request->all();
        $Jig = Jig::where('id', $input['id'])->first();
        if($Jig){
            $validated = Jig::validateUpdate($input);
            if ($validated->fails()) {
                return $this->failure('', $validated->errors()->first());
            }
            $update = $Jig->update($input);
            if($update){
                return $this->success($Jig);
            }else{
                return $this->failure('', 'Không thành công');
            }  
        }
        else{
            return $this->failure('', 'Không tìm thấy công đoạn');
        }
    }

    public function createJig(Request $request){
        $input = $request->all();
        $validated = Jig::validateUpdate($input, false);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $Jig = Jig::create($input);
        return $this->success($Jig, 'Tạo thành công');
    }

    public function deleteJig(Request $request){
        $input = $request->all();
        Jig::whereIn('id', $input)->delete();
        return $this->success('Xoá thành công');
    }

    public function exportJig(Request $request){
        $query = Jig::orderBy('id');
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
        $header = ['ID', 'Tên'];
        $table_key = [
            'A'=>'id',
            'B'=>'name',
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
        
        $sheet->setCellValue([1, 1], 'Quản lý Jig')->mergeCells([1, 1, $start_col-1, 1])->getStyle([1, 1, $start_col-1, 1])->applyFromArray($titleStyle);
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
        header('Content-Disposition: attachment;filename="Jig.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Jig.xlsx');
        $href = '/exported_files/Jig.xlsx';
        return $this->success($href);
    }

    public function importJig(Request $request){
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
                $validated = Jig::validateUpdate($input);
                if ($validated->fails()) {
                    return $this->failure('', 'Lỗi dòng thứ '.($key).': '.$validated->errors()->first());
                }
                $data[] = $input;
            }
        }
        foreach ($data as $key => $input) {
            $cell = Jig::where('id', $input['id'])->first();
            if ($cell) {
                $cell->update($input);
            } else {
                Jig::create($input);
            }
        }
        return $this->success([], 'Upload thành công');
    }
}
