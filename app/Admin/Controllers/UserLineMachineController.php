<?php

namespace App\Admin\Controllers;

use App\Models\CustomUser;
use App\Models\ErrorLog;
use App\Models\Jig;
use App\Models\Sheft;
use App\Models\UserLine;
use App\Models\UserLineMachine;
use App\Models\UserMachine;
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

class UserLineMachineController extends AdminController
{
    use API;

    public function getMachineAssignment(Request $request){
        $input = $request->all();
        $query = CustomUser::orderBy('name');
        if (!isset($request->all_user)) {
            $query->whereNull('deleted_at');
        }
        if(isset($input['username'])){
            $query->where('username', 'like', "%".$input['username']."%");
        }
        if(isset($input['name'])){
            $query->where('name', 'like', "%".$input['name']."%");
        }
        $totalPage = $query->count();
        if(isset($input['page']) && isset($input['pageSize'])){
            $query->offset(($input['page'] - 1) * $input['pageSize'])->limit($input['pageSize']);
        }
        $records = $query->with('user_line', 'user_machine')->select('username', 'name', 'id')->get();
        foreach($records as $record){
            $record->line_id = $record->user_line->line_id ?? null;
            $record->machine_id = $record->user_machine->pluck('machine_id') ?? [];
        }
        return $this->success(['data'=>$records, 'totalPage'=>$totalPage]);
    }
    public function updateMachineAssignment(Request $request){
        $input = $request->all();
        $input['user_id'] = $input['id'];
        try {
            DB::beginTransaction();
            $user_line = UserLine::updateOrCreate(
                ['user_id'=>$input['user_id']],
                ['user_id'=>$input['user_id'], 'line_id'=>$input['line_id']],
            );
            if(isset($input['machine_id'])){
                UserMachine::where('user_id', $input['user_id'])->delete();
                foreach($input['machine_id'] as $machine_id){
                    UserMachine::create(['user_id'=>$input['user_id'], 'machine_id'=>$machine_id]);
                }
            }
            DB::commit();
            return $this->success('', 'Cập nhật thành công');
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
    }

    public function createMachineAssignment(Request $request){
        $input = $request->all();
        try {
            DB::beginTransaction();
            $machine_assign = UserLineMachine::create($input);
            DB::commit();
            return $this->success($machine_assign, 'Tạo thành công');
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
    }

    public function deleteMachineAssignment(Request $request){
        $input = $request->all();
        try {
            DB::beginTransaction();
            UserLineMachine::whereIn('id', $input)->delete();
            DB::commit();
            return $this->success('', 'Xoá thành công');
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
    }
}
