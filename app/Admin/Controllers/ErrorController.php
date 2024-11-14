<?php

namespace App\Admin\Controllers;

use App\Models\Error;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use App\Models\Line;

class ErrorController extends AdminController
{
    use API;
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Lỗi công đoạn';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        
        $grid = new Grid(new Error());
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        });
        $grid->column('id', __('Mã lỗi'))->sortable();
        // $grid->column('name', __('Name'));
        $grid->column('noi_dung', __('Nội dung'));
        $grid->column("line.name",__('Lỗi công đoạn'));
        // $grid->column('created_at', __('Created at'));
        // $grid->column('updated_at', __('Updated at'));
        $grid->column('nguyen_nhan', __('Nguyên nhân'));
        $grid->column('khac_phuc', __('Khắc phục'));
        $grid->column('phong_ngua', __('Phòng ngừa'));

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
        $show = new Show(Error::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('noi_dung', __('Noi dung'));
        $show->field('line_id', __('Line id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('nguyen_nhan', __('Nguyen nhan'));
        $show->field('khac_phuc', __('Khac phuc'));
        $show->field('phong_ngua', __('Phong ngua'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Error());

        $form->text('name', __('Name'));
        $form->text('noi_dung', __('Noi dung'));
        $form->text('line_id', __('Line id'));
        $form->textarea('nguyen_nhan', __('Nguyen nhan'));
        $form->textarea('khac_phuc', __('Khac phuc'));
        $form->textarea('phong_ngua', __('Phong ngua'));

        return $form;
    }

    public function getErrors(Request $request){
        $query = Error::with('line')->orderBy('created_at', 'DESC');
        if(isset($request->id)){
            $query->where('id', 'like', "%$request->id%");
        }
        if(isset($request->name)){
            $query->where('name', 'like', "%$request->name%");
        }
        $errors = $query->get();
        return $this->success($errors);
    }
    public function updateErrors(Request $request){
        $line_arr = [];
        $lines = Line::all();
        foreach($lines as $line){
            $line_arr[Str::slug($line->name)] = $line->id;
        }

        $input = $request->all();
        if(isset($line_arr[Str::slug($input['line'])])){
            $input['line_id'] = $line_arr[Str::slug($request->line)];
        }
        $validated = Error::validateUpdate($input);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $error = Error::where('id', $input['id'])->first();
        if($error){
            $update = $error->update($input);
            return $this->success($error);
        }
        else{
            return $this->failure('', 'Không tìm thấy lỗi');
        }
    }

    public function createErrors(Request $request){
        $line_arr = [];
        $lines = Line::all();
        foreach($lines as $line){
            $line_arr[Str::slug($line->name)] = $line->id;
        }

        $input = $request->all();
        if(isset($line_arr[Str::slug($input['line'])])){
            $input['line_id'] = $line_arr[Str::slug($input['line'])];
        }
        $validated = Error::validateUpdate($input, false);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $error = Error::create($input);
        return $this->success($error, 'Tạo thành công');
    }

    public function deleteErrors(Request $request){
        $input = $request->all();
        Error::whereIn('id', $input)->delete();
        return $this->success('Xoá thành công');
    }

    public function exportErrors(Request $request){
        $query = Error::with('line')->orderBy('created_at', 'DESC');
        if(isset($request->id)){
            $query->where('id', 'like', "%$request->id%");
        }
        if(isset($request->name)){
            $query->where('name', 'like', "%$request->name%");
        }
        $errors = $query->get();
        foreach($errors as $error){
            $error->line_name = $error->line->name;
        }
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $start_row = 2;
        $start_col = 1;
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
        $header = ['Mã lỗi', 'Tên lỗi', 'Công đoạn'];
        $table_key = [
            'A'=>'id',
            'B'=>'name',
            'C'=>'line_name',
        ];
        foreach($header as $key => $cell){
            if(!is_array($cell)){
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            }
            $start_col+=1;
        }
        $sheet->setCellValue([1, 1], 'Quản lý lỗi')->mergeCells([1, 1, $start_col-1, 1])->getStyle([1, 1, $start_col-1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row+1;
        foreach($errors->toArray() as $key => $row){
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
        header('Content-Disposition: attachment;filename="Lỗi.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Lỗi.xlsx');
        $href = '/exported_files/Lỗi.xlsx';
        return $this->success($href);
    }

    public function importErrors(Request $request){
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
                $input['id'] = $row['A'];
                $input['name'] = $row['B'];
                if(isset($line_arr[Str::slug($row['C'])])){
                    $input['line_id'] = $line_arr[Str::slug($row['C'])];
                }
                $validated = Error::validateUpdate($input);
                if ($validated->fails()) {
                    return $this->failure('', 'Lỗi dòng thứ '.($key).': '.$validated->errors()->first());
                }
                $data[] = $input;
            }
        }
        foreach ($data as $key => $input) {
            $error = Error::where('id', $input['id'])->first();
            if($error){
                $error->update($input);
            }else{
                Error::create($input);
            }
        }
        return $this->success([], 'Upload thành công');
    }
}
