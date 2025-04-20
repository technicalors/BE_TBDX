<?php

namespace App\Admin\Controllers;

use App\Models\ErrorLog;
use App\Models\TestCriteria;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use App\Models\Line;
use Exception;
use Illuminate\Support\Facades\DB;

class TestCriteriaController extends AdminController
{
    use API;
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Chỉ tiêu kiểm tra';
    public function upload_xlsx($action, $title)
    {
        return view('import', [
            "action" => $action,
            "title" => $title
        ]);
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new TestCriteria());
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        });
        $grid->tools(function ($tool) {
            $tool->append($this->upload_xlsx('/admin/test_criteria/import', 'Chọn file chỉ tiêu kiểm tra'));
        });
        // $grid->column('id', __('Id'));
        $grid->column('line.name', __('Công đoạn'));
        $grid->column('chi_tieu', __('Chỉ tiêu'));
        $grid->column('hang_muc', __('Hạng mục'));
        $grid->column('tieu_chuan', __('Tiêu chuẩn'));
        $grid->column('ref_line.name', __('Tham chiếu công đoạn'));
        $grid->column('phan_dinh', __('Phán định'));
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
        $show = new Show(TestCriteria::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('line_id', __('Line id'));
        $show->field('chi_tieu', __('Chi tieu'));
        $show->field('hang_muc', __('Hang muc'));
        $show->field('tieu_chuan', __('Tieu chuan'));
        $show->field('phan_dinh', __('Phan dinh'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new TestCriteria());

        $form->number('line_id', __('Line id'));
        $form->text('chi_tieu', __('Chi tieu'));
        $form->text('hang_muc', __('Hang muc'));
        $form->text('tieu_chuan', __('Tieu chuan'));
        $form->text('reference', __('Tham chieu cong doan'));
        $form->text('phan_dinh', __('Phan dinh'))->default('OK/NG');

        return $form;
    }
    function readFilex($activeIndex = 0, $file_name)
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

        $spreadsheet = $reader->load($file_name);
        $spreadsheet->setActiveSheetIndex($activeIndex);
        $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        return $allDataInSheet;
    }
    public function import($flag = false)
    {
        // if (!isset($_FILES['files'])) { {
        //         admin_error('Định dạng file không đúng', 'error');
        //         return back();
        //     }
        // }
        // // file path
        // $arr = ["Kích thước", "Ngoại quan", "Đặc tính"];
        // $lines  = [16, 10, 11, 22, 12, 14, 13, 15, 20];
        // TestCriteria::truncate();
        // for ($k = 0; $k <= 8; $k++) {
        //     $dataSheet = $this->readFilex($k, $_FILES['files']['tmp_name']);
        //     for ($i = 5; $i <= count( $dataSheet); $i++) {
        //         $row = $dataSheet[$i];
        //         $test1 = new TestCriteria();
        //         $test1->hang_muc = $row['A'] ?? " ";
        //         $test1->tieu_chuan = $row['B'] ?? " ";
        //         $test1->chi_tieu = 'Kích thước';
        //         $test1->line_id = $lines[$k];
        //         $test1->save();


        //         $test2 = new TestCriteria();
        //         $test2->hang_muc = $row['E'] ?? " ";
        //         $test2->tieu_chuan = $row['F'] ?? " ";
        //         $test2->chi_tieu = 'Ngoại quan';
        //         $test2->line_id = $lines[$k];
        //         $test2->save();

        //         $test2 = new TestCriteria();
        //         $test2->hang_muc = $row['I'] ?? " ";
        //         $test2->tieu_chuan = $row['J'] ?? " ";
        //         $test2->chi_tieu = 'Đặc tính';
        //         if ($lines[$k] == 15 || $lines[$k] == 20) {
        //         $test2->chi_tieu = 'Ngoại quan';
        //         }

        //         $test2->line_id = $lines[$k];
        //         $test2->save();
        //     }
        // }
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
                if(isset($line_arr[Str::slug($row['A'])])){
                    $input['line_id'] = $line_arr[Str::slug($row['A'])];
                }
                $input['hang_muc'] = $row['B'];
                $input['chi_tieu'] = $row['C'];
                $input['tieu_chuan'] = $row['D'] ?? " ";
                $input['phan_dinh'] = $row['E'];
                $input['reference'] = isset($line_arr[Str::slug($row['F'])]) ? $line_arr[Str::slug($row['F'])] : '';
                $validated = TestCriteria::validateUpdate($input);
                if ($validated->fails()) {
                    return $this->failure('', 'Lỗi dòng thứ '.($key).': '.$validated->errors()->first());
                }
                $data[] = $input;
            }
        }
        foreach ($data as $key => $input) {
            $test_criteria = TestCriteria::where('line_id', $input['line_id'])->where('hang_muc', 'like', $input['hang_muc'])->first();
            if($test_criteria) {
                $test_criteria->update($input);
            }else{
                $test_criteria = TestCriteria::create($input);
            }
        }
        if ($flag) return true;
        admin_success('Tải lên thành công', 'success');
        return back();
    }

    public function getTestCriteria(Request $request){
        $line_arr = [];
        $lines = Line::all();
        foreach($lines as $line){
            $line_arr[Str::slug($line->name)] = $line->id;
        }

        $query = TestCriteria::with('line', 'ref_line')->orderBy('id');
        if(isset($request->line)){
            if(Str::slug($request->line) === 'iqc'){
                $query->where('line_id', 38);
            }else{
                $query->where('line_id', isset($line_arr[Str::slug($request->line)]) ? $line_arr[Str::slug($request->line)] : '');
            }
        }
        if(isset($request->hang_muc)){
            $query->where('hang_muc', 'like', "%$request->hang_muc%");
        }
        $test_criterias = [];
        foreach( $query->get() as $key => $test_criteria ){
            if(str_replace(' ', '', $test_criteria->hang_muc) === ""){
                continue;
            }
            if($test_criteria->line_id == 38){
                $test_criteria->line_name  = "IQC";
            }else{
                $test_criteria->line_name  = $test_criteria->line->name ?? "";
            }
            $test_criterias[] = $test_criteria;
        }
        return $this->success($test_criterias);
    }
    public function updateTestCriteria(Request $request){
        $line_arr = [];
        $lines = Line::all();
        foreach($lines as $line){
            $line_arr[Str::slug($line->name)] = $line->id;
        }

        $input = $request->all();
        if(isset($line_arr[Str::slug($input['line'])])){
            $input['line_id'] = $line_arr[Str::slug($input['line'])];
        }else if(Str::slug($input['line']) === 'iqc'){
            $input['line_id'] = 38;
        }
        if(isset($line_arr[Str::slug($input['reference'])])){
            $input['reference'] = $line_arr[Str::slug($input['reference'])];
        }
        $validated = TestCriteria::validateUpdate($input);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $test_criteria = TestCriteria::where('id', $input['id'])->first();
        if($test_criteria){
            $update = $test_criteria->update($input);
            return $this->success($test_criteria);
        }
        else{
            return $this->failure('', 'Không tìm thấy chỉ tiêu');
        }
    }

    public function createTestCriteria(Request $request){
        $line_arr = [];
        $lines = Line::all();
        foreach($lines as $line){
            $line_arr[Str::slug($line->name)] = $line->id;
        }

        $input = $request->all();
        if(isset($line_arr[Str::slug($input['line'])])){
            $input['line_id'] = $line_arr[Str::slug($input['line'])];
        }else if(Str::slug($input['line']) === 'iqc'){
            $input['line_id'] = 38;
        }
        if(isset($input['reference']) && isset($line_arr[Str::slug($input['reference'])])){
            $input['reference'] = $line_arr[Str::slug($input['reference'])];
        }
        $validated = TestCriteria::validateUpdate($input, false);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $test_criteria = TestCriteria::create($input);
        return $this->success($test_criteria, 'Tạo thành công');
    }

    public function deleteTestCriteria(Request $request){
        $input = $request->all();
        TestCriteria::whereIn('id', $input)->delete();
        return $this->success('Xoá thành công');
    }

    public function exportTestCriteria(Request $request){
        $line_arr = [];
        $lines = Line::all();
        foreach($lines as $line){
            $line_arr[Str::slug($line->name)] = $line->id;
        }
        $query = TestCriteria::with('line')->orderBy('id');
        if(isset($request->line)){
            $query->where('line_id', $line_arr[Str::slug($request->line)]);
        }
        if(isset($request->hang_muc)){
            $query->where('hang_muc', 'like', "%$request->hang_muc%");
        }
        $test_criterias = [];
        foreach( $query->get() as $key => $test_criteria ){
            if(str_replace(' ', '', $test_criteria->hang_muc) === ""){
                continue;
            }
            if($test_criteria->line_id == 38){
                $test_criteria->line_name  = "IQC";
            }else{
                $test_criteria->line_name  = $test_criteria->line->name ?? "";
            }
            $test_criteria->ref_line_name  = $test_criteria->ref_line ? $test_criteria->ref_line->name : '';
            $test_criterias[] = $test_criteria->toArray();
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
        $header = ['Công đoạn', 'Mã lỗi', 'Tiêu chí', 'Hạng mục', 'Chỉ tiêu', 'Tiêu chuẩn', 'Phân định', 'Tham chiếu TCKT công đoạn'];
        $table_key = [
            'A'=>'line_name',
            'B'=>'id',
            'C'=>'name',
            'D'=>'hang_muc',
            'E'=>'chi_tieu',
            'F'=>'tieu_chuan',
            'G'=>'phan_dinh',
            'H'=>'ref_line_name',
        ];
        foreach($header as $key => $cell){
            if(!is_array($cell)){
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            }
            $start_col+=1;
        }
        
        $sheet->setCellValue([1, 1], 'Quản lý thông số sản phẩm')->mergeCells([1, 1, $start_col-1, 1])->getStyle([1, 1, $start_col-1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row+1;
        foreach($test_criterias as $key => $row){
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
        header('Content-Disposition: attachment;filename="Chỉ tiêu kiểm tra.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Chỉ tiêu kiểm tra.xlsx');
        $href = '/exported_files/Chỉ tiêu kiểm tra.xlsx';
        return $this->success($href);
    }

    public function importTestCriteria(Request $request){
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
            if ($key > 1) {
                $input = [];
                if(!$row['C'] || !$row['D'] || !$row['A']){
                    continue;
                }
                if(isset($line_arr[Str::slug($row['A'])])){
                    $input['line_id'] = $line_arr[Str::slug($row['A'])];
                }
                $input['id'] = $row['C'];
                $input['name'] = $row['D'];
                $input['phan_loai'] = Str::slug($row['B']);
                $input['popup_input'] = $row['F'];
                $input['popup_select'] = $row['E'];
                $input['tieu_chuan'] = $row['H'];
                $input['nguyen_tac'] = $row['I'];
                $input['master_data'] = $row['J'];
                $input['ghi_chu'] = $row['K'];
                $validated = TestCriteria::validateUpdate($input);
                if ($validated->fails()) {
                    return $this->failure('', 'Lỗi dòng thứ '.($key).': '.$validated->errors()->first());
                }
                $data[] = $input;
            }
        }
        try {
            DB::beginTransaction();
            TestCriteria::where('id', 'not like', 'S%')->where('id', 'not like', 'I%')->delete();
            foreach ($data as $key => $input) {
                // $test_criteria = TestCriteria::where('line_id', $input['line_id'])->where('phan_loai', $input['phan_loai'])->where('name', $input['name'])->first();
                // if($test_criteria) {
                //     $test_criteria->update($input);
                // }else{
                    $test_criteria = TestCriteria::create($input);
                // }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            $err = ErrorLog::saveError($request, $th);
            return $this->failure($err, 'Đã xảy ra lỗi');
        }
        return $this->success([], 'Upload thành công');
    }
    public function importTestCriteriaVer2(Request $request){
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
                if(!$row['A'] || !$row['B'] || !$row['C'] || !$row['D']){
                    throw new Exception('Thiếu thông tin hàng ' . $key, 1);
                }
                if(isset($line_arr[Str::slug($row['A'])])){
                    $input['line_id'] = $line_arr[Str::slug($row['A'])];
                }
                $input['id'] = $row['B'];
                $input['name'] = $row['C'];
                $input['hang_muc'] = $row['D'];
                $input['chi_tieu'] = $row['E'];
                $input['tieu_chuan'] = $row['F'];
                $input['phan_dinh'] = $row['G'];
                // $validated = TestCriteria::validateUpdate($input);
                // if ($validated->fails()) {
                //     return $this->failure('', 'Lỗi dòng thứ '.($key).': '.$validated->errors()->first());
                // }
                $data[] = $input;
            }
        }
        try {
            DB::beginTransaction();
            foreach ($data as $key => $input) {
                $test_criteria = TestCriteria::find($input['id']);
                if($test_criteria) {
                    $test_criteria->update($input);
                }else{
                    $test_criteria = TestCriteria::create($input);
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            $err = ErrorLog::saveError($request, $th);
            return $this->failure($err, 'Đã xảy ra lỗi');
        }
        return $this->success([], 'Upload thành công');
    }
}
