<?php

namespace App\Admin\Controllers;

use App\Models\AuthPath;
use App\Models\Permission;
use App\Models\PermissionPermission;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;

class PermissionController extends AdminController
{
    use API;

    public function getPermissions(Request $request){
        $query = Permission::orderBy('created_at', 'desc');
        if(isset($request->name)){
            $query->where('name', 'like', "%$request->name%");
        }
        $roles = $query->get();
        return $this->success($roles);
    }
    
    public function updatePermission(Request $request){
        $input = $request->all();
        $permission = Permission::where('id', $input['id'])->first();
        if($permission){
            // $input['slug'] = Str::slug($input['name']);
            // if(isset($input['link'])){
            //     $input['http_path'] = implode(';', $input['link']);
            // }
            $update = $permission->update($input);
            return $this->success($permission);
        }
        else{
            return $this->failure('', 'Không tìm thấy quyền');
        }
    }

    public function createPermission(Request $request){
        $input = $request->all();
        // $input['slug'] = Str::slug($input['name']);
        // if(isset($input['link'])){
        //     $input['http_path'] = implode(';', $input['link']);
        // }
        $permission = Permission::create($input);
        return $this->success($permission, 'Tạo thành công');
    }

    public function deletePermissions(Request $request){
        $input = $request->all();
        Permission::whereIn('id', $input)->delete();
        return $this->success('Xoá thành công');
    }

    public function exportPermissions(Request $request){
        $query = Permission::orderBy('id');
        if(isset($request->name)){
            $query->where('name', 'like', "%$request->name%");
        }
        $permissions = $query->get();
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
        $header = ['ID', 'Tên', 'Slug'];
        $table_key = [
            'A'=>'id',
            'B'=>'name',
            'C'=>'slug',
        ];
        foreach($header as $key => $cell){
            if(!is_array($cell)){
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            }
            $start_col+=1;
        }
        $sheet->setCellValue([1, 1], 'Quản lý quyền')->mergeCells([1, 1, $start_col-1, 1])->getStyle([1, 1, $start_col-1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row+1;
        foreach($permissions->toArray() as $key => $row){
            $table_col = 1;
            $row = (array)$row;
            $sheet->setCellValue([1, $table_row],$key+1)->getStyle([1, $table_row])->applyFromArray($centerStyle);
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
        header('Content-Disposition: attachment;filename="Quyền.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Quyền.xlsx');
        $href = '/exported_files/Quyền.xlsx';
        return $this->success($href);
    }

    public function importPermissions(Request $request){
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
                $input['id'] = $row['A'];
                $input['name'] = $row['B'];
                $input['slug'] = $row['C'];
                $data[] = $input;
            }
        }
        foreach ($data as $key => $input) {
            $permission = Permission::find($input['id']);
            if($permission) {
                $permission->update($input);
            }else{
                $permission = Permission::create($input);
            }
        }
        return $this->success([], 'Upload thành công');
    }
}
