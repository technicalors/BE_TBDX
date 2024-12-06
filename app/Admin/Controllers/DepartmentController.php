<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CustomUser;
use App\Models\Department;
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

class DepartmentController extends Controller
{
    use API;

    public function index(Request $request){
        $deparrtments = Department::all();
        return $this->success($deparrtments);
    }

    public function create(Request $request){
        $deparrtment = Department::create($request->all());
        return $this->success($deparrtment);
    }

    public function update(Request $request){
        $deparrtment = Department::where('id', $request->id)->update($request->all());
        return $this->success($deparrtment);
    }

    public function delete(Request $request){
        $input = $request->all();
        $deparrtment = Department::whereIn('id', $input)->delete();
        return $this->success($deparrtment);
    }
}
