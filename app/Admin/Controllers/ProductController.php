<?php

namespace App\Admin\Controllers;

use App\Models\Customer;
use App\Models\Material;
use App\Models\Product;
use App\Models\Spec;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;

class ProductController extends AdminController
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

        $materials = Material::all();
        $customers = Customer::all();

        $products = Product::all();

        Spec::truncate();

        foreach ($products as $item) {
            $item->delete();
        }
        // $products->delete();
        $mark_material = [];
        $mark_customer = [];
        $mark_product = [];

        foreach ($materials as $item) {
            // dd($item->id);
            $mark_material[$item->id] = true;
        }
        foreach ($customers as $item) {
            $mark_customer[$item->id] = true;
        }

        // dd($mark_material);

        $titleRow1 = $allDataInSheet[3];
        $titleRow2 = $allDataInSheet[4];
        for ($i = 5; $i <= count($allDataInSheet); $i++) {
            $row = $allDataInSheet[$i];

            if (isset($mark_product[$row['B']]) || $row['B'] == '' || $row['H'] == '') continue;
            $product = new Product();
            $product->id = str_replace(" ", "", $row["B"]);
            $product->name = $row["C"];
            $product->customer_id = $row["F"];
            $product->material_id = $row["H"];
            $product->dinh_muc = $row["N"];
            $product->dinh_muc_thung = $row["DC"];

            $product->nhiet_do_phong = $row["EF"];
            $product->do_am_phong = $row["EG"];
            $product->do_am_giay = $row["EH"];
            $product->thoi_gian_bao_on = $row["EI"];

            $product->chieu_dai_thung = $row["DE"];
            $product->chieu_rong_thung = $row["DF"];
            $product->chieu_cao_thung = $row["DG"];
            $product->the_tich_thung = $row["DH"];
            $product->kt_kho_dai = $row["BC"];
            $product->kt_kho_rong = $row["BE"];
            $product->ver = $row['D'];
            $product->his = $row['E'];



            $product->u_nhiet_do_phong = $row['EO'];
            $product->u_do_am_phong = $row['EP'];
            $product->u_do_am_giay = $row['EQ'];
            $product->u_thoi_gian_u = $row['ER'];

            $this->importSpec($row, $titleRow1, $titleRow2, $product->id);
            // return;

            $product->save();
            $mark_product[$row['B']] = true;

            if (!isset($mark_material[$row['H']])) {
                $material = new Material();
                $material->id = $row['H'];
                $material->ten = $row['I'];
                $material->code = $row['J'];
                $info = [
                    "mau" => $row['K'],
                    "DL" => $row['L']
                ];

                $material->thong_so = $info;
                $material->save();
                $mark_material[$row['H']] = true;
            }
            if ($row['F'] == '') continue;
            if (!isset($mark_customer[$row['F']])) {
                $customer = new Customer();
                $customer->name = $row['G'];
                $customer->id = $row['F'];
                $customer->save();
                $mark_customer[$row['F']] = true;
            }
        }

        admin_success('Tải lên thành công', 'success');
        return back();
    }


    private function importSpec($currRow, $titleRow1, $titleRow2, $product_id)
    {



        $title = [];
        foreach ($titleRow2 as $i => $item) {
            $title[$i] = $titleRow2[$i];
            if (!isset($item)) {
                $title[$i] = $titleRow1[$i];
            }
        }

        $lines = [
            [
                "id" => 10,
                "start" => "BB",
                "end" => "BT"
            ],
            [
                "id" => 11,
                "start" => "BU",
                "end" => "CC"
            ],
            [
                "id" => 13,
                "start" => "CU",
                "end" => "DQ"
            ],
            [
                "id" => 15,
                "start" => "CU",
                "end" => "DQ"
            ],
            [
                "id" => "all",
                "start" => "DR",
                "end" => "DT"
            ],

        ];
        $line_id = [];
        foreach ($currRow as $key => $item) {
            if ($key == "BB") {
                $line_id = [10];
            } else if ($key == "BU") {
                $line_id = [11];
            } else if ($key == "CU") {
                $line_id = [13, 15];
            } else if ($key == "EC") {
                $line_id = [10, 11, 12, 14, 13, 15];
            } else if ($key == "DU") {
                $line_id = [];
            } else if ($key == "CD") {
                $line_id = [12, 14];
            } else if ($key == "DR") {
                $line_id = [20];
            } else if ($key == "EF") {
                $line_id = [];
            }

            foreach ($line_id as $id) {
                // if (!isset($title[$key])) continue;
                $spec = new Spec();
                $spec->name = $title[$key];
                $spec->value = $item;
                $spec->product_id = $product_id;
                $spec->slug = Str::slug($spec->name);
                $spec->line_id = $id;
                $spec->save();
            }
        }
    }



    /**
     * Make a grid builder.
     *
     * @return Grid
     */

    protected function grid()
    {
        $grid = new Grid(new Product());
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            // $actions->disableEdit();
            $actions->disableView();
        });
        $grid->disableCreateButton();

        $grid->tools(function ($tool) {
            $tool->append($this->upload_xlsx('/admin/products/import', 'Chọn file sản phẩm/spec'));
        });
        $grid->column('id', __('Mã hàng'));
        $grid->column('name', __('Tên'));
        // $grid->column('unit_id', __('Unit id'));
        // $grid->column('info', __('Info'));
        $grid->column('material_id', __('Mã nguyên liệu'));
        $grid->column('customer.name', __('Khách hàng'));
        $grid->column('ver', __('Ver'));
        $grid->column('his', __('His'));
        $grid->column('nhiet_do_phong', __('Nhiệt độ phòng'));
        $grid->column('do_am_phong', __('Độ ẩm phòng'));
        $grid->column('do_am_giay', __('Độ ẩm giấy'));
        $grid->column('dinh_muc', __('số tờ/pallet'));
        $grid->column('thoi_gian_bao_on', __('Thời gian bảo ôn'));
        $grid->column('chieu_dai_thung', __('Chiều dài thùng(mm)'));
        $grid->column('chieu_rong_thung', __('Chiều rộng thùng(mm)'));
        $grid->column('chieu_cao_thung', __('Chiều cao thùng(mm)'));
        $grid->column('the_tich_thung', __('Thể tích thùng(m3)'));
        // $grid->column('created_at', __('Created at'));
        // $grid->column('updated_at', __('Updated at'));

        $grid->filter(function($filter){
            $filter->like('name', 'Name');
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
        $show = new Show(Product::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('unit_id', __('Unit id'));
        $show->field('info', __('Info'));
        $show->field('material_id', __('Material id'));
        $show->field('customer_id', __('Customer id'));
        $show->field('nhiet_do_phong', __('Nhiet do phong'));
        $show->field('do_am_phong', __('Do am phong'));
        $show->field('do_am_giay', __('Do am giay'));
        $show->field('thoi_gian_cho', __('Thoi gian cho'));
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
        $form = new Form(new Product());

        // $form->text('name', __('Name'));
        // $form->text('unit_id', __('Unit id'));
        // $form->textarea('info', __('Info'));
        // $form->text('material_id', __('Material id'))->default('1');
        // $form->text('customer_id', __('Customer id'));
        // $form->text('nhiet_do_phong', __('Nhiet do phong'));
        // $form->text('do_am_phong', __('Do am phong'));
        // $form->text('do_am_giay', __('Do am giay'));
        // $form->text('thoi_gian_cho', __('Thoi gian cho'));
        $form->text('thoi_gian_bao_on', __('Thời gian bảo ôn'));
        $form->text('u_thoi_gian_u', __('Thời gian ủ'));

        return $form;
    }

    public function getSpecProduct(Request $request){
        $query = Product::with('customer')->orderBy('created_at', 'DESC');
        if(isset($request->id)){
            $query->where('id', 'like', "%$request->id%");
        }
        if(isset($request->name)){
            $query->where('name', 'like', "%$request->name%");
        }
        $products = $query->get();
        return $this->success($products);
    }
    public function updateSpecProduct(Request $request){
        $input = $request->all();
        $customer = Customer::where('name', $input['customer'])->first();
        $input["customer_id"] = $customer ? $customer->id : null;
        $validated = Product::validateUpdate($input);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $product = Product::where('id', $input['id'])->first();
        if($product){
            $update = $product->update($input);
            return $this->success($product);
        }
        else{
            return $this->failure('', 'Không tìm thấy máy');
        }
    }

    public function createSpecProduct(Request $request){
        $input = $request->all();
        $customer = Customer::where('name', $input['customer'])->first();
        $input["customer_id"] = $customer ? $customer->id : null;
        $validated = Product::validateUpdate($input, false);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $product = Product::create($input);
        return $this->success($product, 'Tạo thành công');
    }

    public function deleteSpecProduct(Request $request){
        $input = $request->all();
        Product::whereIn('id', $input)->delete();
        return $this->success('Xoá thành công');
    }

    public function exportSpecProduct(Request $request){
        $query = Product::with('customer')->orderBy('created_at', 'DESC');
        if(isset($request->id)){
            $query->where('id', 'like', "%$request->id%");
        }
        if(isset($request->name)){
            $query->where('name', 'like', "%$request->name%");
        }
        $products = $query->get();
        foreach($products as $product){
            $product->customer_name = $product->customer->name;
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
        $header = ['Mã hàng', 'Tên', 'Mã nguyên liệu', 'Khách hàng', 'Ver', 'His', 'Nhiệt độ phòng', 'Độ ẩm phòng', 'Độ ẩm giấy', 'Số tờ/pallet', 'Thời gian bảo ôn', 'Chiều dài thùng', 'Chiều rộng thùng', 'Chiều cao thùng', 'Thể tích thùng', 'Định mức thùng', 'Nhiệt độ phòng ủ', 'Độ ẩm phòng ủ', 'Độ ẩm giấy ủ', 'Thời gian ủ', 'Number of bin', 'KT khổ dài', 'KT khổ rộng'];
        $table_key = [
            'A'=>'id',
            'B'=>'name',
            'C'=>'material_id',
            'D'=>'customer_name',
            'E'=>'ver',
            'F'=>'his',
            'G'=>'nhiet_do_phong',
            'H'=>'do_am_phong',
            'I'=>'do_am_giay',
            'J'=>'dinh_muc',
            'K'=>'thoi_gian_bao_on',
            'L'=>'chieu_dai_thung',
            'M'=>'chieu_rong_thung',
            'N'=>'chieu_cao_thung',
            'O'=>'the_tich_thung',
            'P'=>'dinh_muc_thung',
            'Q'=>'u_nhiet_do_phong',
            'R'=>'u_do_am_phong',
            'S'=>'u_do_am_giay',
            'T'=>'u_thoi_gian_u',
            'U'=>'number_of_bin',
            'V'=>'kt_kho_dai',
            'W'=>'kt_kho_rong',
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
        foreach($products->toArray() as $key => $row){
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
        header('Content-Disposition: attachment;filename="Sản phẩm.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Sản phẩm.xlsx');
        $href = '/exported_files/Sản phẩm.xlsx';
        return $this->success($href);
    }

    public function importSpecProduct(Request $request){
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
                $input['material_id'] = $row['C'];
                $customer = Customer::where('name', $row['D'])->first();
                $input["customer_id"] = $customer ? $customer->id : null;
                $input['ver'] = $row['E'];
                $input['his'] = $row['F'];
                $input['nhiet_do_phong'] = $row['G'];
                $input['do_am_phong'] = $row['H'];
                $input['do_am_giay'] = $row['I'];
                $input['dinh_muc'] = $row['J'];
                $input['thoi_gian_bao_on'] = $row['K'];
                $input['chieu_dai_thung'] = $row['L'];
                $input['chieu_rong_thung'] = $row['M'];
                $input['chieu_cao_thung'] = $row['N'];
                $input['the_tich_thung'] = $row['O'];
                $input['dinh_muc_thung'] = $row['P'];
                $input['u_nhiet_do_phong'] = $row['Q'];
                $input['u_do_am_phong'] = $row['R'];
                $input['u_do_am_giay'] = $row['S'];
                $input['u_thoi_gian_u'] = $row['T'];
                $input['number_of_bin'] = $row['U'];
                $input['kt_kho_dai'] = $row['V'];
                $input['kt_kho_rong'] = $row['W'];
                $validated = Product::validateUpdate($input);
                if ($validated->fails()) {
                    return $this->failure('', 'Lỗi dòng thứ '.($key).': '.$validated->errors()->first());
                }
                $data[] = $input;
            }
        }
        foreach ($data as $key => $input) {
            $product = Product::where('id', $input['id'])->first();
            if ($product) {
                $product->update($input);
            } else {
                Product::create($input);
            }
            
        }
        return $this->success([], 'Upload thành công');
    }
}
