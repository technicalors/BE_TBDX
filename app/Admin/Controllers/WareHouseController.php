<?php

namespace App\Admin\Controllers;

use App\Models\Cell;
use App\Models\Sheft;
use App\Models\WareHouse;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;

class WarehouseController extends AdminController
{
    use API;
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'WareHouse';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        
        $grid = new Grid(new Cell());
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        });
        $grid->tools(function ($tool) {
            $tool->append($this->upload_xlsx('/admin/warehouse/import', 'Chọn file thông tin kho'));
        });
        $grid->column(__(' Mã kho'))->display(function () {
            $id = $this->sheft->warehouse->id;
            return "<span >$id</span>";
        });
        $grid->column(__('Kho'))->display(function () {
            $id = $this->sheft->warehouse->name;
            return "<span >$id</span>";
        });

        $grid->column("id", __('Rack'))->display(function () {
            return $this->id;
        });

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
        $show = new Show(WareHouse::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('note', __('Note'));
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
        $form = new Form(new WareHouse());

        $form->text('name', __('Name'));
        $form->text('note', __('Note'));

        return $form;
    }


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

        // dd($allDataInSheet);
        WareHouse::truncate();
        Sheft::truncate();
        Cell::truncate();

        $mark_warehouse = [];
        $mark_sheft = [];
        $mark_cell = [];


        for ($i = 4; $i <= count($allDataInSheet); $i++) {
            $row = $allDataInSheet[$i];
            $ten_kho = $row['C'];
            $ma_kho = $row['D'];

            $ma_rack = $row['E'][0];
            $ma_cell = $row['E'];


            if (!isset($mark_warehouse[$ma_kho])) {
                $warehouse = new WareHouse();
                $warehouse->name = $ten_kho;
                $warehouse->save();
                $warehouse->id = $ma_kho;
                $warehouse->save();
                $mark_warehouse[$ma_kho] = $warehouse;
            } else {
                $warehouse = $mark_warehouse[$ma_kho];
            }

            if (!isset($mark_sheft[$ma_rack])) {
                $sheft = new Sheft();
                $sheft->name = $ma_rack;
                $sheft->warehouse_id = $warehouse->id;
                $sheft->save();
                $sheft->id = $ma_rack;
                $sheft->save();
                $mark_sheft[$ma_rack] = $sheft;
            }

            if (!isset($mark_cell[$ma_cell])) {
                $cell = new Cell();
                $cell->name = $ma_cell;
                $cell->sheft_id = $sheft->id;
                $cell->save();
                $cell->id = $ma_cell;
                $cell->save();
                $mark_sheft[$ma_cell] = $cell;
            }
        }
        admin_success('Tải lên thành công', 'success');
        return back();
    }

    public function getWarehouses(Request $request){
        $query = WareHouse::orderBy('created_at', 'desc');
        if(isset($request->id)){
            $query->where('id', 'like', "%$request->id%");
        }
        if(isset($request->name)){
            $query->where('name', 'like', "%$request->name%");
        }
        $warehouses = $query->get();
        return $this->success($warehouses);
    }
    public function updateWarehouse(Request $request){
        $input = $request->all();
        $warehouse = WareHouse::where('id', $input['id'])->first();
        if($warehouse){
            $validated = WareHouse::validateUpdate($input);
            if ($validated->fails()) {
                return $this->failure('', $validated->errors()->first());
            }
            $update = $warehouse->update($input);
            if($update){
                return $this->success($warehouse);
            }else{
                return $this->failure('', 'Không thành công');
            }  
        }
        else{
            return $this->failure('', 'Không tìm thấy công đoạn');
        }
    }

    public function createWarehouse(Request $request){
        $input = $request->all();
        $validated = WareHouse::validateUpdate($input, false);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $warehouse = WareHouse::create($input);
        return $this->success($warehouse, 'Tạo thành công');
    }

    public function deleteWarehouses(Request $request){
        $input = $request->all();
        WareHouse::whereIn('id', $input)->delete();
        return $this->success('Xoá thành công');
    }

    public function exportWarehouses(Request $request){
        $query = WareHouse::orderBy('created_at', 'desc');
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
        $header = ['ID', 'Tên', 'Ghi chú'];
        $table_key = [
            'A'=>'id',
            'B'=>'name',
            'C'=>'note',
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
        
        $sheet->setCellValue([1, 1], 'Quản lý kho')->mergeCells([1, 1, $start_col-1, 1])->getStyle([1, 1, $start_col-1, 1])->applyFromArray($titleStyle);
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
        header('Content-Disposition: attachment;filename="Kho.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Kho.xlsx');
        $href = '/exported_files/Kho.xlsx';
        return $this->success($href);
    }

    public function importWarehouses(Request $request){
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
                $input['note'] = $row['C'] ?? " ";
                $validated = WareHouse::validateUpdate($input);
                if ($validated->fails()) {
                    return $this->failure('', 'Lỗi dòng thứ '.($key).': '.$validated->errors()->first());
                }
                $data[] = $input;
            }
        }
        foreach ($data as $key => $input) {
            $warehouse = WareHouse::where('id', $input['id'])->first();
            if ($warehouse) {
                $warehouse->update($input);
            } else {
                WareHouse::create($input);
            }
        }
        return $this->success([], 'Upload thành công');
    }
}
