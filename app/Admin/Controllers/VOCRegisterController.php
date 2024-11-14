<?php

namespace App\Admin\Controllers;

use App\Helpers\QueryHelper;
use App\Models\CustomUser;
use App\Models\DRC;
use App\Models\GroupPlanOrder;
use App\Models\LocatorMLTMap;
use App\Models\Material;
use App\Models\Order;
use App\Models\Vehicle;
use App\Models\VOCRegister;
use App\Models\VOCType;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VOCRegisterController extends AdminController
{
    use API;

    public function getList(Request $request)
    {
        $start = Carbon::now()->subDays(7)->format('Y-m-d');
        $end = Carbon::now()->format('Y-m-d');
        $query = VOCRegister::query()->with(['type', 'register', 'replier'])->orderByDesc('created_at');

        if (isset($request->no)) {
            $query->where('no', 'like', "%{$request->no}%");
        }

        if (isset($request->registered_by)) {
            $query->where('registered_by', $request->registered_by);
        }

        if (isset($request->replied_by)) {
            $query->where('replied_by', $request->replied_by);
        }

        if (isset($request->start_date)) {
            $query->whereDate('created_at', '>=', $request->start_date);
        } else {
            $query->whereDate('created_at', '>=', $start);
        }

        if (isset($request->end_date)) {
            $query->whereDate('created_at', '>=', $request->end_date);
        } else {
            $query->whereDate('created_at', '>=', $end);
        }

        $records = $query->get();
        return $this->success($records);
    }

    public function createRecord(Request $request) {
        $request->validate([
            'voc_type_id' => 'required|exists:voc_types,id',
            'title' => 'required',
            'content' => 'required',
            'solution' => 'nullable',
        ]);

        $prefix = date('Ymd');
        $no = QueryHelper::generateNewId(new VOCRegister(), $prefix, 3, 'no');
        Log::debug($no);
        $result = VOCRegister::create([
            'no' => $no,
            'voc_type_id' => $request->voc_type_id,
            'title' => $request->title,
            'content' => $request->content,
            'solution' => $request->solution ?? null,
            'registered_by' => auth()->user()->id,
            'registered_at' => date('Y-m-d H:i:s'),
            'reply' => null,
            'replied_by' => null,
            'replied_at' => null,
            'status' => VOCRegister::STATUS_PENDING,
        ]);
        
        return $this->success($result, 'Thao tác thành công', 200);
    }

    public function updateRecord(Request $request, $id) {
        $request->validate([
            'reply' => 'required',
        ]);

        $record = VOCRegister::find($id);
        if (empty($record)) return $this->failure([], 'Không tìm thấy dữ liệu', 404);

        $record->update([
            'reply' => $request->reply,
            'replied_by' => auth()->user()->id,
            'replied_at' => date('Y-m-d H:i:s'),
            'status' => VOCRegister::STATUS_REPLIED,
        ]);
        
        return $this->success($record, 'Thao tác thành công', 200);
    }

    public function deleteRecord($id) {
        $record = VOCRegister::find($id);
        if (empty($record)) return $this->failure([], 'Không tìm thấy dữ liệu', 404);

        $record->delete();
        
        return $this->success([], 'Thao tác thành công', 200);
    }
}
