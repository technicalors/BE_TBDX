<?php

namespace App\Admin\Controllers;

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
            if ($key > 2) {
                if ($row['A']) {
                    $input = [];
                    $input['customer_id'] = $row['B'];
                    $input['dai'] = $row['C'];
                    $input['rong'] = $row['D'];
                    $input['cao'] = $row['E'];
                    $input['kich_thuoc'] = $row['F'];
                    $input['phan_loai_1'] = Str::slug($row['H']);
                    $input['buyer_id'] = $row['I'];
                    $input['kho_khuon'] = $row['J'];
                    $input['dai_khuon'] = $row['K'];
                    $input['so_con'] = $row['L'];
                    $input['so_manh_ghep'] = $row['M'];
                    $input['pad_xe_ranh'] = $row['N'];
                    $input['khuon_id'] = $row['O'];
                    $input['machine_id'] = $row['P'];
                    $input['sl_khuon'] = $row['Q'];
                    // $input['buyer_note'] = $row['Q'];
                    $input['note'] = $row['R'];
                    $input['layout'] = $row['S'];
                    $input['supplier'] = $row['T'];
                    $input['ngay_dat_khuon'] = $row['U'];
                    $khuon_link[] = $input;
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
}
