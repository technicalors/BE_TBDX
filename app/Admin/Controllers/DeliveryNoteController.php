<?php

namespace App\Admin\Controllers;

use App\Models\CustomUser;
use App\Models\DeliveryNote;
use App\Models\DRC;
use App\Models\ErrorLog;
use App\Models\GroupPlanOrder;
use App\Models\LocatorMLTMap;
use App\Models\Material;
use App\Models\Order;
use App\Models\Vehicle;
use App\Models\WareHouseFGExport;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Error;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeliveryNoteController extends AdminController
{
    use API;

    public function getDeliveryNoteList(Request $request)
    {
        $query = DeliveryNote::query()->orderBy('created_at');
        if(isset($request->start_date) && isset($request->end_date)){
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)));
        }
        if(isset($request->id)){
            $query->where('id', $request->id);
        }
        if(isset($request->vehicle_id)){
            $query->where('vehicle_id', 'like', "%$request->vehicle_id%");
        }
        if(isset($request->exporter_id)){
            $query->whereHas('exporter', function($q)use($request){
                $q->where('name', 'like', "%$request->exporter_id%");
            });
        }
        if(isset($request->created_by)){
            $query->whereHas('creator', function($q)use($request){
                $q->where('name', 'like', "%$request->created_by%");
            });
        }
        $totalPage = $query->count();
        if(isset($request->page) && isset($request->pageSize)){
            $page = ($request->page - 1) ?? 0;
            $pageSize = $request->pageSize;
            $query->offset($page * $pageSize)->limit($pageSize)->get();
        }
        $records = $query->with('exporters')->get();
        foreach ($records as $key => $value) {
            $value->exporter_ids = array_filter(array_map(function($exporter){
                return $exporter['id'];
            }, $value->exporters->toArray()));
        }
        return $this->success(['data'=>$records, 'totalPage'=>$totalPage]);
    }
    public function createDeliveryNote(Request $request)
    {
        $input = $request->all();
        if((empty($input['exporter_ids']) || count($input['exporter_ids']) < 0) && isset($input['exporter_id'])){
            $input['exporter_ids'] = [$input['exporter_id']];
        }
        if(empty($input['exporter_ids'])){
            return $this->failure('', 'Chưa chọn người xuất');
        }
        $note = DeliveryNote::where('id', 'like', date('d/m/y-') . '%')->orderByRaw("CAST(SUBSTRING_INDEX(id, '-', -1) AS UNSIGNED) DESC")->first();
        if ($note) {
            $stt = $note->id;
        } else {
            $stt = date('d/m/y-') . '0';
        }
        $number_id = str_replace(date('d/m/y-'), '', $stt);
        try {
            DB::beginTransaction();
            $note = DeliveryNote::create([
                'id' => date('d/m/y-') . ($number_id + 1),
                'created_by' => $request->user()->id,
                'vehicle_id' => $input['vehicle_id'],
                'exporter_id' => $input['exporter_id'] ?? null,
                'driver_id' => $input['driver_id'],
            ]);
            if ($note) {
                $warehouse_fg_export_ids = array_column($input['export_ids'], 'id');
                $export_query = WareHouseFGExport::whereIn('id', $warehouse_fg_export_ids);
                $export_query->update(['delivery_note_id' => $note->id]);
                $note->save();
                $note->exporters()->attach($input['exporter_ids']);
            }
            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            throw $th;
        }
        return $this->success('', 'Tạo thành công');
    }

    public function deleteDeliveryNote(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $note = DeliveryNote::where('id', $input['id'])->doesntHave('warehouse_fg_logs')->first();
            if ($note) {
                $note->delete();
                $note->exporters()->detach();
                WareHouseFGExport::where('delivery_note_id', $input['id'])->update(['delivery_note_id' => null]);
            } else {
                return $this->failure('', 'Không thể xoá');
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
        return $this->success('', 'Xoá thành công');
    }
    public function updateDeliveryNote(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $note = DeliveryNote::find($input['id']);
            if($note){
                $note->update($input);
                $note->exporters()->sync($input['exporter_ids']);
            }
            else{
                return $this->failure('', 'Không tìm thấy lệnh xuất');
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th->getMessage(), 'Đã xảy ra lỗi');
        }
        return $this->success('', 'Cập nhật thành công');
    }
}
