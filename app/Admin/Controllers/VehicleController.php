<?php

namespace App\Admin\Controllers;

use App\Models\CustomUser;
use App\Models\DRC;
use App\Models\GroupPlanOrder;
use App\Models\LocatorMLTMap;
use App\Models\Material;
use App\Models\Order;
use App\Models\Vehicle;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VehicleController extends AdminController
{
    use API;

    public function getVehicles(Request $request)
    {
        $query = Vehicle::with('driver', 'assistant_driver1', 'assistant_driver2');
        $records = $query->get();
        foreach ($records as $key => $record) {
            $record->user1_name = $record->driver->name ?? "";
            $record->user1_username = $record->driver->username ?? "";
            $record->user1_phone_number = $record->driver->phone_number ?? "";
            $record->user2_name = $record->assistant_driver1->name ?? "";
            $record->user2_username = $record->assistant_driver1->username ?? "";
            $record->user2_phone_number = $record->assistant_driver1->phone_number ?? "";
            $record->user3_name = $record->assistant_driver2->name ?? "";
            $record->user3_username = $record->assistant_driver2->username ?? "";
            $record->user3_phone_number = $record->assistant_driver2->phone_number ?? "";
        }
        return $this->success($records);
    }
    public function updateVehicles(Request $request)
    {
        $input = $request->all();
        $vehicle = Vehicle::where('id', $input['id'])->first();
        if ($vehicle) {
            $update = $vehicle->update($input);
            if ($update) {
                isset($input['user1_phone_number']) && $vehicle->driver()->update(['phone_number' => $input['user1_phone_number']]);
                isset($input['user2_phone_number']) && $vehicle->assistant_driver1()->update(['phone_number' => $input['user2_phone_number']]);
                isset($input['user3_phone_number']) && $vehicle->assistant_driver2()->update(['phone_number' => $input['user3_phone_number']]);
                return $this->success($update);
            } else {
                return $this->failure('', 'Không thành công');
            }
        } else {
            return $this->failure('', 'Không tìm thấy xe');
        }
    }

    public function createVehicles(Request $request)
    {
        $input = $request->all();
        $check = Vehicle::find($input['id']);
        if($check) return $this->failure('', 'Số xe đã tồn tại trong hệ thống');
        $vehicle = Vehicle::create($input);
        isset($input['user1_phone_number']) && $vehicle->driver()->update(['phone_number' => $input['user1_phone_number']]);
        isset($input['user2_phone_number']) && $vehicle->assistant_driver1()->update(['phone_number' => $input['user2_phone_number']]);
        isset($input['user3_phone_number']) && $vehicle->assistant_driver2()->update(['phone_number' => $input['user3_phone_number']]);
        return $this->success($vehicle, 'Tạo thành công');
    }

    public function deleteVehicles(Request $request)
    {
        $input = $request->all();
        Vehicle::whereIn('id', $input)->delete();
        return $this->success('Xoá thành công');
    }

    public function exportVehicles(Request $request)
    {
        $query = Vehicle::with('driver', 'assistant_driver1', 'assistant_driver2');
        $records = $query->get();
        foreach ($records as $record) {
            $record->user1_name = $record->driver->name ?? "";
            $record->user1_username = $record->driver->username ?? "";
            $record->user1_phone_number = $record->driver->phone_number ?? "";
            $record->user2_name = $record->assistant_driver1->name ?? "";
            $record->user2_username = $record->assistant_driver1->username ?? "";
            $record->user2_phone_number = $record->assistant_driver1->phone_number ?? "";
            $record->user3_name = $record->assistant_driver2->name ?? "";
            $record->user3_username = $record->assistant_driver2->username ?? "";
            $record->user3_phone_number = $record->assistant_driver2->phone_number ?? "";
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
        $header = ['STT', 'Phương tiện', 'Tải trọng', 'Lái xe' => ['Họ và tên', 'MÃ NV', 'SĐT'], 'Phụ xe 1' => ['Họ và tên', 'MÃ NV', 'SĐT'], 'Phụ xe 2' => ['Họ và tên', 'MÃ NV', 'SĐT']];
        $table_key = [
            'A' => 'stt',
            'B' => 'id',
            'C' => 'weight',
            'D' => 'user1_name',
            'E' => 'user1_username',
            'F' => 'user1_phone_number',
            'G' => 'user2_name',
            'H' => 'user2_username',
            'I' => 'user2_phone_number',
            'J' => 'user3_name',
            'K' => 'user3_username',
            'L' => 'user3_phone_number',
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

        $sheet->setCellValue([1, 1], 'Danh sách xe')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row + 2;
        foreach ($records->toArray() as $key => $row) {
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
        header('Content-Disposition: attachment;filename="Danh sách xe.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Danh sách xe.xlsx');
        $href = '/exported_files/Danh sách xe.xlsx';
        return $this->success($href);
    }

    public function importVehicles(Request $request)
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
        $vehicle = [];
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 4
            if ($key > 2) {
                $input = [];
                $input['id'] = $row['B'];
                $input['weight'] = $row['C'];
                $user1 = CustomUser::where('username', (int)$row['E'])->where('username', '<>', 'admin')->first();
                $input['user1'] = $user1->id ?? null;
                $user2 = CustomUser::where('username', (int)$row['H'])->where('username', '<>', 'admin')->first();
                $input['user2'] = $user2->id ?? null;
                $user3 = CustomUser::where('username', (int)$row['K'])->where('username', '<>', 'admin')->first();
                $input['user3'] = $user3->id ?? null;
                if ($input['id']) {
                    $vehicle[] = $input;
                }
            }
        }
        foreach ($vehicle as $key => $input) {
            Vehicle::create($input);
        }
        return $this->success([], 'Upload thành công');
    }

    public function num_to_letters($n)
    {
        $n -= 1;
        for ($r = ""; $n >= 0; $n = intval($n / 26) - 1)
            $r = chr($n % 26 + 0x41) . $r;
        return $r;
    }
}
