<?php

namespace App\Admin\Controllers;

use App\Models\Customer;
use App\Models\CustomerShort;
use App\Models\ErrorLog;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Str;
use App\Traits\API;
use Illuminate\Support\Facades\DB;

class CustomerController extends AdminController
{
    use API;
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Customer';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Customer());
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        });

        $grid->column('id', __('Mã khách hàng'));
        $grid->column('name', __('Tên khách hàng'));
        $grid->column('thong_tin', __('Thông tin'));

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
        $show = new Show(Customer::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('thong_tin', __('Thong tin'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Customer());

        $form->number('name', __('Name'));
        $form->number('thong_tin', __('Thong tin'));

        return $form;
    }

    public function getCustomerByShortName(Request $request)
    {
        $query = CustomerShort::with('customer')->orderBy('customer_id')->orderBy('short_name');
        if (isset($request->short_name)) {
            $query->where('short_name', 'like', "%$request->short_name%");
        }
        if (isset($request->name)) {
            $query->whereHas('customer', function($q) use($request){
                $q->where('name', 'like', "%$request->name%");
            });
        }
        if (isset($request->id)) {
            $query->where('customer_id', 'like', "%$request->id%");
        }
        $totalPage = $query->count();
        if (isset($request->page) || isset($request->pageSize)) {
            $query->offset(($request->page - 1) * $request->pageSize)->limit($request->pageSize);
        }
        $customer_short = $query->get();
        foreach ($customer_short as $customer) {
            $customer->name = $customer->customer->name ?? "";
        }
        return $this->success(['data'=>$customer_short,'totalPage'=>$totalPage]);
    }

    public function getCustomers(Request $request)
    {
        $query = Customer::orderBy('id');
        if (isset($request->name)) {
            $query->where('name', 'like', "%$request->name%");
        }
        if (isset($request->id)) {
            $query->where('id', 'like', "%$request->id%");
        }
        $totalPage = $query->count();
        if (isset($request->page) || isset($request->pageSize)) {
            $query->offset(($request->page - 1) * $request->pageSize)->limit($request->pageSize);
        }
        $customers = $query->get();
        return $this->success(['data'=>$customers,'totalPage'=>$totalPage]);
    }

    public function updateCustomer(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $customer = Customer::updateOrCreate(['id'=>$input['customer_id']], ['id'=>$input['customer_id'], 'name'=>$input['name']]);
            if ($customer) {
                $short_name = CustomerShort::updateOrCreate(['customer_id'=>$customer->id], ['short_name'=>$input['short_name']]);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        return $this->success($customer, 'Cập nhật thành công');
    }

    public function createCustomer(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $customer = Customer::create(['id'=>$input['customer_id'], 'name'=>$input['name']]);
            if ($customer) {
                $short_name = CustomerShort::create(['customer_id'=>$customer->id, 'short_name'=>$input['short_name']]);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        return $this->success($customer, 'Tạo thành công');
    }

    public function deleteCustomer(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $input = $request->all();
            CustomerShort::whereIn('id', $input)->delete();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        return $this->success('Xoá thành công');
    }

    public function exportCustomer(Request $request)
    {
        $query = CustomerShort::with('customer')->orderBy('short_name');
        if (isset($request->name)) {
            $query->where('short_name', 'like', "%$request->name%");
        }
        if (isset($request->id)) {
            $query->where('customer_id', 'like', "%$request->id%");
        }
        $customer_short = $query->get();
        foreach ($customer_short as $customer) {
            $customer->name = $customer->customer->name ?? "";
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
                'outCustomer' => array(
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
        $header = ['STT', 'Tên KH rút gọn', 'Mã KH (không quá 5 ký tự)', 'Tên khách hàng'];
        $table_key = [
            'A' => 'stt',
            'B' => 'short_name',
            'C' => 'customer_id',
            'D' => 'name',
        ];
        foreach ($header as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            }
            $start_col += 1;
        }

        $sheet->setCellValue([1, 1], 'Quản lý khách hàng')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row + 1;
        foreach ($customer_short->toArray() as $key => $row) {
            $table_col = 1;
            $sheet->setCellValue([1, $table_row], $key + 1)->getStyle([1, $table_row])->applyFromArray($centerStyle);
            $row = (array)$row;
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
        header('Content-Disposition: attachment;filename="Danh sách khách hàng.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Danh sách khách hàng.xlsx');
        $href = '/exported_files/Danh sách khách hàng.xlsx';
        return $this->success($href);
    }

    public function importCustomer(Request $request)
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
        try {
            DB::beginTransaction();
            $spreadsheet = $reader->load($_FILES['files']['tmp_name']);
            $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            Customer::query()->delete();
            CustomerShort::query()->delete();
            foreach ($allDataInSheet as $key => $row) {
                if ($key > 1) {
                    if ($row['C'] && $row['B']) {
                        $input['id'] = $row['C'];
                        $input['customer_id'] = $row['C'];
                        $input['short_name'] = $row['B'];
                        $input['name'] = $row['D'];
                        CustomerShort::create($input);
                        $check = Customer::find($input['id']);
                        if (!$check) {
                            Customer::create($input);
                        }
                    }
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, "Đã xảy ra lỗi");
        }

        return $this->success([], 'Upload thành công');
    }
}
