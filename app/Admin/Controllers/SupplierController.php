<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CustomUser;
use App\Models\DRC;
use App\Models\GroupPlanOrder;
use App\Models\LocatorMLTMap;
use App\Models\Material;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\Vehicle;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupplierController extends Controller
{
    use API;

    public function index(Request $request)
    {
        $query = Supplier::query();
        if($request->id){
            $query->where('id', 'like', "%$request->id%");
        }
        if($request->name){
            $query->where('name', 'like', "%$request->name%");
        }
        $records = $query->get();
        return $this->success($records);
    }
    public function update(Request $request)
    {
        $input = $request->all();
        $supplier = Supplier::where('id', $input['id'])->first();
        if ($supplier) {
            $update = $supplier->update($input);
            if ($update) {
                return $this->success($update);
            } else {
                return $this->failure('', 'Không thành công');
            }
        } else {
            return $this->failure('', 'Không tìm thấy nhà cung cấp');
        }
    }

    public function create(Request $request)
    {
        $input = $request->all();
        $check = Supplier::find($input['id']);
        if($check) return $this->failure('', 'Mã nhà cung cấp đã tồn tại');
        $supplier = Supplier::create($input);
        return $this->success($supplier, 'Tạo thành công');
    }

    public function delete(Request $request)
    {
        $input = $request->all();
        Supplier::whereIn('id', $input)->delete();
        return $this->success('Xoá thành công');
    }
}
