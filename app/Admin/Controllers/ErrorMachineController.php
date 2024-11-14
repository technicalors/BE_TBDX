<?php

namespace App\Admin\Controllers;

use App\Models\ErrorLog;
use App\Models\ErrorMachine;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use App\Models\Line;
use Illuminate\Support\Facades\DB;

class ErrorMachineController extends AdminController
{
    use API;
    public function upload_xlsx($action, $title)
    {
        return view('import', [
            "action" => $action,
            "title" => $title
        ]);
    }

    public function import()
    {

        if (!isset($_FILES['files'])) { {
                admin_error('Định dạng file không đúng', 'error');
                return back();
            }
        }

        ErrorMachine::truncate();


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
        $allDataInSheet = $spreadsheet->getSheet(2)->toArray(null, true, true, true);

        for ($i = 7; $i <= count($allDataInSheet); $i++) {

            $row = $allDataInSheet[$i];
            // if (!isset($row['B']) || !isset($row["C"])) continue;
            $label1 = ["B", "C", "D", "E"];
            $label2 = ["F", "G", "H", "I"];
            $label3 = ["J", "K", "L", "M"];
            $label4 = ["N", "O", "P", "Q"];
            $this->createError($label1, $row, 10);
            $this->createError($label2, $row, 11);
            $this->createError($label3, $row, 12);
            $this->createError($label4, $row, 13);
        }
        admin_success('Tải lên thành công', 'success');
        return back();
    }


    private function createError($label, $row, $line_id)
    {
        if ($row[$label[0]] == "") return;
        $id = Str::slug($row[$label[0]]);
        if ($id == "ma-loi") return;
        $error = new ErrorMachine();
        $error->id = $id;
        $error->noi_dung = $row[$label[1]];
        $error->nguyen_nhan = $row[$label[2]];
        $error->khac_phuc = $row[$label[3]];
        $error->phong_ngua = "";
        $error->line_id = $line_id;
        $error->save();
        return $error;
    }


    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Lỗi dừng máy';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ErrorMachine());
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        });

        $grid->tools(function ($tool) {
            $tool->append($this->upload_xlsx('/admin/error-machines/import', 'Chọn file kế hoạch sản xuất'));
        });


        $grid->column('id', __('Mã lỗi'))->sortable();
        $grid->column('noi_dung', __('Nội dung'));
        $grid->column('nguyen_nhan', __('Nguyên nhân'));
        $grid->column('khac_phuc', __('Khắc phục'));
        $grid->column('line.name', __('Công đoạn'))->sortable();

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
        $show = new Show(ErrorMachine::findOrFail($id));


        $show->field('id', __('Id'));
        $show->field('noi_dung', __('Noi dung'));
        $show->field('nguyen_nhan', __('Nguyen nhan'));
        $show->field('khac_phuc', __('Khac phuc'));
        $show->field('line_id', __('Line id'));
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
        $form = new Form(new ErrorMachine());

        $form->text('noi_dung', __('Noi dung'));
        $form->text('nguyen_nhan', __('Nguyen nhan'));
        $form->text('khac_phuc', __('Khac phuc'));
        $form->number('line_id', __('Line id'));

        return $form;
    }

    public function getErrorMachines(Request $request)
    {
        $query = ErrorMachine::with('line')->has('line')->orderBy('created_at');
        $line_arr = $this->lineArray();
        if (isset($request->line_id)) {
            $query->where('line_id', $request->line_id);
        }
        if (isset($request->code)) {
            $query->where('code', 'like', "%" . $request->code . "%");
        }
        if (isset($request->ten_su_co)) {
            $query->where('ten_su_co', 'like', "%" . $request->ten_su_co . "%");
        }
        $totalPage = $query->count();
        if (isset($request->page) || isset($request->pageSize)) {
            $query->offset(($request->page - 1) * $request->pageSize)->limit($request->pageSize);
        }
        $error_machines = $query->get();
        return $this->success(['data' => $error_machines, 'totalPage' => $totalPage]);
    }

    public function updateErrorMachine(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $validated = ErrorMachine::validateUpdate($input);
            if ($validated->fails()) {
                return $this->failure('', $validated->errors()->first());
            }
            $error = ErrorMachine::where('id', $input['id'])->first();
            if ($error) {
                $update = $error->update($input);
                return $this->success($error);
            } else {
                return $this->failure('', 'Không tìm thấy lỗi máy');
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
    }

    public function createErrorMachine(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $validated = ErrorMachine::validateUpdate($input, false);
            if ($validated->fails()) {
                return $this->failure('', $validated->errors()->first());
            }
            $error = ErrorMachine::create($input);
            DB::commit();
            return $this->success($error, 'Tạo thành công');
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
    }

    public function deleteErrorMachines(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            ErrorMachine::whereIn('id', $input)->delete();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        return $this->success('Xoá thành công');
    }

    public function exportErrorMachines(Request $request)
    {
        $query = ErrorMachine::with('line')->has('line')->orderBy('created_at');
        $line_arr = $this->lineArray();
        if (isset($request->line)) {
            $query->where('line_id', isset($line_arr[Str::slug($request->line)]) ? $line_arr[Str::slug($request->line)] : '');
        }
        if (isset($request->code)) {
            $query->where('code', 'like', "%" . $request->code . "%");
        }
        $error_machines = $query->get();
        foreach ($error_machines as $error_machine) {
            $error_machine->line_name = $error_machine->line->name;
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
        $header = ['Mã lỗi', 'Nội dung', 'Công đoạn', 'Nguyên nhân', 'Khắc phục', 'Phòng ngừa'];
        $table_key = [
            'A' => 'code',
            'B' => 'noi_dung',
            'C' => 'line_name',
            'D' => 'nguyen_nhan',
            'E' => 'khac_phuc',
            'F' => 'phong_ngua',
        ];
        foreach ($header as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            }
            $start_col += 1;
        }
        $sheet->setCellValue([1, 1], 'Quản lý lỗi máy')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row + 1;
        foreach ($error_machines->toArray() as $key => $row) {
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
        header('Content-Disposition: attachment;filename="Lỗi máy.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Lỗi máy.xlsx');
        $href = '/exported_files/Lỗi máy.xlsx';
        return $this->success($href);
    }

    public function importErrorMachines(Request $request)
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
        $line_arr = $this->lineArray();
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 2
            if ($key > 2) {
                $input = [];
                $input['code'] = $row['A'];
                $input['noi_dung'] = $row['B'];
                if (isset($line_arr[Str::slug($row['C'])])) {
                    $input['line_id'] = $line_arr[Str::slug($row['C'])];
                }
                $input['nguyen_nhan'] = $row['D'];
                $input['khac_phuc'] = $row['E'];
                $input['phong_ngua'] = $row['F'];
                $validated = ErrorMachine::validateUpdate($input);
                if ($validated->fails()) {
                    return $this->failure('', 'Lỗi dòng thứ ' . ($key) . ': ' . $validated->errors()->first());
                }
                $data[] = $input;
            }
        }
        foreach ($data as $key => $input) {
            $error_machine = ErrorMachine::where('code', $input['code'])->first();
            if ($error_machine) {
                $error_machine->update($input);
            } else {
                $error_machine = ErrorMachine::create($input);
            }
        }
        return $this->success([], 'Upload thành công');
    }

    public function lineArray()
    {
        $line_arr = [];
        $lines = Line::select('id', 'name')->get();
        foreach ($lines as $line) {
            $line_arr[Str::slug($line->name)] = $line->id;
        }
        return $line_arr;
    }
}
