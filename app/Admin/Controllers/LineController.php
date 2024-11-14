<?php

namespace App\Admin\Controllers;

use App\Models\Line;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;

class LineController extends AdminController
{
    use API;
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Công đoạn';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Line());
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        });
        $grid->model()->where('display',"=",1);
        // $grid->column('id', __('Id'));
        $grid->column('name', __('Tên'));
        // $grid->column('note', __('Note'));
        // $grid->column('created_at', __('Created at'));
        // $grid->column('updated_at', __('Updated at'));

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Line::findOrFail($id));
        

        $show->field('id', __('Id'));
        $show->field('name', __('Tên'));
        // $show->field('note', __('Note'));
        // $show->field('created_at', __('Created at'));
        // $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Line());

        $form->text('name', __('Name'));
        $form->text('note', __('Note'));

        return $form;
    }

    public function getLine(Request $request){
        $query = Line::orderBy('ordering');
        if(isset($request->line)){
            $query->where('name', 'like', "%$request->line%");
        }
        $lines = $query->get();
        return $this->success($lines);
    }
    public function updateLine(Request $request){
        $input = $request->all();
        $line = Line::where('id', $input['id'])->first();
        if($line){
            $update = $line->update($input);
            if($update){
                return $this->success($line);
            }else{
                return $this->failure('', 'Không thành công');
            }  
        }
        else{
            return $this->failure('', 'Không tìm thấy công đoạn');
        }
    }

    public function createLine(Request $request){
        $input = $request->all();
        $line = Line::create($input);
        return $this->success($line, 'Tạo thành công');
    }

    public function deleteLine(Request $request){
        $input = $request->all();
        Line::whereIn('id', $input)->delete();
        return $this->success('Xoá thành công');
    }

    public function exportLine(Request $request){
        $lines = Line::orderBy('ordering')->get();
        foreach($lines as $line){
            $line->display = $line->display === 1 ? "Có" : "Không";
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
        $header = ['Thứ tự', 'Công đoạn', 'Hiển thị'];
        $table_key = [
            'A'=>'ordering',
            'B'=>'name',
            'C'=>'display',
        ];
        foreach($header as $key => $cell){
            if(!is_array($cell)){
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            }
            $start_col+=1;
        }
        
        $sheet->setCellValue([1, 1], 'Quản lý công đoạn')->mergeCells([1, 1, $start_col-1, 1])->getStyle([1, 1, $start_col-1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row+1;
        foreach($lines->toArray() as $key => $row){
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
        header('Content-Disposition: attachment;filename="Công đoạn.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Công đoạn.xlsx');
        $href = '/exported_files/Công đoạn.xlsx';
        return $this->success($href);
    }

    public function importLine(Request $request){
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
        $line_arr = [];
        $lines = Line::all();
        foreach($lines as $line){
            $line_arr[Str::slug($line->name)] = $line->id;
        }
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 2
            if ($key > 2) {
                $input = [];
                $input['ordering'] = $row['A'];
                $input['name'] = $row['B'];
                $input['id'] = isset($line_arr[Str::slug($row['B'])]) ? $line_arr[Str::slug($row['B'])] : '';
                $input['display'] = $row['C'] === "Có" ? 1 : 0;
                $validated = Line::validateUpdate($input);
                if ($validated->fails()) {
                    return $this->failure('', 'Lỗi dòng thứ '.($key).': '.$validated->errors()->first());
                }
                $data[] = $input;
            }
        }
        foreach ($data as $key => $input) {
            $line = Line::where('id', $input['id'])->first();
            if ($line) {
                $line->update($input);
            } else {
                Line::create($input);
            }
        }
        return $this->success([], 'Upload thành công');
    }
}
