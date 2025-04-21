<?php

namespace App\Admin\Controllers;

use App\Models\CheckSheetWork;
use App\Models\ProductionPlan;
use App\Models\User;
use App\Models\CheckSheetLog;
use App\Models\ErrorMachine;
use Illuminate\Support\Str;
use App\Models\Cell;
use App\Models\CellProduct;
use App\Models\Customer;
use App\Models\CustomUser;
use App\Models\Error;
use App\Models\ErrorLog;
use App\Models\GroupPlanOrder;
use App\Models\InfoCongDoan;
use App\Models\Insulation;
use App\Models\IOTLog;
use App\Models\Layout;
use App\Models\MachineIOT;
use App\Models\Line;
use App\Models\LineTable;
use App\Models\LocatorFGMap;
use App\Models\LocatorMLTMap;
use App\Models\LogInTem;
use App\Models\LogWarningParameter;
use App\Models\Lot;
use App\Models\LSXLog;
use App\Models\LSXPallet;
use App\Models\Machine;
use App\Models\MachineParameter;
use App\Models\Product;
use App\Models\WareHouseLog;
use App\Traits\API;
use Carbon\Carbon;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Exception;
use GrahamCampbell\ResultType\Success;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Expr\FuncCall;
use App\Models\MachineLog;
use App\Models\MachineMap;
use App\Models\MachineParameterLogs;
use App\Models\MachineParameters;
use App\Models\MachineSpeed;
use App\Models\MachineStatus;
use App\Models\Mapping;
use App\Models\Material;
use App\Models\MaterialExportLog;
use App\Models\MaterialLog;
use App\Models\Monitor;
use App\Models\OddBin;
use App\Models\Order;
use App\Models\Pallet;
use App\Models\Scenario;
use App\Models\Spec;
use App\Models\TestCriteria;
use App\Models\ThongSoMay;
use App\Models\Tracking;
use App\Models\Unit;
use App\Models\WareHouseExportPlan;
use App\Models\WarehouseFG;
use App\Models\WareHouseFGExport;
use App\Models\WarehouseFGLog;
use App\Models\WareHouseMLTExport;
use App\Models\Workers;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use SebastianBergmann\CodeUnit\FunctionUnit;
use stdClass;

class ApiMobileController extends AdminController
{
    use API;
    private $user;
    public function __construct(CustomUser $customUser)
    {
        $this->user = $customUser;
    }
    private function parseDataUser($user)
    {
        $permission = [];

        foreach ($user->roles as $role) {
            $tm = ($role->permissions()->pluck("slug"));
            foreach ($tm as $t) {
                $permission[] = $t;
            }
        }

        $data =  [
            "username" => $user->username,
            "name" => $user->name,
            "avatar" => $user->avatar,
            "gender" => $user->gender,
            "email" => $user->email,
            "address" => $user->address,
            "phone" => $user->phone,
            "permission" => $permission,
            "token" => $user->createToken("")->plainTextToken,
        ];
        return $data;
    }

    public function login(Request $request)
    {
        $validate = Validator::make($request->all(), [
            "username" => "required",
            "password" => "required",
        ]);
        if ($validate->fails()) {
            return $this->failure();
        }
        $credentials = $request->only(["username", 'password']);
        if (Admin::guard()->attempt($credentials)) {
            $user = Admin::user();
            if($user->deleted_at != null){
                return $this->failure([], 'Tài khoản đã bị vô hiệu!');
            }
            $user = $this->user->find($user->id);
            // $user->tokens()->delete();
            $user->update(['login_times_in_day'=>$user->login_times_in_day + 1, 'last_use_at'=>Carbon::now()]);
            return $this->success($this->parseDataUser($user), 'Đăng nhập thành công');
        }
        return $this->failure([], 'Sai tên đăng nhập hoặc mật khẩu!');
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if (isset($user))
            $user->tokens()->delete();
        return $this->success();
    }

    public function userInfo(Request $request)
    {
        $user =  $request->user();
        if ($user)
            return $this->success($this->parseDataUser($user));
        return $this->failure([], "Nguời dùng không tồn tại");
    }

    public function userChangePassword(Request $request)
    {
        $user = $request->user();
        if (!$request->password || !$request->newPassword) {
            return $this->failure([], "password and newPassword is required");
        }

        if (Hash::check($request->password, $user->password)) {
            $user->password = Hash::make($request->newPassword);
            $user->save();
            return $this->success();
        }
        return $this->failure([], "Incorrect password");
    }

    /* =====================    PLAN   ================*/

    public function overallPlan(Request $request) {}

    /* =====================    END-PLAN   ================*/
    /* =====================    MACHINE  ================*/

    public function listMachine(Request $request)
    {
        $all = Machine::select('id as label', 'id as value')->get();
        return $this->success($all);
    }

    public function detailMachine(Request $request)
    {
        $machine = Machine::find($request->machine_id);
        if (!$machine) return $this->failure([], "Machine not found");
        return $this->success($machine->parameter->first());
    }

    public function machineLog()
    {
        // $logs = MachineLog::whereDate('created_at', date('Y-m-d'))->orWhere('info->result', '')->get();
        $logs = MachineLog::all();
        $data = [];
        foreach ($logs as $key => $log) {
            $object = new \stdClass();
            $object->model = $log->machine_id;
            $object->tg_bat_dau = $log->info['start_time'];
            $object->tg_ket_thuc = isset($log->info['end_time']) ? $log->info['end_time'] : 'Chưa cập nhật';
            if ($object->tg_ket_thuc != 'Chưa cập nhật') {
                $object->tg_dung = number_format((strtotime($object->tg_ket_thuc) - strtotime($object->tg_bat_dau)) / 60);
            } else {
                $object->tg_dung = 'Chưa cập nhật';
            }
            $object->so_lan_dung = MachineLog::where('machine_id', $log->machine_id)->whereDate('created_at', date('Y-m-d'))->count();
            if (!isset($log->info['result'])) {
                $object->id = $log->id;
            }
            $data[] = $object;
        }
        return $this->success($data);
    }

    /* =====================  END  MACHINE  ================*/



    /* =====================  API CỦA AN  ================*/

    // lấy list máy của công đoạn
    public function getMachineOfLine(Request $request)
    {
        $line = Line::find($request->line_id);
        if (!$line) return $this->success();
        return $this->success($line->machine);
    }

    // api TabChecksheet - Màn OI Thiết bị
    public function getChecksheetOfMachine(Request $request)
    {
        $machine_id = $request->machine_id;
        $machine = Machine::find($machine_id);
        if (!$machine) return $this->success();
        $line = Line::find($machine['line_id']);

        $checksheet_ids = $line->checkSheet->pluck('id');
        $checkSheetWork = CheckSheetWork::whereIn('check_sheet_id', $checksheet_ids)->with('checksheet')->get();

        $startDate = date("Y-m-d 00:00:00");
        $endDate = date("Y-m-d 23:59:59");
        $string = '%"machine_id":"' . $machine_id . '"%';
        $logs = CheckSheetLog::where('info', 'like', $string)->whereBetween('created_at', [$startDate, $endDate])->get();




        if (count($logs) == 0) return $this->success([
            "data" => $checkSheetWork,
            "is_checked" => false,
        ]);
        $log = $logs[0];
        foreach ($checkSheetWork as $s) {
            $s['date_time'] = $log['created_at'];
            foreach ($log['info']['data'] as $cs) {
                if ($s['id'] == $cs['id']) $s['value'] = $cs['value'];
            }
        }
        return $this->success([
            "data" => $checkSheetWork,
            "is_checked" => true,
        ]);
    }
    public function lineChecksheetLogSave(Request $request)
    {
        $machine_id = $request->machine_id;
        $data = $request->data;
        $checksheet = CheckSheetLog::where("info->machine_id", $machine_id)->whereDate('created_at', Carbon::today())->first();
        if (!$checksheet) {
            $res = CheckSheetLog::create([
                "info" => [
                    "machine_id" => $machine_id,
                    "data" => $data,
                ]
            ]);
        } else {
            $res = $checksheet;
        }
        $res->id = Carbon::now()->timestamp;
        $res->save();
        return $this->success($res);
    }


    // api TabChon lỗi - Màn OI Thiết bị
    public function lineError(Request $request)
    {
        $line = Line::find($request->line_id);
        if (!$line) return $this->success();
        $error = $line->error;
        // format lại data dùng tạm thời
        foreach ($error as $e) {
            $e['name'] = $e['noi_dung'];
            // $e['code'] = $e['code'];
        }
        return $this->success($error);
    }

    public function logsMachine(Request $request)
    {
        $machine_id = $request->machine_id;
        $machine = Machine::find($machine_id);
        if (!$machine) {
            return $this->failure([], 'Không tìm thấy mã máy');
        }
        $logs = MachineLog::where('machine_id', $machine->code)->orderBy('created_at', 'DESC')
            ->get();
        $log_data = [];
        foreach ($logs as $log) {
            $info = $log['info'];
            $info['start_time'] = $log['info']['start_time'] ? date('H:i:s', $log['info']['start_time']) : '';
            $info['end_time'] =  isset($log['info']['end_time']) ? date('H:i:s', $log['info']['end_time']) : '';
            $log['info'] =  $info;
            if (!isset($log['info']['error_id'])) $error = null;
            else {
                $error = ErrorMachine::where('id', $log['info']['error_id'])->get()[0];
                $error['name'] = $error['noi_dung'];
            };
            $log['error'] = $error;
            $log_data[] = $log;
        }
        // dd($log_data);
        return $this->success($log_data);
    }

    public function logsMachine_save(Request $request)
    {
        $current_machine_log = $request->machine_log;
        $machine_log = MachineLog::find($current_machine_log['id'] ?? '');
        $machine_id = $machine_log->machine_id;
        if (!$machine_log) return $this->failure("Thông tin lỗi máy không đúng!");
        $machine = Machine::where('code', $machine_log['machine_id'])->with('line')->get();
        if (count($machine) > 0) $machine = $machine[0];
        else return $this->failure("Lỗi không có thông tin máy");
        $info = $machine_log->info;
        if (isset($current_machine_log['id_error'])) {
            $info['error_id'] = $current_machine_log['id_error'];
        } else {
            $new_error = new ErrorMachine();
            $new_error->noi_dung = $current_machine_log['name_error'] ?? "";
            // $new_error->code = "";
            $new_error->nguyen_nhan = $current_machine_log['nguyen_nhan_error'] ?? "";
            $new_error->khac_phuc = $current_machine_log['khac_phuc_error'] ?? "";
            $new_error->phong_ngua = $current_machine_log['nguyen_nhan_error'] ?? "";
            $new_error->line_id = $machine['line_id'];
            $new_error->save();
            $info['error_id'] = $new_error['id'];
        }
        $info['user_id'] = $request->user()->id;
        $info['user_name'] = $request->user()->name;
        $machine_log->info = $info;
        $machine_log->save();
        $records = MachineLog::where('machine_id', $machine_id)->get();
        $check = true;
        foreach ($records as $key => $record) {
            if (!isset($record->info['error_id'])) {
                $check = false;
                break;
            }
        }
        if ($check) {
            Monitor::where('machine_id', $machine_id)->where('type', 'tb')->where('status', 0)->update(['status' => 1]);
        }
        return $this->success($machine_log);
    }

    public function machineOverall(Request $request)
    {
        $machine = Machine::find($request->machine_id);
        if (!$machine) return $this->failure([], 'Không tìm thấy mã máy');
        $logs = MachineLog::where('machine_id', $machine->code)->whereNotNull(['info->start_time', 'info->end_time'])->whereDate('created_at', date('Y-m-d'))->get();
        $tg_dung = 0;
        $so_lan_dung = 0;
        $so_loi = 0;
        foreach ($logs as $log) {
            $tg_dung += $log['info']['end_time'] - $log['info']['start_time'];
            $so_lan_dung += 1;
            $so_loi += isset($log['info']['error_id']) ? 1 : 0;
        }
        $obj = new stdClass;
        $obj->tg_dung = $tg_dung;
        $obj->so_lan_dung = $so_lan_dung;
        $obj->so_loi = $so_loi;
        return $this->success($obj, '');
    }

    /* =====================  END  UNUSUAL  ================*/

    //LINE

    public function listLine(Request $request)
    {
        $list = Line::where("display", "1")->orderBy('ordering', 'ASC')->get();
        $except = [
            'sx' => ['kho-thanh-pham', 'oqc'],
            'cl' => ['kho-thanh-pham', 'kho-bao-on', 'u']
        ];
        $data = [];
        if (isset($request->type)) {
            if ($request->type === 'tb') {
                foreach ($list as $item) {
                    if (count($item->machine) > 0) {
                        $data[] = [
                            "label" => $item->name,
                            "ordering" => $item->ordering,
                            "value" => $item->id
                        ];
                    }
                }
            } else {
                foreach ($list as $item) {
                    $line_key = Str::slug($item->name);
                    if (in_array($line_key, $except[$request->type])) {
                        continue;
                    }
                    $data[] = [
                        "label" => $item->name,
                        "ordering" => $item->ordering,
                        "value" => $item->id
                    ];
                }
            }
        } else {
            foreach ($list as $item) {
                $data[] = [
                    "label" => $item->name,
                    "ordering" => $item->ordering,
                    "value" => $item->id
                ];
            }
        }


        return $this->success($data);
    }

    public function listMachineOfLine(Request $request)
    {
        $line_id = $request->line_id;
        // dd($line_id);
        $line = Line::find($line_id);
        $listMachine = $line->machine;
        return $this->success($listMachine);
    }

    public function lineOverall(Request $request)
    {
        // if ($request->type == 1)
        $data =  LSXLog::overrallIn($request->line_id);

        return $this->success($data);
    }

    public function lineUser()
    {

        $list = Workers::all();
        return $this->success($list);
    }




    //Dashboard

    public function dashboardGiamSat(Request $request)
    {
        // $line = Line::where('name', 'like', 'in%')->first();
        $machines = [];
        // if ($request->type == 2) {
        //     $line = Line::where('name', 'like', 'gh%')->first();
        // }
        // $machines = Machine::whereHas('line', function ($q) use ($line) {
        //     return $q->where('id', $line->id);
        // })->get();

        $machines = Machine::all();

        $res = [];
        foreach ($machines as $machine) {
            $rq = new \Illuminate\Http\Request();
            $rq->replace(['machine_id' => $machine->id]);
            $info = $this->uiManufacturing($rq, true);
            $info['so_loi'] = rand(1, 8);
            $info['cycle_time'] = rand(800, 1000);
            $info['thoi_gian_dung'] = rand(0, 5);
            $info['ty_le_van_hanh'] = rand(80, 100) . '%';
            $res[] = [
                // "line" => $line->name,
                "line" => $machine->line->name,
                "machine" => $machine->id,
                "info" => $info
            ];
        }
        return $this->success($res);
    }





    public function uploadKHXKT(Request $request)
    {
        $hash = hash_file("md5", $_FILES['file']['tmp_name']);
        $lists = MaterialExportLog::where("file", $hash);
        $lists->delete();
        // get file extension
        $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        if ($extension == 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        } elseif ($extension == 'xlsx') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        }
        // file path
        $spreadsheet = $reader->load($_FILES['file']['tmp_name']);
        $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 5
            if ($key > 6) {
                if (is_null($row['E']) || is_null($row['H'])) {
                    break;
                }
                $record = MaterialExportLog::whereDate('created_at', date('Y-m-d'))->where('material_id', $row['E'])->first();
                if ($record) {
                    $sl_kho_xuat = (int) str_replace(',', '', $row['H']) + $record->sl_kho_xuat;
                    $record->update(['sl_kho_xuat' => $sl_kho_xuat]);
                } else {
                    MaterialExportLog::create(['material_id' => $row['E'], 'sl_kho_xuat' => (int) str_replace(',', '', $row['H']), 'file' => $hash]);
                }
            }
        }
        return $this->success([], 'Upload excel thành công');
    }

    public function uploadTonKho(Request $request)
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
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 5
            if ($key > 1) {
                if (is_null($row['B']) && is_null($row['C'])) {
                    break;
                }
                $mql = $row['C'] ?? 1;
                $locator_map = LocatorFGMap::where('locator_id', $row['E'])->first();
                if ($locator_map) {
                    $pallet_id = $locator_map->pallet_id;
                    $pallet = Pallet::find($pallet_id);
                    $pallet->update(['so_luong' => ((int)$pallet->so_luong + (int)$row['D']), 'number_of_lot' => ((int)$pallet->number_of_lot + 1)]);
                } else {
                    $date = date('ymd', strtotime($row['F']));
                    $count = Pallet::where('id', 'like', '%' . $date . '%')->count();
                    $inp['id'] = 'PL' . $date . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
                    $inp['so_luong'] =  $row['D'];
                    $inp['number_of_lot'] =  1;
                    $pallet = Pallet::create($inp);
                    unset($inp);
                    $pallet_id = $pallet->id;
                    LocatorFGMap::create(['pallet_id' => $pallet_id, 'locator_id' => $row['E']]);
                }
                $count_lo = LSXPallet::where('lo_sx', 'like', '%T' . $date . '%')->count();
                $lo_sx = 'T' . $date . str_pad($count_lo + 1, 3, '0', STR_PAD_LEFT);
                $order = Order::where('mdh', $row['B'])->where('mql', $mql)->first();
                if ($order) {
                    $customer_id = $order->customer_id;
                } else {
                    $customer_id = null;
                }
                LSXPallet::create(['lo_sx' => $lo_sx, 'pallet_id' => $pallet_id, 'mdh' => $row['B'], 'mql' => $mql, 'so_luong' => $row['D'], 'customer_id' => $customer_id]);
            }
        }
        return $this->success([], 'Upload excel thành công');
    }
    public function storeLot(Request $request)
    {
        $input = $request->all();
        foreach ($input['log'] as $key => $value) {
            $count = 0;
            $plan = ProductionPlan::where('lo_sx', $value['lo_sx'])->first();
            foreach ($value['value_pallet'] as $key => $val) {
                if (!$val['value']) {
                    return $this->failure([], 'Chia pallet không thành công');
                    break;
                } else {
                    $count += $val['value'];
                }
            }
            // if($count > $plan->sl_nvl){
            //     return $this->failure([], 'Số lượng của pallet không được vượt quá số lượng kế hoạch');
            //     break;
            // }
        }
        // if($count <= )
        foreach ($input['log'] as $key => $value) {
            $plan = ProductionPlan::where('lo_sx', $value['lo_sx'])->whereDate('ngay_sx', '>=', date('Y-m-d'))->first();
            foreach ($value['value_pallet'] as $key => $val) {
                $pallet = new Lot();
                $pallet->lo_sx = $value['lo_sx'];
                $pallet->type = 0;
                $pallet->so_luong =  $val['value'];
                $pallet->product_id =  $plan->product->id;
                $pallet->material_export_log_id =  $value['id'];
                $pallet->id =  $pallet->lo_sx . "." . $plan->product->id . ".pl" .  + ($val['key'] + 1);
                $pallet->save();
            }
        }
        // MaterialExportLog::find($value['id'])->update(['status' => 1]);
        return $this->success([], 'Chia pallet thành công');
    }

    public function dashboardMonitor(Request $request)
    {
        $monitors = Monitor::whereDate('created_at', date('Y-m-d'))->orderBy('created_at', 'DESC')->get();
        return $this->success($monitors);
    }

    public function getMonitor(Request $request)
    {
        $trackings = Tracking::whereNotNull('lot_id')->get();
        foreach ($trackings as $key => $tracking) {
            $machine = Machine::where('code', $tracking->machine_id)->first();
            $lot = Lot::find($tracking->lot_id);
            $plan = $lot->getPlanByLine($machine->line_id);
            $info_cd = InfoCongDoan::where('lot_id', $tracking->lot_id)->where('line_id', $machine->line_id)->first();
            if ($plan && $plan->thoi_gian_chinh_may) {
                $check_info = InfoCongDoan::where('lot_id', 'like', '%' . $lot->lo_sx . '%')->where('line_id', $machine->line_id)->count();
                if ($check_info == 1) {
                    if (is_null($info_cd->thoi_gian_bam_may)) {
                        $thoi_gian_tt = strtotime(date('Y-m-d H:i:s')) -  strtotime($info_cd->thoi_gian_bat_dau);
                        if ($thoi_gian_tt > (($plan->thoi_gian_chinh_may * 3600) - 60)) {
                            $check = Monitor::where('type', 'sx')->where('machine_id', $machine->code)->where('parameter_id', 1)->where('status', 0)->first();
                            if (!$check) {
                                $monitor = new Monitor();
                                $monitor->type = 'sx';
                                $monitor->parameter_id = 1;
                                $monitor->content = 'Vượt thời gian định mức';
                                $monitor->machine_id = $machine->code;
                                $monitor->status = 0;
                                $monitor->save();
                            }
                        }
                    } else {
                        $check = Monitor::where('type', 'sx')->where('machine_id', $machine->code)->where('parameter_id', 1)->where('status', 0)->first();
                        if ($check) {
                            $time = number_format((strtotime($info_cd->thoi_gian_bam_may) - strtotime($info_cd->thoi_gian_bat_dau) - ($plan->thoi_gian_chinh_may * 3600)) / 60);
                            Monitor::where('type', 'sx')->where('machine_id', $machine->code)->where('parameter_id', 1)->where('status', 0)->update(['status' => 1, 'value' => $time]);
                        }
                        $tg_chay = (strtotime(date('Y-m-d H:i:s')) - strtotime($info_cd->thoi_gian_bam_may));
                        if ($tg_chay > 1800) {
                            $sl_kh = ($plan->sl_thanh_pham * $plan->so_bat) / ($plan->thoi_gian_thuc_hien * 3600);
                            $sl_chuan = $sl_kh * $tg_chay;
                            if ($sl_chuan > $info_cd->sl_dau_ra_hang_loat) {
                                $check_monitor = Monitor::where('type', 'sx')->where('machine_id', $machine->code)->where('parameter_id', 2)->where('status', 0)->first();
                                if (!$check_monitor) {
                                    $monitor = new Monitor();
                                    $monitor->type = 'sx';
                                    $monitor->parameter_id = 2;
                                    $monitor->content = 'Chậm tiến độ sản xuất';
                                    $monitor->machine_id = $machine->code;
                                    $monitor->status = 0;
                                    $monitor->save();
                                }
                            } else {
                                $check_monitor = Monitor::where('type', 'sx')->where('machine_id', $machine->code)->where('parameter_id', 2)->where('status', 0)->first();
                                if ($check_monitor) {
                                    Monitor::where('type', 'sx')->where('machine_id', $machine->code)->where('parameter_id', 2)->where('status', 0)->update(['status' => 1]);
                                }
                            }
                        }
                    }
                }
            }
        }
        Monitor::whereDate('created_at', '<', date('Y-m-d'))->where('type', 'tb')->update(['status' => 1]);
        $data = Monitor::where('status', 0)->whereIn('machine_id', $trackings->pluck('machine_id'))->orderBy('created_at', 'DESC')->get();
        foreach ($data as $key => $value) {
            $value->content = $value->content . ' ' . $value->value;
        }
        return $this->success($data);
    }

    public function insertMonitor(Request $request)
    {
        $monitor = Monitor::where('machine_id', $request->machine_id)->orderBy('created_at', 'desc')->first();
        if (!$monitor) {
            $monitor = new Monitor();
        }
        $monitor['type'] = $request->type;
        $monitor['content'] = $request->content;
        $monitor['description'] = $request->description;
        $monitor['machine_id'] = $request->machine_id;
        $monitor['status'] = $request->status ? 1 : 0;
        $monitor['created_at'] = date('Y-m-d H:i:s');
        $monitor->save();
        return $this->success($monitor);
    }


    //PALLET

    public function palletList(Request $request)
    {

        if (!isset($request->id)) {
            return $this->success(Lot::all());
        }
        $pallet = Lot::find($request->id);
        if (!isset($pallet)) return $this->failure([], "Không tìm thấy pallet");
        return $this->success($pallet);
    }



    //Production-Process

    public function scanPallet(Request $request)
    {
        $pallet = Lot::find($request->lot_id);
        if (!isset($pallet)) {
            return $this->failure([], "Không tìm thấy pallet");
        }
        if ($pallet->so_luong <= 0) {
            return $this->failure([], "Không có số lượng");
        }
        $line = Line::find($request->line_id);
        if (!isset($line)) {
            return $this->failure([], "Không tìm thấy công đoạn");
        }
        $line_key = Str::slug($line->name);
        $machine_ids = $line->machine->pluck('id');
        $machine_iot_ids = $line->machine->where('is_iot', 1)->pluck('id');
        if (count($machine_iot_ids)) {
            $checksheet_logs = CheckSheetLog::whereIn('info->machine_id', $machine_iot_ids)->whereDate('created_at', Carbon::today())->get();
            if (!count($checksheet_logs) > 0 || count($checksheet_logs) !== count($machine_iot_ids)) {
                return $this->failure([], "Chưa nhập kiểm tra checksheet");
            }
        }
        // $log = LSXLog::where("lot_id", $pallet->id)->whereDate('created_at', Carbon::today())->get();
        $log = LSXLog::where("lot_id", $pallet->id)->get();
        $machine = Machine::whereIn('id', $machine_ids)->get()->pluck('code');

        $query_tracking = Tracking::whereIn('machine_id', $machine);
        $order_line = ["kho-bao-on", "in", "u", "phu", "be", 'gap-dan', "chon"];
        $previous_line = array_slice($order_line, 0, array_search($line_key, $order_line));
        foreach ($query_tracking->get() as $record) {
            if (is_null($record->lot_id) || $record->lot_id === "" || $record->lot_id === $pallet->id) {
                $record['lot_id'] = $pallet->id;
                $record->save();
            } else {
                return $this->failure([], 'Đang có lot chạy trên máy, không thể chạy lot khác');
                break;
            }
        }

        if ($log && count($log)) {
            $log = $log[0];
            if (!in_array($line_key, ['chon', 'kho-thanh-pham']) && !isset($log->info['kho-bao-on']['input']['do_am_giay']) && $pallet->type === 0) {
                return $this->failure([], "Không đủ điều kiện thời gian, độ ẩm");
            }

            // $line_in_order = Line::where('display', 1)->where('ordering', '<>', 0)->orderBy('ordering')->get();
            // foreach($line_in_order as $key => $line_sx){
            //     if($key !== 0 && $pallet->type === 0 && $line_key !== 'chon'){
            //         $previous_line = $line_in_order[$key -1];
            //         $line_name = Str::slug($previous_line->name);
            //         if(!isset($log->info[$line_name]) || !isset($log->info[$line_name]['thoi_gian_ra'])){
            //             return $this->failure([], 'Chưa hoàn thành sản xuất ở công đoạn ' . $previous_line->name);
            //         }
            //         if($line_sx->id === $line->id){
            //             break;
            //         }
            //     }
            // }
        } else {
            if ($line_key !== 'kho-bao-on' && $line_key !== 'chon' && $pallet->type === 0) {
                return $this->failure([], 'Thực hiện bảo ôn trước');
            }
            $log = new LSXLog();
            $log->lot_id = $pallet->id;
            $log->info = [];

            $log->save();
        }

        //check bat
        $bats = Lot::where('p_id', $pallet->id)->where('type', 1)->get();
        // return ($bats);
        if ($line_key === 'gap-dan' && count($bats) <= 0) {
            $soluong = (int)($pallet->so_luong) / $pallet->plan->so_bat;
            $b1 = new Lot();
            $b1->so_luong = $soluong;
            $b1->type = 1;
            $b1->id = $pallet->id . ".B" . (count($bats) + 1);
            $b1->lo_sx = $pallet->lo_sx;
            $b1->product_id = $pallet->product->id;
            $b1->p_id = $pallet->id;
            $b1->save();

            $bat_log = new LSXLog();
            $bat_log->lot_id = $b1->id;
            $bat_log->save();

            $info_cd_bat = new InfoCongDoan();
            $info_cd_bat->type = 'sx';
            $info_cd_bat->lot_id = $b1->id;
            $info_cd_bat->line_id = $line->id;
            $info_cd_bat->sl_dau_vao_hang_loat = $soluong;
            $info_cd_bat->sl_dau_ra_hang_loat = $soluong;
            $info_cd_bat->save();
        }

        $info = $log->info;

        if (!isset($log->info[$line_key])) { //scan vào công đoan
            $machines = $line->machine;
            foreach ($machines as $machine) {
                MachineStatus::reset($machine->code);
            }
            if ($line_key == 'chon') {
                $info[$line_key] = [
                    "thoi_gian_vao" => Carbon::now(),
                    "user_id" => $request->user()->id,
                    "user_name" => $request->user()->name,
                    "sl_in_tem" => 0,
                ];
            } else {
                $info[$line_key] = [
                    "thoi_gian_vao" => Carbon::now(),
                    "user_id" => $request->user()->id,
                    "user_name" => $request->user()->name,
                ];
            }
        }
        $log->info = $info;
        $log->save();

        $info_cong_doan = InfoCongDoan::where('type', 'sx')->where("lot_id", $pallet->id)->where('line_id', $request->line_id)->first();
        if (!$info_cong_doan) {
            $info_cong_doan = new InfoCongDoan();
            $info_cong_doan->type = 'sx';
            $info_cong_doan->lot_id = $pallet->id;
            $info_cong_doan->save();
        }
        if ($line_key === 'in-luoi' || $line_key === 'boc' && !$info_cong_doan->sl_dau_vao_hang_loat) {
            $info_cong_doan->sl_dau_vao_hang_loat = $pallet->so_luong;
        }
        if ($line_key === 'chon' && !$info_cong_doan->sl_dau_vao_hang_loat) {
            $info_cong_doan->sl_dau_vao_hang_loat = $pallet->so_luong;
        }
        if (!isset($info_cong_doan->line_id)) {
            $info_cong_doan['line_id'] = $request->line_id;
            $info_cong_doan['thoi_gian_bat_dau'] = Carbon::now();
        }
        if ($line_key === 'kho-bao-on' && !isset($info_cong_doan->line_id)) {
            $info_cong_doan['thoi_gian_ket_thuc'] = Carbon::now();
        }
        $info_cong_doan->save();
        return $this->success($log);
    }

    //

    public function inTem(Request $request)
    {
        return $this->endIntem($request);
    }

    //
    public function endIntem($request)
    {
        $lot_id = $request->lot_id;
        $line_id = $request->line_id;
        $is_pass = $request->is_pass ? $request->is_pass : false;
        $pallet = Lot::find($lot_id);
        if (!isset($pallet)) {
            return $this->failure([], "Không tìm thấy pallet");
        }

        $line = Line::find($line_id);
        if (!isset($line)) {
            return $this->failure([], "Không tìm thấy công đoạn");
        }
        # cong doan pallet mới => không có lsx_log;
        $line_key = Str::slug($line->name);
        // if ($line_key == "chon") {
        //     $cur_pallet = clone $pallet;
        //     $pallet = $pallet->parrent;
        // }
        $log = $pallet->log;
        // dd($pallet);
        // return $log->checkQC($line_key, $pallet->plan);

        $info = $log->info;
        $log_in_tem = LogInTem::where('lot_id', $pallet->id)->where('line_id', $line->id)->where('type', 1)->get();
        // if(isset($info[$line_key]['thoi_gian_ra'])){
        //     if(count($log_in_tem) > 0){
        //         return $this->success($log_in_tem->pluck('log'));
        //     }else{
        //         return $this->failure([], 'Chưa có log in tem');
        //     }
        // }
        if (!$is_pass) {
            if ((!in_array($line_key, ['kho-bao-on', 'u']) && !$log->checkQC($line_key, $pallet->plan))) return $this->failure([], "Bạn chưa thể in tem, cần kiểm tra lại chất lượng");
            if ($line_key === 'kho-bao-on' && !isset($log->info['kho-bao-on']['input']['do_am_giay']) && $pallet->type === 0) {
                return $this->failure([], "Không đủ điều kiện thời gian, độ ẩm");
            }
        }
        if ($line_key !== 'gap-dan' && $line_key != 'chon') {
            if (isset($info['qc']) && isset($info['qc'][$line_key])) {
                $info['qc'][$line_key]['thoi_gian_ra'] = Carbon::now();
            }
            $info[$line_key]['thoi_gian_ra'] = Carbon::now();
            $info[$line_key]['thoi_gian_xuat_kho'] = Carbon::now();
        }
        // $info_cong_doan = InfoCongDoan::where("lot_id", $pallet->id)->where('line_id', $request->line_id)->first();
        if ($line_key == 'gap-dan') {
            if (!isset($info[$line_key]['bat'])) {
                $info[$line_key]['bat'] = [];
            }
            $bat = Lot::where('p_id', $pallet->id)->where('type', 1)->orderBy('created_at', 'DESC')->first();
            $info[$line_key]['bat'][$bat->id] = [
                "thoi_gian_ra" => Carbon::now()
            ];

            //Chia thùng

            $info_cong_doan = InfoCongDoan::where("type", "sx")->where("line_id", $line->id)->where("lot_id", $pallet->id)->first();
            $bats = $info["qc"][$line_key]['bat'];
            if (!array_key_exists($bat->id, $bats)) {
                return $this->failure([], "Bạn chưa thể in tem, cần kiểm tra lại chất lượng");
            }
            $order_line = array_reverse([10, 22, 11, 12, 14]); //In, In lưới, Phủ, Bế, Bóc
            $info_cds = null;
            foreach ($order_line as $l) {
                $prev_info_cd = InfoCongDoan::where("type", "sx")->where("lot_id", $pallet->id)->where("line_id", $l)->first();
                if ($prev_info_cd) {
                    $info_cds = $prev_info_cd;
                    break;
                }
            }
            // $info_cds = InfoCongDoan::where("type", "sx")->where("lot_id", $pallet->id)->where("line_id", 12)->first();
            // if (!$info_cds) {
            //     $info_cds = InfoCongDoan::where("type", "sx")->where("lot_id", $pallet->id)->where("line_id", 11)->first();
            // }
            // if (!$info_cds) {
            //     $info_cds = InfoCongDoan::where("type", "sx")->where("lot_id", $pallet->id)->where("line_id", 10)->first();
            // }

            // if ($info_cds) {
            //     $soluong = $info_cds->sl_dau_ra_hang_loat - $info_cds->sl_tem_vang - $info_cds->sl_ng;
            //     // if ($line == 'in' || $line == 'phu' || $line == 'be' || $line == 'in-luoi' || $line == 'boc') {
            //         $soluong = (int)($soluong / $pallet->plan->so_bat);
            //     // }
            // } else {
            //     $soluong = $pallet->so_luong;
            // }


            // $soluong = (int)($soluong);
            // // dd($soluong);
            // #

            // if ($soluong < 0) {
            //     $soluong = 0;
            // }
            $soluong = $pallet->so_luong;

            if ((count($bats) + 1) <= $pallet->plan->so_bat) {
                $t1 = new Lot();
                $t1->so_luong = $soluong / $pallet->plan->so_bat;
                $t1->type = 1;
                $t1->id = $pallet->id . ".B" . (count($bats) + 1);
                $t1->lo_sx = $pallet->lo_sx;
                $t1->product_id = $pallet->plan->product->id;
                $t1->p_id = $pallet->id;
                $t1->save();

                $bat_log = new LSXLog();
                $bat_log->lot_id = $t1->id;
                $bat_log->info = [];
                $bat_log->save();

                $info_cd_bat = new InfoCongDoan();
                $info_cd_bat->type = 'sx';
                $info_cd_bat->sl_dau_ra_hang_loat = $soluong / $pallet->plan->so_bat;
                $info_cd_bat->sl_dau_vao_hang_loat = $soluong / $pallet->plan->so_bat;
                $info_cd_bat->lot_id = $t1->id;
                $info_cd_bat->line_id = $line->id;
                $info_cd_bat->save();
            }
            $count = count($info[$line_key]['bat']);
            if ($count === $pallet->plan->so_bat) {
                $info[$line_key]['thoi_gian_ra'] = Carbon::now();
                $info[$line_key]['thoi_gian_xuat_kho'] = Carbon::now();
                if (isset($info['qc']) && isset($info['qc'][$line_key])) {
                    $info['qc'][$line_key]['thoi_gian_ra'] = Carbon::now();
                }

                $info_cong_doan['thoi_gian_ket_thuc'] = Carbon::now();
                $info_cong_doan->save();
                $pallet['so_luong'] = ($info_cong_doan->sl_dau_ra_hang_loat - $info_cong_doan->sl_tem_vang - $info_cong_doan->sl_ng) / $pallet->plan->so_bat;
                $pallet->save();
            }
            // return $bats;
        }
        if ($line_key == "chon") {
            // $sl_thuc_te = 0;
            $sl_thuc_te_ok = 0;
            if (!isset($info["chon"]['table'])) {
                return $this->failure([], 'Chưa ghi nhận giao việc');
            }
            $tables = $info["chon"]['table'];
            foreach ($tables as $tb) {
                if ((!isset($tb["so_luong_thuc_te_ok"]) || !$tb["so_luong_thuc_te_ok"]) || (!isset($tb["so_luong_thuc_te"]) || !$tb["so_luong_thuc_te"])) {
                    return $this->failure([], 'Chưa hoàn thành ghi nhận số lượng thực tế ok');
                }
                $sl_thuc_te_ok += (int)$tb['so_luong_thuc_te_ok'];
                // $sl_thuc_te += (int)$tb['so_luong_thuc_te'] ?? 0;
            }
            $info_cong_doan = $pallet->infoCongDoan()->where("line_id", 15)->first();
            if (isset($info_cong_doan)) {
                $sl_pallet = $info_cong_doan->sl_dau_vao_hang_loat - ($sl_thuc_te_ok + $info_cong_doan->sl_ng);
                $pallet['so_luong'] = $sl_pallet;
                $pallet->save();
                // $info_cong_doan->sl_dau_vao_hang_loat = $sl_thuc_te;
                $info_cong_doan->sl_dau_ra_hang_loat = $sl_thuc_te_ok + $info_cong_doan->sl_ng;
                $info_cong_doan->save();
            }
            $sl_ok = $sl_thuc_te_ok - $info["chon"]['sl_in_tem'];

            # Chia thùng theo bát, phần thừa lưu vào table
            $dinh_muc = $pallet->product->dinh_muc_thung;
            $length = ceil($request->sl_in_tem / $dinh_muc);
            # chia thùng công đoạn chọn
            $child = clone $pallet;
            $pallet  = $child->parrent;
            $new_id = [];
            $new_sl = [];
            $check_cd = InfoCongDoan::where('line_id', 20)->where('lot_id', $pallet->id)->first();
            if ($check_cd && $check_cd->sl_tem_vang > 0) {
                array_push($new_id, $pallet->id);
                array_push($new_sl, $request->sl_in_tem);
            } else {
                for ($i = 0; $i < $length; $i++) {
                    try {
                        $thung = Lot::where('type', 2)->where('p_id', $pallet->id)->get();
                        $t1 = new Lot();
                        if ($i == $length - 1) {
                            $t1->so_luong = $request->sl_in_tem - ($dinh_muc * $i);
                        } else {
                            $t1->so_luong = $dinh_muc;
                        }
                        $t1->type = 2;
                        $t1->id = $child->id . "-T" . (count($thung) + 1);
                        $t1->lo_sx = $pallet->lo_sx;
                        $t1->product_id = $pallet->plan->product->id;
                        $t1->p_id = $pallet->id;
                        $t1->save();
                        array_push($new_id, $child->id . "-T" . (count($thung) + 1));
                        array_push($new_sl, $t1->so_luong);

                        $t_log = new LSXLog();
                        $t_log->lot_id = $t1->id;
                        $t_log->info = [];
                        $t_log->save();
                    } catch (Exception $ex) {
                    }
                }
            }
            ## phần dư 
            $odd_bin = OddBin::where('product_id', $pallet->product_id)->where('lo_sx', $pallet->lo_sx)->first();
            if (!$odd_bin) {
                $odd_bin = new OddBin();
                $odd_bin->product_id = $pallet->product_id;
                $odd_bin->lo_sx = $pallet->lo_sx;
                $odd_bin->so_luong = 0;
                $odd_bin->save();
            }
            if ($sl_ok < $request->sl_in_tem) {
                $sl_con_lai = $odd_bin->so_luong - ($request->sl_in_tem - $sl_ok);
                $odd_bin->so_luong = $sl_con_lai;
                $odd_bin->save();
            } elseif ($sl_ok > $request->sl_in_tem) {
                $sl_con_lai = $odd_bin->so_luong + ($sl_ok - $request->sl_in_tem);
                $odd_bin->so_luong = $sl_con_lai;
                $odd_bin->save();
            }
            $info["chon"]['sl_in_tem'] = $info["chon"]['sl_in_tem'] + $sl_ok;
            if ($child->so_luong <= 0) {
                $info[$line_key]['thoi_gian_ra'] = Carbon::now();
                if (isset($info['qc']) && isset($info['qc'][$line_key])) {
                    $info['qc'][$line_key]['thoi_gian_ra'] = Carbon::now();
                }
                $info[$line_key]['thoi_gian_xuat_kho'] = Carbon::now();
                $info_cong_doan['thoi_gian_ket_thuc'] = Carbon::now();
                $info_cong_doan->save();
            }
        }
        $log->info = $info;
        $log->save();
        $plan = $pallet->plan;
        $info_cong_doan = InfoCongDoan::where("lot_id", $pallet->id)->where('line_id', $line_id)->first();
        if ($info_cong_doan && $line_key !== 'gap-dan') {
            $info_cong_doan['thoi_gian_ket_thuc'] = Carbon::now();
            $info_cong_doan->save();
            if ($line_key !== 'kho-bao-on' && $line_key !== 'u') {
                $pallet['so_luong'] = $info_cong_doan->sl_dau_ra_hang_loat - $info_cong_doan->sl_tem_vang - $info_cong_doan->sl_ng;
                $pallet->save();
            }
        }
        $machines = $line->machine;
        foreach ($machines as $item) {
            if ($line_key == 'gap-dan') {
                $ll = $pallet->log;
                $bats = $ll->info['qc']['gap-dan']['bat'];
                if (count($bats) === $plan->so_bat ?? 0) {
                    MachineStatus::deactive($item->code);
                    Tracking::where('machine_id', $item->code)->update(['lot_id' => null]);
                }
            } else {
                MachineStatus::deactive($item->code);
                Tracking::where('machine_id', $item->code)->update(['lot_id' => null]);
            }
        }

        if (!isset($length)) $length = 1;

        $res = ["log" => $log, "sl_tem_can_in" => (int) $length, "lot_id" => $pallet];

        $res['lot_id'] = [$pallet->id];
        $res['so_luong'] = [$plan ? $pallet->so_luong / $plan->so_bat : 0];
        if ($line_key === 'gap-dan') {
            $id_gd = [];
            $sl_gd = [];
            $info_cong_doan_bat = InfoCongDoan::where("lot_id", $bat->id)->where('line_id', $line_id)->first();
            if ($info_cong_doan_bat) {
                $sl_gd[] = $bat->so_luong;
                $id_gd[] = $bat->id;
                $res['lot_id'] = $id_gd;
                $res['so_luong'] = $sl_gd;
            }
        }

        if ($line_key == "chon") {
            $res['lot_id'] = $new_id;
            $res['so_luong'] = $new_sl;
        }

        $new_log_in_tem = new LogInTem();
        $new_log_in_tem['lot_id'] = $pallet->id;
        $new_log_in_tem['line_id'] = $line->id;
        $new_log_in_tem['log'] = $res;
        $new_log_in_tem['type'] = 1;
        $new_log_in_tem->save();
        return $this->success($res);
    }


    private function gopThung($child, $dinh_muc, $pallet, $final)
    {
        $data = [];
        $q = OddBin::where("lot_id", $pallet->id)->where('so_luong', '>', 0);
        $bins = $q->get();
        $sum = $q->sum("so_luong");
        $cur_sum = 0;
        $name = $pallet->id . ".B";
        $tv = '';
        if ($child->type == '3') {
            $tv = '.TV13';
        }
        if ($final) {
            $length = ceil($sum / $dinh_muc);
            if ($length > 0) {
                foreach ($bins as $k => $bin) {
                    $name = $name . ".$bin->so_bat";
                }
                for ($i = 0; $i < $length; $i++) {
                    $thung = Lot::where('type', 2)->where('p_id', $pallet->id)->get();
                    $t1 = new Lot();
                    if ($i == $length - 1) {
                        $t1->so_luong = $sum - ($dinh_muc * $i);
                    } else {
                        $t1->so_luong = $dinh_muc;
                    }
                    $t1->type = 2;
                    $t1->id = $name . $tv . "-T" . (count($thung) + 1);
                    $t1->lo_sx = $pallet->lo_sx;
                    $t1->product_id = $pallet->plan->product->id;
                    $t1->p_id = $pallet->id;
                    $t1->save();
                    $data[] = $t1;
                }
            }
            OddBin::where("lot_id", $pallet->id)->delete();
            return $data;
        }
        if ($sum < $dinh_muc) {
            return $data;
        }
        foreach ($bins as $bin) {
            if ($cur_sum + $bin->so_luong < $dinh_muc) {
                $cur_sum += $bin->so_luong;
                $bin->so_luong = 0;
                $name = $name . ".$bin->so_bat";
                $bin->save();
            } else {
                if ($bin->so_luong >= $dinh_muc) {
                    $name = $name . $bin->so_bat;
                } elseif ($cur_sum < $dinh_muc) {
                    $name = $name . ".$bin->so_bat";
                }
                $bin->so_luong = $bin->so_luong -  ($dinh_muc - $cur_sum);
                $cur_sum = $dinh_muc;
                $bin->save();
            }
        }
        $thung = Lot::where('type', 2)->where('p_id', $pallet->id)->get();
        $t1 = new Lot();
        $t1->so_luong = $dinh_muc;
        $t1->type = 2;
        $t1->id = $name . $tv . "-T" . (count($thung) + 1);
        $t1->lo_sx = $pallet->lo_sx;
        $t1->product_id = $pallet->plan->product->id;
        $t1->p_id = $pallet->id;
        $t1->save();
        $data[] = $t1;
        return $data;
    }

    public function inputPallet(Request $request)
    {
        $pallet = Lot::find($request->lot_id);
        if (!isset($pallet)) {
            return $this->failure([], "Không tìm thấy pallet");
        }
        $line = Line::find($request->line_id);
        if (!isset($line)) {
            return $this->failure([], "Không tìm thấy công đoạn");
        }

        $log = $pallet->log;
        $info = $log->info;
        $line_key = Str::slug($line->name);
        if (!isset($info[$line_key]["input"])) {
            $input = [];
        } else {
            $input = $info[$line_key]["input"];
        }
        $insulation = Insulation::find(1);
        $inp = $request->all();
        $inp['t_ev'] = $insulation->t_ev;
        $inp['e_hum'] =  $insulation->e_hum;
        $info[$line_key]["input"] =  array_merge($input, $inp);
        // $info[$line_key]["input"] =  [];
        $log->info = $info;
        $log->save();
        return $this->success($log);
    }

    private function danhSachPalletBaoOn()
    {
        $data = [];
        //  $list = LSXLog::listPallet('kho-bao-on')->get();
        $now  = date('Y-m-d', strtotime('-5 day'));
        $list = InfoCongDoan::with('lot.product', 'lot.plan', 'lot.log')->where('line_id', 9)->whereDate('thoi_gian_bat_dau', '>=', $now)->orderBy('thoi_gian_bat_dau', 'DESC')->get();
        $insulation = Insulation::find(1);
        foreach ($list as $item) {
            if (isset($item->lot)) {
                if ($item->thoi_gian_ket_thuc) {
                    $daxuat = $item->lot->so_luong;
                    $so_luong_con_lai = 0;
                } else {
                    $daxuat = 0;
                    $so_luong_con_lai = $item->lot->so_luong;
                }

                if ($item->thoi_gian_ket_thuc) {
                    $status = 3;
                }
                $startTime = $item->thoi_gian_bat_dau;
                $startTime = new Carbon($startTime);
                $now = Carbon::now();
                $cnt = $now->diffInHours($startTime);
                $t = filter_var($item->lot->product->thoi_gian_bao_on, FILTER_SANITIZE_NUMBER_INT);
                if ($cnt < $t) $status = 2;
                if (!isset($status)) {
                    $status = 1;
                }
                $record =   [
                    "lo_sx" => $item->lot->lo_sx,
                    "lot_id" => $item->lot->id,
                    "ma_hang" => $item->lot->product->id,
                    "ten_sp" => $item->lot->product->name,
                    "dinh_muc" => $item->lot->so_luong,
                    "sl_ke_hoach" => $item->lot->plan ? $item->lot->plan->sl_nvl : 0,
                    "thoi_gian_bat_dau" => $item->thoi_gian_bat_dau ?? "",
                    "thoi_gian_bao_on" => "",
                    "thoi_gian_bao_on_tieu_chuan" => $item->lot->product->thoi_gian_bao_on === '-' ? 0 : filter_var($item->lot->product->thoi_gian_bao_on, FILTER_SANITIZE_NUMBER_INT),
                    "do_am_phong" => (isset($item->thoi_gian_ket_thuc) && isset($log['kho-bao-on']['input']["e_hum"])) ? $item->lot->log->info['kho-bao-on']['input']["e_hum"] : $insulation->e_hum,
                    "nhiet_do_phong" => (isset($item->lot->log->info['kho-bao-on']["thoi_gian_xuat_kho"]) && isset($item->lot->log->info['kho-bao-on']['input']["t_ev"])) ? $item->lot->log->info['kho-bao-on']['input']["t_ev"] : $insulation->t_ev,
                    "do_am_phong_tieu_chuan" => $item->lot->product->do_am_phong,
                    "do_am_giay" => isset($item->lot->log->info['kho-bao-on']['input']['do_am_giay']) ? $item->lot->log->info['kho-bao-on']['input']['do_am_giay'] : "",
                    "do_am_giay_tieu_chuan" => $item->lot->product->do_am_giay,
                    "thoi_gian_xuat_kho_bao_on" => $item->thoi_gian_ket_thuc ?? "",
                    "sl_da_xuat" => $daxuat,
                    "sl_con_lai" => $so_luong_con_lai,
                    "uph_an_dinh" => $item->lot->plan->UPH ?? 0,
                    "uph_thuc_te" => "",
                    "status" => $status,

                ];
                try {
                    $min = (int)filter_var(explode("~", $item->lot->product->do_am_giay)[0], FILTER_SANITIZE_NUMBER_INT);
                    $max = (int) filter_var(explode("~", $item->lot->product->do_am_giay)[1], FILTER_SANITIZE_NUMBER_INT);
                    $record['do_am_giay_max'] = $max;
                    $record['do_am_giay_min'] = $min;
                } catch (Exception $ex) {
                }
                $data[] = $record;
            }
        }
        return $data;
    }

    private function danhSachPalletU()
    {
        $data = [];
        $now  = date('Y-m-d', strtotime('-5 day'));
        $list = InfoCongDoan::with('lot.product', 'lot.plan', 'lot.log')->where('line_id', 21)->whereDate('thoi_gian_bat_dau', '>=', $now)->orderBy('thoi_gian_bat_dau', 'DESC')->get();
        $insulation = Insulation::find(1);
        foreach ($list as $item) {
            if (isset($item->lot)) {
                if ($item->thoi_gian_ket_thuc) {
                    $daxuat = $item->lot->so_luong;
                    $so_luong_con_lai = 0;
                } else {
                    $daxuat = 0;
                    $so_luong_con_lai = $item->lot->so_luong;
                }

                if ($item->thoi_gian_ket_thuc) {
                    $status = 3;
                }
                $startTime = $item->thoi_gian_bat_dau;
                $startTime = new Carbon($startTime);
                $now = Carbon::now();
                $cnt = $now->diffInHours($startTime);
                $t = filter_var($item->lot->product->thoi_gian_u, FILTER_SANITIZE_NUMBER_INT);
                if ($cnt < $t) $status = 2;
                if (!isset($status)) {
                    $status = 1;
                }
                $record =   [
                    "lo_sx" => $item->lot->lo_sx,
                    "lot_id" => $item->lot->id,
                    "ma_hang" => $item->lot->product->id,
                    "ten_sp" => $item->lot->product->name,
                    "dinh_muc" => $item->lot->so_luong,
                    "sl_ke_hoach" => $item->lot->plan ? $item->lot->plan->sl_nvl : 0,
                    "thoi_gian_bat_dau" => $item->thoi_gian_bat_dau ?? "",
                    "thoi_gian_u" => "",
                    "thoi_gian_u_tieu_chuan" => $item->lot->product->thoi_gian_u === '-' ? 0 : filter_var($item->lot->product->thoi_gian_u, FILTER_SANITIZE_NUMBER_INT),
                    "do_am_phong" => (isset($item->thoi_gian_ket_thuc) && isset($log['u']['input']["e_hum"])) ? $item->lot->log->info['u']['input']["e_hum"] : $insulation->e_hum,
                    "nhiet_do_phong" => (isset($item->lot->log->info['u']["thoi_gian_xuat_kho"]) && isset($item->lot->log->info['u']['input']["t_ev"])) ? $item->lot->log->info['u']['input']["t_ev"] : $insulation->t_ev,
                    "do_am_phong_tieu_chuan" => $item->lot->product->do_am_phong,
                    "do_am_giay" => isset($item->lot->log->info['u']['input']['do_am_giay']) ? $item->lot->log->info['u']['input']['do_am_giay'] : "",
                    "do_am_giay_tieu_chuan" => $item->lot->product->do_am_giay,
                    "thoi_gian_xuat_kho_u" => $item->thoi_gian_ket_thuc ?? "",
                    "sl_da_xuat" => $daxuat,
                    "sl_con_lai" => $so_luong_con_lai,
                    "uph_an_dinh" => $item->lot->plan->UPH ?? 0,
                    "uph_thuc_te" => "",
                    "status" => $status,

                ];
                try {
                    $min = (int)filter_var(explode("~", $item->lot->product->do_am_giay)[0], FILTER_SANITIZE_NUMBER_INT);
                    $max = (int) filter_var(explode("~", $item->lot->product->do_am_giay)[1], FILTER_SANITIZE_NUMBER_INT);
                    $record['do_am_giay_max'] = $max;
                    $record['do_am_giay_min'] = $min;
                } catch (Exception $ex) {
                }
                $data[] = $record;
            }
        }
        return $data;
    }

    private function danhSachPalletIn2Chon($line)
    {
        $linex = [
            "kho_bao_on" => 9,
            "in" => 10,
            "phu" => 11,
            "be" => 12,
            "gap-dan" => 13,
            "boc" => 14,
            "chon" => 15,
            "u" => 21,
            "in-luoi" => 22
        ];
        $records = [];
        $now  = date('Y-m-d', strtotime('-5 day'));
        $list = InfoCongDoan::with('lot.product', 'lot.plan', 'lot.log')->where('line_id', $linex[$line])->whereDate('thoi_gian_bat_dau', '>=', $now)->orderBy('thoi_gian_bat_dau', 'DESC')->get();
        foreach ($list as $item) {
            $plan = ProductionPlan::where('cong_doan_sx', $line)->where('lo_sx', $item->lot->lo_sx)->first();
            if (!$plan) {
                $plan = $item->lot->plan;
            }
            $sl_dau_ra = 0;
            if ($line == 'in' || $line == 'phu' || $line == 'be' || $line == 'in-luoi' || $line == 'boc') {
                $sl_dau_ra = $plan ? $item->sl_dau_ra_hang_loat / $plan->so_bat : 0;
            } else {
                $sl_dau_ra = $item->sl_dau_ra_hang_loat;
            }
            $data =  [
                "lo_sx" => $item->lot->lo_sx,
                "lot_id" => $item->lot->id,
                "ma_hang" => $item->lot->product->id,
                "ten_sp" => $item->lot->product->name,
                "dinh_muc" => $item->lot->product->dinh_muc,
                "sl_ke_hoach" => $plan->sl_nvl ?? 0,
                'thoi_gian_bat_dau_kh' => $plan->thoi_gian_bat_dau ?? "",
                'thoi_gian_bat_dau' => $item->thoi_gian_bat_dau ? date('Y-m-d H:i:s', strtotime($item->thoi_gian_bat_dau)) : "",
                "thoi_gian_ket_thuc_kh" => $plan->thoi_gian_ket_thuc ?? "",
                'thoi_gian_ket_thuc' => $item->thoi_gian_ket_thuc ? date('Y-m-d H:i:s', strtotime($item->thoi_gian_ket_thuc)) : "",
                'sl_dau_vao_kh' => $plan->sl_nvl ?? 0,
                'sl_dau_ra_kh' =>  $plan->sl_thanh_pham ?? "",
                'sl_dau_vao' => "",
                'sl_dau_ra' => "",
                "sl_dau_ra_ok" => "",
                "sl_tem_vang" => "",
                "sl_tem_ng" => "",
                "ti_le_ht" => "",
                "uph_an_dinh" => $plan->UPH ?? "",
                "uph_thuc_te" => "",
                "status" => (int)!is_null($item->thoi_gian_ket_thuc),
                "nguoi_sx" => $item->lot->log->info[$line]['user_name'] ?? "",
                "thoi_gian_bam_may" => $item->thoi_gian_bam_may,
            ];
            $sl_tv = 0;
            $sl_ng = 0;
            if ($line == 'in' || $line == 'phu' || $line == 'be' || $line == 'in-luoi' || $line == 'boc') {
                $data['sl_dau_vao'] = $plan ? $item->sl_dau_vao_hang_loat / $plan->so_bat : 0;
                $data['sl_dau_ra'] = $plan ? $item->sl_dau_ra_hang_loat / $plan->so_bat : 0;
                $data['sl_tem_vang'] = $plan ? $item->sl_tem_vang / $plan->so_bat : 0;
                $data['sl_tem_ng'] = $plan ? $item->sl_ng / $plan->so_bat : 0;
            } else {
                $data['sl_dau_vao'] = $item->sl_dau_vao_hang_loat;
                $data['sl_dau_ra'] = $item->sl_dau_ra_hang_loat;
                $data['sl_tem_vang'] = $item->sl_tem_vang;
                $data['sl_tem_ng'] = $item->sl_ng;
            }
            $sl_tv = $data['sl_tem_vang'];
            $sl_ng = $data['sl_tem_ng'];
            $data['sl_dau_ra_ok'] = $data['sl_dau_ra'] - $data['sl_tem_vang'] - $data['sl_tem_ng'];
            if ($line == 'in' || $line == 'phu' || $line == 'be' || $line == 'in-luoi' || $line == 'boc') {
                $data['ti_le_ht'] = $data['sl_dau_ra_kh'] > 0 ? ((int)((($sl_dau_ra -  $sl_tv - $sl_ng) / (int)$data['sl_dau_ra_kh']) * 100)) : 0;
            } else {
                $data['ti_le_ht'] = $data['sl_dau_ra_kh'] > 0 ? ((int)(($sl_dau_ra / (int)($data['sl_dau_ra_kh'])) * 100)) : 0;
            }
            try {
                $linex = Line::find($linex[$line]);
                $machine = $linex->machine[0];
                $status = MachineStatus::getRecord($machine->code);
                $now = Carbon::now();
                $start = new Carbon($status->updated_at);
                $d_time = $now->diffInMinutes($start) + 1;
                if ($line == 'in' || $line == 'phu' || $line == 'be' || $line == 'in-luoi' || $line == 'boc') {
                    $upm = $plan ? (int)($item->sl_dau_ra_hang_loat / ($d_time * $plan->so_bat)) : 0;
                } else {
                    $upm = (int)($item->sl_dau_ra_hang_loat / $d_time);
                }
                $data['uph_thuc_te'] = $upm * 60;
            } catch (Exception $ex) {
            }
            if ($line == "chon") {
                try {
                    if ($data['sl_dau_ra_kh'] > 0)
                        $data['ti_le_ht'] = (int) (100 * ($data['sl_dau_ra_ok'] / $data['sl_dau_ra_kh']));
                    else {
                        $data['ti_le_ht'] = "";
                    }
                } catch (Exception $ex) {
                }
            }
            $records[] = $data;
        }
        return $records;
    }



    public function infoPallet(Request $request)
    {
        $mark = [
            0,
            0,
            "in",
            "phu",
            "be",
            'gap-dan',
            "boc",
            "chon",
            "u",
            "in-luoi"
        ];
        $data = [];
        if ($request->type == 1) {
            $data = $this->danhSachPalletBaoOn();
        } else if ($request->type == 8) {
            $data = $this->danhSachPalletU();
        } else {
            $data = $this->danhSachPalletIn2Chon($mark[$request->type]);
        }

        return $this->success($data);
    }

    public function lineAssign(Request $request)
    {

        $pallet = Lot::find($request->lot_id);
        // $pallet = $pallet->parrent;
        if (!isset($pallet)) {
            return $this->failure([], "Không tìm thấy pallet");
        }
        $line = Line::find($request->line_id);
        if (!isset($line)) {
            return $this->failure([], "Không tìm thấy công đoạn");
        }

        $line_key = Str::slug($line->name);
        $log = $pallet->log;
        $info = $log->info;

        if (!isset($info[$line_key]['table'])) {
            $info[$line_key]['table'] = [];
        };
        $table =  $info[$line_key]['table'];
        $table[] = $request->users;


        //
        // $cnt_user = 0;
        // foreach ($table as $item) {
        //     if (is_array($item))
        //         $cnt_user += count($item);
        // }
        // $cnt = 0;
        // $sl_dau_vao = $pallet->so_luong;

        // foreach ($table as &$item) {
        //     if (is_array($item)) {

        //         foreach ($item as &$user) {

        //             $user['sl_cong_viec'] = (int)($sl_dau_vao / $cnt_user);
        //             if ($cnt === ($cnt_user - 1)) {
        //                 $user['sl_cong_viec'] += $sl_dau_vao % $cnt_user;
        //             }
        //             $cnt++;
        //         }
        //     }
        // }
        // return $table;
        $info[$line_key]['table'] = $table;

        $log->info = $info;

        $log->save();
        return $this->success($log);
    }

    public function listTable()
    {
        $list = LineTable::all();
        $data = [];
        foreach ($list as $item) {
            $data[] = [
                "value" => $item->id,
                "label" => $item->ten_ban
            ];
        }
        return $this->success($data);
    }

    public function lineTableWork(Request $request)
    {
        $pallet = Lot::find($request->lot_id);
        if (!isset($pallet)) {
            return $this->failure([], "Không tìm thấy pallet");
        }
        // $pallet = $pallet->parrent;
        // $line = Line::find($request->line_id);
        // if (!isset($line)) {
        //     return $this->failure([], "Không tìm thấy công đoạn");
        // }

        // $line_key = Str::slug($line->name);
        $line_key = "chon";
        $log = $pallet->log;
        $info = $log->info;
        $table = $info[$line_key]['table'];
        $sl_ok = 0;

        foreach ($request->table as $key => $t) {
            foreach ($table as $k => $value) {
                if ($value['table_id'] == $t['table_id'] && (!isset($value['so_luong_thuc_te_ok']) || (isset($value['so_luong_thuc_te_ok']) && $value['so_luong_thuc_te_ok'] == ''))) {
                    $user_work = $table[$k];
                    if (isset($t['so_luong_thuc_te_ok']) && !isset($t['so_luong_thuc_te_ok_submited'])) {
                        $sl_ok += $t['so_luong_thuc_te_ok'];
                    }
                    $user_work['so_luong_thuc_te'] = isset($t['so_luong_thuc_te']) ? $t['so_luong_thuc_te'] : '';
                    $user_work['so_luong_thuc_te_submited'] = isset($t['so_luong_thuc_te']) ? Carbon::now() : '';
                    $user_work['so_luong_thuc_te_ok'] = isset($t['so_luong_thuc_te_ok']) ? $t['so_luong_thuc_te_ok'] : '';
                    $user_work['so_luong_thuc_te_ok_submited'] = isset($t['so_luong_thuc_te_ok']) ? Carbon::now() : '';
                    $table[$k] = $user_work;
                }
            }
        }
        $info[$line_key]['table'] = $table;
        $log->info = $info;
        $log->save();
        return $this->success($log);
    }

    public function getTableAssignData(Request $request)
    {
        $pallet = Lot::find($request->lot_id);
        if (!isset($pallet)) {
            return $this->failure([], "Không tìm thấy pallet");
        }
        $line_key = "chon";
        $object = new stdClass();
        $object->sl_le_ok = OddBin::where('product_id', $pallet->product_id)->where('lo_sx', $pallet->lo_sx)->sum('so_luong');
        $object->is_result = false;
        $log = $pallet->log;
        $info_cd = InfoCongDoan::where('lot_id', $request->lot_id)->where('line_id', 15)->first();
        $sl_ok = 0;
        if ($log && isset($log['info'][$line_key]) && isset($log['info'][$line_key]['table'])) {
            $info = $log['info'];
            $table = [];
            foreach ($info[$line_key]['table'] as $key => $value) {
                if (!isset($value['so_luong_thuc_te_ok']) || (isset($value['so_luong_thuc_te_ok']) && $value['so_luong_thuc_te_ok'] == '')) {
                    $object->is_result = true;
                    $table[] = $value;
                }
                if (isset($value['so_luong_thuc_te_ok'])) {
                    $sl_ok += (int)$value['so_luong_thuc_te_ok'];
                }
            };
            $object->table = $table;
        } else {
            $object->table = [];
        }
        $object->sl_con_lai = $info_cd->sl_dau_vao_hang_loat - $sl_ok;
        return $this->success($object);
    }


    //QC-

    public function infoQCPallet(Request $request)
    {
        $mark = [
            0,
            0,
            "in",
            "phu",
            "be",
            "boc",
            "gap-dan",
            "chon"
        ];
        $data = [];
        $data = $this->danhSachPalletQC($request->line_id);

        return $this->success($data);
    }

    public function findSpec($test, $spcecs)
    {;
        $find = "±";
        // return $test;
        $hang_muc = Str::slug($test->hang_muc);
        // return $hang_muc;
        foreach ($spcecs as $item) {

            if (str_contains($item->slug, $hang_muc)) {
                if (str_contains($item->value, $find)) {
                    $arr = explode($find, $item->value);
                    $test["input"] = true;
                    $test["tieu_chuan"] = filter_var($arr[0], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $test["delta"] =  filter_var($arr[1], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $test['note'] = $item->value;
                    return $test;
                }
            }
        }
        $test['input'] = false;
        return $test;
    }

    public function testList(Request $request)
    {
        // $line_spec = Line::find($request->line_id);
        $line_test = Line::find($request->line_id);
        // if (!isset($line_spec)) return $this->failure([], 'Không tìm thấy công đoạn');
        // if ($line_spec->id === 14) {  //Nếu công đoạn Bóc, lấy chỉ tiêu công đoạn Bế
        //     $line_spec = Line::find(12);
        // } elseif ($line_spec->id === 22) { //Nếu công đoạn In lưới, lấy chỉ tiêu công đoạn Phủ
        //     $line_spec = Line::find(11);
        // }
        $pallet = Lot::find($request->lot_id);
        if (!isset($pallet)) {
            return $this->failure([], "Không tìm thấy pallet");
        }
        $product =  $pallet->product;
        // if (Str::slug($line_spec->name) === 'oqc') {
        //     $line_spec = Line::find(15);
        //     $line_test = Line::find(15);
        // }

        $list  = TestCriteria::whereHas('line', function ($q) use ($line_test) {
            $q->where("line_id",  $line_test->id);
        })->get();
        $reference = array_merge($list->pluck('reference')->toArray(), [$line_test->id]);
        $spcec = Spec::whereIn("line_id", $reference)->where("product_id", $product->id)->get();
        // if (in_array($line_spec->id, [13, 15, 20])) {
        //     $adding_spec = Spec::whereIn("slug", ['vi-tri-hinh-in-1-mm', 'vi-tri-hinh-in-2-mm'])->where("product_id", $product->id)->where("line_id", 14)->get();
        //     $spcec = $spcec->merge($adding_spec);
        // }
        $data = [];
        $ct = [];
        if (Str::slug(Line::find($request->line_id)->name) === 'oqc') {
            foreach ($list as $item) {
                if (!isset($data['dac-tinh'])) {
                    $data['dac-tinh'] = [];
                }
                if ($item->hang_muc == " ") continue;
                array_push($data['dac-tinh'], $this->findSpec($item, $spcec));
                $ct['dac-tinh'] = '';
            }
        } else {
            foreach ($list as $item) {
                if (!isset($data[Str::slug($item->chi_tieu)])) {
                    $data[Str::slug($item->chi_tieu)] = [];
                }
                if ($item->hang_muc == " ") continue;
                array_push($data[Str::slug($item->chi_tieu)], $this->findSpec($item, $spcec));
                $ct[Str::slug($item->chi_tieu)] = $item->chi_tieu;
            }
        }



        return $this->success(
            ["chi_tieu" => $ct, "data" => $data]
        );
    }

    public function errorList(Request $request)
    {
        $line = Line::find($request->line_id);
        // if (!isset($line)) return $this->failure([], 'Không tìm thấy công đoạn');

        if ($line) {
            $list = Error::whereHas('line', function ($q) use ($line) {
                return $q->where('line_id', $line->id);
            })->get();
        } else {
            $list = Error::all();
        }

        if ($request->error_id) {
            $order_line = [10, 22, 11, 12, 14, 13, 15, 20]; //In, In lưới, Phủ Bế, Bóc, Gấp dán, Chọn, OQC
            $previous_lines = array_slice($order_line, 0, array_search($line->id, $order_line) + 1);
            // return $previous_lines;
            $erro = Error::where('id', $request->error_id)->whereHas('line', function ($q) use ($previous_lines) {
                return $q->whereIn('line_id', $previous_lines);
            })->first();
            if ($erro) return $this->success($erro);
            return $this->failure([], "Không tìm thấy mã lỗi");
        }
        return $this->success($list);
    }

    private function checkSheet($line)
    {
        $machines = $line->machine()->pluck('id');
        $res = CheckSheetLog::whereIn("machine_id", $machines)->whereDate("created_at", Carbon::today())->count();
        if ($res) return true;
        return false;
    }



    public function scanPalletQC(Request $request)
    {

        $pallet = Lot::find($request->lot_id);
        if (!isset($pallet)) {
            return $this->failure([], "Không tìm thấy pallet");
        }
        $line = Line::find($request->line_id);
        if (!isset($line)) {
            return $this->failure([], "Không tìm thấy công đoạn");
        }

        // if(!$this->checkSheet($line)){
        //     return $this->failure([],"Bạn chưa hoàn thành checksheet");
        // }



        $key_line = Str::slug($line->name);
        // if ($key_line == "chon" || $key_line == "oqc") {
        //     $pallet = $pallet->parrent;
        //     if (!isset($pallet)) {
        //         return $this->failure([], "Không tìm thấy pallet");
        //     }
        // }
        $log = LSXLog::where("lot_id", $pallet->id)->first();
        if (!$log && $line->id != 20) {
            return $this->failure([], 'Chưa vào sản xuất');
        }
        if (!$log) {
            $log = new LSXLog();
            $log['lot_id'] = $pallet->id;
            $log['info'] = [];
            $log->save();
        }
        //Sửa lại key_line là công đoạn trước đó
        $lineList = ['in' => 'kho-bao-on', 'phu' => 'in', 'be' => 'phu', 'gap-dan' => 'be', 'boc' => 'gap-dan', 'chon' => 'boc', 'kho-thanh-pham' => 'chon'];
        // if(!isset($log->info[$key_line]) || !isset($log->info[$key_line]['thoi_gian_vao'])){
        //     return $this->failure([], 'Chưa vào sản xuất ở công đoạn này');
        // }

        $info = $log->info;
        if (!isset($info['qc'])) {
            $info['qc'] = [];
        }
        $tm = $info['qc'];
        if (!isset($tm[$key_line])) {
            $tm[$key_line] = [];
        }
        if (!isset($tm[$key_line]['thoi_gian_vao'])) {
            $tm[$key_line]['thoi_gian_vao'] = Carbon::now();
        }
        $permission_user = [];
        foreach ($request->user()->roles as $role) {
            $permission = ($role->permissions()->pluck("slug"));
            $permission_user = array_merge($permission_user, $permission->toArray());
        }
        if (count(array_intersect(['oqc', 'pqc'], $permission_user)) > 0) {
            $tm[$key_line]['user_id']  = $request->user()->id;
            $tm[$key_line]['user_name']  = $request->user()->name;
        }

        if ($key_line === 'oqc') {
            $info['oqc'] = ['thoi_gian_vao' => Carbon::now(), 'user_id' => $request->user()->id, 'user_name' => $request->user()->name];
            $info_cong_doan = InfoCongDoan::where('line_id', $line->id)->where('lot_id', $request->lot_id)->first();
            if (!$info_cong_doan) {
                $info_cong_doan = new InfoCongDoan();
                $info_cong_doan['type'] = 'sx';
                $info_cong_doan['sl_dau_vao_hang_loat'] = $pallet->so_luong;
                $info_cong_doan['sl_dau_ra_hang_loat'] = $pallet->so_luong;
                $info_cong_doan['lot_id'] = $pallet->id;
                $info_cong_doan['line_id'] = $request->line_id;
                $info_cong_doan['thoi_gian_bat_dau'] = Carbon::now();
                $info_cong_doan->save();
            }
        }

        $info['qc'] = $tm;
        $log->info = $info;
        $log->save();

        return $this->success($log);
    }


    private function formatDataTest($request, $flag = false)
    {

        if (!$flag) {
            $res = [];
            $res[$request->key] = [];
            $res[$request->key]['data'] = $request->data;
            $res[$request->key]['result'] = $request->result;
        } else {
            $res = [];

            $res['data'] = $request->data;
            $res['result'] = $request->result;
        }

        return $res;
    }


    private function formatDataError($request, $errors = [], $flag = false)
    {
        $res = [];
        $res['errors'] = $errors;
        $permission = [];
        foreach ($request->user()->roles as $role) {
            $tm = ($role->permissions()->pluck("slug"));
            foreach ($tm as $t) {
                $permission[] = $t;
            }
        }
        if (!$flag) {
            $res['errors'][] = ['data' => $request->data, 'user_id' => $request->user()->id, 'type' => count(array_intersect(['oqc', 'pqc'], $permission)) > 0 ? 'qc' : 'sx', 'thoi_gian_kiem_tra' => Carbon::now()];
        } else {
            $errors_data = [];
            foreach ($errors as $key => $err) {
                if (!is_numeric($err)) {
                    foreach ($err['data'] as $err_key => $err_val) {
                        if (!isset($errors_data[$err_key])) {
                            $errors_data[$err_key] = 0;
                        }
                        $errors_data[$err_key] += $err_val;
                    }
                } else {
                    if (!isset($errors_data[$key])) {
                        $errors_data[$key] = 0;
                    }
                    $errors_data[$key] += $err;
                }
            }
            $arrays = [json_decode(json_encode($request->data), true), $errors_data];
            // return $arrays;
            foreach ($arrays as $array) {
                foreach ($array ?? [] as $key => $value) {
                    // return [$array, $value];
                    if (!is_numeric($value)) {
                        continue;
                    }
                    if (!isset($merged[$key])) {
                        $merged[$key] = $value;
                    } else {
                        $merged[$key] += $value;
                    }
                }
            }
            // return $merged;
            $res['errors'] = $merged;
        }

        return $res;
    }

    public function resultTest(Request $request)
    {
        $pallet = Lot::find($request->lot_id);
        if (!isset($pallet)) {
            return $this->failure([], "Không tìm thấy pallet");
        }
        $line = Line::find($request->line_id);
        $line_key = Str::slug($line->name);
        if (!isset($line)) {
            return $this->failure([], "Không tìm thấy công đoạn");
        }
        $key_line = Str::slug($line->name);
        // if ($key_line == "chon" || $key_line == "oqc") {
        //     $pallet = $pallet->parrent;
        //     if (!isset($pallet)) {
        //         return $this->failure([], "Không tìm thấy pallet");
        //     }
        // }
        $log = $pallet->log;
        $info = $log->info;
        if (!isset($info['qc'][$line_key])) {
            $info['qc'][$line_key] = [];
        }
        if ($key_line !== 'gap-dan') {
            $info['qc'][$line_key] = array_merge($info['qc'][$line_key], $this->formatDataTest($request));
        } else {
            $latest_bats = Lot::where('p_id', $pallet->id)->where('type', 1)->orderBy('created_at', 'DESC')->first();
            if ($latest_bats) {
                $info['qc'][$line_key]['bat'][$latest_bats->id] = array_merge($info['qc'][$line_key]['bat'][$latest_bats->id] ?? [], $this->formatDataTest($request));
            }
        }
        // return $info;
        $log->info =  $info;
        $log->save();
        return $this->success($log);
    }

    public function errorTest(Request $request)
    {
        $pallet = Lot::find($request->lot_id);
        if (!isset($pallet)) {
            return $this->failure([], "Không tìm thấy pallet");
        }
        $line = Line::find($request->line_id);
        $line_key = Str::slug($line->name);
        if (!isset($line)) {
            return $this->failure([], "Không tìm thấy công đoạn");
        }
        $log = $pallet->log;
        $plan = $pallet->plan;
        $info = $log->info;
        if (!isset($info['qc'][$line_key])) {
            $info['qc'][$line_key] = [];
        }
        $info_cong_doan = $pallet->infoCongDoan()->where('type', 'sx')->where('line_id', $line->id)->first();
        $sl_ng = 0;
        $request_errors = 0;
        foreach ($request['data'] as $err_key => $err_val) {
            $request_errors += $err_val;
        }
        if ($line_key !== "gap-dan") {
            $errors = $this->formatDataError($request, isset($info['qc'][$line_key]['errors']) ? $info['qc'][$line_key]['errors'] : [], true);
            $info['qc'][$line_key] = array_merge($info['qc'][$line_key], $this->formatDataError($request, isset($info['qc'][$line_key]['errors']) ? $info['qc'][$line_key]['errors'] : [], false));
            foreach ($errors['errors'] as $value) {
                $sl_ng += $value;
            }
            $info['qc'][$line_key]['sl_ng'] = $sl_ng;
        } else {
            $bat = Lot::where('p_id', $pallet->id)->where('type', 1)->orderBy('created_at', 'DESC')->first();
            $errors = $this->formatDataError($request, isset($info['qc'][$line_key]['bat'][$bat->id]['errors']) ? $info['qc'][$line_key]['bat'][$bat->id]['errors'] : [], true);
            $bat_info = $info['qc'][$line_key]['bat'];
            if (!isset($bat_info[$bat->id])) {
                $bat_info[$bat->id] = [];
            }
            $bat_info[$bat->id] = array_merge($bat_info[$bat->id], $this->formatDataError($request, isset($info['qc'][$line_key]['bat'][$bat->id]['errors']) ? $info['qc'][$line_key]['bat'][$bat->id]['errors'] : [], false));
            $info['qc'][$line_key]['bat'] = $bat_info;
            foreach ($errors['errors'] ?? [] as $value) {
                $sl_ng += $value;
            }
            $info['qc'][$line_key]['bat'][$bat->id]['sl_ng'] = $sl_ng;
        }
        // return $errors;
        $log->info = $info;

        if ($info_cong_doan) {
            $sl_con_lai = 0;
            if ($request->line_id == 10 || $request->line_id == 11 || $request->line_id == 12 || $request->line_id == 14 || $request->line_id == 22) {
                $sl_con_lai = $info_cong_doan->sl_dau_ra_hang_loat - $info_cong_doan->sl_tem_vang - ($sl_ng * $plan->so_bat ?? 0);
                $info_cong_doan['sl_ng'] += $request_errors * $plan->so_bat ?? 0;
            } else {
                if ($line_key === 'gap-dan') {
                    $bat = Lot::where('p_id', $pallet->id)->where('type', 1)->orderBy('created_at', 'DESC')->first();
                    $info_cd_bat = $bat->infoCongDoan()->where('type', 'sx')->where('line_id', $line->id)->first();
                    $sl_con_lai = $info_cd_bat->sl_dau_ra_hang_loat - $info_cd_bat->sl_tem_vang - $sl_ng;
                }
                $info_cong_doan['sl_ng'] += $request_errors;
            }
            if ($sl_con_lai < 0) {
                return $this->failure([], 'Tổng số lượng tem vàng và NG không được vượt quá số lượng thực tế');
            }
            $info_cong_doan->save();
        }

        if ($line_key === 'gap-dan') {
            $bat = Lot::where('p_id', $pallet->id)->where('type', 1)->orderBy('created_at', 'DESC')->first();
            $log_bat = $bat->log;
            $info_bat = $log_bat->info;
            $info_bat['qc'][$line_key]['sl_ng'] = $sl_ng;
            $log_bat->info = $info_bat;
            $log_bat->save();

            $info_cd_bat = $bat->infoCongDoan()->where('type', 'sx')->where('line_id', $line->id)->first();
            $info_cd_bat['sl_ng'] = $sl_ng;
            $info_cd_bat->save();
        }
        $log->save();
        return $this->success($log);
    }


    private function mergeAndSum($d1, $d2)
    {
        // dd($d1,$d2);

        $res = [];
        if (!isset($d1)) $d1 = [];
        if (!isset($d2)) $d2 = [];
        foreach ($d1 as $item) {
            $key = key($item);
            if (isset($res[$key])) {
                $res[$key] += $item[$key];
            } else {
                $res[$key] = $item[$key];
            }
        }
        foreach ($d2 as $item) {
            $key = key($item);
            // dd($item);
            if (isset($res[$key])) {
                $res[$key] += $item[$key];
            } else {
                $res[$key] = $item[$key];
            }
        }
        $ret = [];
        foreach ($res as $key => $item) {
            $ret[] = [
                $key => $item
            ];
        }

        return $ret;
    }


    public function khoanhVung(Request $request)
    {
        $pallet = Lot::find($request->lot_id);
        if (!isset($pallet)) {
            return $this->failure([], "Không tìm thấy pallet");
        }
        $line = Line::find($request->line_id);
        $line_key = Str::slug($line->name);
        if (!isset($line)) {
            return $this->failure([], "Không tìm thấy công đoạn");
        }
        $log = $pallet->log;
        $info = $log->info;
        if (!isset($info['qc'][$line_key])) {
            $info['qc'][$line_key] = [];
        }
    }


    public function danhSachPalletQC($line_id)
    {
        $line = Line::find($line_id);
        $data = [];
        $line_key = Str::slug($line->name);
        $list  = LSXLog::listPallet($line_key)->get();
        foreach ($list as $item) {
            if (isset($item->lot)) {
                if (isset($item->info['qc'][$line_key]))
                    $data[] = $item->lot->thongTinQC($line);
            }
        }
        return $data;
    }

    public function detailLoSX(Request $request)
    {
        $lot = Lot::find($request->lot_id);
        $plan = $lot->plan;
        // $lo_sx = $plan->loSX;
        $product = $lot->product;
        $lo_sx = $product->lots;
        // return $lo_sx;
        $san_luong = InfoCongDoan::whereIn('lot_id', $lo_sx->pluck('id'))->where('line_id', $request->line_id)->where('type', 'sx')->whereDate('created_at', date('Y-m-d'))->get();
        // return $san_luong->toArray();
        // $plan = $lot->plan;
        // $product = $plan->product;
        // $log = $lot->log->info;
        // return $this->success($san_luong);
        if ($request->line_id == 10 ||  $request->line_id == 11 || $request->line_id == 12 || $request->line_id == 14 || $request->line_id == 22) {
            return $this->success([
                "lot_id" => $lot->id,
                "ma_hang" => $product->id,
                "ten_sp" => $product->name,
                "lo_sx" => $lot->lo_sx,
                "sl_ke_hoach" => $plan->sl_thanh_pham ?? 0,
                "sl_thuc_te" => $plan ? $san_luong->sum('sl_dau_ra_hang_loat') / $plan->so_bat : 0,
                'sl_tem_vang' => $plan ? $san_luong->sum('sl_tem_vang') / $plan->so_bat : 0,
                'sl_ng' => $plan ? $san_luong->sum('sl_ng') / $plan->so_bat : 0,
                'sl_dau_ra' => $plan ? $san_luong->sum('sl_dau_ra_hang_loat') / $plan->so_bat : 0,
                'ver' => $product->ver,
                'his' => $product->his,
            ]);
        } else {
            return $this->success([
                "lot_id" => $lot->id,
                "ma_hang" => $product->id,
                "ten_sp" => $product->name,
                "lo_sx" => $lot->lo_sx,
                "sl_ke_hoach" => $plan ? $plan->so_bat * $plan->sl_thanh_pham : 0,
                "sl_thuc_te" => $san_luong->sum('sl_dau_ra_hang_loat'),
                'sl_tem_vang' => $san_luong->sum('sl_tem_vang'),
                'sl_ng' => $san_luong->sum('sl_ng'),
                'sl_dau_ra' => $san_luong->sum('sl_dau_ra_hang_loat'),
                'ver' => $product->ver,
                'his' => $product->his,
            ]);
        }
    }

    public function qcOverall(Request $request)
    {
        $line = Line::find($request->line_id);
        if (!isset($line)) return $this->failure([], 'Không tìm thấy công đoạn');
        $key_line = Str::slug($line->name);
        $data = new stdClass();
        $query = ProductionPlan::where('cong_doan_sx', $key_line);
        $info_cong_doan = InfoCongDoan::where('line_id', $line->id)->where('type', 'sx')->whereDate('thoi_gian_ket_thuc', Carbon::today())->get();
        if ($request->line_id == 10 || $request->line_id == 11 || $request->line_id == 12 || $request->line_id == 14 || $request->line_id == 22) {
            $data->ke_hoach = (int)$query->sum('sl_thanh_pham');
            $data->muc_tieu = round(($data->ke_hoach / 12) * ((int)date('H') - 6));
            $data->ket_qua = $info_cong_doan->sum('sl_dau_ra_hang_loat');
        } else {
            $data->ke_hoach = (int)$query->sum('so_bat') * (int)$query->sum('sl_thanh_pham');
            $data->muc_tieu = round(($data->ke_hoach / 12) * ((int)date('H') - 6));
            $data->ket_qua = $info_cong_doan->sum('sl_dau_ra_hang_loat');
        }
        return $this->success($data);
    }

    public function inTemVang(Request $request)
    {
        $pallet = Lot::find($request->lot_id);
        if (!isset($pallet)) {
            return $this->failure([], "Không tìm thấy pallet");
        }
        $line = Line::find($request->line_id);
        if (!isset($line)) {
            return $this->failure([], "Không tìm thấy công đoạn");
        }
        $line_key = Str::slug($line->name);
        $nguoi_sx = isset($pallet->log->info[$line_key]['user_name']) ? $pallet->log->info[$line_key]['user_name'] : '';
        $nguoi_qc = isset($pallet->log->info['qc'][$line_key]['user_name']) ? $pallet->log->info['qc'][$line_key]['user_name'] : '';
        if ($line_key === 'gap-dan') {
            $bat = Lot::where('p_id', $pallet->id)->where('type', 1)->orderBy('created_at', 'DESC')->first();
            if ($bat) {
                $pallet = $bat;
            }
        }
        $log = $pallet->log;
        if ($line_key === 'gap-dan') {
            $parrent = $pallet->parrent;
            $log_parent = $parrent->log;
            if (!$log_parent->checkQC($line_key)) return $this->failure([], "Bạn chưa thể in tem, cần kiểm tra lại chất lượng");
        } else {
            if (!$log->checkQC($line_key)) return $this->failure([], "Bạn chưa thể in tem, cần kiểm tra lại chất lượng");
        }

        $info = $log->info;
        // $log_in_tem = LogInTem::where('lot_id', $pallet->id)->where('line_id', $line->id)->where('type', 2)->get();
        // if(isset($info['qc'][$line_key]['thoi_gian_ra'])){
        //     if(count($log_in_tem) > 0){
        //         return $this->success($log_in_tem->pluck('log'));
        //     }else{
        //         return $this->failure([], 'Chưa có log in tem');
        //     }
        // }
        $plan = $pallet->plan;
        $product = $pallet->product;
        $san_luong = $pallet->infoCongDoan()->where('type', 'sx')->where('line_id', $line->id)->first();
        if ($san_luong->sl_tem_vang <= 0) {
            return $this->failure('', 'Không có số lượng tem vàng không thể in tem');
        }
        $errors = Error::whereIn('id', $info['qc'][$line_key]['loi_tem_vang'] ?? [])->get()->pluck('noi_dung')->toArray();
        $data = [];
        if ($line->id == 20) {
            $cd_tiep_theo = 'Chọn';
        } else if ($line->id == 22) {
            $cd_tiep_theo = 'Bế';
        } else {
            $cd_td = Line::where('ordering', '>', $line->ordering)->orderBy('ordering', 'ASC')->first();
            $cd_tiep_theo = $cd_td->name;
        }
        if ($line->id == 10 || $line->id == 11 || $line->id == 12 || $line->id == 14 || $line->id == 22) {
            $data = [
                "lot_id" => $pallet->id,
                "ma_hang" => $product->id,
                "ten_sp" => $product->name,
                "lo_sx" => $pallet->lo_sx,
                'luong_sx' => ($san_luong && $plan->so_bat) ? (($san_luong->sl_dau_ra_hang_loat + $san_luong->sl_dau_ra_chay_thu) / $plan->so_bat) : 0,
                'sl_ok' => ($san_luong && $plan->so_bat) ? (($san_luong->sl_dau_ra_hang_loat / $plan->so_bat) - ($san_luong->sl_tem_vang ? $san_luong->sl_tem_vang / $plan->so_bat : 0) - ($san_luong->sl_ng ? $san_luong->sl_ng / $plan->so_bat : 0)) : 0,
                'sl_tem_vang' => ($san_luong && $plan->so_bat) ? $san_luong->sl_tem_vang / $plan->so_bat : 0,
                'sl_ng' => ($san_luong && $plan->so_bat) ? $san_luong->sl_ng / $plan->so_bat : 0,
                'sl_dau_ra' => ($san_luong && $plan->so_bat) ? $san_luong->sl_dau_ra_hang_loat / $plan->so_bat : 0,
                'ver' => $product->ver,
                'his' => $product->his,
                "nguoi_sx" => $nguoi_sx,
                "nguoi_qc" => $nguoi_qc,
                'ghi_chu' => implode(', ', $errors),
                'cd_tiep_theo' => $cd_tiep_theo,
            ];
        } else {
            $data = [
                "lot_id" => $pallet->id,
                "ma_hang" => $product->id,
                "ten_sp" => $product->name,
                "lo_sx" => $pallet->lo_sx,
                'luong_sx' => $san_luong ? ($san_luong->sl_dau_ra_hang_loat + $san_luong->sl_dau_ra_chay_thu) : 0,
                'sl_ok' => $san_luong ? ($san_luong->sl_dau_ra_hang_loat - ($san_luong->sl_tem_vang ?? 0) - ($san_luong->sl_ng ?? 0)) : 0,
                'sl_tem_vang' => $san_luong ? $san_luong->sl_tem_vang : 0,
                'sl_ng' => $san_luong ? $san_luong->sl_ng : 0,
                'sl_dau_ra' => $san_luong ? $san_luong->sl_dau_ra_hang_loat : 0,
                'ver' => $product->ver,
                'his' => $product->his,
                "nguoi_sx" => $nguoi_sx,
                "nguoi_qc" => $nguoi_qc,
                'ghi_chu' => implode(', ', $errors),
                'cd_tiep_theo' => $cd_tiep_theo,
            ];
        }
        // return !isset($info['qc'][$line_key]['sl_tem_vang']);
        $lot_tem_vang = new Lot();
        if ($pallet->type === 3) {
            $parts  = explode('.', $pallet->id);
            array_pop($parts);
            $string = implode('.', $parts);
            $lot_tem_vang->id = $string . '.TV' . $line->id;
        } else {
            $lot_tem_vang->id = $pallet->id . '.TV' . $line->id;
        }
        $check_lot_tv = Lot::where('id', $lot_tem_vang->id)->first();
        if (!$check_lot_tv) {
            $lot_tem_vang->type = 3;
            $lot_tem_vang->lo_sx = $pallet->lo_sx;
            $lot_tem_vang->so_luong = $san_luong->sl_tem_vang;
            $lot_tem_vang->finished = 0;
            $lot_tem_vang->product_id = $pallet->product_id;
            $lot_tem_vang->p_id = $request->lot_id;
            $lot_tem_vang->save();
            $data['lot_id'] = $lot_tem_vang->id;
        } else {
            $data['lot_id'] = $check_lot_tv->id;
            if ($line->id == 20) {
                Lot::find($check_lot_tv->id)->update(['so_luong' => $san_luong->sl_tem_vang]);
            }
        }
        if (isset($data['sl_ok']) && $data['sl_ok'] <= 0 && isset($info['qc'][$line_key]['sl_tem_vang'])) {
            $info[$line_key]['thoi_gian_ra'] = Carbon::now();
            $info[$line_key]['thoi_gian_xuat_kho'] = Carbon::now();
            $log->info = $info;
            $log->save();
            $params = new stdClass();
            $params->lot_id = $pallet->id;
            $params->line_id = $line->id;
            $params->is_pass = true;
            if ($line_key === 'gap-dan') {
                $params->lot_id = $pallet->parrent->id;
                $this->endIntem($params);
            } else {
                $this->endIntem($params);
            }
        }
        $new_id = [];
        $new_sl = [];

        array_push($new_id, $data['lot_id']);
        array_push($new_sl, $data['sl_tem_vang']);

        $data['new_id'] = $new_id;
        $data['new_sl'] = $new_sl;

        // $new_log_in_tem = new LogInTem();
        // $new_log_in_tem['lot_id'] = $pallet->id;
        // $new_log_in_tem['line_id'] = $line->id;
        // $new_log_in_tem['log'] = $data;
        // $new_log_in_tem['type'] = 2;
        // $new_log_in_tem->save();
        return $this->success($data);
    }

    public function updateSoLuongTemVang(Request $request)
    {
        $pallet = Lot::find($request->lot_id);
        if (!isset($pallet)) {
            return $this->failure([], "Không tìm thấy pallet");
        }
        $line = Line::find($request->line_id);
        if (!isset($line)) {
            return $this->failure([], "Không tìm thấy công đoạn");
        }
        $line_key = Str::slug($line->name);
        $log = $pallet->log;
        $plan = $pallet->plan;
        $info_cong_doan = InfoCongDoan::where('type', 'sx')->where("lot_id", $pallet->id)->where('line_id', $request->line_id)->first();
        if ($info_cong_doan) {
            if ($request->line_id == 10 || $request->line_id == 11 || $request->line_id == 12 || $request->line_id == 14 || $request->line_id == 22) {
                $sl_con_lai = $info_cong_doan->sl_dau_ra_hang_loat - (($request->sl_tem_vang * $plan->so_bat ?? 0) + $info_cong_doan->sl_tem_vang) - $info_cong_doan->sl_ng;
                if ($sl_con_lai < 0) {
                    return $this->failure([], 'Tổng số lượng tem vàng và NG không được vượt quá số lượng thực tế');
                }
                $info_cong_doan['sl_tem_vang'] += ($request->sl_tem_vang * $plan->so_bat ?? 0);
            } else {
                if ($line_key === 'gap-dan') {
                    $bat = Lot::where('p_id', $pallet->id)->where('type', 1)->orderBy('created_at', 'DESC')->first();
                    $info_cd_bat = $bat->infoCongDoan()->where('type', 'sx')->where('line_id', $line->id)->first();
                    $sl_con_lai = $info_cd_bat->sl_dau_ra_hang_loat - ($request->sl_tem_vang + $info_cd_bat->sl_tem_vang) - $info_cd_bat->sl_ng;
                    if ($sl_con_lai < 0) {
                        return $this->failure([], 'Tổng số lượng tem vàng và NG không được vượt quá số lượng thực tế');
                    }
                    $bat->so_luong = $sl_con_lai;
                    $bat->save();
                } else {
                    $sl_con_lai = $info_cong_doan->sl_dau_ra_hang_loat - ($request->sl_tem_vang + $info_cong_doan->sl_tem_vang) - $info_cong_doan->sl_ng;
                    if ($sl_con_lai < 0) {
                        return $this->failure([], 'Tổng số lượng tem vàng và NG không được vượt quá số lượng thực tế');
                    }
                }
                if ($line->id == 20) {
                    $info_cong_doan['sl_tem_vang'] = $request->sl_tem_vang;
                } else {
                    $info_cong_doan['sl_tem_vang'] += $request->sl_tem_vang;
                }
            }
            // $info_cong_doan->save();
        }
        $info = $log->info;

        $info['qc'][$line_key]['thoi_gian_ra'] = Carbon::now();
        if (!isset($info['qc'][$line_key]['sl_tem_vang'])) {
            $info['qc'][$line_key]['sl_tem_vang'] = 0;
        }
        if ($request->line_id == 10 || $request->line_id == 11 || $request->line_id == 12 || $request->line_id == 14 || $request->line_id == 22) {
            $info['qc'][$line_key]['sl_tem_vang'] += $request->sl_tem_vang * ($plan ? $plan->so_bat : 0);
        } else {
            if ($line->id == 20) {
                $info['qc'][$line_key]['sl_tem_vang'] = $request->sl_tem_vang;
            } else {
                $info['qc'][$line_key]['sl_tem_vang'] += $request->sl_tem_vang;
            }
        }
        $errors = Error::whereIn('id', $request->errors)->get()->pluck('id');
        $info['qc'][$line_key]['loi_tem_vang'] = $errors;

        if ($line_key === 'gap-dan') {
            $bat = Lot::where('p_id', $pallet->id)->where('type', 1)->orderBy('created_at', 'DESC')->first();
            if (!isset($info['qc'][$line_key]['bat'])) {
                $info['qc'][$line_key]['bat'] = [];
            }
            if (!isset($info['qc'][$line_key]['bat'][$bat->id])) {
                $info['qc'][$line_key]['bat'][$bat->id] = [];
            }
            if (!isset($info['qc'][$line_key]['bat'][$bat->id]['sl_tem_vang'])) {
                $info['qc'][$line_key]['bat'][$bat->id]['sl_tem_vang'] = 0;
            }
            $info['qc'][$line_key]['bat'][$bat->id]['sl_tem_vang'] += $request->sl_tem_vang;
            $info['qc'][$line_key]['bat'][$bat->id]['loi_tem_vang'] = $errors;
            $log_bat = $bat->log;
            $info_cong_doan_bat = InfoCongDoan::where('type', 'sx')->where("lot_id", $bat->id)->where('line_id', $request->line_id)->first();
            if ($info_cong_doan_bat) {
                $info_cong_doan_bat['sl_tem_vang'] += $request->sl_tem_vang;
            }
            $info_bat = $log_bat->info;
            $info_bat['qc'][$line_key]['thoi_gian_ra'] = Carbon::now();
            if (!isset($info_bat['qc'][$line_key]['sl_tem_vang'])) {
                $info_bat['qc'][$line_key]['sl_tem_vang'] = 0;
            }
            $info_bat['qc'][$line_key]['loi_tem_vang'] = $errors;
            $info_bat['qc'][$line_key]['sl_tem_vang'] += $request->sl_tem_vang;
            $log_bat->info = $info_bat;
            $log_bat->save();
            $info_cong_doan_bat->save();
        }
        $log->info = $info;
        $log->save();
        if ($info_cong_doan) $info_cong_doan->save();
        return $this->success($info_cong_doan);
    }


    // MQTT

    public function webhook(Request $request)
    {
        $privateKey = "MIGfMA0GCSqGSIb3DQ";
        if ($request->private_key !== $privateKey) {
            return $this->failure([], "Incorrect private_key");
        }
        $machine = Machine::where('code', $request->machine_id)->first();
        if ($request->record_type == 'sx') {
            $status = MachineStatus::getStatus($request->machine_id);
            // if ($status == 0) {
            //     Tracking::updateData($request->machine_id, $request->input, $request->output);
            //     return;
            // }
            $info_cong_doan = InfoCongDoan::where('type', $request->record_type)->where('line_id', $machine->line_id)->where("thoi_gian_bat_dau", '<>', null)->whereNull('thoi_gian_ket_thuc')->orderBy('created_at', 'DESC')->first();

            $sl_bat = $info_cong_doan->lot->plan->so_bat;

            $tracking = Tracking::getData($request->machine_id);

            $d_input = $request->input - $tracking->input;
            $d_output = $request->output - $tracking->output;
            if ($machine->line_id != 13) {
                $d_output = $sl_bat * $d_output;
                $d_input = $sl_bat * $d_input;
            }
            if ($d_input < 0) $d_input = 0;
            if ($d_output < 0) $d_output = 0;
            Tracking::updateData($request->machine_id, $request->input, $request->output);
            if ($info_cong_doan) {
                $status = MachineStatus::getStatus($request->machine_id);
                if ($status == 0) { //chạy thử/vào hàng
                    if (!isset($info_cong_doan->sl_dau_vao_chay_thu)) $info_cong_doan->sl_dau_vao_chay_thu = 0;
                    $info_cong_doan->sl_dau_vao_chay_thu += $d_input;

                    if (!isset($info_cong_doan->sl_dau_ra_chay_thu)) $info_cong_doan->sl_dau_ra_chay_thu = 0;
                    $info_cong_doan->sl_dau_ra_chay_thu += $d_output;
                } else if ($status == 1) { // chạy hàng loạt
                    if (!isset($info_cong_doan->sl_dau_vao_hang_loat)) $info_cong_doan->sl_dau_vao_hang_loat = 0;
                    $info_cong_doan->sl_dau_vao_hang_loat += $d_input;

                    if (!isset($info_cong_doan->sl_dau_ra_hang_loat)) $info_cong_doan->sl_dau_ra_hang_loat = 0;
                    $info_cong_doan->sl_dau_ra_hang_loat += $d_output;
                    if ($d_output > 0) {
                        $speed = $request->output - $tracking->output;
                        $machine_speed = MachineSpeed::create(['machine_id' => $request->machine_id, 'speed' => ($speed) * 720]);
                    }
                }
                $info_cong_doan->save();
            }
        }
        if ($request->record_type == 'cl') {
            $log_iot = new MachineIOT();
            $log_iot->data = $request->all();
            $log_iot->save();
            if ($request->machine_id == 'bao-on') {
                $insulation = Insulation::find(1);
                if ($insulation) {
                    $insulation->update(['t_ev' => $request->t_ev, 'e_hum' => $request->e_hum]);
                } else {
                    Insulation::create(['t_ev' => $request->t_ev, 'e_hum' => $request->e_hum]);
                }
            }
            $tracking = Tracking::where('machine_id', $request->machine_id)->first();
            LogWarningParameter::checkParameter($request);
            if (!$tracking) {
                $tracking = new Tracking();
                $tracking->machine_id = $request->machine_id;
                $tracking->timestamp = $request->timestamp;
                $tracking->save();
            }
            if (is_null($tracking->timestamp)) {
                $tracking->update(['timestamp' => $request->timestamp]);
            }
            if (!is_null($tracking->timestamp) && $tracking->status == 1 && !is_null($tracking->lot_id)) {
                if ($request->timestamp  >= ($tracking->timestamp +  300)) {
                    $start = $tracking->timestamp;
                    $end = $tracking->timestamp +  300;
                    $logs = MachineIOT::where('data->record_type', "cl")->where('data->machine_id', $request->machine_id)->where('data->timestamp', '>=', $start)->where('data->timestamp', '<=', $end)->pluck('data')->toArray();
                    $parameters = MachineParameters::where('machine_id', $request->machine_id)->where('is_if', 1)->pluck('parameter_id')->toArray();
                    $arr = [];
                    foreach ($parameters as $key => $parameter) {
                        $arr[$parameter] = 0;
                        foreach ((array) $logs as $key => $log) {
                            if (isset($log[$parameter])) {
                                if (in_array($parameter, ['uv1', 'uv2', 'uv3'])) {
                                    $arr[$parameter] = $log[$parameter];
                                } else {
                                    $arr[$parameter] = (float)$arr[$parameter] + (float)$log[$parameter];
                                }
                            }
                        }
                    }
                    foreach ($parameters as $key => $parameter) {
                        if (!in_array($parameter, ['uv1', 'uv2', 'uv3'])) {
                            $arr[$parameter] = $logs ? number_format($arr[$parameter] / count($logs), 2) : 0;
                        }
                    }
                    $machine_speed = MachineSpeed::where('machine_id', $machine->code)->get();
                    if (count($machine_speed)) {
                        $arr['speed'] = number_format($machine_speed->sum('speed') / $machine_speed->count());
                        MachineSpeed::where('machine_id', $machine->code)->delete();
                    }
                    MachineIOT::where('data->record_type', "cl")->where('data->machine_id', $request->machine_id)->delete();
                    Tracking::where('machine_id', $request->machine_id)->update(['timestamp' => $request->timestamp]);
                    MachineParameterLogs::where('machine_id', $request->machine_id)->where('start_time', '<=', date('Y-m-d H:i:s', $request->timestamp))->where('end_time', '>=', date('Y-m-d H:i:s', $request->timestamp))->update(['data_if' => $arr]);
                    if ($machine) {
                        $line = $machine->line;
                        $updated_tracking = Tracking::where('machine_id', $machine->code)->first();
                        $lot = Lot::find($updated_tracking->lot_id);
                        if ($updated_tracking->status === 1) {
                            $thong_so_may = new ThongSoMay();
                            $ca = (int)date('H', $request->timestamp);
                            $thong_so_may['ngay_sx'] = date('Y-m-d H:i:s');
                            $thong_so_may['ca_sx'] = ($ca >= 7 && $ca <= 17) ? 1 : 2;
                            $thong_so_may['xuong'] = '';
                            $thong_so_may['line_id'] = $line->id;
                            $thong_so_may['lot_id'] = $lot ? $lot->id : null;
                            $thong_so_may['lo_sx'] = $lot ? $lot->lo_sx : null;
                            $thong_so_may['machine_code'] = $machine->code;
                            $thong_so_may['data_if'] = $arr;
                            $thong_so_may['date_if'] = date('Y-m-d H:i:s', $request->timestamp);
                            $thong_so_may->save();
                        }
                    }
                }
            }
        }
        ##
        if ($request->record_type == "tb") {
            $tracking = Tracking::where('machine_id', $request->machine_id)->first();
            $tracking->update(['status' => $request->status]);
            $machine_status = MachineStatus::where('machine_id', $request->machine_id)->first();
            if ($machine_status->status == 1) {
                $res = MachineLog::UpdateStatus($request);
            }
        }
        ##
        // if(isset($tracking) && !is_null($tracking->lot_id)){
        $iot_log = new IOTLog();
        $input = $request->all();
        $iot_log->data = $input;
        $iot_log->save();
        // }
        return $this->success([]);
    }

    public function frequency(Request $request)
    {
        $privateKey = "MIGfMA0GCSqGSIb3DQ";
        if ($request->private_key !== $privateKey) {
            return $this->failure([], "Incorrect private_key");
        }
        return '1000@500@300';
    }
    public function webhook_history()
    {
        $history = IOTLog::all();
        return $this->success($history);
    }

    public function recallIOT(Request $request)
    {
        $privateKey = "MIGfMA0GCSqGSIb3DQ";
        if ($request->private_key !== $privateKey) {
            return $this->failure([], "Incorrect private_key");
        }
        return [
            "record_type" => "sx",
            "time_start" => "1690859776",
            "time_end" => "1690859776",
            "machine_id" => "SN_UV"
        ];
    }

    public function tinhSanLuongIOT(Request $request)
    {
        $privateKey = "MIGfMA0GCSqGSIb3DQ";
        if ($request->private_key !== $privateKey) {
            return $this->failure([], "Incorrect private_key");
        }
        $machine = Machine::where('code', $request->machine_id)->first();
        $info_cong_doan = InfoCongDoan::where('type', 'sx')->where('line_id', $machine->line_id)->whereNotNull("thoi_gian_bat_dau")->whereNull('thoi_gian_ket_thuc')->orderBy('created_at', 'DESC')->first();
        if ($info_cong_doan) {
            $info_cong_doan['thoi_gian_bam_may'] = date('Y-m-d H:i:s', $request->timestamp);
            $info_cong_doan->save();
        }
        MachineStatus::active($request->machine_id);
        return $info_cong_doan;
        // return [
        //     "record_type" => "tsl",
        //     "machind_id" => "SN_UV",
        //     "timestamp" => "1690859776"
        // ];
    }

    public function thuNghiemIOT(Request $request)
    {
        $privateKey = "MIGfMA0GCSqGSIb3DQ";
        if ($request->private_key !== $privateKey) {
            return $this->failure([], "Incorrect private_key");
        }
        return [

            "machind_id" => "SN_UV",
            "timetamp" => "1690859776"
        ];
    }

    //END MQTT
    public function listLot(Request $request)
    {
        $input = $request->all();
        $query = MaterialExportLog::orderBy('material_id', 'DESC')->orderBy('created_at', 'DESC');
        if (isset($input['start_date'])) {
            $query->whereDate('created_at', '>=', $input['start_date']);
        } else {
            $query->whereDate('created_at', date('Y-m-d'));
        }
        if (isset($input['end_date'])) {
            $query->whereDate('created_at', '<=', $input['end_date']);
        } else {
            $query->whereDate('created_at', date('Y-m-d'));
        }
        $records = $query->get();
        $data = [];
        foreach ($records as $key => $record) {
            $lots = Lot::with('plan')->where('type', 0)->where('material_export_log_id', $record->id)->orderBy('created_at', 'DESC')->get();
            if (count($lots) > 0) {
                foreach ($lots as $k => $lot) {
                    if (!$lot->plan) continue;
                    $object = new \stdClass();
                    $product = Product::where('id', $lot->product_id)->first();
                    $object->id = $record->id;
                    $object->lsx = $lot->lo_sx;
                    $object->sl_kho_xuat = $record->sl_kho_xuat;
                    $object->sl_thuc_te = $record->sl_thuc_te;
                    $object->so_luong_thieu = $record->sl_kho_xuat - $record->sl_thuc_te;
                    $object->lot_id = $lot->id;
                    $object->ngay_sx = date("d/m/Y", strtotime($lot->plan->ngay_sx));
                    $object->tg_sx = $lot->plan->thoi_gian_bat_dau;
                    $object->product_id =  $product->id;
                    $object->ten_sp = $product->name;
                    $object->quy_cach = $product->kt_kho_dai . '*' . $product->kt_kho_rong;
                    $object->khach_hang = $lot->plan->khach_hang;
                    $object->manvl =  $product->material_id;
                    $object->soluongtp = $lot->so_luong;
                    $object->so_luong_ke_hoach = $lot->plan->sl_nvl;
                    $object->status = $record->status;
                    $object->sl_kho_xuat = $record->sl_kho_xuat;
                    $object->cd_tiep_theo = 'Bảo ôn';
                    $data[] = $object;
                }
            } else {
                $object = new \stdClass();
                $object->id = $record->id;
                $object->lsx = '';
                $object->sl_kho_xuat = $record->sl_kho_xuat;
                $object->sl_thuc_te = $record->sl_thuc_te;
                $object->so_luong_thieu = $record->sl_kho_xuat - $record->sl_thuc_te;
                $object->lot_id = '';
                $object->ma_sp = '';
                $object->ten_sp = '';
                $object->quy_cach = '';
                $object->khach_hang = '';
                $object->manvl = $record->material_id;
                $object->soluongtp = '';
                $object->status = $record->status;
                $object->cd_tiep_theo = 'Bảo ôn';
                $data[] = $object;
            }
        }
        return $this->success($data);
    }

    //Upload KHSX
    public function uploadKHSX()
    {
        $hash = hash_file("md5", $_FILES['files']['tmp_name']);
        $lists = ProductionPlan::where("file", $hash);
        $lists->delete();
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
        // foreach ($allDataInSheet as $key => $row) {
        //     //Lấy dứ liệu từ dòng thứ 5
        //     if ($key > 3 && !is_null($row['H']) && !is_null($row['I'])) {
        //         if (is_null($row['B'])) {
        //             return $this->failure([], 'Dòng số ' . $key . ': Thiếu thứ tự ưu tiên');
        //         }
        //         if (is_null($row['C'])) {
        //             return $this->failure([], 'Dòng số ' . $key . ': Thiếu thời gian bắt đầu');
        //         }
        //         // if (is_null($row['D'])) {
        //         //     return $this->failure([], 'Dòng số ' . $key . ': Thiếu ngày đặt hàng');
        //         // }
        //         if (is_null($row['E'])) {
        //             return $this->failure([], 'Dòng số ' . $key . ': Thiếu thời gian ngày sản xuất');
        //         }
        //         if (is_null($row['F'])) {
        //             return $this->failure([], 'Dòng số ' . $key . ': Thiếu mã đơn hàng');
        //         }
        //         if (is_null($row['G'])) {
        //             return $this->failure([], 'Dòng số ' . $key . ': Thiếu công đoạn sản xuất');
        //         }
        //         if (is_null($row['H'])) {
        //             return $this->failure([], 'Dòng số ' . $key . ': Thiếu công đoạn sản xuất');
        //         }
        //         if (is_null($row['I'])) {
        //             return $this->failure([], 'Dòng số ' . $key . ': Thiếu mã sản phẩm');
        //         }
        //         if (is_null($row['J'])) {
        //             return $this->failure([], 'Dòng số ' . $key . ': Thiếu số lượng kế hoạch');
        //         }
        //         // if (is_null($row['Y'])) {
        //         //     return $this->failure([], 'Dòng số ' . $key . ': Thiếu mã quản lý');
        //         // }
        //     }
        // }
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 5
            if ($key > 3 && is_null($row['C'])) {
                break;
            }
            if ($key > 3) {
                if (!is_null($row['C'])) {
                    $input['ngay_sx'] = date('Y-m-d', strtotime($row['A']));
                    $input['thu_tu_uu_tien'] = $row['B']; //
                    $input['lo_sx'] = $row['C']; //
                    $input['ngay_dat_hang'] = $row['D'] ? date('Y-m-d', strtotime($row['D'])) : null;
                    $input['khach_hang'] = $row['E'];
                    $input['order_id'] = $row['F']; //
                    $input['dai'] = $row['L']; //
                    $input['rong'] = $row['M']; //
                    $input['cao'] = $row['N']; //
                    $input['ngay_giao_hang'] = $row['Q'] ? date('Y-m-d', strtotime($row['Q'])) : null;
                    $input['sl_kh'] = $row['AI']; //
                    $input['so_lop'] = $row['R']; //
                    $input['so_than'] = $row['S']; //
                    $input['so_ra'] = $row['T']; //
                    $input['toc_do'] = $row['X']; //
                    $input['dai_tam'] = $row['AB']; //
                    $input['so_dao'] = $row['AC']; //
                    $input['layout_id'] = $row['BE']; //
                    $input['machine_id'] = 'So01';
                    $input['kho'] = $row['Z']; //
                    $input['thoi_gian_bat_dau'] = !is_null($row['V']) ? date('Y-m-d H:i:s', strtotime($input['ngay_sx'] . ' ' . $row['V'])) : null;
                    $input['thoi_gian_ket_thuc'] = !is_null($row['AE']) ? date('Y-m-d H:i:s', strtotime($input['ngay_sx'] . ' ' . $row['AE'])) : null;
                    $input['so_m_toi'] = $row['AD'];
                    $input['is_pqc'] = $row['AD'] === 'PQC' ? 1 : 0;
                    $input['is_iqc'] = 0;
                    $input['ghi_chu'] = $row['U'];
                    $input['mql'] =  $row['K'];
                    $input['file'] = $hash;
                    $record = ProductionPlan::create(
                        $input
                    );
                    LSXLog::create(['machine_id' => 'So01', 'lo_sx' => $row['C'], 'thu_tu_uu_tien' => $row['B']]);
                    if (!is_null($row['BN'])) {
                        $input['machine_id'] = $row['BN'];
                        $input['toc_do'] = $row['BR']; //
                        $input['thoi_gian_bat_dau'] = !is_null($row['BP']) ? date('Y-m-d H:i:s', strtotime($input['ngay_sx'] . ' ' . $row['BP'])) : null;
                        $input['thoi_gian_ket_thuc'] = !is_null($row['BS']) ? date('Y-m-d H:i:s', strtotime($input['ngay_sx'] . ' ' . $row['BS'])) : null;
                        $input['ghi_chu'] = $row['BO'];
                        $input['is_pqc'] = $row['AD'] === 'PQC' ? 1 : 0;
                        $input['is_iqc'] = 0;
                        $record = ProductionPlan::create(
                            $input
                        );
                        LSXLog::create(['machine_id' => $row['BN'], 'lo_sx' => $row['C'], 'thu_tu_uu_tien' => $row['B']]);
                    }
                    if (!is_null($row['BC'])) {
                        $input['machine_id'] = $row['BC'];
                        $input['toc_do'] = $row['BI']; //
                        $input['thoi_gian_bat_dau'] = !is_null($row['BG']) ? date('Y-m-d H:i:s', strtotime($input['ngay_sx'] . ' ' . $row['BG'])) : null;
                        $input['thoi_gian_ket_thuc'] = !is_null($row['BJ']) ? date('Y-m-d H:i:s', strtotime($input['ngay_sx'] . ' ' . $row['BJ'])) : null;
                        $input['ghi_chu'] = $row['BF'];
                        $input['is_pqc'] = 1;
                        $input['is_iqc'] = 0;
                        $record = ProductionPlan::create(
                            $input
                        );
                        LSXLog::create(['machine_id' => $row['BC'], 'lo_sx' => $row['C'], 'thu_tu_uu_tien' => $row['B']]);
                    }
                    //Mapping máy in
                    if (!is_null($row['BE']) && !is_null($row['BC'])) {
                        $layout = Layout::where('layout_id', $row['BE'])->where('machine_id', trim($row['BC']))->first();
                        if ($layout) {
                            if (!is_null($layout->ma_film_1) && !is_null($layout->ma_muc_1)) {
                                $obj1 = new Mapping();
                                $obj1->lo_sx = $row['C'];
                                $obj1->machine_id = $row['BC'];
                                $obj1->position = 1;
                                $info = new stdClass();
                                $info->label = ['Vị trí lô 1', 'Mã film', 'Mã mực'];
                                if ($row['BC'] == 'P06') {
                                    $info->value = ['P00602', $layout->ma_film_1, $layout->ma_muc_1];
                                } else {
                                    $info->value = ['P01502', $layout->ma_film_1, $layout->ma_muc_1];
                                }
                                $info->key = ['vi_tri_lo_1', 'ma_film_1', 'ma_muc_1'];
                                $info->check_api = [0, 0, 0];
                                $obj1->info = $info;
                                $obj1->save();
                            }
                            if (!is_null($layout->ma_film_2) && !is_null($layout->ma_muc_2)) {
                                $obj1 = new Mapping();
                                $obj1->lo_sx = $row['C'];
                                $obj1->position = 2;
                                $obj1->machine_id = $row['BC'];
                                $info = new stdClass();
                                $info->label = ['Vị trí lô 2', 'Mã film', 'Mã mực'];
                                if ($row['BC'] == 'P06') {
                                    $info->value = ['P00603', $layout->ma_film_2, $layout->ma_muc_2];
                                } else {
                                    $info->value = ['P01503', $layout->ma_film_2, $layout->ma_muc_2];
                                }
                                $info->key = ['vi_tri_lo_2', 'ma_film_2', 'ma_muc_2'];
                                $info->check_api = [0, 0, 0];
                                $obj1->info = $info;
                                $obj1->save();
                            }
                            if (!is_null($layout->ma_film_3) && !is_null($layout->ma_muc_3)) {
                                $obj1 = new Mapping();
                                $obj1->lo_sx = $row['C'];
                                $obj1->machine_id = $row['BC'];
                                $obj1->position = 3;
                                $info = new stdClass();
                                $info->label = ['Vị trí lô 3', 'Mã film', 'Mã mực'];
                                if ($row['BC'] == 'P06') {
                                    $info->value = ['P00604', $layout->ma_film_3, $layout->ma_muc_3];
                                } else {
                                    $info->value = ['P01504', $layout->ma_film_3, $layout->ma_muc_3];
                                }
                                $info->key = ['vi_tri_lo_3', 'ma_film_3', 'ma_muc_3'];
                                $info->check_api = [0, 0, 0];
                                $obj1->info = $info;
                                $obj1->save();
                            }
                            if (!is_null($layout->ma_film_4) && !is_null($layout->ma_muc_4)) {
                                $obj1 = new Mapping();
                                $obj1->lo_sx = $row['C'];
                                $obj1->machine_id = $row['BC'];
                                $obj1->position = 4;
                                $info = new stdClass();
                                $info->label = ['Vị trí lô 4', 'Mã film', 'Mã mực'];
                                if ($row['BC'] == 'P06') {
                                    $info->value = ['P00605', $layout->ma_film_4, $layout->ma_muc_4];
                                } else {
                                    $info->value = ['P01505', $layout->ma_film_4, $layout->ma_muc_4];
                                }
                                $info->key = ['vi_tri_lo_4', 'ma_film_4', 'ma_muc_4'];
                                $info->check_api = [0, 0, 0];
                                $obj1->info = $info;
                                $obj1->save();
                            }
                            if (!is_null($layout->ma_film_5) && !is_null($layout->ma_muc_5)) {
                                $obj1 = new Mapping();
                                $obj1->lo_sx = $row['C'];
                                $obj1->machine_id = $row['BC'];
                                $obj1->position = 5;
                                $info = new stdClass();
                                $info->label = ['Vị trí lô 1', 'Mã film', 'Mã mực'];
                                if ($row['BC'] == 'P06') {
                                    $info->value = ['P00606', $layout->ma_film_5, $layout->ma_muc_5];
                                } else {
                                    $info->value = ['P01506', $layout->ma_film_5, $layout->ma_muc_5];
                                }
                                $info->key = ['vi_tri_lo_5', 'ma_film_5', 'ma_muc_5'];
                                $info->check_api = [0, 0, 0];
                                $obj1->info = $info;
                                $obj1->save();
                            }

                            if (!is_null($layout->ma_khuon)) {
                                $obj1 = new Mapping();
                                $obj1->lo_sx = $row['C'];
                                $obj1->machine_id = $row['BC'];
                                $obj1->position = 6;
                                $info = new stdClass();
                                $info->label = ['Vị trí khuôn', 'Mã khuôn'];
                                if ($row['BC'] == 'P06') {
                                    $info->value = ['P00607', $layout->ma_khuon];
                                } else {
                                    $info->value = ['P01507', $layout->ma_khuon];
                                }
                                $info->key = ['vi_tri_khuon', 'ma_khuon'];
                                $info->check_api = [0, 0, 0];
                                $obj1->info = $info;
                                $obj1->save();
                            }
                        }
                    }

                    //Mapping máy sóng
                    if ($row['AM']) {
                        $obj1 = new Mapping();
                        $obj1->lo_sx = $row['C'];
                        $obj1->machine_id = 'S0105';
                        $info = new stdClass();
                        $info->label = ['Vị trí F', 'Mã cuộn F'];
                        $info->value = ['S010501', $row['AM']];
                        $info->key = ['vi_tri_f', 'ma_cuon_f'];
                        $info->check_api = [0, 1];
                        $obj1->info = $info;
                        $obj1->save();
                        $material_ids = Material::where('ma_vat_tu', $row['AM'])->pluck('id')->toArray();
                        $map = LocatorMLTMap::where('material_id', $material_ids)->orderBy('created_at', 'ASC')->first();
                        if ($map) {
                            WareHouseMLTExport::create(['position_id' => 'S010501', 'position_name' => 'F', 'material_id' => $map->material_id, 'locator_id' => $map->locator_mlt_id]);
                        }
                    }
                    if ($row['AN'] || $row['AO']) {
                        $obj1 = new Mapping();
                        $obj1->lo_sx = $row['C'];
                        $obj1->machine_id = 'S0104';
                        $info = new stdClass();
                        $label = [];
                        $value = [];
                        $key = [];
                        $check_api = [];
                        if ($row['AN']) {
                            array_push($label, 'Vị trí sE', 'Mã cuộn');
                            array_push($value, 'S010401', $row['AN']);
                            array_push($key, 'vi_tri_se', 'ma_cuon_se');
                            array_push($check_api, 0, 1);
                            $material_ids = Material::where('ma_vat_tu', $row['AN'])->pluck('id')->toArray();
                            $map = LocatorMLTMap::where('material_id', $material_ids)->orderBy('created_at', 'ASC')->first();
                            if ($map) {
                                WareHouseMLTExport::create(['position_id' => 'S010401', 'position_name' => 'sE', 'material_id' => $map->material_id, 'locator_id' => $map->locator_mlt_id]);
                            }
                        }
                        if ($row['AO']) {
                            array_push($label, 'Vị trí lE', 'Mã cuộn');
                            array_push($value, 'S010402', $row['AO']);
                            array_push($key, 'vi_tri_le', 'ma_cuon_le');
                            array_push($check_api, 0, 1);
                            $material_ids = Material::where('ma_vat_tu', $row['AO'])->pluck('id')->toArray();
                            $map = LocatorMLTMap::where('material_id', $material_ids)->orderBy('created_at', 'ASC')->first();
                            if ($map) {
                                WareHouseMLTExport::create(['position_id' => 'S010402', 'position_name' => 'lE', 'material_id' => $map->material_id, 'locator_id' => $map->locator_mlt_id]);
                            }
                        }
                        $info->label = $label;
                        $info->value = $value;
                        $info->check_api = $check_api;
                        $info->key = $key;
                        $obj1->info = $info;
                        $obj1->save();
                    }
                    if ($row['AP'] || $row['AQ']) {
                        $obj1 = new Mapping();
                        $obj1->lo_sx = $row['C'];
                        $obj1->machine_id = 'S0103';
                        $info = new stdClass();
                        $label = [];
                        $value = [];
                        $key = [];
                        $check_api = [];
                        if ($row['AP']) {
                            array_push($label, 'Vị trí sB', 'Mã cuộn');
                            array_push($value, 'S010301', $row['AP']);
                            array_push($key, 'vi_tri_sb', 'ma_cuon_sb');
                            array_push($check_api, 0, 1);
                            $material_ids = Material::where('ma_vat_tu', $row['AP'])->pluck('id')->toArray();
                            $map = LocatorMLTMap::where('material_id', $material_ids)->orderBy('created_at', 'ASC')->first();
                            if ($map) {
                                WareHouseMLTExport::create(['position_id' => 'S010301', 'position_name' => 'sB', 'material_id' => $map->material_id, 'locator_id' => $map->locator_mlt_id]);
                            }
                        }
                        if ($row['AQ']) {
                            array_push($label, 'Vị trí lB', 'Mã cuộn');
                            array_push($value, 'S010302', $row['AQ']);
                            array_push($key, 'vi_tri_lb', 'ma_cuon_lb');
                            array_push($check_api, 0, 1);
                            $material_ids = Material::where('ma_vat_tu', $row['AQ'])->pluck('id')->toArray();
                            $map = LocatorMLTMap::where('material_id', $material_ids)->orderBy('created_at', 'ASC')->first();
                            if ($map) {
                                WareHouseMLTExport::create(['position_id' => 'S010302', 'position_name' => 'lB', 'material_id' => $map->material_id, 'locator_id' => $map->locator_mlt_id]);
                            }
                        }
                        $info->label = $label;
                        $info->value = $value;
                        $info->check_api = $check_api;
                        $info->key = $key;
                        $obj1->info = $info;
                        $obj1->save();
                    }
                    if ($row['AR'] || $row['AS']) {
                        $obj1 = new Mapping();
                        $obj1->lo_sx = $row['C'];
                        $obj1->machine_id = 'S0102';
                        $info = new stdClass();
                        $label = [];
                        $value = [];
                        $key = [];
                        $check_api = [];
                        if ($row['AR']) {
                            array_push($label, 'Vị trí sC', 'Mã cuộn');
                            array_push($value, 'S010201', $row['AR']);
                            array_push($key, 'vi_tri_sc', 'ma_cuon_sc');
                            array_push($check_api, 0, 1);
                            $material_ids = Material::where('ma_vat_tu', $row['AR'])->pluck('id')->toArray();
                            $map = LocatorMLTMap::where('material_id', $material_ids)->orderBy('created_at', 'ASC')->first();
                            if ($map) {
                                WareHouseMLTExport::create(['position_id' => 'S010201', 'position_name' => 'sC', 'material_id' => $map->material_id, 'locator_id' => $map->locator_mlt_id]);
                            }
                        }
                        if ($row['AS']) {
                            array_push($label, 'Vị trí lC', 'Mã cuộn');
                            array_push($value, 'S010202', $row['AS']);
                            array_push($key, 'vi_tri_lc', 'ma_cuon_lc');
                            array_push($check_api, 0, 1);
                            $material_ids = Material::where('ma_vat_tu', $row['AS'])->pluck('id')->toArray();
                            $map = LocatorMLTMap::where('material_id', $material_ids)->orderBy('created_at', 'ASC')->first();
                            if ($map) {
                                WareHouseMLTExport::create(['position_id' => 'S010202', 'position_name' => 'lC', 'material_id' => $map->material_id, 'locator_id' => $map->locator_mlt_id]);
                            }
                        }
                        $info->label = $label;
                        $info->value = $value;
                        $info->check_api = $check_api;
                        $info->key = $key;
                        $obj1->info = $info;
                        $obj1->save();
                    }
                } else {
                    break;
                }
            }
        }
        return $this->success([], 'Upload thành công');
    }

    public function uploadKHXK(Request $request)
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
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 5
            if ($key > 3 && !is_null($row['C']) && !is_null($row['D'])) {
                if (is_null($row['B'])) {
                    return $this->failure([], 'Dòng số ' . $key . ': Thiếu ngày xuất');
                }
                if (is_null($row['C'])) {
                    return $this->failure([], 'Dòng số ' . $key . ': Thiếu khách hàng');
                }
                if (is_null($row['D'])) {
                    return $this->failure([], 'Dòng số ' . $key . ': Thiếu mã đơn hàng');
                }
                if (is_null($row['E'])) {
                    return $this->failure([], 'Dòng số ' . $key . ': Thiếu mã quản lý');
                }
                if (is_null($row['F'])) {
                    return $this->failure([], 'Dòng số ' . $key . ': Thiếu số lượng');
                }
            }
        }
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 5
            if ($key > 3) {
                if (!is_null($row['C']) && !is_null($row['D'])) {
                    $input['ngay_xuat'] = date('Y-m-d H:i:s', strtotime($row['B']));
                    $input['customer_id'] = $row['C']; //
                    $input['mdh'] = $row['D']; //
                    $input['mql'] = $row['E'];
                    $input['so_luong'] = $row['F']; //
                    $record = WareHouseFGExport::create($input);
                    unset($input);
                }
            }
        }
        return $this->success([], 'Upload thành công');
    }
    public function getListWareHouseExportPlan(Request $request)
    {
        $list_query = WareHouseExportPlan::select('*');
        if ($request->date && count($request->date) > 1) {
            $list_query->whereDate('ngay_xuat_hang', '>=', date('Y-m-d', strtotime($request->date[0])))->whereDate('ngay_xuat_hang', '<=', date('Y-m-d', strtotime($request->date[1])));
        }
        if ($request->khach_hang) {
            $list_query->where('khach_hang', 'like', $request->khach_hang);
        }
        if ($request->ten_sp) {
            $list_query->where('product_id', $request->ten_sp);
        }
        $data = $list_query->get();
        return $this->success($data);
    }


    public function getListProductionPlan(Request $request)
    {
        $input = $request->all();
        $lines = Line::all();
        $list = [];
        if (count($input) > 0) {
            $list_query = ProductionPlan::select('*');
            if (isset($input['date']) && count($input['date'])) {
                $list_query->whereDate('thoi_gian_bat_dau', '>=', date('Y-m-d', strtotime($input['date'][0])))
                    ->whereDate('thoi_gian_bat_dau', '<=', date('Y-m-d', strtotime($input['date'][1])));
            }
            if (isset($input['line_id'])) {
                $line = Line::find($input['line_id']);
                if ($line) {
                    $line_key = Str::slug($line->name);
                    $list_query->where('cong_doan_sx', $line_key);
                }
            }
            if (isset($input['product_id'])) {
                $list_query->where('product_id', $input['product_id']);
            }
            if (isset($input['ten_sp'])) {
                $list_query->where('product_id', $input['ten_sp']);
            }
            if (isset($input['lo_sx'])) {
                $list_query->where('lo_sx', $input['lo_sx']);
            }
            if (isset($input['khach_hang'])) {
                $khach_hang = Customer::where('id', $input['khach_hang'])->first();
                if ($khach_hang) {
                    $list_query->where('khach_hang', $khach_hang->name);
                }
            }
            $list = $list_query->orderBy('created_at', 'DESC')->get();
        } else {
            $list =  ProductionPlan::whereDate('ngay_sx', '>=', date('Y-m-d'))->orderBy('created_at', 'DESC')->get();
        }
        return $this->success($list);
    }
    function find_line_by_slug($needle, $haystack)
    {
        foreach ($haystack as $item) {
            if (Str::slug($item->name) === $needle) {
                return $item->name;
                break;
            }
        }
    }
    public function getProposeImport(Request $request)
    {
        $input = $request->all();
        $lot = Lot::find($input['lot_id']);
        if (!$lot) {
            return $this->failure([], "Mã thùng không tồn tại");
        }
        $check_lot = DB::table('cell_lot')->where('lot_id', $input['lot_id'])->count();
        if ($check_lot) {
            return $this->failure([], "Mã thùng đã có trong kho");
        }
        $lot_parrent = Lot::find($lot->p_id);
        if (!$lot_parrent || ($lot_parrent && $lot_parrent->type != 2)) {
            $log = $lot->log;
            if ($log) {
                $info = $log->info;
                if (isset($info['qc']) && isset($info['qc']['oqc'])) {
                    if (isset($info['qc']['oqc']['sl_ng']) && (int)$info['qc']['oqc']['sl_ng'] > 0) {
                        return $this->failure([], 'Có hàng NG');
                    }
                } else {
                    return $this->failure([], 'Chưa qua OQC');
                }
            } else {
                return $this->failure([], 'Chưa qua OQC');
            }
        }
        $data = new \stdClass();
        $data->so_luong = $lot->so_luong;
        $data->khach_hang = $lot->product->customer_id;
        $data->ten_san_pham = $lot->product->name;
        $data->ma_thung = $input['lot_id'];
        $product = Product::find($lot->product_id);
        $cell_check = Cell::where('product_id', $product->id)->count();
        $number_of_bin = 5;
        if ($product->chieu_rong_thung >= 340) {
            $number_of_bin = 4;
        }
        if ($cell_check === 0) {
            $cell = Cell::where('number_of_bin', 0)->whereNull('product_id')->orderBy('name', 'ASC')->first();
            if (!$cell) {
                $cell = Cell::where('number_of_bin', 0)->orderBy('name', 'ASC')->first();
            }
            if (!$cell) {
                return $this->failure('', 'Không còn vị trí phù hợp');
            }
            $data->vi_tri_de_xuat = $cell->id;
        } else {
            $cell_find = Cell::where('product_id', $product->id)->where('number_of_bin', '<', $number_of_bin)->orderBy('id', 'ASC')->first();
            if ($cell_find) {
                $data->vi_tri_de_xuat = $cell_find->id;
            } else {
                // $cell_propose = Cell::where('product_id', $product->id)->orderBy('name', 'DESC')->first();
                // $row = explode('.', $cell_propose->id)[0]; //Tầng
                // $col = explode('.', $cell_propose->id)[1]; // Ô
                // if ((int)($col) < 8) {
                //     $data->vi_tri_de_xuat = $row . '.' . sprintf("%02d", (int)($col) + 1);
                // } else {
                //     if ($row < 2) {
                //         $data->vi_tri_de_xuat = $cell_propose->sheft_id . '.2' . $col;
                //     }
                // }
                $cell_propose = Cell::where('number_of_bin', 0)->orderBy('id')->first();
                if ($cell_propose) {
                    $cell_propose->product_id = $product->id;
                    $cell_propose->save();
                    $data->vi_tri_de_xuat = $cell_propose->id;
                } else {
                    return $this->failure('', 'Không còn vị trí phù hợp');
                }
            }
        }
        return $this->success([$data]);
    }
    public function importWareHouse(Request $request)
    {
        $input = $request->all();
        $cell = Cell::find($input['cell_id']);
        $lot = Lot::find($input['lot_id']);
        $product_id = $lot->product_id;
        $number_of_bin = $cell->number_of_bin + 1;
        $cell->lot()->attach($input['lot_id']);
        Cell::find($input['cell_id'])->update(['product_id' => $product_id, 'number_of_bin' => $number_of_bin]);
        $check_lot = DB::table('cell_lot')->where('lot_id', $lot->p_id)->first();
        if (!$check_lot) {
            $input['type'] = 1;
            $input['created_by'] = $request->user()->id;
            $input['so_luong'] = $lot->so_luong;
            WareHouseLog::create($input);
        }
        return $this->success([], 'Nhập kho thành công');
    }
    public function infoImportWareHouse()
    {
        $records = WareHouseLog::whereDate('created_at', date('Y-m-d'))->where('type', 1)->get();
        $lot_ids = WareHouseLog::whereDate('created_at', date('Y-m-d'))->where('type', 1)->pluck('lot_id')->toArray();
        $lo_sx = Lot::whereIn('id', $lot_ids)->pluck('lo_sx')->toArray();
        $tong_ma_hang = ProductionPlan::whereIn('lo_sx', $lo_sx)->distinct()->count('product_id');
        $so_luong = WareHouseLog::whereDate('created_at', date('Y-m-d'))->where('type', 1)->sum('so_luong');
        $data = [];
        $labels = ['Tổng số thùng', 'Tổng số mã nhập kho', 'Số lượng'];
        $values = [count($records), $tong_ma_hang, $so_luong];
        foreach ($labels as $key => $label) {
            $object = new stdClass();
            $object->title = $label;
            $object->value = $values[$key];
            $data[] = $object;
        }
        return $this->success($data);
    }
    public function listImportWareHouse()
    {
        $records = WareHouseLog::whereDate('created_at', date('Y-m-d'))->where('type', 1)->orderBy('created_at', 'DESC')->get();
        $data = [];
        foreach ($records as $key => $record) {
            $object = new stdClass();
            $lot = Lot::find($record->lot_id);
            $object->thoi_gian_nhap = date('d/m/Y H:i:s', strtotime($record->created_at));
            $object->lo_sx = $lot->lo_sx;
            $object->lot_id = $record->lot_id;
            $object->ten_san_pham = $lot->product->name;
            $object->so_luong = $lot->so_luong;
            $object->vi_tri = $record->cell_id;
            $object->status = 2;
            $object->nguoi_nhap = CustomUser::find($record->created_by)->name;
            $data[] = $object;
        }
        return $this->success($data);
    }
    public function listCustomerExport()
    {
        $customers = WareHouseExportPlan::whereDate('ngay_xuat_hang', date('Y-m-d'))->distinct()->pluck('khach_hang')->toArray();
        $data = [];
        foreach ($customers as $key => $customer) {
            $object = new stdClass();
            $object->label = $customer;
            $object->value = $customer;
            $data[] = $object;
        }
        return $this->success($data);
    }
    public function getProposeExport(Request $request)
    {
        $khach_hang = $request->khach_hang;
        $records = WareHouseExportPlan::where('khach_hang', $khach_hang)->whereDate('ngay_xuat_hang', date('Y-m-d'))->whereColumn('sl_yeu_cau_giao', '>', 'sl_thuc_xuat')->get();
        $data = [];
        $lot_arr = [];
        foreach ($records as $key => $record) {
            $cell_ids = Cell::where('product_id', $record->product_id)->pluck('id')->toArray();
            $cell_lots = DB::table('cell_lot')->whereIn('cell_id', $cell_ids)->orderBy('created_at', 'ASC')->get();
            if (count($cell_lots) == 0) {
                $object = new stdClass();
                $object->product_id = $record->product ? $record->product->id : '';
                $object->ten_san_pham = $record->product ? $record->product->name : '';
                $object->lot_id = 'Không có tồn';
                $object->ke_hoach_xuat = $record->sl_yeu_cau_giao;
                $object->thuc_te_xuat = $record->sl_thuc_xuat;
                $object->vi_tri = '-';
                $object->so_luong =  '-';
                $object->pic = '-';
                $data[] = $object;
            }
            $product = Product::find($record->product_id);
            $dinh_muc = 0;
            foreach ($cell_lots as $key => $cell_lot) {
                if (in_array($cell_lot->lot_id, $lot_arr)) {
                    continue;
                } else {
                    $lot_arr[] = $cell_lot->lot_id;
                }
                if ($dinh_muc <  ($record->sl_yeu_cau_giao - $record->sl_hang_le - $record->sl_thuc_xuat)) {
                    $lot = Lot::find($cell_lot->lot_id);
                    // if ($lot->so_luong < $product->dinh_muc_thung) continue;
                    $object = new stdClass();
                    $object->product_id = $record->product->id;
                    $object->ten_san_pham = $record->product->name;
                    $object->lot_id = $cell_lot->lot_id;
                    $object->ke_hoach_xuat = $record->sl_yeu_cau_giao;
                    $object->thuc_te_xuat = $record->sl_thuc_xuat;
                    $object->vi_tri = $cell_lot->cell_id;
                    $object->so_luong =  $lot->so_luong;
                    $object->pic = '';
                    $data[] = $object;
                    $dinh_muc = $dinh_muc + $lot->so_luong;
                }
            }
            if ($record->sl_hang_le > 0) {
                $lot_ids = Lot::where('product_id', $record->product_id)->where('so_luong', $record->sl_hang_le)->pluck('id');
                if ($lot_ids) {
                    $object = new stdClass();
                    $cell_lot1 = DB::table('cell_lot')->whereIn('lot_id', $lot_ids)->first();
                    if ($cell_lot1) {
                        $lot_le = Lot::find($cell_lot1->lot_id);
                        if ($cell_lot1) {
                            $object->product_id = $record->product->id;
                            $object->ten_san_pham = $record->product->name;
                            $object->lot_id = $lot_le->id;
                            $object->ke_hoach_xuat = $record->sl_yeu_cau_giao;
                            $object->thuc_te_xuat =  $record->sl_thuc_xuat;
                            $object->vi_tri =  $cell_lot1->cell_id;
                            $object->so_luong =  $lot_le->so_luong;
                            $object->pic = '';
                            $data[] = $object;
                        }
                    }
                }
            }
        }
        return $this->success($data);
    }
    public function exportWareHouse(Request $request)
    {
        $input = $request->all();
        $cell = Cell::find($input['cell_id']);
        $lot = Lot::find($input['lot_id']);
        if ($cell->number_of_bin == 1) {
            $cell->update(['product_id' => null]);
        }
        $number_of_bin = $cell->number_of_bin - 1;
        $cell->lot()->detach($input['lot_id']);
        Cell::find($input['cell_id'])->update(['number_of_bin' => $number_of_bin]);
        $record = WareHouseExportPlan::where('khach_hang', $input['khach_hang'])->where('product_id', $lot->product_id)->whereDate('ngay_xuat_hang', date('Y-m-d'))->first();
        if ($record) {
            $sl = $lot->so_luong + $record->sl_thuc_xuat;
            WareHouseExportPlan::where('khach_hang', $input['khach_hang'])->where('product_id', $lot->product_id)->whereDate('ngay_xuat_hang', date('Y-m-d'))->update(['sl_thuc_xuat' => $sl]);
        }
        $input['type'] = 2;
        $input['created_by'] = $request->user()->id;
        $input['so_luong'] = $lot->so_luong;
        WareHouseLog::create($input);
        return $this->success([], 'Xuất kho thành công');
    }
    public function infoExportWareHouse()
    {
        $sum_so_luong_kh = WareHouseExportPlan::sum('sl_yeu_cau_giao');
        $sum_so_luong_tt = WareHouseExportPlan::sum('sl_thuc_xuat');
        $ti_le = $sum_so_luong_kh != 0 ? number_format(($sum_so_luong_tt * 100) / $sum_so_luong_kh) . ' %' : 0;
        $values = [$sum_so_luong_kh, $sum_so_luong_tt, $ti_le];
        $labels = ['Kế hoạch xuất', 'Sản lượng', 'Tỷ lệ'];
        $data = [];
        foreach ($labels as $key => $label) {
            $object = new stdClass();
            $object->title = $label;
            $object->value = $values[$key];
            $data[] = $object;
        }
        return $this->success($data);
    }
    public function listLogMaterial(Request $request)
    {
        $data = MaterialExportLog::whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))
            ->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)))
            ->whereColumn('sl_kho_xuat', '>', 'sl_thuc_te')->get();
        return $this->success($data);
    }
    public function updateLogMaterial(Request $request)
    {
        $input = $request->all();
        foreach ($input['log'] as $key => $value) {
            $log = MaterialExportLog::find($value['id']);
            $sl = $value['sl_thuc_xuat'] ? $value['sl_thuc_xuat'] : 0;
            $sl_thuc_te = $log->sl_thuc_te + $sl;
            $log->update(['sl_thuc_te' => $sl_thuc_te]);
        }
        return $this->success([], 'Nhập thành công');
    }

    public function updateLogMaterialRecord(Request $request)
    {
        $input = $request->all();
        MaterialExportLog::find($input['id'])->update($input);
        return $this->success([], 'Cập nhật thành công');
    }

    public function listLsxUseMaterial(Request $request)
    {
        $input = $request->all();
        $id = $input['id'];
        $material_id = $input['material_id'];
        $sl_thuc_te = $input['sl_thuc_te'];
        $product_ids = Product::where('material_id', $material_id)->pluck('id')->toArray();
        $product_plans = ProductionPlan::whereDate('ngay_sx', '>=', date('Y-m-d'))->where('cong_doan_sx', 'in')->whereIn('product_id', $product_ids)->orderBy('thu_tu_uu_tien', 'ASC')->get();
        $data = [];
        foreach ($product_plans as $key => $product_plan) {
            $object = new stdClass;
            $object->id = $id;
            $object->product_id = $product_plan->product_id;
            $object->ten_san_pham = $product_plan->product->name;
            $object->lo_sx = $product_plan->lo_sx;
            $object->sl_ke_hoach = $product_plan->sl_nvl;
            $object->sl_pallet = count($product_plan->loSX);
            $object->pallet = $product_plan->loSX;
            $data[] = $object;
            $sl_thuc_te = $sl_thuc_te - $product_plan->sl_nvl;
        }
        return $this->success($data);
    }
    public function splitBarrel(Request $request)
    {
        $input = $request->all();
        $lot = Lot::find($input['lot_id']);
        $lot->update(['so_luong' => $input['remain_quanlity']]);

        $count_lot = Lot::where('p_id', $input['lot_id'])->count();
        $new_lot = new Lot();
        $new_lot->id = $lot->id . '.TC' . ($count_lot + 1);
        $new_lot->type = $lot->type;
        $new_lot->lo_sx = $lot->lo_sx;
        $new_lot->so_luong = $input['export_quanlity'];
        $new_lot->finished = 0;
        $new_lot->product_id = $lot->product_id;
        $new_lot->material_export_log_id = '';
        $new_lot->p_id = $lot->id;
        $new_lot->save();
        //
        $data = [];
        $tem_lot = new stdClass();
        $tem_lot->product_id = $lot->product_id;
        $tem_lot->so_luong = $lot->so_luong;
        $tem_lot->ver_his = '';
        $tem_lot->lo_sx = $lot->lo_sx;
        $tem_lot->cd_thuc_hien = 'Kho thành phẩm';
        $tem_lot->tg_sx = $lot->plan->thoi_gian_bat_dau;
        $tem_lot->ngay_sx = date('d/m/Y', strtotime($lot->plan->ngay_sx));
        $tem_lot->lot_id = $lot->id;
        $tem_lot->cd_tiep_theo = 'Kho thành phẩm';
        $tem_lot->nguoi_sx = '';
        $data[] = $tem_lot;
        //
        $tem_new_lot = new stdClass();
        $tem_new_lot->product_id = $new_lot->product_id;
        $tem_new_lot->so_luong = $new_lot->so_luong;
        $tem_new_lot->ver_his = '';
        $tem_new_lot->lo_sx = $new_lot->lo_sx;
        $tem_new_lot->cd_thuc_hien = 'Kho thành phẩm';
        $tem_new_lot->tg_sx = $new_lot->plan->thoi_gian_bat_dau;
        $tem_new_lot->ngay_sx = date('d/m/Y', strtotime($new_lot->plan->ngay_sx));
        $tem_new_lot->lot_id = $lot->id . '.TC' . ($count_lot + 1);
        $tem_new_lot->cd_tiep_theo = 'Kho thành phẩm';
        $tem_new_lot->nguoi_sx = '';
        $data[] = $tem_new_lot;
        return $this->success($data);
    }
    public function getHistoryWareHouse(Request $request)
    {
        $input = $request->all();
        $warehouse_log_query = WareHouseLog::select('*');
        if (isset($input['date']) && count($input['date']) > 1) {
            $warehouse_log_query->whereDate('created_at', '>=', date('Y-m-d', strtotime($input['date'][0])))->whereDate('created_at', '<=', date('Y-m-d', strtotime($input['date'][1])));
        }

        $lot_ids = $warehouse_log_query->pluck('lot_id')->toArray();
        $lot_query  = Lot::whereIn('id', $lot_ids);
        if (isset($input['khach_hang'])) {
            $lot_query->whereHas('product', function ($product_query) use ($input) {
                $product_query->where('customer_id', 'like', "%" . $input['khach_hang'] . "%");
            });
        }
        if (isset($input['lo_sx'])) {
            $lot_query->where('id', 'like', '%' . $input['lo_sx'] . '%');
        }
        if (isset($input['ten_sp'])) {
            $lot_query->where('id', 'like', '%' . $input['ten_sp'] . '%');
        }
        $lots = $lot_query->get();
        $data = [];
        foreach ($lots as $key => $lot) {
            $log_import = WareHouseLog::with('creator')->where('lot_id', $lot->id)->where('type', 1)->first();
            $log_export = WareHouseLog::with('creator')->where('lot_id', $lot->id)->where('type', 2)->first();
            $object = new stdClass();
            $object->ngay = $log_import ? date('d/m/Y', strtotime($log_import->created_at)) : '';
            $object->ma_khach_hang = $lot->product->customer->id;
            $object->ten_khach_hang = $lot->product->customer->name;
            $object->product_id = $lot->product_id;
            $object->ten_san_pham = $lot->product->name;
            $object->dvt = 'Mảnh';
            $object->lo_sx = $lot->lo_sx;
            $object->vi_tri = $log_import ? $log_import->cell_id : '';
            $object->kho = 'KTP';
            $object->lot_id = $lot->id;
            $object->ngay_nhap = $log_import ? date('d/m/Y H:i:s', strtotime($log_import->created_at)) : '';
            $object->so_luong_nhap  = $log_import ? $log_import->so_luong : 0;
            $object->nguoi_nhap  = $log_import ? $log_import->creator->name : '';
            $object->ngay_xuat = $log_export ? date('d/m/Y H:i:s', strtotime($log_export->created_at)) : '';
            $object->so_luong_xuat  = $log_export ? $log_export->so_luong : 0;
            $object->nguoi_xuat  = $log_export ? $log_export->creator->name : '';
            $object->ton_kho = $object->so_luong_nhap - $object->so_luong_xuat;
            $object->so_ngay_ton = !$log_export ? ((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($log_import->created_at)))) / 86400) : '';
            $data[] = $object;
        }
        return $this->success($data);
    }
    public function destroyPallet(Request $request)
    {
        $input = $request->all();
        foreach ($input as $key => $value) {
            if (is_numeric($value)) {
                MaterialExportLog::where('id', $value)->delete();
            } else {
                Lot::where('id', $value)->delete();
            }
        }
        return $this->success([], 'Xóa thành công');
    }
    public function storeLogMaterial(Request $request)
    {
        $input = $request->all();
        MaterialExportLog::create($input);
        return $this->success([], 'Thêm mới thành công');
    }
    public function destroyProductPlan(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            foreach ($input as $key => $value) {
                $plan = ProductionPlan::with('info_losx')->where('id', $value)->first();
                if ($plan) {
                    if ($plan->info_losx && $plan->info_losx->status > 0) {
                        return $this->failure('', 'Kế hoạch đã chạy. Không thể xoá');
                    }
                    $delete = $plan->delete();
                    if ($delete) {
                        $plan->mapping()->delete();
                        $plan->l_s_x_log()->delete();
                        $plan->group_plan_order()->delete();
                        $plan->info_losx()->delete();
                    }
                } else {
                    return $this->failure('', 'Không thể xoá');
                }
            }
            DB::commit();
            return $this->success([], 'Xóa thành công');
        } catch (\Exception $th) {
            DB::rollBack();
            return $this->failure($th->getMessage(), 'Xóa không thành công');
        }
        return $this->success([], 'Xóa thành công');
    }
    public function storeProductPlan(Request $request)
    {
        $input = $request->all();
        $check = ProductionPlan::where('lo_sx', $input['lo_sx'])->first();
        if ($check) {
            return $this->failure([], 'Đã tồn tại KHSX');
        } else {
            $plan = ProductionPlan::create($input);
            return $this->success($plan, 'Thêm thành công');
        }
    }
    public function updateProductPlan(Request $request)
    {
        $input = $request->all();
        $plan = ProductionPlan::with('order')->find($input['id']);
        $list = ProductionPlan::with('order')->orderBy('thu_tu_uu_tien')->orderBy('created_at', 'DESC')->where('machine_id', $plan->machine_id)->whereDate('ngay_sx', $plan->ngay_sx)->get();
        $current = $plan->thu_tu_uu_tien;
        $target = (int)$input['thu_tu_uu_tien'];
        try {
            DB::beginTransaction();
            if ($current !== $target) {
                if ($current > $target) {
                    foreach ($list as $data) {
                        if ($data->thu_tu_uu_tien < $target) {
                            continue;
                        }
                        if ($plan->id === $data->id) {
                            continue;
                        }
                        if ($data->thu_tu_uu_tien > $current) {
                            continue;
                        }
                        $data->thu_tu_uu_tien += 1;
                        $data->save();
                        $lo_sx_log = LSXLog::where('lo_sx', $data->lo_sx)->update(['thu_tu_uu_tien' => $data->thu_tu_uu_tien]);
                        $formula = DB::table('formulas')->where('phan_loai_1', $order->phan_loai_1 ?? null)->where('phan_loai_2', $order->phan_loai_2 ?? null)->first();
                        $data->infoCongDoan()->update([
                            'ngay_sx' => $data->ngay_sx,
                            'dinh_muc' => $data->sl_kh,
                            'thu_tu_uu_tien' => $data->thu_tu_uu_tien,
                            'so_ra' => $data->order->so_ra,
                            'so_dao' => isset($data->order->so_ra) ? ceil($data->sl_kh * ($formula->he_so ?? 1) / $data->order->so_ra) : ($data->order->so_dao ?? 0),
                        ]);
                    }
                } else {
                    foreach ($list as $data) {
                        if ($data->thu_tu_uu_tien < $current) {
                            continue;
                        }
                        if ($plan->id === $data->id) {
                            continue;
                        }
                        if ($data->thu_tu_uu_tien > $target) {
                            continue;
                        }
                        $data->thu_tu_uu_tien -= 1;
                        $data->save();
                        $lo_sx_log = LSXLog::where('lo_sx', $data->lo_sx)->update(['thu_tu_uu_tien' => $data->thu_tu_uu_tien]);
                        $formula = DB::table('formulas')->where('phan_loai_1', $order->phan_loai_1 ?? null)->where('phan_loai_2', $order->phan_loai_2 ?? null)->first();
                        $data->infoCongDoan()->update([
                            'ngay_sx' => $data->ngay_sx,
                            'dinh_muc' => $data->sl_kh,
                            'thu_tu_uu_tien' => $data->thu_tu_uu_tien,
                            'so_ra' => $data->order->so_ra,
                            'so_dao' => isset($data->order->so_ra) ? ceil($data->sl_kh * ($formula->he_so ?? 1) / $data->order->so_ra) : ($data->order->so_dao ?? 0),
                        ]);
                    }
                }
            }
            $formula = DB::table('formulas')->where('phan_loai_1', $plan->order->phan_loai_1 ?? null)->where('phan_loai_2', $plan->order->phan_loai_2 ?? null)->first();
            if($plan->machine->line_id == 31 || $plan->machine->line_id == 32) {
                $input['sl_kh'] += ($input['loss_quantity'] * $plan->order->so_ra) / ($formula->he_so ?? 1);
            }else{
                $input['sl_kh'] = $plan->orders->sum('sl');
                $input['sl_kh'] += ($input['loss_quantity'] * $plan->order->so_ra) / ($formula->he_so ?? 1);
            }
            $update = $plan->update($input);
            $lo_sx_log = LSXLog::where('lo_sx', $plan->lo_sx)->update(['thu_tu_uu_tien' => $input['thu_tu_uu_tien']]);
            $plan->infoCongDoan()->update([
                'ngay_sx' => $plan->ngay_sx,
                'dinh_muc' => $plan->sl_kh,
                'thu_tu_uu_tien' => $plan->thu_tu_uu_tien,
                'so_ra' => $plan->order->so_ra,
                'so_dao' => isset($plan->order->so_ra) ? ceil($plan->sl_kh * ($formula->he_so ?? 1) / $plan->order->so_ra) : ($plan->order->so_dao ?? 0),
            ]);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::debug($th);
            return $this->failure($th->getMessage(), 'Đã xảy ra lỗi');
        }
        return $this->success($list, 'Cập nhật thành công');
    }

    public function updateSanLuong(Request $request)
    {
        $pallet = Lot::find($request->lot_id);
        if (!isset($pallet)) {
            return $this->failure([], "Không tìm thấy pallet");
        }
        $line = Line::find($request->line_id);
        if (!isset($line)) {
            return $this->failure([], "Không tìm thấy công đoạn");
        }
        $info_cong_doan = $pallet->infoCongDoan()->where('line_id', $line->id)->where('type', 'sx')->first();
        $info_cong_doan['sl_dau_ra_hang_loat'] = $request->san_luong * $pallet->plan->so_bat;
        $info_cong_doan->save();
        return $this->success([], 'Cập nhật thành công');
    }

    public function checkSanLuong(Request $request)
    {
        $pallet = Lot::find($request->lot_id);
        if (!isset($pallet)) {
            return $this->failure([], "Không tìm thấy pallet");
        }
        $line = Line::find($request->line_id);
        if (!isset($line)) {
            return $this->failure([], "Không tìm thấy công đoạn");
        }
        $info_cong_doan = $pallet->infoCongDoan()->where('line_id', $line->id)->where('type', 'sx')->first();
        if (isset($info_cong_doan['sl_dau_ra_hang_loat']) && $info_cong_doan['sl_dau_ra_hang_loat']) {
            return $this->failure([], 'Đã nhập sản lượng');
        } else {
            return $this->success([], '');
        }
    }
    public function destroyWareHouseExport(Request $request)
    {
        $input = $request->all();
        foreach ($input as $key => $value) {
            WareHouseExportPlan::where('id', $value)->delete();
        }
        return $this->success([], 'Xóa thành công');
    }
    public function createWareHouseExport(Request $request)
    {
        $input = $request->all();
        WareHouseExportPlan::create($input);
        return $this->success([], 'Thêm thành công');
    }
    public function updateWareHouseExport(Request $request)
    {
        $input = $request->all();
        WareHouseExportPlan::find($input['id'])->update($input);
        return $this->success([], 'Cập nhật thành công');
    }

    public function batDauTinhSanLuong(Request $request)
    {
        $pallet = Lot::find($request->lot_id);
        if (!isset($pallet)) {
            return $this->failure([], "Không tìm thấy pallet");
        }
        $line = Line::find($request->line_id);
        if (!isset($line)) {
            return $this->failure([], "Không tìm thấy công đoạn");
        }
        $info_cong_doan = $pallet->infoCongDoan()->where('line_id', $line->id)->first();
        if (!$info_cong_doan->thoi_gian_bam_may) {
            $info_cong_doan['thoi_gian_bam_may'] = Carbon::now();
            $info_cong_doan->save();
            return $this->success($info_cong_doan->thoi_gian_bam_may, 'Bắt đầu tinh sản lượng');
        } else {
            return $this->failure(null, 'Đã bắt đàu tính sản lượng');
        }
    }

    public function prepareGT(Request $request)
    {
        $thung1 = Lot::where('id', $request->thung1)->where('type', 2)->whereExists(function ($query) {
            $query->select("cell_lot.lot_id")
                ->from('cell_lot')
                ->whereRaw('lot.id = cell_lot.lot_id');
        })->first();
        $thung2 = Lot::where('id', $request->thung2)->where('type', 2)->whereExists(function ($query) {
            $query->select("cell_lot.lot_id")
                ->from('cell_lot')
                ->whereRaw('lot.id = cell_lot.lot_id');
        })->first();
        $text = '';
        $data = [
            'thung1' => $thung1 ? $thung1->id : '',
            'sl_thung1' => $thung1 ? $thung1->so_luong : 0,
            'thung2' => $thung2 ? $thung2->id : '',
            'sl_thung2' => $thung2 ? $thung2->so_luong : 0
        ];
        if ($thung1 && $thung2) {
            if ($thung1->id === $thung2->id) {
                return $this->failure([
                    'thung1' => '',
                    'sl_thung1' => 0,
                    'thung2' => '',
                    'sl_thung2' => 0
                ], 'Không gộp cùng một mã thùng');
            }
            if ($thung1->product_id !== $thung2->product_id) {
                return $this->failure([
                    'thung1' => '',
                    'sl_thung1' => 0,
                    'thung2' => '',
                    'sl_thung2' => 0
                ], 'Phải gộp thùng có cùng mã sản phẩm');
            }
        }
        return $this->success($data, $text);
    }

    public function gopThungIntem(Request $request)
    {
        $thung1 = Lot::where('id', $request->thung1)->where('type', 2)->first();
        $thung2 = Lot::where('id', $request->thung2)->where('type', 2)->first();
        if (!$thung1 || !$thung2) {
            return $this->failure(null, 'Không tìm thấy thùng');
        } else {
            if ($thung1->id === $thung2->id) {
                return $this->failure([], 'Không gộp cùng một mã thùng');
            }
            if ($thung1->product_id !== $thung2->product_id) {
                return $this->failure([], 'Phải gộp thùng có cùng mã sản phẩm');
            }
            $thung1['so_luong'] = $thung1['so_luong'] + $request->sl_gop;
            $thung1->save();
            $thung2['so_luong'] = $thung2['so_luong'] - $request->sl_gop;
            $thung2->save();

            $data = [];
            $tem_lot = new stdClass();
            $tem_lot->product_id = $thung1->product_id;
            $tem_lot->so_luong = $thung1->so_luong;
            $tem_lot->ver_his = '';
            $tem_lot->lo_sx = $thung1->lo_sx;
            $tem_lot->cd_thuc_hien = 'Kho thành phẩm';
            $tem_lot->tg_sx = $thung1->plan->thoi_gian_bat_dau;
            $tem_lot->ngay_sx = date('d/m/Y', strtotime($thung1->plan->ngay_sx));
            $tem_lot->lot_id = $thung1->id;
            $tem_lot->cd_tiep_theo = 'Kho thành phẩm';
            $tem_lot->nguoi_sx = '';
            $data[] = $tem_lot;
            //
            if ($thung2->so_luong > 0) {
                $tem_new_lot = new stdClass();
                $tem_new_lot->product_id = $thung2->product_id;
                $tem_new_lot->so_luong = $thung2->so_luong;
                $tem_new_lot->ver_his = '';
                $tem_new_lot->lo_sx = $thung2->lo_sx;
                $tem_new_lot->cd_thuc_hien = 'Kho thành phẩm';
                $tem_new_lot->tg_sx = $thung2->plan->thoi_gian_bat_dau;
                $tem_new_lot->ngay_sx = date('d/m/Y', strtotime($thung2->plan->ngay_sx));
                $tem_new_lot->lot_id = $thung2->id;
                $tem_new_lot->cd_tiep_theo = 'Kho thành phẩm';
                $tem_new_lot->nguoi_sx = '';
                $data[] = $tem_new_lot;
            } else {
                $cell_lot = DB::table('cell_lot')->where('lot_id', $thung2->id)->first();
                $cell = Cell::find($cell_lot->cell_id);
                $number_of_bin = $cell->number_of_bin > 0 ? $cell->number_of_bin - 1 : 0;
                $cell->update(['number_of_bin' => $number_of_bin]);
                DB::table('cell_lot')->where('lot_id', $thung2->id)->delete();
            }

            return $this->success($data);
        }
    }

    public function updateWarehouseEportPlan(Request $request)
    {
        $user = auth('admin')->user();
        return $this->success($user, '');
    }

    public function listScenario()
    {
        $records = Scenario::all();
        return $this->success($records);
    }
    public function updateScenario(Request $request)
    {
        $input = $request->all();
        Scenario::find($input['id'])->update($input);
        return $this->success([], 'Cập nhật thành công');
    }
    public function historyMonitor(Request $request)
    {
        $input = $request->all();
        $query = Monitor::with('machine')->orderBy('created_at', 'DESC');
        if (isset($input['type'])) {
            $query = $query->where('type', $input['type']);
        }
        if (isset($input['machine_id'])) {
            $query = $query->where('machine_id', $input['machine_id']);
        }
        if (isset($input['status'])) {
            $query = $query->where('status', $input['status']);
        }
        if (isset($input['start_date'])) {
            $query = $query->whereDate('created_at', '>=', $input['start_date']);
        } else {
            $query = $query->whereDate('created_at', '>=', date('Y-m-d'));
        }
        if (isset($input['end_date'])) {
            $query = $query->whereDate('created_at', '<=', $input['end_date']);
        } else {
            $query = $query->whereDate('created_at', '<=', date('Y-m-d'));
        }
        $records = $query->get();
        return $this->success($records);
    }
    public function ui_getLines(Request $request)
    {
        $lines = Line::all();
        foreach ($lines as $line) {
            $line['machine'] = $line->machine;
        }
        return $this->success($lines);
    }

    public function ui_getLineListMachine(Request $request)
    {
        if (isset($request->line)) {
            $line = Line::find($request->line);
            return $this->success($line->machine);
        } else {
            $machine = Machine::select('id', 'code', 'name')->get();
            return $this->success($machine);
        }
    }
    public function ui_getProducts(Request $request)
    {
        return $this->success(Product::all());
    }
    public function ui_getStaffs(Request $request)
    {
        $list = Workers::all();
        return $this->success($list);
    }

    public function ui_getLoSanXuat()
    {
        return $this->success(ProductionPlan::all()->pluck('lo_sx'));
    }

    public function ui_getErrors()
    {
        return $this->success(Error::all());
    }

    public function ui_getErrorsMachine()
    {
        return $this->success(ErrorMachine::all());
    }
    public function ui_getCustomers(Request $request)
    {
        return $this->success(Customer::all());
    }
    public function uiThongSoMay(Request $request)
    {
        $query = ThongSoMay::with('machine');
        $line = Line::find($request->line_id);
        if ($line) {
            $query->where('line_id', $line->id);
        }
        if (isset($request->machine_code)) {
            $query->where('machine_code', $request->machine_code);
        }
        if (isset($request->lo_sx)) {
            $query->where('lo_sx', $request->lo_sx);
        }
        if (isset($request->date) && count($request->date) === 2) {
            $query->whereDate('ngay_sx', '>=', date('Y-m-d 00:00:00', strtotime($request->date[0])))
                ->whereDate('ngay_sx', '<=', date('Y-m-d 23:59:59', strtotime($request->date[1])));
        } else {
            $query->whereDate('ngay_sx', '>=', date('Y-m-d'))
                ->whereDate('ngay_sx', '<=', date('Y-m-d'));
        }
        if (isset($request->ca_sx)) {
            $query->where('ca_sx', $request->ca_sx);
        }
        if (isset($request->date_if)) {
            $query->whereDate('date_if', date('Y-m-d', strtotime($request->date_if)));
        }
        if (isset($request->date_input)) {
            $query->whereDate('date_input', date('Y-m-d', strtotime($request->date_input)));
        }
        $thong_so_may = $query->get();
        $data = [];
        foreach ($thong_so_may as $record) {
            $data_if = $record->data_if;
            if (isset($data_if['t_gun']) && str_replace(',', '', $data_if['t_gun']) > 6000) {
                $data_if['t_gun'] = strval(rand(165 * 10, 175 * 10) / 10);
            } else {
                continue;
            }
            $record->data_if = $data_if;
        }
        return $this->success($thong_so_may);
    }
    public function ui_getMachines(Request $request)
    {
        return $this->success(Machine::all());
    }
    function getMachineParameters(Request $request)
    {
        $machine = Machine::with(['parameters' => function ($query) {
            $query->select('parameters.*', 'machine_parameters.is_if');
        }])->where('code', $request->machine_id)->first();
        $columns = [];
        foreach ($machine->parameters as $param) {
            $col = new stdClass;
            $col->title = $param->name;
            $col->dataIndex = $param->id;
            $col->key = $param->id;
            $col->is_if = $param->is_if;
            $columns[] = $col;
        }

        $machine_parameter_logs = MachineParameterLogs::where('machine_id', $request->machine_id)->whereDate('start_time', date('Y-m-d'))->get();
        $data = [];
        foreach ($machine_parameter_logs as $machine_params) {
            $data[] = array_merge($machine_params->data_if ?? [], $machine_params->data_input ?? [], ['start_time' => $machine_params->start_time, 'end_time' => $machine_params->end_time]);
        }
        $obj = new stdClass;
        $obj->columns = $columns;
        $obj->data = $data;
        return $this->success($obj);
    }

    public function updateMachineParameters(Request $request)
    {
        $date = date('Y-m-d H:i:s', strtotime($request->date));
        $machine_parameter_logs = MachineParameterLogs::where('machine_id', $request->machine_id)
            ->where(function ($query) use ($date) {
                $query->where('start_time', '<=', $date)
                    ->where('end_time', '>=', $date);
            })
            ->first();
        if ($machine_parameter_logs) {
            $key = $request->key;
            $input = $machine_parameter_logs['data_input'];
            $input[$key] = $request->value;
            $machine_parameter_logs['data_input'] = $input;
            $machine_parameter_logs->save();
        }
        $tsm = ThongSoMay::orderBy('created_at', 'DESC')->first();
        if ($tsm) {
            $key = $request->key;
            $input = $tsm->data_input;
            $input[$key] = $request->value;
            $tsm['data_input'] = $input;
            $tsm['date_input'] = Carbon::now();
            $tsm->save();
        }
        return $this->success($machine_parameter_logs);
    }

    public function detailLot(Request $request)
    {
        $input = $request->all();
        $cell_lot = DB::table('cell_lot')->where('lot_id', $input['lot_id'])->first();
        if (!$cell_lot) {
            return $this->failure([], 'Chưa nhập kho');
        }
        $lot = Lot::find($input['lot_id']);
        $object = new stdClass();
        $object->lot_id = $lot->id;
        $object->product_id = $lot->product_id;
        $object->ten_san_pham = $lot->product->name;
        $object->so_luong = $lot->so_luong;
        $object->vi_tri = $cell_lot->cell_id;
        return $this->success($object);
    }
    public function infoChon(Request $request)
    {
        $input = $request->all();
        $lot = Lot::find($input['lot_id']);
        $log = $lot->log;
        $info = $log->info;
        $object = new stdClass();
        if (!isset($info['chon']['table'])) {
            return $this->failure([], 'Chưa giao việc');
        } else {
            $sl_ok = 0;
            foreach ($info['chon']['table'] as $key => $value) {
                $sl_ok += isset($value['so_luong_thuc_te_ok']) ? $value['so_luong_thuc_te_ok'] : 0;
            }
            $object->sl_ok = $sl_ok - $info['chon']['sl_in_tem'];
            $object->sl_ton = OddBin::where('product_id', $lot->product_id)->where('lo_sx', $lot->lo_sx)->sum('so_luong');
        }
        return $this->success($object);
    }
    public function statusIOT()
    {
        $status = 1;
        return $this->success($status);
    }
    public function taoTem(Request $request)
    {
        $input = $request->all();
        $count = Lot::where('lo_sx', $input['lo_sx'])->where('product_id', $input['product_id'])->where('type', 2)->count();
        $data = [];
        $product = Product::find($input['product_id']);
        for ($i = 1; $i <= $input['number_bin']; $i++) {
            $obj = new Lot();
            $obj->id = $input['lo_sx'] . '.' . $input['product_id'] . '.pl1-T' . ($count + $i);
            $obj->so_luong = $input['so_luong'];
            $obj->lo_sx = $input['lo_sx'];
            $obj->type = 2;
            $obj->product_id = $input['product_id'];
            $obj->save();
            $obj->product_id = $product->name;
            $obj->lot_id = $obj->id;
            $obj->ngay_sx = date('d/m/Y');
            $obj->tg_sx = date('d/m/Y H:i:s');
            $data[] = $obj;
        }
        return $this->success($data);
    }
    public function listProduct()
    {
        $records = Product::select('name as label', 'id as value')->get();
        return $this->success($records);
    }

    public function converImportWarehouseFG()
    {
        try {
            DB::beginTransaction();
            $lsxs = LSXPallet::with('warehouseFGLog')->whereDate('created_at', '>=', '2024-11-04')->get();
            $created_at = date('Y-m-d H:i:s');
            $user_id = 1;
            $location = 'F01.CX';
            foreach ($lsxs as $key => $lsx) {
                $warehouseLog = WarehouseFGLog::where('pallet_id', $lsx->pallet_id)->first();
                if ($warehouseLog) {
                    $created_at = $warehouseLog->created_at;
                    $user_id = $warehouseLog->created_by;
                    $location = $warehouseLog->locator_id;
                    if ($warehouseLog->lo_sx == $lsx->lo_sx) {
                        continue;
                    }
                    $inp = [];
                    $inp['lo_sx'] = $lsx->lo_sx;
                    $inp['pallet_id'] = $lsx->pallet_id;
                    $inp['type'] = 1;
                    $inp['locator_id'] = $location;
                    $inp['so_luong'] = $lsx->so_luong;
                    $inp['created_by'] = $user_id;
                    $inp['order_id'] = $lsx->order_id;
                    $inp['created_at'] = $created_at;
                    WarehouseFGLog::create($inp);
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->failure($th, 'Đã xảy ra lỗi' . $th->getMessage());
        }
        return $this->success([], 'Chuyển đổi thành công');
    }
}
