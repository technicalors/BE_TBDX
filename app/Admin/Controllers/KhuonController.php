<?php

namespace App\Admin\Controllers;

use App\Models\Customer;
use App\Models\CustomUser;
use App\Models\ErrorLog;
use App\Models\Khuon;
use App\Models\KhuonLink;
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
use Illuminate\Support\Facades\DB;
use stdClass;

class KhuonController extends AdminController
{
    use API;

    public function getKhuon(Request $request)
    {
        $query = KhuonLink::orderBy('created_at', 'DESC')->orderBy('khuon_id');
        if (isset($request->id)) {
            $query->where('khuon_id', 'like', "%$request->id%");
        }
        if (isset($request->customer_id)) {
            $query->where('customer_id', 'like', "%$request->customer_id%");
        }
        if (isset($request->kich_thuoc)) {
            $query->where('kich_thuoc', 'like', "%$request->kich_thuoc%");
        }
        if (isset($request->page) && isset($request->pageSize)) {
            $count = $query->count();
            $query->offset(($request->page - 1) * $request->pageSize)->limit($request->pageSize);
            $khuon = $query->get();
            foreach ($khuon as $value) {
                $value->designer_name = $value->designer->name ?? "";
            }
            return $this->success(['data' => $khuon, 'totalPage' => $count]);
        } else {
            $khuon = $query->get();
            return $this->success($khuon);
        }
    }
    public function updateKhuon(Request $request)
    {
        $input = $request->all();
        $khuon = KhuonLink::where('id', $input['id'])->first();
        if ($khuon) {
            $validated = KhuonLink::validateUpdate($input);
            if ($validated->fails()) {
                return $this->failure('', $validated->errors()->first());
            }
            $input['phan_loai_1'] = Str::slug($input['phan_loai_1']);
            $input['designer_id'] = CustomUser::where('name', 'like', "%" . trim($input['designer_name']) . "%")->first()->id ?? null;
            $update = $khuon->update($input);
            return $this->success($khuon, 'Cập nhật thành công');
        } else {
            return $this->failure('', 'Không tìm thấy công đoạn');
        }
    }

    public function createKhuon(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $validated = KhuonLink::validateUpdate($input, false);
            if ($validated->fails()) {
                return $this->failure('', $validated->errors()->first());
            }
            $input['phan_loai_1'] = Str::slug($input['phan_loai_1']);
            if(!empty($input['designer_name'])){
                $input['designer_id'] = CustomUser::where('name', 'like', "%" . trim($input['designer_name']) . "%")->first()->id ?? null;
            }
            $khuon = KhuonLink::create($input);
            DB::commit();
            return $this->success($khuon, 'Tạo thành công');
        } catch (\Throwable $th) {
            //throw $th;
            DB::commit();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Không thành công');
        }
    }

    public function deleteKhuon(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            KhuonLink::whereIn('id', $input)->delete();
            DB::commit();
            return $this->success('Xoá thành công');
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('Xoá không thành công');
        }
    }

    public function importKhuon(Request $request)
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
        $khuon_link = [];
        foreach ($allDataInSheet as $key => $row) {
            $row = array_map("trim", $row);
            if ($key > 2) {
                if ($row['A']) {
                    $input = [];
                    $input['customer_id'] = $row['B'];
                    $input['dai'] = $row['C'];
                    $input['rong'] = $row['D'];
                    $input['cao'] = $row['E'];
                    $input['kich_thuoc'] = $row['F'];
                    $input['phan_loai_1'] = Str::slug($row['G']);
                    $input['buyer_id'] = $row['H'];
                    $input['kho_khuon'] = $row['I'];
                    $input['dai_khuon'] = $row['J'];
                    $input['so_con'] = $row['K'];
                    $input['so_manh_ghep'] = $row['L'];
                    $input['pad_xe_ranh'] = $row['M'];
                    $input['khuon_id'] = $row['N'];
                    $input['machine_id'] = $row['O'];
                    // $input['sl_khuon'] = $row['Q'];
                    // $input['buyer_note'] = $row['Q'];
                    $input['note'] = $row['P'];
                    $input['layout'] = $row['Q'];
                    $input['supplier'] = $row['R'];
                    $input['ngay_dat_khuon'] = $row['S'];
                    $designer = CustomUser::where('name', 'like', "%" . trim($row['T']) . "%")->first();
                    $input['designer_id'] = $designer->id ?? null;
                    $khuon_link[] = array_filter($input);
                }
            }
        }
        try {
            DB::beginTransaction();
            KhuonLink::query()->delete();
            foreach ($khuon_link as $key => $input) {
                KhuonLink::insert($input);
            }
            DB::commit();
            return $this->success([], 'Upload thành công');
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
    }

    public function exportKhuon(Request $request)
    {
        $query = KhuonLink::orderBy('created_at', 'DESC')->orderBy('khuon_id');
        if (isset($request->id)) {
            $query->where('khuon_id', 'like', "%$request->id%");
        }
        if (isset($request->customer_id)) {
            $query->where('customer_id', 'like', "%$request->customer_id%");
        }
        if (isset($request->kich_thuoc)) {
            $query->where('kich_thuoc', 'like', "%$request->kich_thuoc%");
        }
        $khuon = $query->get();
        $data = [];
        foreach ($khuon as $key => $value) {
            $obj = new stdClass;
            $obj->stt = $key + 1;
            $obj->short_name = $value->customer_id;
            $obj->dai = $value->dai;
            $obj->rong = $value->rong;
            $obj->cao = $value->cao;
            $obj->kich_thuoc = $value->kich_thuoc;
            $obj->phan_loai_1 = $value->phan_loai_1;
            $obj->buyer_id = $value->buyer_id;
            $obj->kho_khuon = $value->kho_khuon;
            $obj->dai_khuon = $value->dai_khuon;
            $obj->so_con = $value->so_con;
            $obj->so_manh_ghep = $value->so_manh_ghep;
            $obj->pad_xe_ranh = $value->pad_xe_ranh;
            $obj->khuon_id = $value->khuon_id;
            $obj->machine_id = $value->machine_id;
            $obj->note = $value->note;
            $obj->layout = $value->layout;
            $obj->supplier = $value->supplier;
            $obj->ngay_dat_khuon = $value->ngay_dat_khuon;
            $obj->designer_name = $value->designer->name ?? "";
            $data[] = (array)$obj;
        }
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'wrapText' => true
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
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $start_row = 2;
        $start_col = 1;
        $sheet = $spreadsheet->getActiveSheet();
        $header = [
            'STT',
            'Khách hàng',
            'Kích thước ĐH'=>[
                'Dài',
                'Rộng',
                'Cao',
                'Kích thước chuẩn'
            ],
            'Phân loại 1',
            'Mã buyer',
            'Khuôn bế'=>[
                'Khổ',
                'Dài',
                'Số con'
            ],
            'Số mảnh ghép',
            'Pad xẻ rãnh',
            'Mã khuôn bế',
            'Máy',
            'Ghi chú',
            'Layout',
            'Nhà cung cấp',
            'Ngày đặt khuôn',
            'Người thiết kế'
        ];
        foreach ($header as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row + 1])->getStyle([$start_col, $start_row, $start_col, $start_row + 1])->applyFromArray($headerStyle);
            } else {
                $sheet->setCellValue([$start_col, $start_row], $key)->mergeCells([$start_col, $start_row, $start_col + count($cell) - 1, $start_row])->getStyle([$start_col, $start_row, $start_col + count($cell) - 1, $start_row])->applyFromArray($headerStyle);
                foreach ($cell as $val) {
                    $sheet->setCellValue([$start_col, $start_row + 1], $val)->getStyle([$start_col, $start_row + 1])->applyFromArray($headerStyle);
                    $start_col += 1;
                }
                continue;
            }
            $start_col += 1;
        }
        $sheet->setCellValue([1, 1], 'Danh sách mã khuôn bế theo mã Buyer KH')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->fromArray($data, null, 'A4');
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
        $start_row_table = $start_row + 1;
        $sheet->getStyle([1, $start_row_table, $start_col - 1, count($data) + $start_row_table - 1])->applyFromArray(
            array_merge(
                $centerStyle,
                array(
                    'borders' => array(
                        'allBorders' => array(
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => array('argb' => '000000'),
                        ),
                    )
                )
            )
        );
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Danh sách mã khuôn bế.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Danh sách mã khuôn bế.xlsx');
        $href = '/exported_files/Danh sách mã khuôn bế.xlsx';
        return $this->success($href);
    }
}
