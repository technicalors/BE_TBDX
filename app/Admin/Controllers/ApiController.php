<?php

namespace App\Admin\Controllers;

use alhimik1986\PhpExcelTemplator\params\ExcelParam;
use alhimik1986\PhpExcelTemplator\setters\CellSetterArrayValueSpecial;
use App\Models\Buyer;
use App\Models\ProductionPlan;
use App\Models\ErrorMachine;
use App\Models\Customer;
use App\Models\CustomerShort;
use App\Models\CustomUser;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteOrder;
use App\Models\GroupPlanOrder;
use App\Models\DRC;
use App\Models\ErrorLog;
use App\Models\GoodsReceiptNote;
use App\Models\InfoCongDoan;
use App\Models\IOTLog;
use App\Models\Layout;
use App\Models\Line;
use App\Models\LocatorFG;
use App\Models\LocatorFGMap;
use App\Models\LocatorMLT;
use App\Models\LocatorMLTMap;
use App\Models\Lot;
use App\Models\LotLog;
use App\Models\LSXLog;
use App\Models\LSXPallet;
use App\Models\Machine;
use App\Models\MachineParameter;
use App\Traits\API;
use Carbon\Carbon;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\MachineLog;
use App\Models\MachineMap;
use App\Models\MachineParameterLogs;
use App\Models\MachineParameters;
use App\Models\Mapping;
use App\Models\Material;
use App\Models\Order;
use App\Models\Pallet;
use App\Models\QCLog;
use App\Models\TestCriteria;
use App\Models\Tracking;
use App\Models\Role;
use App\Models\Tem;
use App\Models\TieuChuanNCC;
use App\Models\UserLine;
use App\Models\UserLineMachine;
use App\Models\UserMachine;
use App\Models\WareHouse;
use App\Models\WareHouseFGExport;
use App\Models\WarehouseFGLog;
use App\Models\WareHouseLog;
use App\Models\WareHouseMLTExport;
use App\Models\WareHouseMLTImport;
use App\Models\WarehouseMLTLog;
use DateTime;
use DateTimeImmutable;
use Exception;
use Illuminate\Support\Facades\DB;
use stdClass;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Throwable;
use App\Events\ProductionUpdated;
use App\Models\InfoCongDoanPriority;
use App\Models\ShiftAssignment;
use App\Models\Supplier;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ApiController extends AdminController
{
    use API;
    private $user;
    private $apiUIController;
    public function __construct(CustomUser $customUser, ApiUIController $apiUIController)
    {
        $this->user = $customUser;
        $this->apiUIController = $apiUIController;
    }

    //Login
    private function parseDataUser($user)
    {
        $permission = [];
        $routes = [];
        foreach ($user->roles as $role) {
            $tm = ($role->permissions);
            foreach ($tm as $t) {
                $permission[] = $t->slug;
                if ($t->http_path) {
                    $routes = array_merge($routes, explode(';', $t->http_path));
                }
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
            "routes" => $routes,
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
            $user = $this->user->find($user->id);
            // $user->tokens()->delete();
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
            return $this->failure([], "Mật khẩu cũ và mật khẩu mới là bắt buộc");
        }

        if (Hash::check($request->password, $user->password)) {
            $user->password = Hash::make($request->newPassword);
            $user->save();
            return $this->success($user, 'Đổi mật khẩu thành công');
        }
        return $this->failure([], "Sai mật khẩu, không thể thực hiện thao tác này");
    }
    //End Login

    function splitNumberIntoArray($number, $divider)
    {
        // Kiểm tra nếu chia cho 0 để tránh lỗi
        if ($divider == 0) {
            return false;
        }
        // Tạo mảng để chứa kết quả
        $resultArray = [];
        // Lặp để chia số và thêm vào mảng
        while ($number >= $divider) {
            $resultArray[] = $divider;
            $number -= $divider;
        }
        // Thêm số dư vào mảng nếu có
        if ($number > 0) {
            $resultArray[] = $number;
        }
        return $resultArray;
    }

    public function startProduce(Request $request)
    {
        $machine_status = 0;
        $input = $request->all();
        $machine = Machine::find($input['machine_id']);
        if (!$machine) return $this->failure('', 'Không tìm thấy máy');
        $info_cong_doan = InfoCongDoan::with('order')->where('lo_sx', $input['lo_sx'])->where('machine_id', $input['machine_id'])->first();
        if ($info_cong_doan) {
            $info_cong_doan->update(['status' => 1]);
            InfoCongDoanPriority::updateOrCreate(['info_cong_doan_id' => $info_cong_doan->id], ['priority' => 0]);
            $this->reorderInfoCongDoan();
            $order = $next_info->order ?? null;
            $so_ra = $order->so_ra ?? $info_cong_doan->so_ra;
            $formula = DB::table('formulas')->where('phan_loai_1', $order->phan_loai_1 ?? null)->where('phan_loai_2', $order->phan_loai_2 ?? null)->first();
            Tracking::where('machine_id', $input['machine_id'])->update([
                'is_running' => 1,
                'lo_sx' => $info_cong_doan->lo_sx ?? null,
                'so_ra' => $so_ra ?? 1,
                'thu_tu_uu_tien' => $info_cong_doan->thu_tu_uu_tien ?? 0,
                'sl_kh' => ceil(($info_cong_doan->dinh_muc * ($formula->he_so ?? 1)) / $so_ra) ?? 0,
                'pre_counter' => 0,
                'error_counter' => 0,
                'set_counter' => 0,
            ]);
        } else {
            return $this->failure('', 'Không tìm thấy lô cần chạy');
        }
        return $this->success('');
    }

    public function stopProduce(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $tracking = Tracking::where('machine_id', $input['machine_id'])->first();
            $tracking->update([
                'is_running' => 0,
                'lo_sx' => null,
                'so_ra' => 0,
                'thu_tu_uu_tien' => null,
                'sl_kh' => 0
            ]);
            $info_cong_doan = InfoCongDoan::where('machine_id', $input['machine_id'])->where('status', 1)->first();
            if ($info_cong_doan) {
                $info_cong_doan->update(['status' => 2]);
                InfoCongDoanPriority::where('info_cong_doan_id', $info_cong_doan->id)->delete();
                $this->reorderInfoCongDoan();
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th->getMessage(), 'Đã xảy ra lỗi');
        }
        return $this->success('');
    }

    public function startTracking(Request $request)
    {
        $tracking = Tracking::where('machine_id', $request->machine_id)->first();
        if (!$tracking) {
            return $this->failure('', 'Không thể khởi chạy');
        }
        $info_cong_doan = InfoCongDoan::where('machine_id', $tracking->machine_id)->where('lo_sx', $tracking->lo_sx)->first();
        if (!$info_cong_doan) {
            return $this->failure('', 'Chưa quét tem đầu vào');
        }
        $tracking->update([
            'status' => 1,
            'pre_counter' => $info_cong_doan->sl_dau_vao_chay_thu,
        ]);
        return $this->success($tracking, 'Bắt đầu tính sản lượng');
    }

    public function stopTracking(Request $request)
    {
        $tracking = Tracking::where('machine_id', $request->machine_id)->first();
        if (!$tracking) {
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        $tracking->update([
            'status' => 0
        ]);
        return $this->success($tracking, 'Đã dừng');
    }

    public function reorderInfoCongDoan()
    {
        $infos_priority = InfoCongDoanPriority::orderBy('priority')->get();
        foreach ($infos_priority as $key => $info_priority) {
            $info_priority->update([
                'priority' => $key + 1,
            ]);
        }
        return $infos_priority->pluck('info_cong_doan_id')->toArray();
    }

    public function reorderPriority(Request $request)
    {
        if (empty($request->changes)) {
            return $this->failure('', 'Không có bản ghi nào được cập nhật');
        }
        try {
            DB::beginTransaction();
            foreach ($request->changes as $key => $info) {
                $data = InfoCongDoanPriority::where('info_cong_doan_id', $info['id'])->update(['priority' => $info['newPriority']]);
                if (!$data) {
                    DB::rollBack();
                    return $this->failure('', 'Không thành công');
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::debug($th);
            return $this->failure($th->getMessage(), 'Đã xảy ra lỗi');
        }
        return $this->success('', '');
    }

    public function getPausedPlanList(Request $request)
    {
        $machine = Machine::find($request->machine_id);
        if (!$machine) {
            return $this->failure([], 'Không tìm thấy máy');
        }
        $infos = InfoCongDoan::where('machine_id', $request->machine_id)
            ->orderBy('thu_tu_uu_tien')->orderBy('updated_at')
            ->whereDate('ngay_sx', '>=', date('Y-m-d', strtotime($request->start_date)))
            ->whereDate('ngay_sx', '<=', date('Y-m-d', strtotime($request->end_date)))
            ->where('status', '<', 0)
            ->get();
        $data = [];
        switch ($machine->line_id) {
            case Line::LINE_SONG:
                $data = $this->corrugatingInfoList($infos);
                break;
            case Line::LINE_IN:
            case Line::LINE_DAN:
                foreach ($infos as $info) {
                    $order = $info->order;
                    if (!$order && ($info->plan->order ?? false)) {
                        $order = $info->plan->order;
                    }
                    if (!$order && ($info->tem->order ?? false)) {
                        $order = $info->tem->order;
                    }
                    $info->san_luong = $info->sl_dau_ra_hang_loat ?? 0;
                    $info->sl_ok = $info ? $info->sl_dau_ra_hang_loat - $info->sl_ng_sx - $info->sl_ng_qc : 0;
                    $info->san_luong_kh = $info->dinh_muc ?? 0;
                    $info->quy_cach = $order ? $order->dai . 'x' . $order->rong . ($order->cao ? ('x' . $order->cao) : "") : "";
                    $info->quy_cach_kh = $order ? (!$order->kich_thuoc ? ($order->length . 'x' . $order->width . ($order->height ? ('x' . $order->height) : "")) : $order->kich_thuoc) : "";
                    $info->khach_hang = $order->short_name ?? "-";
                    $info->mdh = $order->mdh ?? "-";
                    $info->mql = $order->mql ?? "-";
                    $info->tmo = $order->tmo ?? "";
                    $info->po = $order->po ?? "";
                    $info->style = $order->style ?? "";
                    $info->style_no = $order->style_no ?? "";
                    $info->color = $order->color ?? "";
                    $info->layout_id = $order->layout_id ?? "-";
                    $info->xuong_giao = $order->xuong_giao ?? "-";
                    $json = ['lo_sx' => $info->lo_sx, 'so_luong' => $info->sl_ok];
                    $info->qr_code = json_encode($json);
                    $info->nhan_vien_sx = $info->user->name ?? "";
                    $info->so_luong = $info->sl_ok;
                    $info->order_kh = $order->order ?? '';
                    $info->dot = $order->dot ?? '';
                    $info->note = $order->note_3 ?? '';
                    $info->slg_sx = $order->sl ?? '';
                    $data[] = $info;
                }
                break;
            default:
                # code...
                break;
        }

        return $this->success($data);
    }

    public function pausePlan(Request $request)
    {
        $infos = InfoCongDoan::whereIn('id', ($request->info_ids ?? []));
        if ($infos->count() <= 0) {
            return $this->failure('', 'Không có bản ghi được cập nhật');
        }
        $infos->update(['status' => -1]);
        $tracking = Tracking::where('machine_id', $request->machine_id)->first();
        if ($tracking && in_array($tracking->lo_sx, $infos->pluck('lo_sx')->toArray())) {
            $tracking->update([
                'lo_sx' => null,
                'so_ra' => 0,
                'thu_tu_uu_tien' => null,
                'sl_kh' => 0
            ]);
        }
        if ($request->machine_id !== 'So01') {
            InfoCongDoanPriority::whereIn('info_cong_doan_id', ($request->info_ids ?? []))->delete();
            $this->reorderInfoCongDoan();
        }

        return $this->success('', 'Đã tạm dừng');
    }

    public function resumePlan(Request $request)
    {
        $infos = InfoCongDoan::whereIn('id', array_reverse($request->info_ids ?? []))->orderBy('thu_tu_uu_tien')->get();
        if (count($infos) <= 0) {
            return $this->failure('', 'Không có bản ghi được cập nhật');
        }
        try {
            DB::beginTransaction();
            if ($request->machine_id === 'So01') {
                foreach ($infos as $key => $info) {
                    $info->update(['status' => 0]);
                    InfoCongDoanPriority::updateOrCreate(['info_cong_doan_id' => $info->id], ['priority' => 0]);
                }
                $this->reorderInfoCongDoan();
            } else {
                $tracking = Tracking::where('machine_id', $request->machine_id)->first();
                if (!$tracking) {
                    return $this->failure('', 'Không tìm thấy máy');
                }
                if ($tracking->lo_sx) {
                    return $this->failure('', 'Máy đang chạy đơn khác, hãy thử lại sau');
                }
                foreach ($infos as $key => $info) {
                    $info->update(['status' => $key === (count($infos) - 1) ? 1 : 0, 'ngay_sx' => date('Y-m-d')]);
                    if ($key === 0) {
                        $tracking->update([
                            'lo_sx' => $info->lo_sx,
                            'sl_kh' => $request->dinh_muc,
                            'thu_tu_uu_tien' => $info->thu_tu_uu_tien,
                            'is_running' => 1,
                            'pre_counter' => 0,
                            'error_counter' => 0,
                        ]);
                    }
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
        }
        return $this->success('', 'Đã tiếp tục');
    }

    public function updateQuantityInfoCongDoan(Request $request)
    {
        $info = InfoCongDoan::find($request->id);
        // return $info;
        $sl_dau_ra_hang_loat = $request->sl_dau_ra_hang_loat;
        if ($info->machine_id == 'So01') {
            $sl_dau_ra_hang_loat = $request->sl_dau_ra_hang_loat * $info->so_ra;
            $info->update([
                'sl_dau_ra_hang_loat' => $sl_dau_ra_hang_loat,
                'status' => 2,
                'nhan_vien_sx' => $request->user()->id ?? null,
                'thoi_gian_ket_thuc' => date('Y-m-d H:i:s'),
            ]);
            $info->infoCongDoanPriority()->delete();
            $this->reorderInfoCongDoan();
            $tracking = Tracking::where('machine_id', $info->machine_id)->where('lo_sx', $info->lo_sx)->first();
            if ($tracking) {
                $tracking->update([
                    'lo_sx' => null,
                    'so_ra' => 0,
                    'thu_tu_uu_tien' => 0,
                    'sl_kh' => 0,
                ]);
            }
        } else {
            $sl_dau_ra_hang_loat = $request->sl_dau_ra_hang_loat;
            $info->update([
                'sl_dau_ra_hang_loat' => $sl_dau_ra_hang_loat,
                'status' => 2,
                'nhan_vien_sx' => $request->user()->id ?? null,
                'thoi_gian_ket_thuc' => date('Y-m-d H:i:s'),
            ]);
            $tracking = Tracking::where('machine_id', $info->machine_id)->where('lo_sx', $info->lo_sx)->first();
            if ($tracking) {
                $next_batch = InfoCongDoan::where('ngay_sx', date('Y-m-d'))->whereIn('status', [0, 1])->where('lo_sx', '<>', $info->lo_sx)->where('machine_id', $tracking->machine_id)->orderBy('created_at', 'DESC')->first();
                $tracking->update([
                    'lo_sx' => $next_batch->lo_sx ?? null,
                    'so_ra' => $next_batch->so_ra ?? 0,
                    'thu_tu_uu_tien' => $next_batch->thu_tu_uu_tien ?? 0,
                    'sl_kh' => $next_batch->dinh_muc ?? 0,
                ]);
            }
        }

        return $this->success('', 'Đã cập nhật');
    }

    public function deletePausedPlanList(Request $request)
    {
        $infos = InfoCongDoan::whereIn('id', ($request->info_ids ?? []))->get();
        try {
            DB::beginTransaction();
            ProductionPlan::whereIn('id', $infos->pluck('plan_id')->toArray())->delete();
            GroupPlanOrder::whereIn('plan_id', $infos->pluck('plan_id')->toArray())->delete();
            $infos->delete();
            InfoCongDoanPriority::whereIn('info_cong_doan_id', ($request->info_ids ?? []))->delete();
            $this->reorderInfoCongDoan();
            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return $this->failure($th->getMessage(), 'Đã xảy ra lỗi');
        }
    }

    function takeTime()
    {
        return date('Y-m-d H:i:s.') . gettimeofday()["usec"];
    }

    public function CorrugatingProduction($request, $tracking)
    {
        $startTime = microtime(true);
        //Kiểm tra tracking. Nếu tracking có chạy thì tiếp tục ngược lại thì không
        if ($tracking->is_running != 0) {
            try {
                DB::beginTransaction();
                //Tìm kiếm lô đang chạy
                if ($tracking->lo_sx) {
                    $info_lo_sx = InfoCongDoan::where('lo_sx', $tracking->lo_sx)->where('machine_id', $tracking->machine_id)->where('status', 1)->first();
                    if ($info_lo_sx) {
                        $current_quantity = $tracking->pre_counter + ($tracking->error_counter ?? 0);
                        $incoming_quantity = $request['Pre_Counter'] + ($request['Error_Counter'] ?? 0);
                        if ($tracking->pre_counter > 0 && ($current_quantity > $incoming_quantity)) {   
                            $running_infos = InfoCongDoan::where('machine_id', $tracking->machine_id)->where('status', 1)->get();
                            if(count($running_infos) > 0){
                                foreach ($running_infos as $info) {
                                    $info->update([
                                        'status' => 2,
                                        'thoi_gian_ket_thuc' => date('Y-m-d H:i:s'),
                                    ]);
                                }
                            }
                            InfoCongDoanPriority::whereIn('info_cong_doan_id', $running_infos->pluck('id')->toArray())->delete();
                            $info_ids = $this->reorderInfoCongDoan();
                            $next_info = InfoCongDoan::where('ngay_sx', date('Y-m-d'))->whereIn('id', $info_ids)->where('so_dao', $request['Set_Counter'] ?? "")->first();
                            if ($next_info) {
                                $so_ra = $next_info->so_ra;
                                $next_info->update(['thoi_gian_bat_dau' => date('Y-m-d H:i:s'), 'status' => 1, 'sl_dau_ra_hang_loat' => $request['Pre_Counter'] * $so_ra, 'so_ra' => $so_ra]);
                                $tracking->update([
                                    'sl_kh' => $next_info->so_dao,
                                    'lo_sx' => $next_info->lo_sx,
                                    'so_ra' => $so_ra,
                                    'thu_tu_uu_tien' => $next_info->thu_tu_uu_tien,
                                    'is_running' => 1,
                                    'pre_counter' => $request['Pre_Counter'],
                                    'set_counter' => $request['Set_Counter'],
                                    'error_counter' => $request['Error_Counter'],
                                ]);
                            } else {
                                $tracking->update([
                                    'sl_kh' => 0,
                                    'lo_sx' => null,
                                    'so_ra' => 1,
                                    'thu_tu_uu_tien' => 0,
                                    'is_running' => 1,
                                    'pre_counter' => $request['Pre_Counter'],
                                    'set_counter' => $request['Set_Counter'],
                                    'error_counter' => $request['Error_Counter'],
                                ]);
                            }
                            $this->broadcastProductionUpdate($info_lo_sx, $tracking->so_ra, true);
                        } else {
                            $info_lo_sx->update([
                                'sl_dau_ra_hang_loat' => $request['Pre_Counter'] * $tracking->so_ra,
                                'sl_ng_sx' => isset($request['Error_Counter']) ? ($request['Error_Counter'] * $tracking->so_ra) : $info_lo_sx->sl_ng_sx,
                                'status' => 1
                            ]);
                            $tracking->update([
                                'pre_counter' => $request['Pre_Counter'],
                                'error_counter' => $request['Error_Counter'],
                                'set_counter' => $request['Set_Counter']
                            ]);
                            $this->broadcastProductionUpdate($info_lo_sx, $tracking->so_ra);
                        }
                    } else {
                        $tracking->update([
                            'sl_kh' => 0,
                            'lo_sx' => null,
                            'so_ra' => 1,
                            'thu_tu_uu_tien' => 0,
                            'is_running' => 1,
                            'pre_counter' => $request['Pre_Counter'],
                            'set_counter' => $request['Set_Counter'],
                            'error_counter' => $request['Error_Counter'],
                        ]);
                    }
                } else {
                    $info_ids = InfoCongDoanPriority::orderBy('priority')->pluck('info_cong_doan_id')->toArray();
                    $next_info = InfoCongDoan::whereIn('id', $info_ids)->where('so_dao', $request['Set_Counter'] ?? "")->first();
                    if ($next_info) {
                        $so_ra = $next_info->so_ra;
                        $next_info->update(['thoi_gian_bat_dau' => date('Y-m-d H:i:s'), 'status' => 1, 'sl_dau_ra_hang_loat' => $request['Pre_Counter'] * $so_ra, 'so_ra' => $so_ra]);
                        $tracking->update([
                            'sl_kh' => $next_info->so_dao,
                            'lo_sx' => $next_info->lo_sx,
                            'so_ra' => $so_ra,
                            'thu_tu_uu_tien' => $next_info->thu_tu_uu_tien,
                            'is_running' => 1,
                            'pre_counter' => $request['Pre_Counter'],
                            'set_counter' => $request['Set_Counter'],
                            'error_counter' => $request['Error_Counter'],
                        ]);
                    } else {
                        $tracking->update([
                            'sl_kh' => 0,
                            'lo_sx' => null,
                            'so_ra' => 1,
                            'thu_tu_uu_tien' => 0,
                            'is_running' => 1,
                            'pre_counter' => $request['Pre_Counter'],
                            'set_counter' => $request['Set_Counter'],
                            'error_counter' => $request['Error_Counter'],
                        ]);
                    }
                }
                DB::commit();
            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        }   
        $endTime = microtime(true);
        $timeTaken = $endTime - $startTime;
        return (['machine_id'=>$tracking->machine_id,'timeTaken'=>$timeTaken, 'pre'=>$request['Pre_Counter'], 'set'=>$request['Set_Counter']]);
    }

    protected function broadcastProductionUpdate($info_lo_sx, $so_ra, $reload = false)
    {
        $info_lo_sx['sl_dau_ra_hang_loat'] = $so_ra ? $info_lo_sx['sl_dau_ra_hang_loat'] / $so_ra : 0;
        $info_lo_sx['sl_ng_sx'] = $so_ra ? $info_lo_sx['sl_ng_sx'] / $so_ra : 0;
        broadcast(new ProductionUpdated(['info_cong_doan' => $info_lo_sx, 'reload' => $reload]))->toOthers();
    }

    public function TemPrintProduction($request, $tracking, $machine)
    {
        if (!$tracking || !$tracking->lo_sx || $tracking->is_running === 0) {
            return;
        }
        $info_cong_doan_in = InfoCongDoan::where('machine_id', $machine->id)->where('lo_sx', $tracking->lo_sx)->first();
        if ($tracking->status === 0 && $info_cong_doan_in) {
            $info_cong_doan_in->update([
                'sl_dau_vao_chay_thu' => $request['Pre_Counter'],
            ]);
        } else {
            //Tìm lô đang chạy
            $broadcast = [];
            if($info_cong_doan_in){
                $next_batch = InfoCongDoan::where('ngay_sx', date('Y-m-d'))->whereIn('status', [0, 1])->where('lo_sx', '<>', $info_cong_doan_in->lo_sx)->where('machine_id', $tracking->machine_id)->orderBy('created_at', 'DESC')->first();
                if ($next_batch) {
                    if (($request['Pre_Counter'] - $tracking->pre_counter)  >= $info_cong_doan_in->dinh_muc) {
                        $info_cong_doan_in->update([
                            'status' => 2,
                            'thoi_gian_ket_thuc' => date('Y-m-d H:i:s'),
                            'sl_dau_ra_hang_loat' => $info_cong_doan_in->dinh_muc
                        ]);
                        $tracking->update([
                            'lo_sx' => $next_batch->lo_sx,
                            'sl_kh' => $next_batch->dinh_muc,
                            'thu_tu_uu_tien' => $next_batch->thu_tu_uu_tien,
                            'pre_counter' => $info_cong_doan_in->dinh_muc + $tracking->pre_counter,
                            'error_counter' => $request['Error_Counter'] ?? 0,
                            'is_running' => 1
                        ]);
                        $broadcast = ['info_cong_doan' => $info_cong_doan_in, 'reload' => true];
                    } else {
                        $info_cong_doan_in->update([
                            'sl_dau_ra_hang_loat' => $request['Pre_Counter'] - $tracking->pre_counter,
                            'status' => 1
                        ]);
                        $info_cong_doan_in->sl_ok = $info_cong_doan_in->sl_dau_ra_hang_loat - $info_cong_doan_in->sl_ng_sx - $info_cong_doan_in->sl_ng_qc;
                        $broadcast = ['info_cong_doan' => $info_cong_doan_in, 'reload' => false];
                    }
                } else {
                    if ($request['Pre_Counter'] < $info_cong_doan_in->sl_dau_ra_hang_loat) {
                        $info_cong_doan_in->update([
                            'thoi_gian_ket_thuc' => date('Y-m-d H:i:s'),
                            'status' => 2,
                        ]);
                        $tracking->update([
                            'lo_sx' => null,
                            'pre_counter' => 0,
                            'error_counter' => 0,
                            'is_running' => 1,
                            'sl_kh' => 0,
                            'thu_tu_uu_tien' => 0,
                            'set_counter' => 0,
                            'status' => 0
                        ]);
                        $broadcast = ['info_cong_doan' => $info_cong_doan_in, 'reload' => true];
                    } else {
                        $info_cong_doan_in->update([
                            'sl_dau_ra_hang_loat' => $request['Pre_Counter'] - $tracking->pre_counter,
                            'status' => 1
                        ]);
                        $info_cong_doan_in->sl_ok = $info_cong_doan_in->sl_dau_ra_hang_loat - $info_cong_doan_in->sl_ng_sx - $info_cong_doan_in->sl_ng_qc;
                        $broadcast = ['info_cong_doan' => $info_cong_doan_in, 'reload' => false];
                    }
                } 
            } else {
                $tracking->update([
                    'lo_sx' => null,
                    'pre_counter' => 0,
                    'error_counter' => 0,
                    'is_running' => 1,
                    'sl_kh' => 0,
                    'thu_tu_uu_tien' => 0,
                    'set_counter' => 0,
                    'status' => 0
                ]);
            }
            
            broadcast(new ProductionUpdated($broadcast))->toOthers();
            return $broadcast;
        }
    }

    public function TemPrintProductionCH($request, $tracking, $machine)
    {
        if (!$tracking || !$tracking->lo_sx || $tracking->is_running === 0 || $tracking->status === 0) {
            return;
        }
        $info_cong_doan_in = InfoCongDoan::where('machine_id', $machine->id)->where('lo_sx', $tracking->lo_sx)->first();
        //Tìm lô đang chạy
        $broadcast = [];
        if($info_cong_doan_in){
            try {
                $next_batch = InfoCongDoan::whereIn('status', [0, 1])->where('lo_sx', '<>', $info_cong_doan_in->lo_sx)->where('machine_id', $tracking->machine_id)->orderBy('created_at', 'DESC')->first();
                if ($next_batch) {
                    if ((int)$request['Pre_Counter'] === 0 && $info_cong_doan_in->sl_dau_ra_hang_loat > 0) {
                        $info_cong_doan_in->update([
                            'status' => 2,
                            'thoi_gian_ket_thuc' => date('Y-m-d H:i:s'),
                        ]);
                        $tracking->update([
                            'lo_sx' => $next_batch->lo_sx,
                            'sl_kh' => $next_batch->dinh_muc,
                            'thu_tu_uu_tien' => $next_batch->thu_tu_uu_tien,
                        ]);
                        $next_batch->update(['status' => 1]);
                        $broadcast = ['info_cong_doan' => $info_cong_doan_in, 'reload' => true];
                    } else {
                        $info_cong_doan_in->update([
                            'sl_dau_ra_hang_loat' => (int)$request['Pre_Counter'],
                            'status' => 1
                        ]);
                        $info_cong_doan_in->sl_ok = $info_cong_doan_in->sl_dau_ra_hang_loat - $info_cong_doan_in->sl_ng_sx - $info_cong_doan_in->sl_ng_qc;
                        $broadcast = ['info_cong_doan' => $info_cong_doan_in, 'reload' => false];
                    }
                    broadcast(new ProductionUpdated($broadcast))->toOthers();
                    return $broadcast;
                } else {
                    if ((int)$request['Pre_Counter'] === 0 && $info_cong_doan_in->sl_dau_ra_hang_loat > 0) {
                        $info_cong_doan_in->update([
                            'thoi_gian_ket_thuc' => date('Y-m-d H:i:s'),
                            'status' => 2,
                        ]);
                        $tracking->update([
                            'lo_sx' => null,
                            'sl_kh' => 0,
                            'thu_tu_uu_tien' => 0,
                        ]);
                        $broadcast = ['info_cong_doan' => $info_cong_doan_in, 'reload' => true];
                    } else {
                        $info_cong_doan_in->update([
                            'sl_dau_ra_hang_loat' => (int)$request['Pre_Counter'],
                            'status' => 1
                        ]);
                        $info_cong_doan_in->sl_ok = $info_cong_doan_in->sl_dau_ra_hang_loat - $info_cong_doan_in->sl_ng_sx - $info_cong_doan_in->sl_ng_qc;
                        $broadcast = ['info_cong_doan' => $info_cong_doan_in, 'reload' => false];
                    }
                    broadcast(new ProductionUpdated($broadcast))->toOthers();
                    return $broadcast;
                }
                //code...
            } catch (\Throwable $th) {
                throw $th;
            }
        }else{
            $tracking->update([
                'lo_sx' => null,
                'sl_kh' => 0,
                'thu_tu_uu_tien' => 0,
            ]);
        }
        return $broadcast;
    }

    public function TemGluingProduction($request, $tracking, $machine)
    {
        if (!$tracking->lo_sx || $tracking->is_running === 0) {
            return;
        }
        //Tìm lô đang chạy
        $broadcast = [];
        $info_cong_doan_dan = InfoCongDoan::where('machine_id', $machine->id)->where('lo_sx', $tracking->lo_sx)->first();
        if ($info_cong_doan_dan) {
            $next_batch = InfoCongDoan::where('ngay_sx', date('Y-m-d'))->whereIn('status', [0, 1])->where('lo_sx', '<>', $info_cong_doan_dan->lo_sx)->where('machine_id', $tracking->machine_id)->orderBy('created_at', 'DESC')->first();
            if ($next_batch) {
                if (($request['Pre_Counter'] - $tracking->pre_counter)  >= $info_cong_doan_dan->dinh_muc) {
                    $info_cong_doan_dan->update([
                        'status' => 2,
                        'thoi_gian_ket_thuc' => date('Y-m-d H:i:s'),
                        'sl_dau_ra_hang_loat' => $info_cong_doan_dan->dinh_muc
                    ]);
                    $tracking->update([
                        'lo_sx' => $next_batch->lo_sx,
                        'sl_kh' => $next_batch->dinh_muc,
                        'thu_tu_uu_tien' => $next_batch->thu_tu_uu_tien,
                        'pre_counter' => $tracking->pre_counter + $info_cong_doan_dan->dinh_muc,
                        'error_counter' => $request['Error_Counter'] ?? 0,
                        'is_running' => 1
                    ]);
                    $broadcast = ['info_cong_doan' => $info_cong_doan_dan, 'reload' => true];
                } elseif ($request['Pre_Counter'] < $info_cong_doan_dan->sl_dau_ra_hang_loat) {
                    $info_cong_doan_dan->update([
                        'thoi_gian_ket_thuc' => date('Y-m-d H:i:s'),
                        'status' => 2,
                    ]);
                    $tracking->update([
                        'lo_sx' => $next_batch->lo_sx,
                        'pre_counter' => 0,
                        'error_counter' => 0,
                        'is_running' => 1,
                        'sl_kh' => $next_batch->dinh_muc,
                        'thu_tu_uu_tien' => 0,
                        'set_counter' => 0
                    ]);
                    $broadcast = ['info_cong_doan' => $info_cong_doan_dan, 'reload' => true];
                } else {
                    $info_cong_doan_dan->update([
                        'sl_dau_ra_hang_loat' => $request['Pre_Counter'] - $tracking->pre_counter,
                        'status' => 1
                    ]);
                    $info_cong_doan_dan->sl_ok = $info_cong_doan_dan->sl_dau_ra_hang_loat - $info_cong_doan_dan->sl_ng_sx - $info_cong_doan_dan->sl_ng_qc;
                    $broadcast = ['info_cong_doan' => $info_cong_doan_dan, 'reload' => false];
                }
            } else {
                if ($request['Pre_Counter'] < $info_cong_doan_dan->sl_dau_ra_hang_loat) {
                    $info_cong_doan_dan->update([
                        'thoi_gian_ket_thuc' => date('Y-m-d H:i:s'),
                        'status' => 2,
                    ]);
                    $tracking->update([
                        'lo_sx' => null,
                        'pre_counter' => 0,
                        'error_counter' => 0,
                        'is_running' => 1,
                        'sl_kh' => 0,
                        'thu_tu_uu_tien' => 0,
                        'set_counter' => 0,
                        'status' => 0
                    ]);
                    $broadcast = ['info_cong_doan' => $info_cong_doan_dan, 'reload' => true];
                } else {
                    $info_cong_doan_dan->update([
                        'sl_dau_ra_hang_loat' => $request['Pre_Counter'] - $tracking->pre_counter,
                        'status' => 1
                    ]);
                    $info_cong_doan_dan->sl_ok = $info_cong_doan_dan->sl_dau_ra_hang_loat - $info_cong_doan_dan->sl_ng_sx - $info_cong_doan_dan->sl_ng_qc;
                    $broadcast = ['info_cong_doan' => $info_cong_doan_dan, 'reload' => false];
                }
            }
            broadcast(new ProductionUpdated($broadcast))->toOthers();
            return $broadcast;
        }
    }

    public function websocket(Request $request)
    {
        if (!isset($request['device_id'])) return 'Không có mã máy';
        $machine = Machine::with('line')->where('device_id', $request['device_id'])->first();
        $line = $machine->line;
        $tracking = Tracking::where('machine_id', $machine->id)->first();
        switch ($line->id) {
            case Line::LINE_SONG:
                return $this->CorrugatingProduction($request, $tracking, $machine);
                break;
            case Line::LINE_IN:
                if ($machine->id === 'CH02' || $machine->id === 'CH03') {
                    return $this->TemPrintProductionCH($request, $tracking, $machine);
                } else {
                    return $this->TemPrintProduction($request, $tracking, $machine);
                }
                break;
            case Line::LINE_DAN:
                return $this->TemGluingProduction($request, $tracking, $machine);
                break;
            default:
                break;
        }
        return $this->success($this->takeTime());
    }

    public function websocketMachineStatus(Request $request)
    {
        if (!isset($request['device_id'])) return $this->failure('Không có mã máy');;
        $machine = Machine::with('line')->where('device_id', $request['device_id'])->first();
        $tracking = Tracking::where('machine_id', $machine->id)->first();
        $res = MachineLog::UpdateStatus(['machine_id' => $machine->id, 'status' => (int)$request['Machine_Status'], 'timestamp' => date('Y-m-d H:i:s'), 'lo_sx' => $tracking->lo_sx ?? null]);
        broadcast(new ProductionUpdated($res))->toOthers();
        return $this->success('Đã cập nhật trạng thái');
    }

    public function websocketMachineParams(Request $request)
    {
        $input = $request->all();
        if (!isset($request->device_id)) return $this->failure('', 'Không có mã máy');
        $machine = Machine::where('device_id', $request->device_id)->first();
        if (!$machine) return $this->failure('', 'Không tìm thấy máy');
        $tracking = Tracking::where('machine_id', $machine->id)->first();
        MachineParameterLogs::create([
            'lo_sx' => $tracking->lo_sx,
            'machine_id' => $machine->id,
            "info" => $input
        ]);
        return $this->success($this->takeTime(), 'Lưu thông số');
    }

    //OI
    //====================Manufacture=====================

    public function listMachine(Request $request)
    {
        $user = $request->user();
        $customOrder = [30, 31, 32, 38];
        $machine_query = Machine::select('id as label', 'id as value', 'line_id', 'device_id', 'is_iot')
            ->orderBy('line_id', 'ASC')
            ->whereNull('parent_id');
        if (isset($request->is_iot)) {
            $machine_query->where('is_iot', $request->is_iot);
        }
        $roles_arr = $user->roles()->pluck('name')->toArray();
        $permissions = $user->roles->flatMap->permissions->pluck('slug')->toArray();
        if (in_array('*', $permissions) || (isset($request->is_iot) && (in_array('PQC', $roles_arr) || in_array('OQC', $roles_arr)))) {
            $machines = $machine_query->get();
            return $this->success($machines);
        }
        // $machine_assign = UserLineMachine::where('user_id', $user->id)->first();
        $user_machine = UserMachine::where('user_id', $user->id)->get();
        if (count($user_machine)) {
            $machine_query->whereIn('id', $user_machine->pluck('machine_id')->toArray());
            $machines = $machine_query->get();
            if (count($machines)) {
                return $this->success($machines);
            }
        } else {
            $user_line = UserLine::where('user_id', $user->id)->first();
            if ($user_line && $user_line->line_id) {
                $machine_query->where('line_id', $user_line->line_id);
                $machines = $machine_query->get();
                if (count($machines)) {
                    return $this->success($machines);
                }
            }
        }
        return $this->success([], 'Tài khoản này không được chỉ định cho máy nào');
    }

    public function getTrackingStatus(Request $request)
    {
        $tracking = Tracking::where('machine_id', $request['machine_id'])->first();
        return $this->success($tracking);
    }

    public function getCurrentManufacturing(Request $request)
    {
        $tracking = Tracking::where('machine_id', $request['machine_id'])->first();
        $info_cong_doan = InfoCongDoan::with('plan.order')->where('lo_sx', $tracking->lo_sx)
            ->where('machine_id', $request['machine_id'])
            ->first();
        $obj = new stdClass;
        if ($info_cong_doan) {
            $plan = $info_cong_doan->plan;
            $so_ra = $info_cong_doan->plan->order->so_ra ?? 1;
            $obj->lo_sx = $info_cong_doan->lo_sx;
            $obj->dinh_muc = ceil($info_cong_doan->dinh_muc / $so_ra);
            $obj->san_luong_kh = ceil($plan->sl_kh / $so_ra);
            $obj->sl_dau_ra_hang_loat = ceil($info_cong_doan->sl_dau_ra_hang_loat / $so_ra);
            $obj->sl_ng_sx = ceil($info_cong_doan->sl_ng_sx / $so_ra);
            $obj->sl_ng_qc = ceil($info_cong_doan->sl_ng_qc / $so_ra);
            $obj->sl_ok = $obj->sl_dau_ra_hang_loat  - $obj->sl_ng_sx - $obj->sl_ng_qc;
        }
        return $this->success($obj);
    }

    function sortList($a, $b)
    {
        $statusA = $a->info_losx->status ?? null;
        $statusB = $b->info_losx->status ?? null;
        // Sort status 2 first, then status 1, then plans with no info_losx
        if ($statusA === $statusB) {
            $createdAtA = $a->info_losx->created_at ?? null;
            $createdAtB = $b->info_losx->created_at ?? null;
            if ($createdAtA === $createdAtB) {
                return $a->created_at <=> $b->created_at;
            } else {
                return $createdAtA <=> $createdAtB;
            }
        } else {
            if ($statusA >= 2 && $statusB < 2) {
                return -1;
            } elseif ($statusA < 2 && $statusB >= 2) {
                return 1;
            } elseif ($statusA == 1 && $statusB != 1) {
                return -1;
            } elseif ($statusA != 1 && $statusB == 1) {
                return 1;
            }
        }
        return 0; // Keep the original order for other cases
    }

    public function  listLotOI(Request $request)
    {
        $machine = Machine::with('line')->find($request->machine_id);
        if (!$machine) return $this->failure([], 'Không tìm thấy máy');
        $line = $machine->line;
        $data = [];
        switch ($line->id) {
            case Line::LINE_SONG:
                //Chia query thành "đã qua sản xuất" và "chưa sản xuất"
                $info_priority = InfoCongDoanPriority::orderBy('priority')->pluck('info_cong_doan_id')->toArray();
                $unfinished_query = InfoCongDoan::whereIn('id', $info_priority)
                    ->whereIn('status', [0, 1])
                    ->whereDate('ngay_sx', '<=', date('Y-m-d'))
                    ->where('machine_id', $request->machine_id)
                    ->with('plan', 'order.buyer', 'infoCongDoanPriority');
                if (count($info_priority)) {
                    $unfinished_query->orderByRaw('FIELD(id, ' . implode(',', ($info_priority ?? [])) . ')');
                } else {
                    $unfinished_query->orderBy('ngay_sx')->orderBy('thu_tu_uu_tien')->orderBy('updated_at');
                }
                $unfinished = $unfinished_query->get();
                $list = $unfinished;
                $data = $this->corrugatingInfoList($list);
                break;
            case Line::LINE_IN:
                return $this->infoList($request);
                break;
            case Line::LINE_DAN:
                return $this->infoList($request);
                break;
            default:
                break;
        }
        return $this->success($data);
    }

    public function corrugatingInfoList($info_list)
    {
        $data = [];
        //Status: 0-Chờ SX; 1-Đang sản xuất; 2-SX xong, chờ QC; 3-QC xong, chờ In tem; 4-All done
        foreach ($info_list as $key => $info_lo_sx) {
            $order = $info_lo_sx->order;
            $formula = DB::table('formulas')->where('phan_loai_1', $order->phan_loai_1 ?? null)->where('phan_loai_2', $order->phan_loai_2 ?? null)->first();
            $info_lo_sx->sl_ok = ceil(($info_lo_sx->sl_dau_ra_hang_loat - $info_lo_sx->sl_ng_sx - $info_lo_sx->sl_ng_qc) / ($info_lo_sx->so_ra > 0 ? $info_lo_sx->so_ra : 1));
            $info_lo_sx->sl_dau_ra_hang_loat = $info_lo_sx->so_ra > 0 ? ceil($info_lo_sx->sl_dau_ra_hang_loat / ($info_lo_sx->so_ra > 0 ? $info_lo_sx->so_ra : 1)) : "";
            $info_lo_sx->quy_cach_kh = $order ? (!$order->kich_thuoc ? ($order->length . 'x' . $order->width . ($order->height ? ('x' . $order->height) : "")) : $order->kich_thuoc) : "";
            $info_lo_sx->quy_cach = $order ? ($order->dai . 'x' . $order->rong . ($order->cao ? 'x' . $order->cao : "")) : "";
            $info_lo_sx->san_luong_kh = ceil(($info_lo_sx->dinh_muc * ($formula->he_so ?? 1)) / ($order->so_ra ?? ($info_lo_sx->so_ra > 0 ? $info_lo_sx->so_ra : 1))) ?? 0;
            $info_lo_sx->khach_hang = $order->short_name ?? "";
            $info_lo_sx->dai_tam = $order->dai_tam ?? "";
            $info_lo_sx->mdh = $order->mdh ?? "";
            $info_lo_sx->so_lop = $order->buyer->so_lop ?? "";
            $info_lo_sx->mql = $order->mql ?? "";
            $info_lo_sx->dai = $order->dai ?? "";
            $info_lo_sx->rong = $order->rong ?? "";
            $info_lo_sx->cao = $order->cao ?? "";
            $info_lo_sx->dot = $order->dot ?? "";
            $info_lo_sx->so_dao = $info_lo_sx->san_luong_kh;
            $info_lo_sx->kho_tong = $order->kho_tong ?? "";
            $info_lo_sx->kho = $order->kho ?? "";
            $info_lo_sx->so_ra = $order->so_ra ?? "";
            $info_lo_sx->note_3 = $order->note_3 ?? "";
            $info_lo_sx->kich_thuoc = $order->kich_thuoc ?? "";
            $info_lo_sx->ma_cuon_f = $order->buyer->ma_cuon_f ?? "";
            $info_lo_sx->ma_cuon_se = $order->buyer->ma_cuon_se ?? "";
            $info_lo_sx->ma_cuon_le = $order->buyer->ma_cuon_le ?? "";
            $info_lo_sx->ma_cuon_sb = $order->buyer->ma_cuon_sb ?? "";
            $info_lo_sx->ma_cuon_lb = $order->buyer->ma_cuon_lb ?? "";
            $info_lo_sx->ma_cuon_sc = $order->buyer->ma_cuon_sc ?? "";
            $info_lo_sx->ma_cuon_lc = $order->buyer->ma_cuon_lc ?? "";
            $info_lo_sx->so_pallet = $info_lo_sx->plan->ordering ?? "";
            $info_lo_sx->priority = $info_lo_sx->infoCongDoanPriority->priority ?? null;
            $qr = new stdClass();
            $qr->lo_sx = $info_lo_sx->lo_sx ?? "";
            $qr->so_luong = $info_lo_sx ? ((int)$info_lo_sx->sl_dau_ra_hang_loat - (int)$info_lo_sx->sl_ng_sx - (int)$info_lo_sx->sl_ng_qc) : "";
            $info_lo_sx->qr_code = json_encode($qr);
            $data[] = $info_lo_sx;
        }
        return $data;
    }

    public function infoList(Request $request)
    {
        $info_lo_sx = InfoCongDoan::with('order', 'user')
            ->where('status', '>=', 0)
            ->where('machine_id', $request->machine_id)
            ->whereDate('ngay_sx', '>=', date('Y-m-d', strtotime($request->start_date)))
            ->whereDate('ngay_sx', '<=', date('Y-m-d', strtotime($request->end_date)))
            ->orderBy('updated_at', 'DESC')
            ->orderBy('order_id')
            ->get();
        $data = [];
        $tracking = Tracking::where('machine_id', $request->machine_id)->first();
        // return $info_lo_sx;
        foreach ($info_lo_sx as $info) {
            $order = $info->order;
            $info->san_luong = $info->sl_dau_ra_hang_loat ?? 0;
            $info->sl_ok = $info ? $info->sl_dau_ra_hang_loat - $info->sl_ng_sx - $info->sl_ng_qc : 0;
            $info->san_luong_kh = $info->dinh_muc ?? 0;
            $info->quy_cach = $order ? $order->dai . 'x' . $order->rong . ($order->cao ? ('x' . $order->cao) : "") : "";
            $info->quy_cach_kh = $order ? (!$order->kich_thuoc ? ($order->length . 'x' . $order->width . ($order->height ? ('x' . $order->height) : "")) : $order->kich_thuoc) : "";
            $info->khach_hang = $order->short_name ?? "-";
            $info->mdh = $order->mdh ?? "-";
            $info->mql = $order->mql ?? "-";
            $info->tmo = $order->tmo ?? "";
            $info->po = $order->po ?? "";
            $info->style = $order->style ?? "";
            $info->style_no = $order->style_no ?? "";
            $info->color = $order->color ?? "";
            $info->layout_id = $order->layout_id ?? "-";
            $info->xuong_giao = $order->xuong_giao ?? "-";
            $json = ['lo_sx' => $info->lo_sx, 'so_luong' => $info->sl_ok];
            $info->qr_code = json_encode($json);
            $info->nhan_vien_sx = $info->user->name ?? "";
            $info->so_luong = $info->sl_ok;
            $info->order_kh = $order->order ?? '';
            $info->dot = $order->dot ?? '';
            $info->note = $order->note_3 ?? '';
            $info->slg_sx = $order->sl ?? '';
            if ($info->quy_cach === "x") {
                $info->quy_cach = $order->kich_thuoc ?? "";
            }
            $data[] = $info;
        }
        $order = [1, 0, 2, 3, 4];
        usort($data, function ($a, $b) use ($order) {
            $pos_a = array_search($a->status, $order);
            $pos_b = array_search($b->status, $order);
            if ($pos_a === false) return 1;
            if ($pos_b === false) return -1;
            return $pos_a - $pos_b;
        });
        return $this->success($data);
    }

    public function corrugatingList($plans)
    {
        $data = [];
        //Status: 0-Chờ SX; 1-Đang sản xuất; 2-SX xong, chờ QC; 3-QC xong, chờ In tem; 4-All done
        foreach ($plans as $key => $plan) {
            $orders = $plan->orders;
            $order = $plan->order;
            $info_lo_sx = $plan->info_losx;
            $obj = new stdClass;
            $obj->dinh_muc = ceil($orders->sum('sl') / ($order->so_ra ?? 1));
            $obj->lo_sx = $plan->lo_sx ?? "";
            $obj->phan_dinh = $info_lo_sx->phan_dinh ?? "";
            $obj->sl_dau_ra_hang_loat = $info_lo_sx ? ceil($info_lo_sx->sl_dau_ra_hang_loat / ($plan->order->so_ra ?? 1)) : "";
            $obj->sl_ok = $info_lo_sx ? ceil(($info_lo_sx->sl_dau_ra_hang_loat - $info_lo_sx->sl_ng_sx - $info_lo_sx->sl_ng_qc) / ($plan->order->so_ra ?? 1)) : "";
            $obj->sl_ng_sx = $info_lo_sx->sl_ng_sx ?? 0;
            $obj->status = $info_lo_sx->status ?? 0;
            $obj->quy_cach_kh = $order ? (!$order->kich_thuoc ? ($order->length . 'x' . $order->width . ($order->height ? ('x' . $order->height) : "")) : $order->kich_thuoc) : "";
            $obj->quy_cach = $order ? ($order->dai . 'x' . $order->rong . ($order->cao ? 'x' . $order->cao : "")) : "";
            $obj->thu_tu_uu_tien = $plan ? $plan->thu_tu_uu_tien : "";
            $obj->san_luong_kh = $order->so_dao ?? 0;
            $obj->plan_id = $plan->id ?? "";
            $obj->khach_hang = $order->short_name ?? "";
            $obj->dai_tam = $order->dai_tam ?? "";
            $obj->mdh = $order->mdh ?? "";
            $obj->so_lop = $order->buyer->so_lop ?? "";
            $obj->mql = $order->mql ?? "";
            $obj->dai = $order->dai ?? "";
            $obj->rong = $order->rong ?? "";
            $obj->cao = $order->cao ?? "";
            $obj->dot = $order->dot ?? "";
            $obj->so_dao = $obj->dinh_muc ?? "";
            $obj->kho_tong = $order->kho_tong ?? "";
            $obj->kho = $order->kho ?? "";
            $obj->so_ra = $order->so_ra ?? "";
            $obj->note_3 = $order->note_3 ?? "";
            $obj->kich_thuoc = $order->kich_thuoc ?? "";
            $obj->ma_cuon_f = $order->buyer->ma_cuon_f ?? "";
            $obj->ma_cuon_se = $order->buyer->ma_cuon_se ?? "";
            $obj->ma_cuon_le = $order->buyer->ma_cuon_le ?? "";
            $obj->ma_cuon_sb = $order->buyer->ma_cuon_sb ?? "";
            $obj->ma_cuon_lb = $order->buyer->ma_cuon_lb ?? "";
            $obj->ma_cuon_sc = $order->buyer->ma_cuon_sc ?? "";
            $obj->ma_cuon_lc = $order->buyer->ma_cuon_lc ?? "";
            $obj->so_m_toi = $plan->so_m_toi;
            $qr = new stdClass();
            $qr->lo_sx = $plan->lo_sx ?? "";
            $qr->so_luong = $info_lo_sx ? ($info_lo_sx->sl_dau_ra_hang_loat - $info_lo_sx->sl_ng_sx - $info_lo_sx->sl_ng_qc) : "";
            $obj->qr_code = json_encode($qr);
            $data[] = $obj;
        }
        return $data;
    }

    public function printerList($plans)
    {
        $data = [];
        //Status: 0-Chờ SX; 1-Đang sản xuất; 2-SX xong, chờ QC; 3-QC xong, chờ In tem; 4-All done
        foreach ($plans as $key => $plan) {
            $order = $plan->order;
            $info_lo_sx =  $plan->info_losx;
            $obj = new stdClass;
            $obj->lo_sx = $plan->lo_sx ?? "-";
            $obj->lot_id = $info_lo_sx->lot_id ?? "-";
            $obj->phan_dinh = $info_lo_sx->phan_dinh ?? "-";
            $obj->san_luong = $info_lo_sx->sl_dau_ra_hang_loat ?? 0;
            $obj->so_luong = $info_lo_sx->sl_dau_ra_hang_loat ?? 0;
            $obj->sl_dau_ra_hang_loat = $info_lo_sx->sl_dau_ra_hang_loat ?? "-";
            $obj->sl_ok = $info_lo_sx ? $info_lo_sx->sl_dau_ra_hang_loat - $info_lo_sx->sl_ng_sx - $info_lo_sx->sl_ng_qc : "-";
            $obj->sl_ng_sx = $info_lo_sx->sl_ng_sx ?? "-";
            $obj->sl_ng_qc = $info_lo_sx->sl_ng_qc ?? "-";
            $obj->status = $info_lo_sx->status ?? 0;
            $obj->dinh_muc = $plan->sl_kh ?? "-";
            $obj->san_luong_kh = $plan->sl_kh ?? "-";
            $obj->quy_cach = $order ? ($order->dai . 'x' . $order->rong . ($order->cao ? 'x' . $order->cao : "")) : "";
            $obj->quy_cach_kh = $order ? (!$order->kich_thuoc ? ($order->length . 'x' . $order->width . ($order->height ? ('x' . $order->height) : "")) : $order->kich_thuoc) : "";
            $obj->plan_id = $plan->id ?? "";
            $obj->khach_hang = $order->short_name ?? "";
            $obj->dai_tam = $order->dai_tam ?? "";
            $obj->tmo = $order->tmo ?? "";
            $obj->style = $order->style ?? "";
            $obj->style_no = $order->style_no ?? "";
            $obj->color = $order->color ?? "";
            $obj->mdh = $order->mdh ?? "";
            $obj->so_lop = $order->buyer->so_lop ?? "";
            $obj->mql = $order->mql ?? "";
            $obj->po = $order->po ?? "";
            $obj->xuong_giao = $order->xuong_giao ?? "";
            $obj->dai = $order->dai ?? "";
            $obj->rong = $order->rong ?? "";
            $obj->cao = $order->cao ?? "";
            $obj->dot = $order->dot ?? "";
            $obj->so_dao = $obj->dinh_muc ?? "";
            $obj->kho_tong = $order->kho_tong ?? "";
            $obj->kho = $order->kho ?? "";
            $obj->so_ra = $order->so_ra ?? "";
            $obj->note_3 = $order->note_3 ?? "";
            $obj->order_kh = $order->order ?? "";
            $obj->nhan_vien_sx = $info_lo_sx->user->name ?? "";
            $obj->slg_sx = $order->sl;
            $qr = new stdClass();
            $qr->lo_sx = $plan->lo_sx ?? "";
            $qr->so_luong = $info_lo_sx ? ($info_lo_sx->sl_dau_ra_hang_loat - $info_lo_sx->sl_ng_sx - $info_lo_sx->sl_ng_qc) : "-";
            $obj->qr_code = json_encode($qr);
            $data[] = $obj;
        }
        return $data;
    }

    public function gluingList($infos)
    {
        $data = [];
        foreach ($infos as $info) {
            $parent = $info->parent;
            $order = null;
            $plan = null;
            if ($parent) {
                if ($parent->plan && $parent->plan->order) {
                    $order = $parent->plan->order;
                    $plan = $parent->plan;
                } elseif ($parent->tem && $parent->tem->order) {
                    $order = $parent->tem->order;
                }
            }
            $obj = new stdClass;
            $obj->lo_sx = $info->lo_sx ?? "";
            $obj->phan_dinh = $info->phan_dinh ?? "";
            $obj->san_luong = $info->sl_dau_ra_hang_loat ?? 0;
            $obj->so_luong = $info->sl_dau_ra_hang_loat ?? 0;
            $obj->sl_dau_ra_hang_loat = $info->sl_dau_ra_hang_loat ?? "";
            $obj->sl_ok = $info ? $info->sl_dau_ra_hang_loat - $info->sl_ng_sx - $info->sl_ng_qc : "";
            $obj->sl_ng_sx = $info->sl_ng_sx ?? "";
            $obj->sl_ng_qc = $info->sl_ng_qc ?? "";
            $obj->status = $info->status ?? 0;
            $obj->san_luong_kh = $plan->sl_kh ?? "";
            $obj->quy_cach = $order ? ($order->dai . 'x' . $order->rong . ($order->cao ? 'x' . $order->cao : "")) : "";
            $obj->quy_cach_kh = $order ? (!$order->kich_thuoc ? ($order->length . 'x' . $order->width . ($order->height ? ('x' . $order->height) : "")) : $order->kich_thuoc) : "";
            $obj->plan_id = $plan->id ?? "";
            $obj->khach_hang = $order->short_name ?? "";
            $obj->dai_tam = $order->dai_tam ?? "";
            $obj->tmo = $order->tmo ?? "";
            $obj->style = $order->style ?? "";
            $obj->style_no = $order->style_no ?? "";
            $obj->color = $order->color ?? "";
            $obj->mdh = $order->mdh ?? "";
            $obj->so_lop = $order->buyer->so_lop ?? "";
            $obj->mql = $order->mql ?? "";
            $obj->po = $order->po ?? "";
            $obj->xuong_giao = $order->xuong_giao ?? "";
            $obj->dai = $order->dai ?? "";
            $obj->rong = $order->rong ?? "";
            $obj->cao = $order->cao ?? "";
            $obj->dot = $order->dot ?? "";
            $obj->so_dao = $obj->dinh_muc ?? "";
            $obj->kho_tong = $order->kho_tong ?? "";
            $obj->kho = $order->kho ?? "";
            $obj->so_ra = $order->so_ra ?? "";
            $obj->note_3 = $order->note_3 ?? "";
            $obj->order_kh = $order->order ?? "";
            $obj->nhan_vien_sx = $info->user->name ?? "";
            $obj->slg_sx = $order ? $order->sl : ($info->tem->so_luong ?? "");
            $qr = new stdClass();
            $qr->lo_sx = $info->lo_sx ?? "";
            $qr->so_luong = $info ? ($info->sl_dau_ra_hang_loat - $info->sl_ng_sx - $info->sl_ng_qc) : "";
            $obj->qr_code = json_encode($qr);
            $data[] = $obj;
        }
        return $data;
    }

    public function inTem(Request $request)
    {
        $machine = Machine::with('line')->find($request->machine_id);
        $data = [];
        if (isset($request->ids)) {
            $info_cong_doan = InfoCongDoan::whereIn('id', $request->ids)->where('machine_id', $request->machine_id)->get();
            foreach ($info_cong_doan as $k => $info) {
                $plan = $info->plan;
                if ($machine->line->id === "30") {
                    $data[] = $this->formatTemSong($info, $plan, $request->user());
                } elseif ($machine->line->id === "31" || $machine->line->id === "32") {
                    $data[] = $this->formatTemInDan($info, $plan, $request->user());
                }
            }
            return $this->success($data);
        } else {
            return $this->success([]);
        }
    }

    function formatTemInDan($info, $plan, $user)
    {
        $obj = new stdClass;
        $obj->lot_id = $info->lot_id;
        $obj->khach_hang = $plan->order->customer->name ?? "";
        $obj->order_id = $plan->order->mdh;
        $obj->quy_cach = $plan->order->dai . 'x' . $plan->order->rong . 'x' . $plan->order->cao;
        $obj->so_luong = $info->sl_dau_ra_hang_loat;
        $obj->order = $plan->order;
        $obj->mql = $plan->order->mql;
        $obj->gmo = $plan->order->layout->gmo;
        $obj->po = $plan->order->layout->po;
        $obj->style = $plan->order->layout->style;
        $obj->style_no = $plan->order->layout->style_no;
        $obj->color = $plan->order->layout->color;
        $obj->ngay_sx = date('d/m/Y ', strtotime($plan->ngay_sx)) . date('H:i:s');
        $obj->nhan_vien_sx = $user->name ?? "";
        $obj->ghi_chu = $plan->ghi_chu;
        $obj->machine_id = $info->machine_id;
        $obj->ca_sx = date('H') >= 7 && date('H') <= 19 ? 'Ca 1' : "Ca 2";
        return $obj;
    }
    function formatTemSong($info, $plan, $user)
    {
        $obj = new stdClass;
        $obj->lot_id = $info->lot_id;
        $obj->khach_hang = $plan->order->customer->name ?? "";
        $obj->order_id = $plan->order->mdh;
        $obj->quy_cach = $plan->order->dai . 'x' . $plan->order->rong . 'x' . $plan->order->cao;
        $obj->lo_sx = $info->lo_sx;
        $obj->so_luong = $info->sl_dau_ra_hang_loat;
        $obj->dai = $plan->order->dai;
        $obj->rong = $plan->order->rong;
        $obj->cao = $plan->order->cao;
        $obj->kho = $plan->order->kho;
        $obj->so_dao = $plan->order->so_dao;
        $obj->so_lop = $plan->order->buyer->so_lop;
        $obj->nhom_may = $plan->nhom_may;
        $obj->ngay_sx = $plan->ngay_sx ? date('d/m/Y ', strtotime($plan->ngay_sx)) . date('H:i:s') : "";
        $obj->ghi_chu = $plan->ghi_chu;
        $obj->nhan_vien_sx = $user->name ?? "";
        $obj->ca_sx = date('H') >= 7 && date('H') <= 19 ? 'Ca 1' : "Ca 2";
        return $obj;
    }

    public function scan(Request $request)
    {
        $tracking = Tracking::where('machine_id', $request->machine_id)->first();
        $machine = Machine::with('line')->find($request->machine_id);
        $tem = Tem::where('lo_sx', $request->lo_sx)->first();
        if (!$tem) {
            return $this->failure('', 'Không có KHSX');
        }
        if (empty($request->so_luong)) {
            return $this->failure('', 'Không có số lượng');
        }
        $check = InfoCongDoan::where('lo_sx', $request->lo_sx)->where('machine_id', $request->machine_id)->first();
        if ($check) {
            return $this->failure('', 'Đã quét lô này');
        }
        $previousQCLog = QCLog::where('lo_sx', $request->lo_sx)->orderBy('updated_at', 'DESC')->first();
        if ($previousQCLog) {
            if (isset($previousQCLog->info['phan_dinh'])) {
                if ($previousQCLog->info['phan_dinh'] === 2) {
                    return $this->failure('', 'Lô ' . $request->lo_sx . ' bị NG');
                }
            } else {
                return $this->failure('', 'Lô ' . $request->lo_sx . ' chưa qua QC');
            }
        }
        try {
            $tracking->update([
                'lo_sx' => $tem->lo_sx,
                'sl_kh' => $request->so_luong,
                'thu_tu_uu_tien' => $tem->ordering,
                'is_running' => 1,
                'pre_counter' => 0,
                'error_counter' => 0,
            ]);
            InfoCongDoan::create([
                'lo_sx' => $request->lo_sx,
                'machine_id' => $request->machine_id,
                'dinh_muc' => $request->so_luong,
                'sl_dau_ra_hang_loat' => 0,
                'thoi_gian_bat_dau' => date('Y-m-d H:i:s'),
                'ngay_sx' => date('Y-m-d'),
                'nhan_vien_sx' => $request->user()->id ?? null,
                'status' => 1,
                'order_id' => $tem->order_id ?? null
            ]);
            InfoCongDoan::where('lo_sx', '!=', $request->lo_sx)->where('machine_id', $request->machine_id)->where('status', 1)->update(['status' => 0]);
        } catch (\Throwable $th) {
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
        return $this->success([]);
    }

    public function scanManual(Request $request)
    {
        if (!isset($request->lo_sx) && !isset($request->so_luong)) {
            return $this->failure('', 'Không quét được');
        }
        $tem = Tem::where('lo_sx', $request->lo_sx)->first();
        if (!$tem) {
            return $this->failure('', 'Không có KHSX');
        }
        $previousQCLog = QCLog::where('lo_sx', $request->lo_sx)->orderBy('updated_at', 'DESC')->first();
        if ($previousQCLog) {
            if (isset($previousQCLog->info['phan_dinh'])) {
                if ($previousQCLog->info['phan_dinh'] === 2) {
                    return $this->failure('', 'Lô ' . $request->lo_sx . ' bị NG');
                }
            } else {
                return $this->failure('', 'Lô ' . $request->lo_sx . ' chưa qua QC');
            }
        }
        $info_lo_sx = InfoCongDoan::where(['lo_sx' => $request->lo_sx, 'machine_id' => $request->machine_id])->first();
        if (!$info_lo_sx) {
            $info_lo_sx = InfoCongDoan::create([
                'lo_sx' => $request->lo_sx,
                'machine_id' => $request->machine_id,
                'dinh_muc' => $request->so_luong,
                'sl_dau_ra_hang_loat' => $request->so_luong,
                'thoi_gian_bat_dau' => date('Y-m-d H:i:s'),
                'ngay_sx' => date('Y-m-d'),
                'nhan_vien_sx' => $request->user()->id ?? null,
                'status' => 2,
                'order_id' => $tem->order_id ?? null
            ]);
        }
        return $this->success($info_lo_sx);
    }

    public function manualInput(Request $request)
    {
        $input = $request->all();
        $info_cong_doan = InfoCongDoan::where('lo_sx', $input['lo_sx'])->where('machine_id', $input['machine_id'])->first();
        if (!$info_cong_doan) {
            $info_cong_doan = new InfoCongDoan();
            $info_cong_doan->lo_sx = $input['lo_sx'];
            $info_cong_doan->machine_id = $input['machine_id'];
            $info_cong_doan->thoi_gian_bat_dau = date('Y-m-d H:i:s');
            $info_cong_doan->status = 2;
        }
        $info_cong_doan->sl_dau_ra_hang_loat = $input['san_luong'];
        $info_cong_doan->thoi_gian_ket_thuc = date('Y-m-d H:i:s');
        if ($info_cong_doan->sl_dau_ra_hang_loat >= $info_cong_doan->dinh_muc) {
            $info_cong_doan->status = 3;
        }
        if (isset($input['sl_ng_sx'])) {
            $info_cong_doan->sl_ng_sx = $input['sl_ng_sx'];
        }
        $info_cong_doan->step = $input['step'];
        $info_cong_doan->save();
        return $this->success($info_cong_doan, 'Đã cập nhật sản lượng');
    }

    public function manualList(Request $request)
    {
        $info_lo_sx = InfoCongDoan::with('tem.order', 'user', 'plan.order')
            ->where('machine_id', $request->machine_id)
            ->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))
            ->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)))
            ->orderBy('created_at', 'DESC')
            ->get();
        $data = [];
        // return $info_lo_sx;
        foreach ($info_lo_sx as $info) {
            $obj = null;
            $order = null;
            $record = $info->plan;
            if ($record) {
                $obj = $record;
            } else if ($info->tem) {
                $obj = $info->tem;
            }
            if (is_null($obj)) continue;
            $obj->lo_sx = $info->lo_sx ?? "-";
            $obj->phan_dinh = $info->phan_dinh ?? "-";
            $obj->san_luong = $info->sl_dau_ra_hang_loat ?? 0;
            $obj->sl_dau_ra_hang_loat = $info->sl_dau_ra_hang_loat ?? "-";
            $obj->sl_ok = $info ? $info->sl_dau_ra_hang_loat - $info->sl_ng_sx - $info->sl_ng_qc : 0;
            $obj->sl_ng_sx = $info->sl_ng_sx ?? "-";
            $obj->sl_ng_qc = $info->sl_ng_qc ?? "-";
            $obj->status = $info->status ?? 0;
            $obj->dinh_muc = $info->dinh_muc ?? "-";
            $obj->san_luong_kh = $info->plan->sl_kh ?? "-";
            $obj->quy_cach = $obj->order ? $obj->order->dai . 'x' . $obj->order->rong . ($obj->order->cao ? ('x' . $obj->order->cao) : "") : "";
            $obj->quy_cach_kh = $obj->order ? (!$obj->order->kich_thuoc ? ($obj->order->length . 'x' . $obj->order->width . ($obj->order->height ? ('x' . $obj->order->height) : "")) : $obj->order->kich_thuoc) : "";
            $obj->khach_hang = $obj->order->short_name ?? "-";
            $obj->mql = $obj->order->mql ?? "-";
            $obj->xuong_giao = $obj->order->xuong_giao ?? "-";
            $json = ['lo_sx' => $info->lo_sx, 'so_luong' => $obj->sl_ok];
            $obj->qr_code = json_encode($json);
            $obj->machine_id = $request->machine_id;
            $obj->sl_tem = 1;
            $obj->step = $info->step;
            $obj->nhan_vien_sx = $info->user->name ?? "";
            $obj->so_luong = $obj->sl_ok;
            $obj->order_kh = $obj->order->order ?? '';
            $obj->dot = $obj->order->dot ?? '';
            $obj->note = $obj->order->note_3 ?? '';
            $obj->slg_sx = $obj->order->sl ?? '';
            $data[] = $obj;
        }
        return $this->success($data);
    }

    public function manualPrintStamp(Request $request)
    {
        $input = $request->all();
        $info_lo_sx = InfoCongDoan::with('plan.order.customer', 'tem', 'user')->whereIn('lo_sx', $input['ids'])->where('machine_id', $input['machine_id'])->get();
        $data = [];
        foreach ($info_lo_sx as $info) {
            $tm = [
                'lo_sx' => $info->lo_sx,
                'khach_hang' => $info->plan->order->customer->name ?? "",
                'mdh' => $info->plan->order->mdh ?? "",
                'quy_cach' => $info->plan ? $info->plan->dai . 'x' . $info->plan->rong . 'x' . $info->plan->cao : $info->tem->quy_cach ?? "",
                'so_luong' => $info->sl_dau_ra_hang_loat ?? "",
                'mql' => $info->plan->order->mql ?? $info->tem->mql ?? "",
                'order' => $info->plan->order->order ?? $info->tem->order ?? "",
                'po' => $info->plan->order->po ?? $info->tem->po ?? "",
                'style' => $info->plan->order->style ?? $info->tem->style ?? "",
                'style_no' => $info->plan->order->style_no ?? $info->tem->style_no ?? "",
                'color' => $info->plan->order->color ?? $info->tem->color ?? "",
                'nhan_vien_sx' => $info->user->name ?? "",
                'machine_id' => $info->machine_id ?? "",
                'note' => $info->plan->order->note3 ?? $info->tem->note ?? "",
            ];
            $split_pallet = $this->splitNumberIntoArray($info->sl, $input['dinh_muc_pallet']);
            foreach ($split_pallet as $key => $dinh_muc) {
                $json = ['lo_sx' => $info->lo_sx, 'pallet' => 'PL' . ($key + 1), 'so_luong' => $dinh_muc];
                $tm['qr_code'] = json_encode($json);
                $data = $tm;
            }
        }
        return $this->success($data);
    }

    public function getManufactureOverall(Request $request)
    {
        $machine = Machine::with('line')->where('id', $request->machine_id)->first();
        if (!$machine) return $this->failure([
            'kh_ca' => 0,
            'san_luong' => 0,
            'ti_le_ca' => 0,
            'tong_phe' => 0
        ], 'Không tìm thấy máy');
        if ($machine->is_iot == 1) {
            $info_cong_doan = InfoCongDoan::where('machine_id', $machine->id)
                ->whereDate('ngay_sx', '>=', date('Y-m-d', strtotime($request->start_date)))
                ->whereDate('ngay_sx', '<=', date('Y-m-d', strtotime($request->end_date)))
                ->get();
            $san_luong = 0;
            $tong_phe = 0;
            $sl_kh = 0;
            switch ($machine->line_id) {
                case '30':
                    $sl_kh = ProductionPlan::whereDate('ngay_sx', '>=', date('Y-m-d', strtotime($request->start_date)))
                        ->whereDate('ngay_sx', '<=', date('Y-m-d', strtotime($request->end_date)))->sum('sl_kh');
                    foreach ($info_cong_doan as $info) {
                        $san_luong += $info->sl_dau_ra_hang_loat;
                        $tong_phe += $info->sl_ng_sx + $info->sl_ng_qc;
                    }
                    break;
                default:
                    $sl_kh = $info_cong_doan->sum('dinh_muc');
                    foreach ($info_cong_doan as $info) {
                        $san_luong += $info->sl_dau_ra_hang_loat - $info->sl_ng_sx - $info->sl_ng_qc;
                        $tong_phe += $info->sl_ng_sx + $info->sl_ng_qc;
                    }
                    break;
            }
            $data = [
                'kh_ca' => $sl_kh,
                'san_luong' => $san_luong,
                'ti_le_ca' => $sl_kh ? floor(($san_luong / $sl_kh) * 100) : 0,
                'tong_phe' => $tong_phe
            ];
        } else {
            $info_cong_doan = InfoCongDoan::where('machine_id', $machine->id)
                ->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))
                ->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)))
                ->get();
            $san_luong = 0;
            $sl_kh = 0;
            $sl_dau_ra = 0;
            foreach ($info_cong_doan as $info) {
                $sl_kh += $info->dinh_muc;
                $san_luong += $info->sl_dau_ra_hang_loat - $info->sl_ng_sx - $info->sl_ng_qc;
                $sl_dau_ra += $info->sl_dau_ra_hang_loat;
            }
            $data = [
                'kh_ca' => $sl_kh,
                'san_luong' => $san_luong,
                'ti_le_ca' => $sl_kh ? floor(($san_luong / $sl_kh) * 100) : 0,
                'tong_phe' => $sl_dau_ra - $san_luong
            ];
        }
        return $this->success([$data]);
    }

    //===============QC===================
    public function checkUserPermission(Request $request)
    {
        $user = $request->user();
        $roles = $user->roles;
        $permissions = [];
        foreach ($roles as $role) {
            foreach ($role->permissions as $permission) {
                $permissions[] = $permission->slug;
            }
        }
        return $this->success($permissions);
    }
    public function getQCLine(Request $request)
    {
        $user = $request->user();
        $role = $user->roles()->first();
        $role = Role::with('machine.line', 'permissions')->where('id', $role->id)->first();
        $permissions = $role->permissions()->pluck('slug')->toArray();
        $qc_permission = array_intersect(['iqc', 'pqc', 'oqc'], $permissions);
        $song_machine_ids = Machine::where('line_id', Line::LINE_SONG)->whereNull('parent_id')->where('is_iot', 1)->pluck('id');
        $in_machine_ids = Machine::where('line_id', Line::LINE_IN)->where('is_iot', 1)->pluck('id');
        $dan_machine_ids = Machine::where('line_id', Line::LINE_DAN)->where('is_iot', 1)->pluck('id');
        // $xalot_machine_ids = Machine::where('line_id', Line::LINE_IN)->pluck('id');
        $data = [];
        if ($qc_permission) {
            if (in_array('iqc', $permissions)) {
                $data[] = ['label' => 'IQC', 'value' => 'iqc', 'machine' => []];
            }
            if (in_array('pqc', $permissions)) {
                if (count($song_machine_ids)) {
                    $data[] = ['label' => 'Sóng', 'value' => 'song', 'machine' => $song_machine_ids];
                }
                if (count($in_machine_ids)) {
                    $data[] = ['label' => 'In', 'value' => 'in', 'machine' => $in_machine_ids];
                }
            }
            if (in_array('oqc', $permissions) && count($dan_machine_ids) > 0) {
                $data[] = ['label' => 'OQC', 'value' => 'oqc', 'machine' => $dan_machine_ids];
            }
            return $this->success($data);
        } else {
            return $this->listMachine($request);
        }
        return $this->success($data);
    }

    public function findSpec($test_criteria, $plan = null, $tieu_chuan = [])
    {
        if ($test_criteria->hang_muc === 'tinh_nang') {
            if (str_contains($test_criteria->tieu_chuan, "±") && $plan) {
                $order = $plan->order;
                if ((str_contains($test_criteria->tieu_chuan, 'W') && !isset($order->rong)) || (str_contains($test_criteria->tieu_chuan, 'H') && !isset($order->cao)) || (str_contains($test_criteria->tieu_chuan, 'L') && !isset($order->dai))) {
                    return [];
                }
                $query = str_replace('cm', '', $test_criteria->tieu_chuan);
                $query = str_replace(',', '.', $query);
                $query = str_replace('[', '(', $query);
                $query = str_replace(']', ')', $query);
                $query = str_replace('W', $order->rong ?? "", $query);
                $query = str_replace('H', $order->cao ?? "", $query);
                $query = str_replace('L', $order->dai ?? "", $query);
                $min_query = str_replace("±", '-', $query);
                $max_query = str_replace("±", '+', $query);
                try {
                    $min = eval('return ' . $min_query . ';');
                    $max = eval('return ' . $max_query . ';');
                } catch (\Throwable $th) {
                    return [];
                }
                return [$min, $max];
            } elseif ($test_criteria->tieu_chuan === "≥ L" && $plan) {
                $order = $plan->order;
                if ((str_contains($test_criteria->tieu_chuan, 'L') && !isset($order->dai))) {
                    return [];
                }
                return [$order->dai, null];
            } elseif ($test_criteria->tieu_chuan === "≥ yêu cầu khách hàng") {
                if (isset($tieu_chuan[Str::slug($test_criteria->name)])) {
                    $value = $tieu_chuan[Str::slug($test_criteria->name)];
                    return [$value, null];
                }
            } elseif ($test_criteria->tieu_chuan === "≥ Tiêu chuẩn giấy cuộn" && $tieu_chuan) {
                if (isset($tieu_chuan[Str::slug($test_criteria->name)])) {
                    $slug = Str::slug($test_criteria->name);
                    $value = [$tieu_chuan[$slug]['min'] ?? 0, $tieu_chuan[$slug]['max'] ?? null];
                    return $value;
                }
            }
        } else {
            if ($test_criteria->tieu_chuan === "≥ Tiêu chuẩn giấy cuộn" && $tieu_chuan) {
                $value = $tieu_chuan[$test_criteria->name];
                if (str_contains($test_criteria->nguyen_tac, '>')) {
                    $dinh_muc = filter_var(explode('>', $test_criteria->nguyen_tac)[1], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    return [$value, $dinh_muc];
                }
                return [$value, null];
            }
            if (str_contains($test_criteria->nguyen_tac, '>')) {
                $dinh_muc = filter_var(explode('>', $test_criteria->nguyen_tac)[1], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                return [0, $dinh_muc];
            }
        }
        return [];
    }
    public function findSpec2($test_criteria, $order = null, $tieu_chuan = [])
    {
        if ($test_criteria->popup_select) {
            $test_criteria->options = preg_split("/\r?\n/", $test_criteria->popup_select);
            $test_criteria->input_type = 'select';
            if (preg_match('/\b\p{Lu}+\b/u', $test_criteria->nguyen_tac, $matches)) {
                $test_criteria->deteminer_value = $matches[0] ?? null;
            }
        }
        if ($test_criteria->phan_dinh === 'Nhập số') {
            $test_criteria->input = true;
            $test_criteria->input_type = 'inputnumber';
        }
        if ($test_criteria->phan_dinh === 'Nhập phán định') {
            $test_criteria->input_type = 'radio';
        }
        if (str_contains($test_criteria->tieu_chuan, "±")) {
            $query = str_replace(
                ['cm', ',', '[', ']', 'W', 'H', 'L'],
                ['', '.', '(', ')', $order->rong ?? "", $order->cao ?? "", $order->dai ?? ""],
                $test_criteria->tieu_chuan
            );
            $min_query = str_replace("±", '-', $query);
            $max_query = str_replace("±", '+', $query);
            try {
                $min = eval('return ' . $min_query . ';');
                $max = eval('return ' . $max_query . ';');
            } catch (\Throwable $th) {
                return [];
            }
            $test_criteria->min = $min;
            $test_criteria->max = $max;
        } elseif ($test_criteria->tieu_chuan === "≥ L") {
            $test_criteria->min = $order->dai ?? 0;
            $test_criteria->max = null;
        } elseif ($test_criteria->tieu_chuan === "≥ Tiêu chuẩn giấy cuộn") {
            $slug = Str::slug($test_criteria->name);
            if (isset($tieu_chuan[$slug])) {
                $test_criteria->min = (float)$tieu_chuan[$slug]['min'] ?? 0;
                $test_criteria->max = $tieu_chuan[$slug]['max'] ?? null;
            }
        }
        if (str_contains($test_criteria->nguyen_tac, '>')) {
            $dinh_muc = filter_var(explode('>', $test_criteria->nguyen_tac)[1], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $test_criteria->min = 0;
            $test_criteria->max = (float)$dinh_muc;
        }
        return $test_criteria;
    }
    // public function getLoiTinhNangPQC(Request $request)
    // {
    //     $line_ids = [];
    //     $line_ids = Machine::whereIn('id', $request->machine)->pluck('line_id');
    //     // foreach ($machines  as $machine) {
    //     //     if ($machine->line) {
    //     //         $line_ids[] = $machine->line->id;
    //     //     }
    //     // }
    //     $tieu_chuan = null;
    //     $list = TestCriteria::whereIn('line_id', $line_ids)->where('hang_muc', 'tinh_nang')->get();
    //     $plan = ProductionPlan::where('lo_sx', $request->lo_sx)->first();
    //     $data = [];
    //     foreach ($list as $key => $value) {
    //         $value->key = $value->id;
    //         if ($value->phan_dinh === 'Nhập số') {
    //             $spec = $this->findSpec($value, $plan, $tieu_chuan);
    //             if (count($spec) === 2) {
    //                 $delta = $spec;
    //                 $value->min = $delta[0];
    //                 $value->max = $delta[1];
    //                 $value->input = true;
    //             }
    //             $data[] = $value;
    //         }
    //     }
    //     return $this->success($data);
    // }

    public function getLoiTinhNangPQCTest(Request $request)
    {
        $line_ids = [];
        $line_ids = Machine::whereIn('id', $request->machine)->pluck('line_id');
        // foreach ($machines  as $machine) {
        //     if ($machine->line) {
        //         $line_ids[] = $machine->line->id;
        //     }
        // }
        $tieu_chuan = null;
        $list = TestCriteria::whereIn('line_id', $line_ids)->where('hang_muc', 'tinh_nang')->get();
        $info_cong_doan = InfoCongDoan::with('plan', 'tem')->where('lo_sx', $request->lo_sx)->whereIn('machine_id', $request->machine)->first();
        $order = null;
        if (isset($info_cong_doan->plan)) {
            $order = $info_cong_doan->plan->order;
        }
        if (isset($info_cong_doan->tem)) {
            $order = $info_cong_doan->tem->order;
        }
        $data = [];
        foreach ($list as $key => $value) {
            $value->key = $value->id;
            $data[] = $this->findSpec2($value, $order, $tieu_chuan);
        }
        return $this->success($data);
    }

    public function getLoiTinhNangIQC(Request $request)
    {
        $tieu_chuan = null;
        if (isset($request->ma_ncc)) {
            $tieu_chuan = TieuChuanNCC::where("ma_ncc", $request->ma_ncc)->first();
        } else {
            $tieu_chuan = null;
        }
        $sorted_ids = ['IT-06', 'IT-03', 'IT-01', 'IT-05', 'IT-04', 'IT-02', 'IT-07'];
        $list = TestCriteria::where('line_id', 38)->where('hang_muc', 'tinh_nang')->get()->sortBy(function ($model) use ($sorted_ids) {
            return array_search($model->getKey(), $sorted_ids);
        });
        $data = [];
        foreach ($list as $key => $value) {
            $value->key = $value->id;
            $value = $this->findSpec2($value, null, $tieu_chuan['requirements'] ?? null);
            if ($value->id === 'IT-02' || $value->id === 'IT-04' || $value->id === 'IT-07') {
                $value->user_decide = true;
            }
            $data[] = $value;
        }
        return $this->success($data);
    }

    public function getLoiNgoaiQuanPQC(Request $request)
    {
        $test_criteria = TestCriteria::where('id', $request->error_id)->where('hang_muc', 'ngoai_quan')->first();
        if ($test_criteria) {
            $test_criteria = $this->findSpec2($test_criteria, null, null);
            return $this->success($test_criteria);
        } else {
            return $this->failure('', 'Không tìm thấy lỗi');
        }
    }

    public function getLoiNgoaiQuanIQC(Request $request)
    {
        if (isset($request->error_id)) {
            $test_criteria = TestCriteria::where('line_id', 38)->where('id', $request->error_id)->where('hang_muc', 'ngoai_quan')->first();
            if ($test_criteria) {
                $delta = $this->findSpec($test_criteria, null, null);
                if (count($delta) === 2) {
                    $test_criteria->min = (int)$delta[0];
                    $test_criteria->max = (int)$delta[1];
                }
                return $this->success($test_criteria);
            } else {
                return $this->failure('', 'Không tìm thấy lỗi');
            }
        } else {
            $test_criterias = TestCriteria::where('line_id', 38)->where('hang_muc', 'ngoai_quan')->get();
            foreach ($test_criterias as $test_criteria) {
                $test_criteria->input_type = 'radio';
            }
            return $this->success($test_criterias);
        }
    }

    public function saveQCResult(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $info_cong_doan = InfoCongDoan::where('machine_id', $input['machine_id'])->where('lo_sx', $input['lo_sx'])->first();
            $lsx_log = QCLog::where('machine_id', $input['machine_id'])->where('lo_sx', $input['lo_sx'])->first();
            if (!$lsx_log) {
                $lsx_log = new QCLog();
                $lsx_log->lo_sx = $input['lo_sx'];
                $lsx_log->machine_id = $input['machine_id'];
                $lsx_log->info = [];
                $lsx_log->save();
            }

            $log = $lsx_log->info;
            if (!$log) $log = [
                'thoi_gian_vao' => date('Y-m-d H:i:s'),
                'user_id' => $request->user()->id,
                'user_name' => $request->user()->name
            ];
            $phan_dinh = 0;
            $counter = 0;
            $is_ng = 0;
            $log = array_merge($log, $input['data']);
            foreach ($log as $key => $log_data) {
                if ($key === 'tinh_nang') {
                    $ng_counter = 0;
                    if ($log_data) {
                        foreach ($log_data ?? [] as $value) {
                            if ($value['result'] === 2) $ng_counter++;
                        }
                        $log['sl_tinh_nang'] = $ng_counter;
                        $info_cong_doan->sl_tinh_nang = $ng_counter;
                    }
                    if ($ng_counter > 0) {
                        $phan_dinh = 2;
                        $is_ng++;
                    } else {
                        $counter++;
                    }
                }
                if ($key === 'ngoai_quan') {
                    $ng_counter = 0;
                    if ($log_data) {
                        foreach ($log_data ?? [] as $value) {
                            if ($value['result'] === 2) $ng_counter++;
                        }
                        $log['sl_ngoai_quan'] = $ng_counter;
                        $info_cong_doan->sl_ngoai_quan = $ng_counter;
                    }
                    if ($ng_counter > 0) {
                        $phan_dinh = 2;
                        $is_ng++;
                    } else {
                        $counter++;
                    }
                }
                if ($key === 'sl_ng_qc') {
                    if ($log_data > 0) {
                        $info_cong_doan->sl_ng_qc = $log_data;
                    } else {
                        $counter++;
                    }
                }
                if ($is_ng === 0 && $counter === 2) {
                    $phan_dinh = 1;
                }
            }
            $log['phan_dinh'] = $phan_dinh;
            $lsx_log->info = $log;
            $lsx_log->save();
            $info_cong_doan->phan_dinh = $phan_dinh;
            if ($info_cong_doan->status !== 1 && $info_cong_doan->phan_dinh !== 0) {
                $info_cong_doan->status = 3;
            }
            $info_cong_doan->save();
            DB::commit();
            return $this->success($lsx_log);
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
    }

    public function pqcLotList(Request $request)
    {
        $input = $request->all();
        $customOrder = [0, 2, 1];

        $query = InfoCongDoan::with('plan.order.customer', 'qc_log', 'tem.order.customer')->select(
            '*',
            'sl_dau_ra_hang_loat as san_luong',
            DB::raw('sl_dau_ra_hang_loat - sl_ng_sx - sl_ng_qc as sl_ok'),
            DB::raw('sl_ng_sx + sl_ng_qc as sl_ng'),
        )
            ->where(function ($query) {
                $query->where('status', '>=', 2)
                    ->orWhere('status', 1)->where('sl_dau_ra_hang_loat', '>=', 100);
            })
            ->where('machine_id', $request->machine_id);
        if (isset($input['start_date']) && isset($input['end_date'])) {
            if ($request->machine_id === 'So01') {
                $query->whereDate('thoi_gian_ket_thuc', '>=', date('Y-m-d', strtotime($input['start_date'])))->whereDate('thoi_gian_ket_thuc', '<=', date('Y-m-d', strtotime($input['end_date'])));
            } else {
                $query->whereDate('ngay_sx', '>=', date('Y-m-d', strtotime($input['start_date'])))->whereDate('ngay_sx', '<=', date('Y-m-d', strtotime($input['end_date'])));
            }
        } else {
            $query->whereDate('ngay_sx', date('Y-m-d'));
        }
        $list = $query->orderByRaw("FIELD(phan_dinh, " . implode(',', $customOrder) . ")")->get();
        foreach ($list as $value) {
            $log = $value->qc_log;
            $order = null;
            if (isset($value->tem)) {
                $order = $value->tem->order;
            } else if (isset($value->plan)) {
                $order = $value->plan->order ?? null;
            }
            if (is_null($order)) {
                continue;
            }
            $value->khach_hang = $order->short_name ?? "-";
            $value->mdh = $order->mdh ?? "-";
            $value->mql = $order->mql ?? "-";
            $value->length = $order->length ?? "-";
            $value->width = $order->width ?? "-";
            $value->height = $order->height ?? "-";
            $value->quy_cach = $order ? (!$order->kich_thuoc ? ($order->length . 'x' . $order->width . ($order->height ? ('x' . $order->height) : "")) : $order->kich_thuoc) : "-";
            $value->checked_tinh_nang = isset($log->info['tinh_nang']);
            $value->checked_ngoai_quan = isset($log->info['ngoai_quan']);
            $value->checked_sl_ng = isset($log->info['sl_ng_qc']);
        }
        return $this->success($list);
    }

    public function pqcOverall(Request $request)
    {
        $input = $request->all();
        $customOrder = [0, 2, 1];
        $query = InfoCongDoan::select(
            '*',
            'sl_dau_ra_hang_loat as san_luong',
            DB::raw('sl_dau_ra_hang_loat - sl_ng_sx - sl_ng_qc as sl_ok'),
            DB::raw('sl_ng_sx + sl_ng_qc as sl_ng'),
        )
            ->where(function ($query) {
                $query->where('status', '>=', 2)
                    ->orWhere('status', 1)->where('sl_dau_ra_hang_loat', '>=', 100);
            })
            ->whereIn('machine_id', $request->machine ?? []);
        if (isset($input['start_date']) && isset($input['end_date'])) {
            $query->whereBetween('updated_at', [date('Y-m-d 00:00:00', strtotime($input['start_date'])), date('Y-m-d 23:59:59', strtotime($input['end_date']))]);
        } else {
            $query->whereBetween('updated_at', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')]);
        }
        $info_cong_doan = $query->orderByRaw("FIELD(phan_dinh, " . implode(',', $customOrder) . ")")->get();
        $data = [
            'sl_kiem_tra' => $info_cong_doan->sum('sl_dau_ra_hang_loat'),
            'sl_ok' => $info_cong_doan->sum('sl_dau_ra_hang_loat') - $info_cong_doan->sum('sl_ng_qc') - $info_cong_doan->sum('sl_qc_sx'),
            'sl_ng' => $info_cong_doan->sum('sl_ng_qc') + $info_cong_doan->sum('sl_qc_sx'),
        ];
        $data['ti_le'] = $data['sl_kiem_tra'] > 0 ? number_format($data['sl_ok'] / $data['sl_kiem_tra'] * 100)  . '%' : '0%';
        return $this->success([$data]);
    }

    public function iqcOverall(Request $request)
    {
        $input = $request->all();
        $customOrder = [0, 2, 1];
        $query = WareHouseMLTImport::orderByRaw("FIELD(iqc, " . implode(',', $customOrder) . ")");
        if (isset($input['start_date']) && isset($input['end_date'])) {
            $query->whereBetween('created_at', [date('Y-m-d 00:00:00', strtotime($input['start_date'])), date('Y-m-d 23:59:59', strtotime($input['end_date']))]);
        } else {
            $query->whereBetween('created_at', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')]);
        }
        $sl_kiem_tra = $query->count();
        $mtl_import = $query->get();
        $sl_ok = 0;
        $sl_ng = 0;
        foreach ($mtl_import as $mtl) {
            if ($mtl->iqc === 1) {
                $sl_ok += 1;
            }
            if ($mtl->iqc === 2) {
                $sl_ng += 1;
            }
        }
        $data = [
            'sl_kiem_tra' => $sl_kiem_tra,
            'sl_ok' => $sl_ok,
            'sl_ng' => $sl_ng,
        ];
        $data['ti_le'] = $data['sl_kiem_tra'] > 0 ? number_format($data['sl_ok'] / $data['sl_kiem_tra'] * 100)  . '%' : '0%';
        return $this->success([$data]);
    }

    public function iqcLotList(Request $request)
    {
        $input = $request->all();
        $customOrder = [0, 2, 1];
        $query = WareHouseMLTImport::orderByRaw("FIELD(iqc, " . implode(',', $customOrder) . ")");
        if (isset($input['start_date']) && isset($input['end_date'])) {
            $query->whereBetween('created_at', [date('Y-m-d 00:00:00', strtotime($input['start_date'])), date('Y-m-d 23:59:59', strtotime($input['end_date']))]);
        } else {
            $query->whereBetween('created_at', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')]);
        }
        $list = $query->get();
        foreach ($list as $value) {
            $value->ma_cuon_ncc = $value->ma_cuon_ncc;
            $value->ten_ncc = $value->supplier->name ?? "";
            $value->sl_ok = $value->log['sl_ok'] ?? 0;
            $value->sl_ng = $value->log['sl_ng'] ?? 0;
            $value->sl_tinh_nang = $value->log['sl_tinh_nang'] ?? 0;
            $value->sl_ngoai_quan = $value->log['sl_ngoai_quan'] ?? 0;
            $value->phan_dinh = $value->log['phan_dinh'] ?? 0;
            $value->sl_dau_ra_hang_loat = $value->so_kg ?? 0;
            $value->checked_tinh_nang = isset($value->log['tinh_nang']);
            $value->checked_ngoai_quan = isset($value->log['ngoai_quan']);
            $value->checked_sl_ng = isset($value->log['sl_ng']);
        }
        return $this->success($list);
    }

    public function saveIQCResult(Request $request)
    {
        $input = $request->all();
        // $lot = Lot::find($input['lot_id']);
        // if(!$lot) return $this->failure('', 'Không tìm thấy lot');
        $mtl_import = WareHouseMLTImport::where(DB::raw("TRIM(ma_cuon_ncc)"), $input['ma_cuon_ncc'])->first();
        $log = $mtl_import->log;
        if (!isset($log['thoi_gian_vao'])) {
            $log['thoi_gian_vao'] = date('Y-m-d H:i:s');
        }
        $log = array_merge(
            $log,
            $input['data'],
            ['user_id' => $request->user()->id, 'user_name' => $request->user()->name]
        );
        $phan_dinh = 0;
        $counter = 0;
        $is_ng = 0;
        foreach ($log as $key => $log_data) {
            if ($key === 'tinh_nang') {
                $ng_counter = 0;
                if ($log_data) {
                    foreach ($log_data ?? [] as $value) {
                        if ($value['result'] === 2) $ng_counter++;
                    }
                    $log['sl_tinh_nang'] = $ng_counter;
                }
                if ($ng_counter > 0) {
                    $phan_dinh = 2;
                    $is_ng++;
                } else {
                    $counter++;
                }
            }
            if ($key === 'ngoai_quan') {
                $ng_counter = 0;
                if ($log_data) {
                    foreach ($log_data ?? [] as $value) {
                        if ($value['result'] === 2) $ng_counter++;
                    }
                    $log['sl_ngoai_quan'] = $ng_counter;
                }
                if ($ng_counter > 0) {
                    $phan_dinh = 2;
                    $is_ng++;
                } else {
                    $counter++;
                }
            }
            if ($is_ng === 0 && $counter === 2) {
                $phan_dinh = 1;
                break;
            }
        }
        $log['phan_dinh'] = $phan_dinh;
        try {
            DB::beginTransaction();
            if ($phan_dinh === 1) {
                $material = Material::orderByraw('CHAR_LENGTH(id) DESC')->where('id', 'like', date('y') . '-%')->orderBy('id', 'DESC')->whereRaw('LENGTH(id) = 8')->first();
                $matches = $material ? explode('-', $material->id) : [0, 0];
                $material_input = $mtl_import->toArray();
                if (isset($matches[1])) {
                    $material_id = ($matches[0] ? $matches[0] : date('y')) . '-' . str_pad($matches[1] + 1, 5, '0', STR_PAD_LEFT);
                    $material_input['id'] = $material_id;
                    $material_input['so_kg_dau'] = $material_input['so_kg'];
                    $material_input['so_m_toi'] = floor($material_input['so_kg'] / ($material_input['kho_giay'] / 100) / ($material_input['dinh_luong'] / 1000));
                    $material = Material::create($material_input);
                    $mtl_import->material_id = $material_id;
                }
            }
            $mtl_import->log = $log;
            $mtl_import->iqc = $phan_dinh;
            $mtl_import->save();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Đã xảy ra lỗi');
        }

        return $this->success($mtl_import);
    }

    //================Machine===============

    // public function overallMachine(Request $request)
    // {
    //     $info_cong_doan = InfoCongDoan::select(
    //         '*',
    //         DB::raw('sl_dau_ra_hang_loat - sl_ng_sx - sl_ng_qc as sl_ok'),
    //         DB::raw('TIMEDIFF(thoi_gian_ket_thuc, thoi_gian_bam_may) as run_time'),
    //         DB::raw('TIMEDIFF(thoi_gian_ket_thuc, thoi_gian_bat_dau) as total_time'),
    //     )
    //         ->where('machine_id', $request->machine_id)
    //         ->whereBetween('created_at', [date('Y-m-d 00:00:00', strtotime($request->start_date)), date('Y-m-d 23:59:59', strtotime($request->end_date))])
    //         ->get();
    //     $plan = ProductionPlan::select('*', DB::raw('TIMEDIFF(ngay_giao_hang, ngay_sx) as plan_run_time'))
    //         ->where('machine_id', $request->machine_id)
    //         ->get();
    //     $machine_logs = MachineLog::select('*', DB::raw('TIMEDIFF(end_time, start_time) as tg_dung_may'))
    //         ->where('machine_id', $request->machine_id)->whereBetween('created_at', [date('Y-m-d 00:00:00', strtotime($request->start_date)), date('Y-m-d 23:59:59', strtotime($request->end_date))])
    //         ->get();
    //     $A = $plan->sum('plan_run_time') ? ($info_cong_doan->sum('run_time') / $plan->sum('plan_run_time')) * 100 : 0;
    //     $P = $info_cong_doan->sum('run_time') ? ($info_cong_doan->sum('sl_dau_ra_hang_loat') * 3600 / $info_cong_doan->sum('run_time')) * 100 : 0;
    //     $Q = $info_cong_doan->sum('sl_dau_ra_hang_loat') ? ($info_cong_doan->sum('sl_ok') / $info_cong_doan->sum('sl_dau_ra_hang_loat')) * 100 : 0;
    //     $OEE = ($A * $P * $Q) / 10000;
    //     $ti_le_van_hanh = $info_cong_doan->sum('total_time') ? ($info_cong_doan->sum('run_time') / $info_cong_doan->sum('total_time')) * 100 : 0;
    //     $thoi_gian_dung_may_tt = $machine_logs->sum('tg_dung_may');
    //     $thoi_gian_dung_may_kh = $plan->sum('tg_dung_may') ?? 0;
    //     return $this->success(['oee' => $OEE, 'ti_le_van_hanh' => $ti_le_van_hanh, 'thoi_gian_dung_may_tt' => $thoi_gian_dung_may_tt, 'thoi_gian_dung_may_kh' => $thoi_gian_dung_may_kh]);
    // }

    public function overallMachine(Request $request)
    {
        // Lấy mốc 7:30 sáng của ngày hiện tại
        $start_of_day = date('Y-m-d 07:30:00');
        $now = date('Y-m-d H:i:s');

        // Lấy thời gian dừng từ MachineLog
        $machine_logs = MachineLog::selectRaw('TIMESTAMPDIFF(SECOND, start_time, end_time) as total_time')
            ->where('machine_id', $request->machine_id)
            ->whereBetween('start_time', [date('Y-m-d 07:30:00'), date('Y-m-d 23:59:59')])
            ->get();

        // Tính tổng thời gian dừng
        $thoi_gian_dung = floor($machine_logs->sum('total_time') / 60); // Đổi giây sang phút
        $so_lan_dung = count($machine_logs);

        // Tính thời gian làm việc từ 7:30 sáng đến hiện tại
        $thoi_gian_lam_viec = floor((strtotime($now) - strtotime($start_of_day)) / 60); // Đổi giây sang phút

        // Tính thời gian chạy bằng thời gian làm việc - thời gian dừng
        $thoi_gian_chay = max(0, $thoi_gian_lam_viec - $thoi_gian_dung); // Đảm bảo không âm

        // Tính tỷ lệ vận hành
        $ty_le_van_hanh = floor(($thoi_gian_chay / max(1, $thoi_gian_lam_viec)) * 100); // Tính phần trăm

        return $this->success([
            'thoi_gian_chay' => $thoi_gian_chay,
            'thoi_gian_dung' => $thoi_gian_dung,
            'so_lan_dung' => $so_lan_dung,
            'ty_le_van_hanh' => $ty_le_van_hanh
        ]);
    }


    public function scanMapping(Request $request)
    {
        $machine = Machine::with('line')->find($request->machine_id);
        $loSX = ProductionPlan::where('lo_sx', $request->lo_sx)->where('machine_id', $request->machine_id)->first();
        $mapping = Mapping::where('lo_sx', $request->lo_sx)->where('machine_id', $request->machine_id)->first();
        if ($mapping->mapping) {
            return $this->success($loSX, 'Đã mapping. Vui lòng duyệt mẫu tại giao diện chất lượng');
        }
        if ($machine->line->id === Line::LINE_SONG) {
            //Lấy dl cuộn giấy
            //Lấy dl vị trí giấy
        } elseif ($machine->line->id === Line::LINE_IN) {
            switch ($request->type) {
                case 'layout':
                    if ($loSX->layout_id === $request->id) $mapping->layout_id = $request->id;
                    break;
                case 'color':
                    if ($loSX->color_id === $request->id) $mapping->color_id = $request->id;
                    break;
                case 'film':
                    if ($loSX->film_id === $request->id) $mapping->film_id = $request->id;
                    break;
                case 'khuon':
                    if ($loSX->khuon_id === $request->id) $mapping->khuon_id = $request->id;
                    break;
                default:
                    break;
            }
            $mapping->save();
            if ($mapping->layout_id && $mapping->color_id && $mapping->film_id && $mapping->khuon_id) {
                return $this->success($loSX, 'Vui lòng duyệt mẫu tại giao diện chất lượng');
            }
        } else {
            return $this->success($loSX, 'Vui lòng duyệt mẫu tại giao diện chất lượng');
        }
        return $this->success('');
    }

    public function getMappingList(Request $request)
    {
        $list = ProductionPlan::select('lo_sx', 'khach_hang', 'layout_id', 'order_id', 'mql')->where('machine_id', $request->machine_id)
            // ->whereDate('ngay_sx', date('Y-m-d'))
            ->get();
        $param_log = MachineParameterLogs::where('machine_id', $request->machine_id)->whereIn('lo_sx', $list->pluck('lo_sx'))->first();
        $column = [
            [
                'title' => 'Lô sx',
                'dataIndex' => 'lo_sx',
                'key' => 'lo_sx',
                'align' => 'center',
            ],
            [
                'title' => 'Khách hàng',
                'dataIndex' => 'khach_hang',
                'key' => 'khach_hang',
                'align' => 'center',
            ],
            [
                'title' => 'Mã layout',
                'dataIndex' => 'layout_id',
                'key' => 'layout_id',
                'align' => 'center',
            ],
            [
                'title' => 'Đơn hàng',
                'dataIndex' => 'order_id',
                'key' => 'order_id',
                'align' => 'center',
            ],
            [
                'title' => 'MQL',
                'dataIndex' => 'mql',
                'key' => 'mql',
                'align' => 'center',
            ],
        ];
        $params = MachineParameter::where('machine_id', $request->machine_id)->where('is_if', 1)->get();
        foreach ($params as $param) {
            array_push($column, ['title' => $param->name, 'dataIndex' => $param->parameter_id, 'key' => $param->parameter_id, 'align' => 'center'],);
        }
        $data = [];
        foreach ($list as $value) {
            $param_log = MachineParameterLogs::where('machine_id', $request->machine_id)->where('lo_sx', $value->lo_sx)->first();
            $mapping = Mapping::where('machine_id', $request->machine_id)->where('lo_sx', $value->lo_sx)->first();
            $value->mapping = $mapping ? ($mapping->mapping ?  true : false) : false;
            $data[] = array_merge($value->toArray(), $param_log->data_input ?? []);
        }
        return $this->success(['data' => $data, 'column' => $column]);
    }

    public function errorMachineLog(Request $request)
    {
        $query = MachineLog::with('user', 'error_machine')->where('machine_id', $request->machine_id);
        if (isset($request->start_date) && isset($request->end_date)) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)));
        }
        $records = $query->orderBy('start_time')->get();
        $data = [];
        foreach ($records as $key => $record) {
            $obj = $record ?? new stdClass;
            $obj->start_time = $record->start_time;
            $obj->end_time = $record->end_time;
            $obj->code = $record->error_machine->code ?? "";
            $obj->ten_su_co = $record->error_machine->ten_su_co ?? "";
            $obj->nguyen_nhan = $record->error_machine->nguyen_nhan ?? "";
            $obj->cach_xu_ly = $record->error_machine->cach_xu_ly ?? "";
            $data[] = $obj;
        }
        return $this->success($data);
    }

    public function errorMachineList(Request $request)
    {
        $machine = Machine::with('line')->where('id', $request->machine_id)->first();
        if (!$machine) return $this->failure('Không tìm thấy máy');
        $errors = ErrorMachine::select('*', 'code as value', 'ten_su_co as label')->where('line_id', $machine->line_id)->get();
        // if (!$errors) return $this->failure('', 'Không tìm thấy lỗi');
        return $this->success($errors);
    }

    public function errorMachineDetail(Request $request)
    {
        $record = ErrorMachine::where('code', $request->code)->first();
        if (!$record) return $this->failure('', 'Không tìm thấy lỗi');
        return $this->success($record);
    }

    public function errorMachineResult(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            if (isset($input['code'])) {
                $error_machine = ErrorMachine::where('code', $input['code'])->first();
            } else {
                $latest_error_machine = ErrorMachine::latest()->first();
                $index = $latest_error_machine ? $latest_error_machine->id : 0;
                $machine = Machine::find($input['machine_id']);
                $input['line_id'] = $machine->line_id;
                $input['code'] = 'SC-' . ($index + 1);
                $error_machine = ErrorMachine::create($input);
            }
            MachineLog::find($input['id'])->update(['error_machine_id' => $error_machine->id, 'user_id' => $request->user()->id, 'handle_time' => date('Y-m-d H:i:s')]);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
        return $this->success('');
    }

    public function getMachineParameters(Request $request)
    {
        $input = $request->all();
        $role = $request->user()->roles()->first();
        $machine_id = $role->machine_id;
        $machine = Machine::find($machine_id);
        if (!$machine) {
            return $this->failure([], 'Tài khoản không được phân máy');
        }
        if ($machine->line_id == Line::LINE_SONG) {
            $params = MachineParameter::select('name', 'parameter_id')->where('is_if', 0)->where('machine_id', $machine_id)->get();
        } else {
            $params = MachineParameter::select('name', 'parameter_id')->where('is_if', 0)->where('machine_id', 'like', '%' . $input['machine_id'] . '%')->get();
        }
        return $this->success($params);
    }
    public function saveMachineParameters(Request $request)
    {
        $input = $request->all();
        $input['user_id'] = $request->user()->id;
        $log = MachineParameterLogs::create($input);
        $record = LSXLog::where('lo_sx', $input['lo_sx'])->where('machine_id', $input['machine_id'])->first();
        $params = is_null($record->params) ? $input['data'] : array_merge($record->params, $input['data']);
        $record->params = $params;
        $record->save();
        return $this->success($record);
    }

    public function getExportMLTLogs(Request $request)
    {
        $records = WarehouseMLTLog::with('material')->orderBy('tg_xuat', 'DESC')->whereDate('tg_xuat', date('Y-m-d'))->get();
        $data = [];
        foreach ($records as $key => $record) {
            $obj = new stdClass();
            $obj->material_id = $record->material_id;
            $obj->ma_cuon_ncc = $record->material->ma_cuon_ncc ?? "";
            $obj->so_kg = $record->so_kg_xuat;
            $obj->vi_tri = $record->locator_id;
            $obj->loai_giay = $record->material->loai_giay ?? "";
            $obj->kho_giay = $record->material->kho_giay ?? "";
            $obj->dinh_luong = $record->material->dinh_luong ?? "";
            $obj->tg_xuat = $record->tg_xuat ? date('H:i:s', strtotime($record->tg_xuat)) : 0;
            $data[] = $obj;
        }
        return $this->success($data);
    }

    public function exportMLTScan(Request $request)
    {
        $material = Material::find($request->material_id);
        if (!$material) {
            return $this->failure('', 'Mã cuộn không tồn tại');
        }
        $material->material_id = $material->id;
        $warehouse_log = WarehouseMLTLog::where('material_id', $material->id)->whereNull('tg_xuat')->orderBy('updated_at', 'DESC')->first();
        if ($warehouse_log) {
            if (!$warehouse_log->locator_id === 'C13') {
                return $this->failure('', 'Không thể xuất cuộn ở khu 13');
            }
            if (!$warehouse_log->tg_xuat) {
                return $this->success($material);
            }
            return $this->failure('', 'Đã quét xuất');
        } else {
            return $this->failure('', 'Cuộn không có trong kho');
        }
    }

    public function updateExportMLTLogs(Request $request)
    {
        $input = $request->all();
        $material = Material::find($input['material_id']);
        if (!$material) {
            return $this->failure('', 'Mã cuộn không tồn tại');
        }
        $locator_mlt = LocatorMLT::find($input['locator_id']);
        if (!$locator_mlt) {
            return $this->failure('', 'Vị trí không phù hợp');
        }
        $inp['exporter_id'] = $request->user()->id;
        $inp['so_kg_xuat'] = $material->so_kg;
        $inp['tg_xuat'] = date('Y-m-d H:i:s');
        $inp['locator_id'] = $input['locator_id'];
        try {
            DB::beginTransaction();
            $material->update(['so_kg' => 0]);
            $warehouse_log = WarehouseMLTLog::where('material_id', $material->id)->orderBy('updated_at', 'DESC')->first();
            if ($warehouse_log) {
                $warehouse_log->update($inp);
            } else {
            }
            LocatorMLTMap::where('material_id', $input['material_id'])->delete();
            $capacity = $locator_mlt->capacity > 0 ? ($locator_mlt->capacity - 1) : 0;
            $locator_mlt->update(['capacity' => $capacity]);
            DB::commit();
            return $this->success($warehouse_log, 'Xuất cuộn thành công');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->failure([], 'Có lỗi xảy ra');
        }
    }

    public function exportMLTList(Request $request)
    {
        $records = WareHouseMLTExport::with('material')->get();
        $data = [];
        foreach ($records as $key => $record) {
            if (!isset($record->material)) {
                continue;
            }
            $obj = new stdClass();
            $obj->id = $record->id;
            $obj->locator_id = $record->locator_id;
            $obj->material_id = $record->material_id;
            $obj->dau_may = $record->position_name;
            $obj->time_need = $record->time_need ? date('d/m/Y H:i:s', strtotime($record->time_need)) : '';
            $obj->so_kg = $record->material->so_kg ?? 0;
            $obj->loai_giay = $record->material->loai_giay ?? "";
            $obj->kho_giay = $record->material->kho_giay ?? 0;
            $obj->status = $record->status;
            $data[] = $obj;
        }
        return $this->success($data);
    }

    public function exportMLTSave(Request $request)
    {
        $record = WareHouseMLTExport::with('material')->where('id', $request->get('id'))->first();
        $input['locator_id'] = $record->locator_id;
        $input['material_id'] = $record->material_id;
        $input['position_id'] = $record->position_id;
        $input['position_name'] = $record->position_name;
        $input['so_kg'] = $record->material->so_kg;
        $input['type'] = 2;
        $input['created_by'] = $request->user()->id;
        WarehouseMLTLog::create($input);
        LocatorMLTMap::where('locator_mlt_id', $record->locator_id)->where('material_id', $input['material_id'])->delete();
        $record->update(['status' => 1]);
        return $this->success('Xuất kho thành công');
    }
    //End OI

    public function getListMappingRequire(Request $request)
    {
        $lo_sx = $request->get('lo_sx');
        $role = $request->user()->roles()->first();
        $machine_id = $role->machine_id;
        $machine = Machine::find($machine_id);
        if (!$machine) {
            return $this->failure([], 'Tài khoản không được phân máy');
        }
        // $record = Mapping::where('lo_sx', $lo_sx)->where('machine_id', 'like', $machine_id . "%")->whereNull('user_id')->orderBy('position', 'ASC')->first();
        if ($machine->line_id == Line::LINE_SONG) {
            $plan = ProductionPlan::with('order.buyer')->where('lo_sx', $lo_sx)->first();
            $mapping = (array)json_decode($plan->order->buyer->mapping);
            if (isset($mapping[$machine->id])) {
                return $this->success($mapping[$machine->id]);
            } else {
                return $this->failure([], 'Vị trí không cần mapping');
            }
        }

        // if ($record && $record->user_id) {
        //     return $this->failure('', 'Đã mapping');
        // }
        // if (!$record) {
        //     return $this->failure('', 'Không có dữ liệu mapping');
        // }
        // return $this->success($record->info);
    }
    //UI

    //===================Manufacture====================
    public function lineList(Request $request)
    {
        $lines = Line::with('machine')->where('display', 1)->orderBy('ordering')->get();
        return $this->success($lines);
    }

    public function productionPlan(Request $request)
    {
        $query = ProductionPlan::with('order', 'machine.line', 'orders', 'creator:id,name')->orderBy('thu_tu_uu_tien')->orderBy('updated_at', 'DESC');
        if (isset($request->end_date) && isset($request->start_date)) {
            $query->whereDate('ngay_sx', '>=', date('Y-m-d', strtotime($request->start_date)))
                ->whereDate('ngay_sx', '<=', date('Y-m-d', strtotime($request->end_date)));
        } else {
            $query->whereDate('ngay_sx', '>=', date('Y-m-d'))
                ->whereDate('ngay_sx', '<=', date('Y-m-d'));
        }
        if (isset($request->machine)) {
            $query->whereIn('machine_id', $request->machine);
        }
        if (isset($request->lo_sx)) {
            $query->where('lo_sx', 'like', "%$request->lo_sx%");
        }
        if (isset($request->short_name)) {
            $query->whereHas('order', function ($plan_query) use ($request) {
                $plan_query->where('short_name', 'like', "%$request->short_name%");
            });
        }
        if (isset($request->mdh)) {
            if (is_array($request->mdh)) {
                $query->where(function ($plan_query) use ($request) {
                    foreach ($request->mdh as $key => $mdh) {
                        $plan_query->orWhere('order_id', 'like', "%$mdh%");
                    }
                });
            } else {
                $query->where('order_id', 'like', "%$request->mdh%");
            }
        }
        $list = $query->get();
        $data = [];
        foreach ($list as $key => $value) {
            $order = $value->order;
            $obj = new stdClass();
            // if (!$order) continue;
            $formula = DB::table('formulas')->where('phan_loai_1', $order->phan_loai_1 ?? null)->where('phan_loai_2', $order->phan_loai_2 ?? null)->first();
            $value->khach_hang = $order->short_name ?? "";
            $value->ket_cau_giay = $value->order->buyer->ket_cau_giay ?? "";
            $value->so_lop = $value->order->buyer->so_lop ?? "";
            $value->ghi_chu = $value->order->note_3 ?? "";
            $value->dot = $value->order->dot ?? "";
            $value->line_id = $value->machine->line->id ?? "";
            $value->san_luong_kh = $value->sl_kh ?? "";
            $value->so_luong = $value->sl_kh;
            $value->so_pallet = $value->ordering ?? "";
            $obj->lo_sx = $value->lo_sx;
            $obj->so_luong = $value->sl_kh;
            $obj->mql = $value->orders ? implode(',', $value->orders->pluck('mql')->toArray()) : '';
            $value->qr_code = json_encode($obj);
            $data[$key] = array_merge($value->toArray(), $order ? $order->toArray() : []);
            $data[$key]['mql'] = $value->orders ? implode(',', $value->orders->pluck('mql')->toArray()) : '';
            $data[$key]['so_dao'] = $data[$key]['so_ra'] ? ceil($data[$key]['sl_kh'] * ($formula->he_so ?? 1) / $data[$key]['so_ra']) : $data[$key]['so_dao'];
            $data[$key]['id'] = $value->id;
        }
        return $this->success($data);
    }

    public function produceOverall(Request $request)
    {
        $query = InfoCongDoan::latest('id');
        if ($request->machine) {
            $query->whereIn('machine_id', $request->machine);
        }
        if (isset($request->end_date) && isset($request->start_date)) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))
                ->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)));
        } else {
            $query->whereDate('created_at', '>=', date('Y-m-d'))
                ->whereDate('created_at', '<=', date('Y-m-d'));
        }
        if (isset($request->mdh)) {
            if (is_array($request->mdh)) {
                $plans_query = ProductionPlan::where(function ($plan_query) use ($request) {
                    foreach ($request->mdh as $key => $mdh) {
                        $plan_query->orWhere('order_id', 'like', "%$mdh%");
                    }
                });
                $tem = Tem::where(function ($tem_query) use ($request) {
                    foreach ($request->mdh as $key => $mdh) {
                        $tem_query->orWhere('mdh', 'like', "%$mdh%");
                    }
                });
                $lo_sx = array_merge($plans_query->pluck('lo_sx')->toArray(), $tem->pluck('lo_sx')->toArray());
                // return $plans_query->pluck('lo_sx')->toArray();
                $query->whereIn('lo_sx', $lo_sx);
            } else {
                $plans_query = ProductionPlan::where('order_id', 'like', "%$request->mdh%");
                $tem = Tem::where('mdh', 'like', "%$request->mdh%");
                $lo_sx = array_merge($plans_query->pluck('lo_sx')->toArray(), $tem->pluck('lo_sx')->toArray());
                $query->whereIn('lo_sx', $lo_sx);
            }
        }
        if (isset($request->customer_id)) {
            $plan = ProductionPlan::whereHas('order', function ($order_query) use ($request) {
                $order_query->where('customer_id', 'like', "%$request->customer_id%");
            });
            $tem = Tem::where('khach_hang', 'like', "%$request->customer_id%");
            $lo_sx = array_merge($plan->pluck('lo_sx')->toArray(), $tem->pluck('lo_sx')->toArray());
            $query->whereIn('lo_sx', $lo_sx);
        }
        if (isset($request->quy_cach)) {
            $plan = ProductionPlan::whereHas('order', function ($order_query) use ($request) {
                $order_query->where(DB::raw('CONCAT_WS("x", dai, rong, cao)'), 'like', "%$request->quy_cach%");
            });
            $tem = Tem::where('quy_cach', 'like', "%$request->quy_cach%");
            $lo_sx = array_merge($plan->pluck('lo_sx')->toArray(), $tem->pluck('lo_sx')->toArray());
            $query->whereIn('lo_sx', $lo_sx);
        }
        if (isset($request->lo_sx)) {
            $query->where('lo_sx', 'like', "%$request->lo_sx%");
        }
        $sl_kh = 0;
        $sl_ok = 0;
        $sl_phe = 0;
        $query->with("plan", "tem")->chunk(100, function ($infos) use ($sl_kh, $sl_ok, $sl_phe) {
            foreach ($infos as $info_cong_doan) {
                $sl_kh += $info_cong_doan->plan->sl_kh ?? $info_cong_doan->tem->so_luong ?? 0;
                $sl_ok += $info_cong_doan->sl_dau_ra_hang_loat - $info_cong_doan->sl_ng_qc - $info_cong_doan->sl_ng_sx;
                $sl_phe += $info_cong_doan->sl_ng_qc + $info_cong_doan->sl_ng_sx;
            }
        });
        $overall = [
            'sl_kh' => $sl_kh,
            'sl_ok' => $sl_ok,
            'chenh_lech' => $sl_ok - $sl_kh,
            'ty_le' => $sl_kh ? floor(($sl_ok / $sl_kh) * 100) : 0,
            'sl_phe' => $sl_phe
        ];
        return $this->success([$overall]);
    }

    public function producePercent(Request $request)
    {
        $query = InfoCongDoan::orderBy('created_at');
        if ($request->machine) {
            $query->whereIn('machine_id', $request->machine);
        }
        if (isset($request->end_date) && isset($request->start_date)) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))
                ->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)));
        } else {
            $query->whereDate('created_at', '>=', date('Y-m-d'))
                ->whereDate('created_at', '<=', date('Y-m-d'));
        }
        if (isset($request->mdh)) {
            if (is_array($request->mdh)) {
                $plans_query = ProductionPlan::where(function ($plan_query) use ($request) {
                    foreach ($request->mdh as $key => $mdh) {
                        $plan_query->orWhere('order_id', 'like', "%$mdh%");
                    }
                });
                $tem = Tem::where(function ($tem_query) use ($request) {
                    foreach ($request->mdh as $key => $mdh) {
                        $tem_query->orWhere('mdh', 'like', "%$mdh%");
                    }
                });
                $lo_sx = array_merge($plans_query->pluck('lo_sx')->toArray(), $tem->pluck('lo_sx')->toArray());
                $query->whereIn('lo_sx', $lo_sx);
            } else {
                $plans_query = ProductionPlan::where('order_id', 'like', "%$request->mdh%");
                $tem = Tem::where('mdh', 'like', "%$request->mdh%");
                $lo_sx = array_merge($plans_query->pluck('lo_sx')->toArray(), $tem->pluck('lo_sx')->toArray());
                $query->whereIn('lo_sx', $lo_sx);
            }
        }
        if (isset($request->customer_id)) {
            $plan = ProductionPlan::whereHas('order', function ($order_query) use ($request) {
                $order_query->where('customer_id', 'like', "%$request->customer_id%");
            });
            $tem = Tem::where('khach_hang', 'like', "%$request->customer_id%");
            $lo_sx = array_merge($plan->pluck('lo_sx')->toArray(), $tem->pluck('lo_sx')->toArray());
            $query->whereIn('lo_sx', $lo_sx);
        }
        if (isset($request->lo_sx)) {
            $query->where('lo_sx', 'like', "%$request->lo_sx%");
        }
        if (isset($request->quy_cach)) {
            $plan = ProductionPlan::whereHas('order', function ($order_query) use ($request) {
                $order_query->where(DB::raw('CONCAT_WS("x", dai, rong, cao)'), 'like', "%$request->quy_cach%");
            });
            $tem = Tem::whereHas('order', function ($order_query) use ($request) {
                $order_query->where(DB::raw('CONCAT_WS("x", dai, rong, cao)'), 'like', "%$request->quy_cach%");
            });
            $lo_sx = array_merge($plan->pluck('lo_sx')->toArray(), $tem->pluck('lo_sx')->toArray());
            $query->whereIn('lo_sx', $lo_sx);
        }
        $infos = $query->with("plan", "machine", "line")->get()->groupBy('lo_sx');
        $data = [];
        foreach ($infos as $key => $lo_sx) {
            foreach ($lo_sx as $info_cong_doan) {
                $line = $info_cong_doan->line;
                if ($line) {
                    if (!isset($data[$key])) $data[$key] = [];
                    if (!isset($data[$key][$line->id])) $data[$key][$line->id] = 0;
                    $data[$key][$line->id] += $info_cong_doan->sl_dau_ra_hang_loat - $info_cong_doan->sl_ng_sx - $info_cong_doan->sl_ng_qc;
                }
            }
        }
        return $this->success((array)$data);
    }

    function queryProduceHistory(Request $request)
    {
        $query = InfoCongDoan::orderBy('thoi_gian_bat_dau')->where('sl_dau_ra_hang_loat', '>', 0);
        if ($request->machine) {
            $query->whereIn('machine_id', $request->machine);
        }
        if ($request->lo_sx) {
            $query->where('lo_sx', $request->lo_sx);
        }
        if (isset($request->end_date) && isset($request->start_date)) {
            $start = date('Y-m-d', strtotime($request->start_date));
            $end = date('Y-m-d', strtotime($request->end_date));
        } else {
            $start = date('Y-m-d');
            $end = date('Y-m-d');
        }
        // $machine_iot = Machine::where('is_iot', 1)->whereIn('id', $request->machine)->pluck('id')->toArray();
        // $query->whereRaw("
        //     CASE 
        //         WHEN machine_id IN ('" . implode("','", $machine_iot) . "') THEN DATE(thoi_gian_bat_dau) BETWEEN ? AND ?
        //         ELSE DATE(created_at) BETWEEN ? AND ?
        //     END
        // ", [$start, $end, $start, $end]);
        $query->whereDate('thoi_gian_bat_dau', '>=', $start)->whereDate('thoi_gian_bat_dau', '<=', $end);
        $query->whereHas('order', function ($order_query) use ($request) {
            if (isset($request->customer_id)) {
                $order_query->where('short_name', 'like', "$request->customer_id%");
            }
            if (isset($request->mdh)) {
                $order_query->where(function ($q) use ($request) {
                    foreach ($request->mdh ?? [] as $key => $mdh) {
                        $q->orWhere('mdh', 'like', "%$mdh%");
                    }
                });
            }
            if (isset($request->mql)) {
                $order_query->whereIn('mql', $request->mql ?? []);
            }
            if (isset($request->quy_cach)) {
                $order_query->where(DB::raw('CONCAT_WS("x", dai, rong, cao)'), 'like', "%$request->quy_cach%");
            }
            if (isset($request->dot)) {
                $order_query->where('dot', $request->dot);
            }
        });
        $query->with("plan.order.customer", "line", "user", "tem.order");
        return $query;
    }
    public function produceHistory(Request $request)
    {
        // return $request->all();
        $query = $this->queryProduceHistory($request);
        // return $query->get();
        $totalPage = $query->count();
        $page = $request->page - 1;
        $pageSize = $request->pageSize;
        $infos = $query->offset($page * $pageSize)->limit($pageSize)->get();
        $data = [];
        foreach ($infos as $key => $info_cong_doan) {
            $plan = $info_cong_doan->plan;
            $tem = $info_cong_doan->tem;
            $order = null;
            if ($tem) {
                $order = $tem->order;
            } else if ($plan) {
                $order = $plan->order;
            }
            $obj = $info_cong_doan;
            $obj->khach_hang = $order->short_name ?? "";
            $obj->mdh = $order->mdh ?? "";
            $obj->mql = $order->mql ?? "";
            $obj->quy_cach = $order ? ($order->dai . 'x' . $order->rong . ($order->cao ? ('x' . $order->cao) : "")) : "";
            // $obj->quy_cach = $order ? (!$order->kich_thuoc ? ($order->length . 'x' . $order->width . ($order->height ? ('x' . $order->height) : $order->kich_thuoc)) : "") : "";
            $obj->sl_dau_vao_kh = $order->sl ?? "";
            $obj->sl_dau_ra_kh = $order->sl ?? "";
            $obj->sl_ok = $info_cong_doan->sl_dau_ra_hang_loat - $info_cong_doan->sl_ng_qc - $info_cong_doan->sl_ng_sx;
            $obj->sl_phe = $info_cong_doan->sl_ng_qc + $info_cong_doan->sl_ng_sx;
            $obj->ty_le_dau_ra_vao = $info_cong_doan->sl_dau_vao_hang_loat ? floor($info_cong_doan->sl_dau_ra_hang_loat / $info_cong_doan->sl_dau_vao_hang_loat * 100) : 0;
            $obj->ngay_sx = $info_cong_doan->thoi_gian_bat_dau ? date('d/m/Y', strtotime($info_cong_doan->thoi_gian_bat_dau)) : "";
            $obj->thoi_gian_bat_dau_kh = $plan && $plan->thoi_gian_bat_dau_kh ? date('H:i:s', strtotime($plan->thoi_gian_bat_dau_kh)) : "";
            $obj->thoi_gian_ket_thuc_kh = $plan && $plan->thoi_gian_ket_thuc_kh ? date('H:i:s', strtotime($plan->thoi_gian_ket_thuc_kh)) : "";
            $obj->thoi_gian_bat_dau = $info_cong_doan->thoi_gian_bat_dau ? date('H:i:s', strtotime($info_cong_doan->thoi_gian_bat_dau)) : "";
            $obj->thoi_gian_ket_thuc = $info_cong_doan->thoi_gian_ket_thuc ? date('H:i:s', strtotime($info_cong_doan->thoi_gian_ket_thuc)) : "";
            $obj->order_id = $order->id ?? "";
            $obj->layout_id = $plan->layout_id ?? "";
            $obj->cycle_time_kh = $plan->cycle_time_kh ?? "";
            $obj->chenh_lech = $info_cong_doan->sl_dau_ra_hang_loat - ($plan->sl_kh ?? 0);
            $obj->ty_le_ok = $info_cong_doan->sl_dau_ra_hang_loat ? floor($obj->sl_ok / $info_cong_doan->sl_dau_ra_hang_loat * 100) : 0;
            $obj->tt_thuc_te = 0;
            $obj->lead_time = 0;
            $obj->user_name = $info_cong_doan->user->name ?? "";
            $obj->dot = $order->dot ?? '';
            $data[] = $obj;
        }
        $res = [
            "data" => $data,
            "totalPage" => $totalPage,
        ];
        return $this->success($res);
    }

    public function deleteProductionHistory(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $info_cong_doan = InfoCongDoan::find($id);
            $info_cong_doan->delete();
            $info_cong_doan->qc_log()->delete();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        return $this->success('', 'Đã xoá thành công');
    }

    public function exportProduceHistory(Request $request)
    {
        $query = $this->queryProduceHistory($request);
        $infos = $query->get();
        $records = [];
        foreach ($infos as $key => $info_cong_doan) {
            $plan = $info_cong_doan->plan;
            $tem = $info_cong_doan->tem;
            $order = null;
            if ($tem) {
                $order = $tem->order;
            } else if ($plan) {
                $order = $plan->order;
            }
            $obj = new stdClass;
            $obj->stt = $key + 1;
            $obj->ngay_sx = $info_cong_doan->created_at ? date('d/m/Y', strtotime($info_cong_doan->created_at)) : "";
            $obj->machine_id = $info_cong_doan->machine_id;
            $obj->khach_hang = $order->short_name ?? "";
            $obj->mdh = $order->mdh ?? "";
            $obj->mql = $order->mql ?? "";
            $obj->quy_cach = $order ? ($order->dai . 'x' . $order->rong . ($order->cao ? ('x' . $order->cao) : "")) : "";
            $obj->thoi_gian_bat_dau = $info_cong_doan->thoi_gian_bat_dau ? date('H:i:s', strtotime($info_cong_doan->thoi_gian_bat_dau)) : "";
            $obj->thoi_gian_ket_thuc = $info_cong_doan->thoi_gian_ket_thuc ? date('H:i:s', strtotime($info_cong_doan->thoi_gian_ket_thuc)) : "";
            $obj->sl_dau_ra_hang_loat = $info_cong_doan->sl_dau_ra_hang_loat;
            $obj->sl_ok = $info_cong_doan->sl_dau_ra_hang_loat - $info_cong_doan->sl_ng_qc - $info_cong_doan->sl_ng_sx;
            $obj->sl_phe = $info_cong_doan->sl_ng_qc + $info_cong_doan->sl_ng_sx;
            $obj->dot = $order->dot ?? '';
            $obj->user_name = $info_cong_doan->user->name ?? "";
            $obj->lo_sx = $info_cong_doan->lo_sx;
            $obj->step = $info_cong_doan->step ? "Bước" : "";
            $records[] = (array)$obj;
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
        $header = [
            'STT',
            'Ngày sản xuất',
            'Máy',
            'Khách hàng',
            'MĐH',
            'MQL',
            'Quy cách',
            'Thời gian bắt đầu',
            'Thời gian kết thúc',
            "Sản lượng đầu ra",
            'Sản lượng sau QC',
            'Số lượng phế',
            'Đợt',
            'Công nhân sản xuất',
            'Lô SX',
            "Bước"
        ];
        $table_key = [
            'A' => 'stt',
            'B' => 'ngay_sx',
            'C' => 'machine_id',
            'D' => 'khach_hang',
            'E' => 'mdh',
            'F' => 'mql',
            'G' => 'quy_cach',
            'H' => 'thoi_gian_bat_dau',
            'I' => 'thoi_gian_ket_thuc',
            'J' => 'sl_dau_ra_hang_loat',
            'K' => 'sl_ok',
            'L' => 'sl_phe',
            'M' => 'dot',
            'N' => 'user_name',
            'O' => 'lo_sx',
            'P' => 'step'
        ];
        foreach ($header as $key => $cell) {
            $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            $start_col += 1;
        }
        $sheet->setCellValue([1, 1], 'Lịch sử sản xuất')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row + 1;
        $sheet->fromArray($records, null, 'A3');
        $sheet->getStyle([1, $table_row, $start_col - 1, count($records) + $table_row - 1])->applyFromArray(
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
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            $sheet->getStyle($column->getColumnIndex() . ($start_row) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Lịch sử sản xuất.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Lịch sử sản xuất.xlsx');
        $href = '/exported_files/Lịch sử sản xuất.xlsx';
        return $this->success($href);
    }

    //===================Quality====================

    public function queryQuality($request)
    {
        $query = InfoCongDoan::orderBy('created_at');
        if ($request->machine) {
            $query->whereIn('machine_id', $request->machine);
        }
        if (isset($request->start_date) && isset($request->end_date)) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))
                ->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)));
        } else {
            $query->whereDate('created_at', '>=', date('Y-m-d'))
                ->whereDate('created_at', '<=', date('Y-m-d'));
        }
        if (isset($request->mdh)) {
            if (is_array($request->mdh)) {
                $plans = ProductionPlan::where(function ($plan_query) use ($request) {
                    foreach ($request->mdh as $key => $mdh) {
                        $plan_query->orWhere('order_id', 'like', "%$mdh%");
                    }
                });
                $tem = Tem::where(function ($tem_query) use ($request) {
                    foreach ($request->mdh as $key => $mdh) {
                        $tem_query->orWhere('mdh', 'like', "%$mdh%");
                    }
                });
                $lo_sx = array_merge($plans->pluck('lo_sx')->toArray(), $tem->pluck('lo_sx')->toArray());
                // return $plans_query->pluck('lo_sx')->toArray();
                $query->whereIn('lo_sx', $lo_sx);
            } else {
                $plans = ProductionPlan::where('order_id', 'like', "%$request->mdh%");
                $tem = Tem::where('mdh', 'like', "%$request->mdh%");
                $lo_sx = array_merge($plans->pluck('lo_sx')->toArray(), $tem->pluck('lo_sx')->toArray());
                $query->whereIn('lo_sx', $lo_sx);
            }
        }
        if (isset($request->mql)) {
            if (is_array($request->mql)) {
                $plans = ProductionPlan::where(function ($plan_query) use ($request) {
                    foreach ($request->mql as $key => $mql) {
                        $plan_query->orWhere('order_id', $mql);
                    }
                });
                $tem = Tem::where(function ($tem_query) use ($request) {
                    foreach ($request->mql as $key => $mql) {
                        $tem_query->orWhere('mql', $mql);
                    }
                });
                $lo_sx = array_merge($plans->pluck('lo_sx')->toArray(), $tem->pluck('lo_sx')->toArray());
                // return $plans_query->pluck('lo_sx')->toArray();
                $query->whereIn('lo_sx', $lo_sx);
            } else {
                $plans = ProductionPlan::where('order_id', $request->mql);
                $tem = Tem::where('mql', $request->mql);
                $lo_sx = array_merge($plans->pluck('lo_sx')->toArray(), $tem->pluck('lo_sx')->toArray());
                $query->whereIn('lo_sx', $lo_sx);
            }
        }
        if (isset($request->customer_id)) {
            $plan = ProductionPlan::whereHas('order', function ($order_query) use ($request) {
                $order_query->where('short_name', 'like', "%$request->customer_id%");
            });
            $tem = Tem::where('khach_hang', 'like', "%$request->customer_id%");
            $lo_sx = array_merge($plan->pluck('lo_sx')->toArray(), $tem->pluck('lo_sx')->toArray());
            $query->whereIn('lo_sx', $lo_sx);
        }
        if (isset($request->lo_sx)) {
            $query->where('lo_sx', 'like', "%$request->lo_sx%");
        }
        if (isset($request->phan_dinh)) {
            $query->where('phan_dinh', $request->phan_dinh);
        }
        return $query;
    }
    public function qualityOverall(Request $request)
    {
        $query = InfoCongDoan::orderBy('created_at');
        if ($request->line_id) {
            $machine_ids = Machine::where('line_id', $request->line_id)->get()->pluck('id');
            $query->whereIn('machine_id', $machine_ids);
        }
        // if (isset($request->start_date) && isset($request->end_date)) {
        //     $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))
        //         ->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)));
        // }else{
        //     $query->whereDate('created_at', '>=', date('Y-m-d'))
        //     ->whereDate('created_at', '<=', date('Y-m-d'));
        // }
        if (isset($request->product_id)) {
            $query->where('lot_id', 'like',  '%' . $request->product_id . '%');
        }
        if (isset($request->khach_hang)) {
            $khach_hang = Customer::where('id', $request->khach_hang)->first();
            if ($khach_hang) {
                $plan = ProductionPlan::where('khach_hang', $khach_hang->name)->get();
                $product_ids = $plan->pluck('product_id')->toArray();
                $query->where(function ($qr) use ($product_ids) {
                    for ($i = 0; $i < count($product_ids); $i++) {
                        $qr->orwhere('lot_id', 'like',  '%' . $product_ids[$i] . '%');
                    }
                });
            }
        }
        if (isset($request->lo_sx)) {
            $lot = Lot::where('lo_sx', $request->lo_sx)->get();
            $query->whereIn('lot_id', $lot->pluck('id'));
        }
        $infos = $query->with("plan")->get();
        $data = [
            'sl_dau_ra_hang_loat' => $infos->sum('sl_dau_ra_hang_loat'),
            'sl_ngoai_quan' => $infos->sum('sl_ngoai_quan'),
            'sl_tinh_nang' => $infos->sum('sl_tinh_nang'),
            'sl_phe' => $infos->sum('sl_ng_qc') + $infos->sum('sl_ng_sx'),
        ];
        $data['ty_le_loi'] = $data['sl_dau_ra_hang_loat'] ? floor(($data['sl_ngoai_quan'] + $data['sl_tinh_nang']) / $data['sl_dau_ra_hang_loat'] * 100) : 0;
        $data['ty_le_phe'] = $data['sl_dau_ra_hang_loat'] ? floor($data['sl_phe'] / $data['sl_dau_ra_hang_loat'] * 100) : 0;
        return $this->success([$data]);
    }
    public function errorTable(Request $request)
    {
        $query = $this->queryQuality($request);
        $page = $request->page - 1;
        $pageSize = $request->pageSize;
        $totalPage = $query->count();
        $infos = $query->with("plan", "line")->offset($pageSize * $page)->limit($pageSize)->get();
        $data = [];
        foreach ($infos as $key => $info_cong_doan) {
            $plan = $info_cong_doan->plan;
            $obj = $info_cong_doan;
            $obj->khach_hang = $plan->customer->name ?? "";
            $obj->quy_cach = $plan ? $plan->dai . 'x' . $plan->rong . 'x' . $plan->cao : "";
            $obj->sl_dau_vao_kh = $plan->sl_kh ?? "";
            $obj->sl_dau_ra_kh = $plan->sl_kh ?? "";
            $obj->order_id = $plan->order_id ?? "";
            $obj->sl_ok = $info_cong_doan->sl_dau_ra_hang_loat - $info_cong_doan->sl_ng_qc - $info_cong_doan->sl_ng_sx;
            $obj->sl_phe = $info_cong_doan->sl_ng_qc + $info_cong_doan->sl_ng_sx;
            $obj->sl_loi = $info_cong_doan->sl_ngoai_quan + $info_cong_doan->sl_tinh_nang;
            $obj->ty_le_loi = $info_cong_doan->sl_dau_ra_hang_loat ? floor($obj->sl_loi / $info_cong_doan->sl_dau_ra_hang_loat * 100) : 0;
            $obj->ty_le_ng = $info_cong_doan->sl_dau_ra_hang_loat ? floor($obj->sl_phe / $info_cong_doan->sl_dau_ra_hang_loat * 100) : 0;
            $obj->ngay_sx = $plan->ngay_sx ?? "";
            $obj->xuong = 'Giấy';
            $obj->layout_id = $plan->layout_id ?? "";
            $data[] = $obj;
        }
        return $this->success(['data' => $data, 'totalPage' => $totalPage]);
    }

    public function qcHistory(Request $request)
    {
        $query = $this->queryQuality($request);
        $page = $request->page - 1;
        $pageSize = $request->pageSize;
        $totalPage = $query->count();
        $infos = $query->with("plan", "line", 'tem')->offset($pageSize * $page)->limit($pageSize)->get();
        $data = [];
        foreach ($infos as $key => $info_cong_doan) {
            $plan = $info_cong_doan->plan;
            $tem = $info_cong_doan->tem;
            $order = null;
            if ($tem) {
                $order = $tem->order;
            } else if ($plan) {
                $order = $plan->order;
            }
            $obj = $info_cong_doan;
            $obj->khach_hang = $order->short_name ?? "";
            $obj->quy_cach = $order ? $order->dai . 'x' . $order->rong . ($order->cao ? 'x' . $order->cao : "") : "";
            $obj->order_id = $order->id ?? "";
            $obj->sl_ok = $info_cong_doan->sl_dau_ra_hang_loat - $info_cong_doan->sl_ng_qc - $info_cong_doan->sl_ng_sx;
            $obj->sl_phe = $info_cong_doan->sl_ng_qc + $info_cong_doan->sl_ng_sx;
            $obj->ty_le_ng = $info_cong_doan->sl_dau_ra_hang_loat ? floor($obj->sl_phe / $info_cong_doan->sl_dau_ra_hang_loat * 100) : 0;
            $obj->mdh = $order->mdh ?? "";
            $obj->mql = $order->mql ?? "";
            $data[] = $obj;
        }
        return $this->success(['data' => $data, 'totalPage' => $totalPage]);
    }

    public function iqcHistory(Request $request)
    {
        $input = $request->all();
        $customOrder = [0, 2, 1];
        $query = WareHouseMLTImport::orderBy('created_at');
        if (isset($input['start_date']) && $input['end_date']) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($input['start_date'])))
                ->whereDate('created_at', '<=', date('Y-m-d', strtotime($input['end_date'])));
        } else {
            $query->whereDate('created_at', '>=', date('Y-m-d'))
                ->whereDate('created_at', '<=', date('Y-m-d'));
        }
        if (isset($input['material_id'])) {
            $query = $query->where('material_id', 'like', '%' . $input['material_id'] . '%');
        }
        if (isset($input['ma_vat_tu'])) {
            $query = $query->where('ma_vat_tu', 'like', '%' . $input['ma_vat_tu'] . '%');
        }
        if (isset($input['ma_cuon_ncc'])) {
            $query = $query->where('ma_cuon_ncc', 'like', '%' . $input['ma_cuon_ncc'] . '%');
        }
        if (isset($input['loai_giay'])) {
            $query = $query->where('loai_giay', 'like', '%' . $input['loai_giay'] . '%');
        }
        $totalPage = $query->count();
        if (isset($request->page) && isset($request->pageSize)) {
            $page = $request->page - 1;
            $pageSize = $request->pageSize;
            $query->offset($page * $pageSize)->limit($pageSize);
        }
        $data = $query->get();
        foreach ($data as $value) {
            $value->ma_cuon_ncc = $value->ma_cuon_ncc;
            $value->ten_ncc = $value->supplier->name ?? "";
            $value->sl_ok = $value->log['sl_ok'] ?? 0;
            $value->sl_ng = $value->log['sl_ng'] ?? 0;
            $value->sl_tinh_nang = $value->log['sl_tinh_nang'] ?? 0;
            $value->sl_ngoai_quan = $value->log['sl_ngoai_quan'] ?? 0;
            $value->phan_dinh = $value->log['phan_dinh'] ?? 0;
            $value->sl_dau_ra_hang_loat = $value->so_kg ?? 0;
            $value->checked_tinh_nang = isset($value->log['tinh_nang']);
            $value->checked_ngoai_quan = isset($value->log['ngoai_quan']);
            $value->checked_sl_ng = isset($value->log['sl_ng']);
            foreach ($value->log['tinh_nang'] ?? [] as $key => $log) {
                $value[$log['id']] = $log['value'];
            }
            foreach ($value->log['ngoai_quan'] ?? [] as $key => $log) {
                $value[$log['id']] = $log['value'];
            }
            $value->user_name = $value->log['user_name'] ?? "";
        }
        $sorted_ids = ['IT-06', 'IT-03', 'IT-01', 'IT-05', 'IT-04', 'IT-02', 'IT-07'];
        $test_criteria = TestCriteria::where('line_id', 38)->where('hang_muc', 'tinh_nang')->get()->sortBy(function ($model) use ($sorted_ids) {
            return array_search($model->getKey(), $sorted_ids);
        });
        $columns = [];
        foreach ($test_criteria as $key => $value) {
            $columns[] = [
                'title' => $value->name,
                'dataIndex' => $value->id,
            ];
        }
        return $this->success(['data' => $data, 'totalPage' => $totalPage, 'columns' => $columns]);
    }

    public function getInfoFromPlan($info) {}

    public function getInfoFromTem($info)
    {
        $tem = $info->tem;
        $obj = $info;
        $obj->khach_hang = $tem->khach_hang ?? "";
        $obj->quy_cach = $tem->quy_cach ?? "";
        $obj->sl_dau_vao_kh = $tem->so_luong ?? "";
        $obj->sl_dau_ra_kh = $tem->so_luong ?? "";
        $obj->mdh = $tem->mdh;
        $obj->mql = $tem->mql;
        $obj->sl_ok = $info->sl_dau_ra_hang_loat - $info->sl_ng_qc - $info->sl_ng_sx;
        $obj->sl_phe = $info->sl_ng_qc + $info->sl_ng_sx;
        $obj->sl_loi = $info->sl_ngoai_quan + $info->sl_tinh_nang;
        $obj->ty_le_loi = $info->sl_dau_ra_hang_loat ? floor($obj->sl_loi / $info->sl_dau_ra_hang_loat * 100) : 0;
        $obj->ty_le_ng = $info->sl_dau_ra_hang_loat ? floor($obj->sl_phe / $info->sl_dau_ra_hang_loat * 100) : 0;
        return $obj;
    }

    public function recheckQC(Request $request)
    {
        try {
            DB::beginTransaction();
            $info_cong_doan = InfoCongDoan::find($request->id);
            if (!$info_cong_doan) return $this->failure('', 'Không thấy bản ghi');
            $info_cong_doan->sl_tinh_nang = 0;
            $info_cong_doan->sl_ngoai_quan = 0;
            $info_cong_doan->sl_ng_qc = 0;
            $info_cong_doan->phan_dinh = 0;
            $info_cong_doan->status = 2;
            $info_cong_doan->save();
            $log = QCLog::where('lo_sx', $info_cong_doan->lo_sx)->where('machine_id', $info_cong_doan->machine_id)->first();
            if ($log) {
                $info = $log->info;
                unset($info['tinh_nang'], $info['ngoai_quan'], $info['sl_ng_qc'], $info['sl_tinh_nang'], $info['sl_ngoai_quan'], $info['phan_dinh']);
                $log->info = $info;
                $log->save();
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
        return $this->success($info_cong_doan, 'Đã cho tái kiểm');
    }

    public function errorQC(Request $request)
    {
        $query = InfoCongDoan::orderBy('created_at');
        if ($request->line_id) {
            $machine_ids = Machine::where('line_id', $request->line_id)->get()->pluck('id');
            $query->whereIn('machine_id', $machine_ids);
        }
        if (isset($request->start_date) && isset($request->end_date)) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))
                ->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)));
        } else {
            $query->whereDate('created_at', '>=', date('Y-m-d'))
                ->whereDate('created_at', '<=', date('Y-m-d'));
        }
        if (isset($request->product_id)) {
            $query->where('lot_id', 'like',  '%' . $request->product_id . '%');
        }
        if (isset($request->khach_hang)) {
            $khach_hang = Customer::where('id', $request->khach_hang)->first();
            if ($khach_hang) {
                $plan = ProductionPlan::where('khach_hang', $khach_hang->name)->get();
                $product_ids = $plan->pluck('product_id')->toArray();
                $query->where(function ($qr) use ($product_ids) {
                    for ($i = 0; $i < count($product_ids); $i++) {
                        $qr->orwhere('lot_id', 'like',  '%' . $product_ids[$i] . '%');
                    }
                });
            }
        }
        if (isset($request->lo_sx)) {
            $lot = Lot::where('lo_sx', $request->lo_sx)->get();
            $query->whereIn('lot_id', $lot->pluck('id'));
        }
        $infos = $query->with("plan")->get();
        $data = [];
        foreach ($infos as $info) {
            $date = $info->plan->ngay_sx ?? null;
            $log = $info->log->info ?? [];
            if (isset($log['qc'][$info->machine_id]['ngoai_quan'])) {
                $ngoai_quan = $log['qc'][$info->machine_id]['ngoai_quan'];
                foreach ($ngoai_quan as $key => $error) {
                    // if(!isset($data[$date])) $data[$date] = [];
                    if (!isset($data[$date][$key])) $data[$date][$key] = 0;
                    $data[$date][$key] += $error['value'] ?? 0;
                }
            }
        }
        return $this->success($data);
    }

    public function topErrorQC(Request $request)
    {
        $query = InfoCongDoan::orderBy('created_at');
        if ($request->line_id) {
            $machine_ids = Machine::where('line_id', $request->line_id)->get()->pluck('id');
            $query->whereIn('machine_id', $machine_ids);
        }
        if (isset($request->start_date) && isset($request->end_date)) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))
                ->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)));
        } else {
            $query->whereDate('created_at', '>=', date('Y-m-d'))
                ->whereDate('created_at', '<=', date('Y-m-d'));
        }
        if (isset($request->product_id)) {
            $query->where('lot_id', 'like',  '%' . $request->product_id . '%');
        }
        if (isset($request->khach_hang)) {
            $khach_hang = Customer::where('id', $request->khach_hang)->first();
            if ($khach_hang) {
                $plan = ProductionPlan::where('khach_hang', $khach_hang->name)->get();
                $product_ids = $plan->pluck('product_id')->toArray();
                $query->where(function ($qr) use ($product_ids) {
                    for ($i = 0; $i < count($product_ids); $i++) {
                        $qr->orwhere('lot_id', 'like',  '%' . $product_ids[$i] . '%');
                    }
                });
            }
        }
        if (isset($request->lo_sx)) {
            $lot = Lot::where('lo_sx', $request->lo_sx)->get();
            $query->whereIn('lot_id', $lot->pluck('id'));
        }
        $infos = $query->with("plan")->get();
        $data = [];
        foreach ($infos as $info) {
            $log = $info->log->info ?? [];
            if (isset($log['qc'][$info->machine_id]['ngoai_quan'])) {
                $ngoai_quan = $log['qc'][$info->machine_id]['ngoai_quan'];
                foreach ($ngoai_quan as $key => $error) {
                    if (!isset($data[$key]['value'])) $data[$key]['value'] = 0;
                    if (!isset($data[$key]['frequency'])) $data[$key]['frequency'] = 0;
                    $data[$key]['value'] += $error['value'] ?? 0;
                    $data[$key]['name'] = $key;
                    $data[$key]['frequency'] += 1;
                }
            }
        }
        array_slice($data, 0, 5);
        return $this->success($data);
    }

    public function machinePerformance(Request $request)
    {
        $machines = Machine::whereNull('parent_id')->where('is_iot', 1)->get();
        $data = [];
        foreach ($machines as $machine) {
            // Lấy thời gian dừng từ MachineLog
            $machine_logs = MachineLog::selectRaw('TIMESTAMPDIFF(SECOND, start_time, end_time) as total_time')
                ->whereNotNull('start_time')->whereNotNull('end_time')
                ->where('machine_id', $machine->id)
                ->whereBetween('start_time', [date('Y-m-d 07:30:00'), date('Y-m-d 23:59:59')])
                ->get();

            // Tính tổng thời gian dừng
            $thoi_gian_dung = floor($machine_logs->sum('total_time') / 3600); // Đổi giây sang giờ
            $so_lan_dung = count($machine_logs);

            // Tính thời gian làm việc từ 7:30 sáng đến hiện tại
            $thoi_gian_lam_viec = 16; // Đổi giây sang giờ

            // Tính thời gian chạy bằng thời gian làm việc - thời gian dừng
            $thoi_gian_chay = max(0, $thoi_gian_lam_viec - $thoi_gian_dung); // Đảm bảo không âm

            // Tính tỷ lệ vận hành
            $ty_le_van_hanh = floor(($thoi_gian_chay / max(1, $thoi_gian_lam_viec)) * 100); // Tính phần trăm

            $percent = $ty_le_van_hanh;
            $data[] = ['percent' => $percent, 'name' => $machine->id];
        }

        return $this->success($data);
    }

    function queryMachineLog($request)
    {
        $query = MachineLog::whereNotNull('error_machine_id');
        if ($request->machine) {
            $query->whereIn('machine_id', $request->machine);
        }
        if (isset($request->start_date) && isset($request->end_date)) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))
                ->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)));
        } else {
            $query->whereDate('created_at', '>=', date('Y-m-d'))
                ->whereDate('created_at', '<=', date('Y-m-d'));
        }
        if (isset($request->khach_hang)) {
            $plan = ProductionPlan::whereHas('order', function ($order_query) use ($request) {
                $order_query->where('short_name', 'like', "%$request->khach_hang%");
            });
            $query->whereIn('lo_sx', $plan->pluck('lo_sx')->toArray());
        }
        if (isset($request->lo_sx)) {
            $query->where('lo_sx', 'like', "%$request->lo_sx%");
        }
        if (isset($request->error_machine_id)) {
            $query->whereHas('error_machine', function ($error_machine_query) use ($request) {
                $error_machine_query->where('code', 'like', "%$request->error_machine_id%");
            });
        }
        if (isset($request->ten_su_co)) {
            $query->whereHas('error_machine', function ($error_machine_query) use ($request) {
                $error_machine_query->where('ten_su_co', 'like', "%$request->ten_su_co%");
            });
        }
        if (isset($request->nguyen_nhan)) {
            $query->whereHas('error_machine', function ($error_machine_query) use ($request) {
                $error_machine_query->where('nguyen_nhan', 'like', "%$request->nguyen_nhan%");
            });
        }
        if (isset($request->cach_xu_ly)) {
            $query->whereHas('error_machine', function ($error_machine_query) use ($request) {
                $error_machine_query->where('cach_xu_ly', 'like', "%$request->cach_xu_ly%");
            });
        }
        $query->with('error_machine', 'user', 'plan');
        return $query;
    }
    public function errorMachineFrequency(Request $request)
    {
        $query = $this->queryMachineLog($request);
        $machine_logs = $query->get()->groupBy(function ($item) {
            return $item->error_machine->ten_su_co ?? "";
        });
        $data = [];
        foreach ($machine_logs as $key => $log) {
            if (!$key) continue;
            $obj = new stdClass();
            $obj->value = count($log);
            $obj->name = $key;
            $data[] = $obj;
        }
        return $this->success($data);
    }

    public function getErrorMachine(Request $request)
    {
        $query = $this->queryMachineLog($request);
        $machine_logs = $query->get();
        foreach ($machine_logs as $log) {
            $plan = $log->plan;
            $log->thoi_gian_xu_ly = number_format((strtotime($log->end_time) - strtotime($log->start_time)) / 3600, 2);
            $log->start_time = date('d/m/Y H:i:s', strtotime($log->start_time));
            $log->end_time = date('d/m/Y H:i:s', strtotime($log->end_time));
            $log->khach_hang = $plan->order->short_name ?? "";
            $log->mql = $plan->order->mql ?? "";
            $log->mdh = $plan->order->mdh ?? "";
            $log->order_id = $plan->order_id ?? "";
            $log->ma_su_co = $log->error_machine->code ?? "";
            $log->ten_su_co = $log->error_machine->ten_su_co ?? "";
            $log->nguyen_nhan = $log->error_machine->nguyen_nhan ?? "";
            $log->cach_xu_ly = $log->error_machine->cach_xu_ly ?? "";
            $log->nguoi_xu_ly = $log->user->name ?? "";
            $log->da_xu_ly = ($log->end_time || $log->handle_time) ? true : false;
        }
        return $this->success($machine_logs);
    }

    public function exportErrorMachine(Request $request)
    {
        $query = $this->queryMachineLog($request);
        $machine_logs = $query->get();
        $data = [];
        foreach ($machine_logs as $key => $log) {
            $obj = new stdClass;
            $plan = $log->plan;
            $obj->stt = $key + 1;
            $obj->machine_id = $log->machine_id;
            $obj->khach_hang = $plan->order->short_name ?? "";
            $obj->mdh = $plan->order->mdh ?? "";
            $obj->mql = $plan->order->mql ?? "";
            $obj->lo_sx = $log->lo_sx;
            $obj->end_time = date('d/m/Y H:i:s', strtotime($log->end_time));
            $obj->start_time = date('d/m/Y H:i:s', strtotime($log->start_time));
            $obj->ma_su_co = $log->error_machine->code ?? "";
            $obj->ten_su_co = $log->error_machine->ten_su_co ?? "";
            $obj->nguyen_nhan = $log->error_machine->nguyen_nhan ?? "";
            $obj->cach_xu_ly = $log->error_machine->cach_xu_ly ?? "";
            $obj->nguoi_xu_ly = $log->user->name ?? "";
            $obj->da_xu_ly = ($log->end_time || $log->handle_time) ? 'Đã hoàn thành' : 'Chưa hoàn thành';
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
            "Máy",
            'Khách hàng',
            "MĐH",
            "MQL",
            "Lô sản xuất",
            'Thời gian dừng',
            "Thời gian chạy",
            "Mã sự cố",
            "Tên sự cố",
            'Nguyên nhân',
            'Cách xử lý',
            'Người xử lý',
            'Tình trạng'
        ];
        foreach ($header as $key => $cell) {
            $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            $start_col += 1;
        }
        $sheet->setCellValue([1, 1], 'DANH SÁCH LỖI')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->fromArray($data, null, 'A3');
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
        header('Content-Disposition: attachment;filename="Thống kê chi tiết lỗi.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Thống kê chi tiết lỗi.xlsx');
        $href = '/exported_files/Thống kê chi tiết lỗi.xlsx';
        return $this->success($href);
    }

    public function machineParameterTable(Request $request)
    {
        $query = MachineParameterLogs::orderBy('created_at');
        if ($request->machine) {
            $query->whereIn('machine_id', $request->machine);
        }
        if ($request->machine_id) {
            $query->where('machine_id', $request->machine_id);
        }
        if (isset($request->lo_sx)) {
            $query->where('lo_sx', $request->lo_sx);
        }
        if (isset($request->start_date) || isset($request->end_date)) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)));
        } else {
            $query->whereDate('created_at', date('Y-m-d'));
        }
        $totalPage = $query->count();
        if (isset($request->page) && isset($request->pageSize)) {
            $query->offset(($request->page - 1) * $request->pageSize)->limit($request->pageSize);
        }
        $columns = [];
        $machine_parameter_logs = $query->with('plan')->get();
        $machine_ids = array_unique($machine_parameter_logs->pluck('machine_id')->toArray());
        $machine_params = MachineParameter::with('machine.line')->whereIn('machine_id', $machine_ids)->get();
        foreach ($machine_params as $machine_param) {
            $line = $machine_param->machine->line;
            $columns[$line->id]['title'] = $line->name;
            $columns[$line->id]['key'] = $line->id;
            $exists = false;
            if (isset($columns[$line->id]['children'])) {
                foreach ($columns[$line->id]['children'] as $child) {
                    if ($child['key'] === $machine_param->parameter_id) {
                        $exists = true;
                        break;
                    }
                }
            }
            // Chỉ thêm vào nếu chưa tồn tại
            if (!$exists) {
                $columns[$line->id]['children'][] = [
                    'title' => $machine_param->name,
                    'key' => $machine_param->parameter_id,
                ];
            }
        }
        $data = [];
        foreach ($machine_parameter_logs as $value) {
            $value->machine_id = $value->machine_id;
            $value->ngay_sx = date('d/m/Y H:i:s', strtotime($value->created_at));
            $data[] = array_merge($value->toArray(), $value->info ?? []);
        }
        return $this->success(['data' => $data, 'totalPage' => $totalPage, 'columns' => array_values($columns)]);
    }


    public function listMaterialImport(Request $request)
    {
        $input = $request->all();
        $query = WareHouseMLTImport::with('supplier');
        if (isset($input['start_date']) && $input['end_date']) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($input['start_date'])))
                ->whereDate('created_at', '<=', date('Y-m-d', strtotime($input['end_date'])));
        } else {
            $query->whereDate('created_at', '>=', date('Y-m-d'))
                ->whereDate('created_at', '<=', date('Y-m-d'));
        }
        if (isset($input['material_id'])) {
            $query = $query->where('material_id', 'like', '%' . $input['material_id'] . '%');
        }
        if (isset($input['ma_vat_tu'])) {
            $query = $query->where('ma_vat_tu', 'like', '%' . $input['ma_vat_tu'] . '%');
        }
        if (isset($input['ma_cuon_ncc'])) {
            $query = $query->where('ma_cuon_ncc', 'like', '%' . $input['ma_cuon_ncc'] . '%');
        }
        if (isset($input['loai_giay'])) {
            $query = $query->where('loai_giay', 'like', '%' . $input['loai_giay'] . '%');
        }
        $records = $query->orderBy('created_at')->get();
        foreach ($records as $record) {
            $record['ten_ncc'] = $record->supplier->name ?? "";
        }
        return $this->success($records);
    }

    public function importMLTLog()
    {
        $records = WarehouseMLTLog::with('material')->orderBy('tg_nhap', 'DESC')->whereDate('tg_nhap', date('Y-m-d'))->get();
        $data = [];
        foreach ($records as $key => $record) {
            $obj = new stdClass();
            $obj->material_id = $record->material_id;
            $obj->ma_cuon_ncc = $record->material->ma_cuon_ncc ?? "";
            $obj->so_kg = $record->so_kg_nhap;
            $obj->vi_tri = $record->locator_id;
            $obj->loai_giay = $record->material->loai_giay ?? "";
            $obj->kho_giay = $record->material->kho_giay ?? "";
            $obj->dinh_luong = $record->material->dinh_luong ?? "";
            $obj->tg_nhap = $record->tg_nhap ? date('H:i:s', strtotime($record->tg_nhap)) : 0;
            $data[] = $obj;
        }
        return $this->success($data);
    }

    public function importMLTScan(Request $request)
    {
        $input = $request->all();
        if (isset($input['material_id'])) {
            $material_id = $request->get('material_id');
            $material = Material::find($material_id);
            if (!$material) {
                return $this->failure('', 'Mã cuộn không tồn tại');
            }
            $log = WarehouseMLTLog::where('material_id', $material->id)->orderBy('updated_at', 'DESC')->first();
            $material_mlt_map = LocatorMLTMap::where('material_id', $material->id)->first();
            if ($log && !$log->tg_xuat) return $this->failure('', 'Cuộn ' . $material->id . ' đã ở trong kho');
            $material->material_id = $material->id;
            $material->status = 1;
            return $this->success($material);
        }
        if (isset($input['locator_id'])) {
            $locator = LocatorMLT::find($input['locator_id']);
            if (!$locator) {
                return $this->failure('', 'Vị trí không phù hợp');
            }
            return $this->success('');
        }
    }

    function findLocation($materials, $id)
    {
        $material = $materials[0];
        $warehouse_mlt_ids = DB::table('phan_khu')->where('supplier_id', $material->loai_giay)->get()->pluck('warehouse_mlt_id');
        $kho_giay = $material->kho_giay;
        $capacity = 0;
        if ($kho_giay >= 80 && $kho_giay <= 89) {
            $capacity = 7;
        } else if ($kho_giay >= 90 && $kho_giay <= 100) {
            $capacity = 6;
        } else if ($kho_giay >= 101 && $kho_giay <= 125) {
            $capacity = 5;
        } else if ($kho_giay >= 126 && $kho_giay <= 150) {
            $capacity = 4;
        } else if ($kho_giay >= 151 && $kho_giay <= 180) {
            $capacity = 3;
        } else if ($kho_giay >= 181) {
            $capacity = 2;
        } else {
            $capacity = 1;
        }
        $locator_mlt = LocatorMLT::whereIn('warehouse_mlt_id', $warehouse_mlt_ids)
            ->where(function ($query) use ($material) {
                $query->where('ma_vat_tu', $material->ma_vat_tu)
                    ->orWhereNull('ma_vat_tu');
            })
            ->withCount('materials')
            ->where(function ($query) use ($capacity) {
                $query->having('materials_count', '<', $capacity)
                    ->orWhere('capacity', 0);
            })
            ->orderBy('materials_count', 'DESC')
            ->orderBy('id')
            ->get();
        $locators = [];
        $count = 0;
        foreach ($locator_mlt as $key => $locate) {
            if ($count >= count($materials)) {
                break;
            }
            $available = $capacity - $locate->materials_count;
            if ($available <= 0) {
                continue;
            } else {
                for ($i = 0; $i < $available; $i++) {
                    if ($count >= count($materials)) {
                        break;
                    }
                    if (!$material->warehouse_mtl_log && $material->material_id) {
                        $material = $materials[$count];
                        $material->locator_id = $locate->id;
                        if ($material->material_id === $id) {
                            $material->status = 1;
                        } else {
                            $material->status = 0;
                        }
                        $locators[$count] = $material;
                    }
                    $count++;
                }
            }
        }
        return $locators;
    }

    public function importMLTSave(Request $request)
    {
        $input = $request->all();
        DB::beginTransaction();
        try {
            $materials = Material::whereIn('id', array_column($input, 'id'))->get();
            foreach ($materials as $key => $material) {
                $material_input = $this->findObject($input, $material->id, 'material_id');
                $locator = LocatorMLT::find($material_input['locator_id'] ?? "");
                if (!$locator) return $this->failure('', 'Vị trí không phù hợp');
                $material = Material::find($material->id);
                $inp['material_id'] = $material->id;
                $inp['locator_id'] = $locator->id;
                $inp['so_kg_nhap'] = $material->so_kg;
                $inp['importer_id'] = $request->user()->id;
                $inp['tg_nhap'] = date('Y-m-d H:i:s');
                WarehouseMLTLog::create($inp);
                LocatorMLTMap::create(['material_id' => $material->id, 'locator_mlt_id' => $locator->id]);
                $locator_mlt = LocatorMLT::find($locator->id);
                $locator_mlt->update(['capacity' => ($locator_mlt->capacity + 1)]);
            }
            DB::commit();
            return $this->success('', 'Nhập kho thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            ErrorLog::saveError($request, $e);
            return $this->failure($e, 'Có lỗi xảy ra vui lòng kiểm tra lại');
        }
    }

    function findObject($array, $value, $key)
    {
        foreach ($array as $object) {
            if (isset($object[$key]) && $object[$key] === $value) {
                return $object;
            }
        }
        return null; // Return null if object not found
    }

    public function getMachineParameterChart(Request $request)
    {
        $param_logs = MachineParameterLogs::where(function ($query) use ($request) {
            $query->whereNotNull('data_if->' . $request->params)->orWhereNotNull('data_input->' . $request->params);
        })
            ->whereBetween('created_at', [date('Y-m-d 00:00:00', strtotime($request->start_date)), date('Y-m-d 23:59:59', strtotime($request->end_date))])->get();
        return $this->success($param_logs);
    }

    public function listPallet(Request $request)
    {
        $records = Pallet::with(['losxpallet', 'locator_fg_map'])->whereDate('created_at', date('Y-m-d'))->orderBy('created_at', 'DESC')->get();
        foreach ($records as $key => $record) {
            $record->key = $record->id;
            $record->khach_hang = $record->losxpallet[0]->customer_id ?? '';
            if ($record->locator_fg_map) {
                $record->is_location = 1;
            } else {
                $record->is_location = 0;
            }
        }
        return $this->success($records);
    }

    public function getOverallWarehouseFG(Request $request)
    {
        $logs = WarehouseFGLog::whereBetween('created_at', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])->get();
        $map = LocatorFGMap::whereNotNull('locator_id')->whereNotNull('pallet_id')->get();
        $sl_nhap = 0;
        $sl_xuat = 0;
        // return $ton_nhap;
        $sl_ton = 0;
        $so_ngay_ton = 0;
        foreach ($logs as $log) {
            if ($log->type === 1) {
                $sl_nhap += $log->so_luong;
                if (in_array($log->pallet_id, $map->pluck('pallet_id')->toArray())) {
                    $sl_ton += $log->so_luong;
                    $current = new DateTime();
                    $import_date = new DateTime($log->created_at);
                    $so_ngay_ton += $current->diff($import_date)->format('%a');
                }
            } else {
                $sl_xuat += $log->so_luong;
            }
        }
        $overall = [
            'sl_nhap' => $sl_nhap,
            'sl_xuat' => $sl_xuat,
            'sl_ton' => $sl_ton,
            'so_ngay_ton' => $so_ngay_ton,
        ];
        return $this->success($overall);
    }

    public function getWarehouseFGLogs(Request $request)
    {
        $logs = WarehouseFGLog::with('locator', 'plan')->where('type', 1)->whereBetween('created_at', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])->groupBy('locator_id')->get();
        foreach ($logs as $log) {
            $log->pallet_id = $log->locator->pallet_id ?? "";
            $log->order_id = $log->plan->order_id;
            $log->khach_hang = $log->plan->customer->name ?? "";
        }
        return $this->success($logs);
    }

    public function suggestPallet(Request $request)
    {
        $obj = new stdClass;
        if (!$request->log_id) {
            $locator = LocatorFG::where('capacity', 0)->orderBy('id')->first();
            $latest_pallet = Pallet::where('id', 'like', 'PL' . date('ymd') . '%')->orderBy('id', 'DESC')->first();
            if ($latest_pallet) {
                $pallet_id = (int)str_replace('PL' . date('ymd'), '', $latest_pallet->id);
            } else {
                $pallet_id = 0;
            }
            $obj->locator_id = $locator->id;
            $obj->so_luong = 0;
            $obj->pallet_id = 'PL' . date('ymd') . str_pad($pallet_id + 1, 4, '0', STR_PAD_LEFT);
        }
        return $this->success($obj);
    }

    public function quantityLosx(Request $request)
    {
        $lo_sx = $request->lo_sx;
        $record = InfoCongDoan::where('lo_sx', $lo_sx)->orderBy('created_at', 'DESC')->first();
        if (!$record) {
            return $this->failure([], 'Lô sản xuất không tồn tại');
        }
        $obj = new stdClass();
        $obj->lo_sx =  $record->lo_sx;
        $obj->so_luong = $record->sl_dau_ra_hang_loat - $record->sl_ng_sx - $record->sl_ng_qc;
        return $this->success($obj);
    }

    public function checkLosx(Request $request)
    {
        $input = $request->all();
        $info = InfoCongDoan::with('tem', 'plan', 'lsxpallet', 'warehouseFGLog')->where('lo_sx', $input['lo_sx'])->orderBy('created_at', 'DESC')->first();
        if (!$info) {
            return $this->failure([], 'Lô chưa được quét vào sản xuất');
        }
        if ($info->step !== 0) {
            return $this->failure([], 'Lô ' . $info->lo_sx . ' chưa sẵn sàng nhập kho');
        }
        $previousQCLog = QCLog::where('lo_sx', $request->lo_sx)->orderBy('updated_at', 'DESC')->first();
        if ($previousQCLog) {
            if (isset($previousQCLog->info['phan_dinh'])) {
                if ($previousQCLog->info['phan_dinh'] === 2) {
                    return $this->failure('', 'Lô ' . $request->lo_sx . ' bị NG');
                }
            } else {
                return $this->failure('', 'Lô ' . $request->lo_sx . ' chưa qua QC');
            }
        }
        if ($info->lsxpallet) {
            return $this->failure('', 'Lô ' . $request->lo_sx . ' đã quét tem gộp');
        }
        if ($info->warehouseFGLog) {
            return $this->failure('', 'Lô ' . $request->lo_sx . ' đã quét nhập kho');
        }
        if (isset($request->list_losx) && count($request->list_losx) > 0) {
            $lo_sx = InfoCongDoan::where('lo_sx', $request->list_losx[0])->with('tem', 'plan')->first();
            $customer = "";
            $inserting_customer = "";
            if ($lo_sx->tem) $customer = $lo_sx->tem->order->customer_id;
            elseif ($lo_sx->plan) $customer = $lo_sx->plan->order->customer_id;
            if ($info->tem) $inserting_customer = $info->tem->order->customer_id;
            elseif ($info->plan) $inserting_customer = $info->plan->order->customer_id;
            if (strtolower(trim($customer)) !== strtolower(trim($inserting_customer))) {
                return $this->failure('', 'Lô ' . $info->lo_sx . ' không có cùng khách hàng với các lô đã quét trước đó');
            }
        }
        return $this->success($info);
    }

    public function infoPallet(Request $request)
    {
        $pallet = Pallet::with('losxpallet', 'locator_fg_map')->find($request->pallet_id);
        $pallet->locator_id = $pallet->locator_fg_map->locator_id ?? "";
        return $this->success($pallet);
    }

    public function storePallet(Request $request)
    {
        $input = $request->all();
        $data = [];
        try {
            DB::beginTransaction();
            $latest_pallet = Pallet::where('id', 'like', 'PL' . date('ymd') . '%')->orderBy('id', 'DESC')->first();
            if ($latest_pallet) {
                $pallet_id = (int)str_replace('PL' . date('ymd'), '', $latest_pallet->id);
            } else {
                $pallet_id = 0;
            }
            $input['id'] = 'PL' . date('ymd') . str_pad($pallet_id + 1, 4, '0', STR_PAD_LEFT);
            $pallet = Pallet::create($input);
            $so_luong = 0;
            foreach ($input['inp_arr'] as $key => $value) {
                $info = InfoCongDoan::with('plan.order')->where('lo_sx', $value['lo_sx'])->orderBy('created_at', 'DESC')->first();
                if ($info->step !== 0) {
                    return $this->failure('', 'Lô ' . $info->lo_sx . ' chưa sẵn sàng để nhập kho');
                }
                $inp['customer_id'] = $info->tem->khach_hang;
                $inp['mql'] = $info->tem->mql;
                $inp['mdh'] = $info->tem->mdh;
                $inp['order_id'] = $info->tem->order_id ?? "";
                $inp['lo_sx'] = $value['lo_sx'];
                $inp['pallet_id'] = $input['id'];
                $inp['so_luong'] = $value['so_luong'];
                $so_luong += $value['so_luong'];
                LSXPallet::create($inp);
                $obj = new stdClass();
                $obj->pallet_id = $input['id'];
                $obj->khach_hang = $info->tem->khach_hang;
                $obj->so_luong = $inp['so_luong'];
                $obj->mql = $info->tem->mql;
                $obj->mdh = $info->tem->mdh;
                $data[] = $obj;
            }
            $pallet->update(['so_luong' => $so_luong, 'number_of_lot' => count($input['inp_arr'])]);
            DB::commit();
            $data = collect($data)->sortBy([['mdh', 'asc'], ['mql', 'asc']]);
            return $this->success($data, 'Tạo pallet thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            ErrorLog::saveError($request, $e);
            return $this->failure($e, 'Có lỗi xảy ra vui lòng kiểm tra lại');
        }
    }

    public function updatePallet(Request $request)
    {
        $input = $request->all();
        $data = [];
        try {
            DB::beginTransaction();
            $input['id'] = $input['pallet_id'];
            $pallet = Pallet::find($input['id']);
            if ($pallet) {
                LSXPallet::where('pallet_id', $input['id'])->delete();
            } else {
                return $this->failure('', 'Không tìm thấy pallet');
            }
            $so_luong = 0;
            foreach ($input['inp_arr'] as $key => $value) {
                $info = InfoCongDoan::with('plan.order')->where('lo_sx', $value['lo_sx'])->orderBy('created_at', 'DESC')->first();
                if ($info->step !== 0) {
                    return $this->failure('', 'Lô ' . $info->lo_sx . ' chưa sẵn sàng để nhập kho');
                }
                $inp['customer_id'] = $info->tem->khach_hang;
                $inp['mql'] = $info->tem->mql;
                $inp['mdh'] = $info->tem->mdh;
                $inp['order_id'] = $info->tem->order_id ?? "";
                $inp['lo_sx'] = $value['lo_sx'];
                $inp['pallet_id'] = $input['id'];
                $inp['so_luong'] = $value['so_luong'];
                $so_luong += $value['so_luong'];
                LSXPallet::create($inp);
                $obj = new stdClass();
                $obj->pallet_id = $input['id'];
                $obj->khach_hang = $info->tem->khach_hang;
                $obj->so_luong = $inp['so_luong'];
                $obj->mql = $info->tem->mql;
                $obj->mdh = $info->tem->mdh;
                $data[] = $obj;
            }
            $pallet->update(['so_luong' => $so_luong, 'number_of_lot' => count($input['inp_arr'])]);
            DB::commit();
            $data = collect($data)->sortBy('order_id', SORT_NATURAL)->values();
            return $this->success($data, 'Tạo pallet thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            ErrorLog::saveError($request, $e);
            return $this->failure($e, 'Có lỗi xảy ra vui lòng kiểm tra lại');
        }
    }

    public function getLogImportWarehouseFG(Request $request)
    {
        $logs = WarehouseFGLog::with('pallet', 'order')->whereDate('created_at', date('Y-m-d'))->where('type', 1)->get()->groupBy('pallet_id');
        $data = [];
        foreach ($logs as $pallet_id => $log) {
            $first_log = $log[0];
            $obj = new stdClass();
            $obj->pallet_id = $pallet_id;
            $obj->so_luong = $log->sum('so_luong') ?? '';
            $obj->khach_hang = $first_log->order->short_name ?? "";
            $obj->locator_id = $first_log->locator_id;
            $obj->mdh = $first_log->order->mdh ?? '';
            $obj->thoi_gian_nhap = isset($first_log->created_at) ? date('d/m/Y H:i', strtotime($first_log->created_at)) : '';
            $data[] = $obj;
        }
        return $this->success($data);
    }

    public function getLogExportWarehouseFG(Request $request)
    {
        $delivery_query = DeliveryNote::orderBy('created_at', 'DESC');
        if (isset($request->start_date) && isset($request->end_date)) {
            $delivery_query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)));
        }
        // if (isset($request->delivery_note_id)) {
        //     $delivery_query->where('id', $request->delivery_note_id);
        // }
        $delivery_query->whereHas('exporters', function ($q) use ($request) {
            if ($request->user()->username !== 'admin') {
                $q->where('admin_user_id', $request->user()->id);
            }
        });
        $delivery_notes = $delivery_query->get();
        $query = WareHouseFGExport::whereIn('delivery_note_id', $delivery_notes->pluck('id')->toArray());
        if (isset($request->start_date) && isset($request->end_date)) {
            $query->whereDate('ngay_xuat', '>=', date('Y-m-d', strtotime($request->start_date)))->whereDate('ngay_xuat', '<=', date('Y-m-d', strtotime($request->end_date)));
        } else {
            $query->whereDate('ngay_xuat', date('Y-m-d'));
        }
        if (isset($request->delivery_note_id)) {
            $query->where('delivery_note_id', $request->delivery_note_id);
        }
        $fg_exports = $query->orderBy('created_at', 'DESC')->with([
            'warehouse_fg_log' => function ($subQuery) {
                $subQuery->where('type', 2);
            },
            // 'lsxpallets'=> function ($subQuery) {
            //     $subQuery->where('remain_quantity', '>', 0);
            // }
        ])->get();
        $data = [];
        $lsx_array = [];
        $test = [];
        if (count($fg_exports) > 0) {
            foreach ($fg_exports as $key => $fg_export) {
                $lsx_pallets = $fg_export->lsxpallets;
                $so_luong_da_xuat = $fg_export->warehouse_fg_log->sum('so_luong');
                // $test[] = [$fg_export->id, $lsx_pallets, $so_luong_da_xuat];
                $sum_sl = 0;
                $sl_can_xuat = $fg_export->so_luong - $so_luong_da_xuat;
                foreach ($lsx_pallets as $lsx_pallet) {
                    if ($sum_sl < $sl_can_xuat && $lsx_pallet->locator_fg_map) {
                        if (in_array($lsx_pallet->lo_sx, $lsx_array)) {
                            continue;
                        }
                        $lsx_array[] = $lsx_pallet->lo_sx;
                        $khach_hang = $lsx_pallet->customer_id ?? "";
                        $data[$lsx_pallet->pallet_id]['pallet_id'] = $lsx_pallet->pallet_id;
                        $data[$lsx_pallet->pallet_id]['locator_id'] = $lsx_pallet->locator_fg_map->locator_id;
                        $data[$lsx_pallet->pallet_id]['so_luong'] = $lsx_pallet->pallet->so_luong ?? 0;
                        $data[$lsx_pallet->pallet_id]['thoi_gian_xuat'] = date('d/m/Y H:i:s', strtotime($fg_export->ngay_xuat));
                        $data[$lsx_pallet->pallet_id]['khach_hang'] = $khach_hang;
                        $data[$lsx_pallet->pallet_id]['delivery_note_id'] = $fg_export->delivery_note_id;
                        if (!isset($data[$lsx_pallet->pallet_id]['lo_sx'])) $data[$lsx_pallet->pallet_id]['lo_sx'] = [];
                        $data[$lsx_pallet->pallet_id]['lo_sx'][] = [
                            'lo_sx' => $lsx_pallet->lo_sx,
                            'so_luong' => $lsx_pallet->remain_quantity,
                            'mql' => $lsx_pallet->mql,
                            'mdh' => $lsx_pallet->mdh,
                            'khach_hang' => $khach_hang,
                            'pallet_id' => $lsx_pallet->pallet_id,
                            'delivery_note_id' => $fg_export->delivery_note_id
                        ];
                        $sum_sl += $lsx_pallet->so_luong;
                    } else {
                        continue;
                    }
                }
            }
        }
        // return $test;
        return $this->success(['data' => array_values($data), 'delivery_notes' => $delivery_notes]);
    }

    public function checkLoSXPallet(Request $request)
    {
        $pallet = Pallet::where('id', $request->pallet_id)->first();
        if (!$pallet) return $this->failure('', 'Không tìm thấy pallet');
        $lsx_pallet = LSXPallet::where('pallet_id', $pallet->id)->get();
        return $this->success([]);
    }

    public function exportPallet(Request $request)
    {
        $input = $request->all();
        if (count($input) <= 0) {
            return $this->failure('', 'Không có lô cần xuất');
        }
        $locatorFgMap = LocatorFGMap::where('pallet_id', $input[0]['pallet_id'] ?? 0)->first();
        if ($locatorFgMap) {
            $vi_tri = $locatorFgMap->locator_id;
        } else {
            $vi_tri = '';
        }

        try {
            DB::beginTransaction();
            $pallet_quantity = 0;
            foreach ($input as $lo) {
                $lsx_pallet = LSXPallet::where('pallet_id', $lo['pallet_id'])->where('lo_sx', $lo['lo_sx'])->first();
                if ($lsx_pallet->remain_quantity < $lo['so_luong']) {
                    return $this->failure('', 'Số lượng còn lại của lô ' . $lo['lo_sx'] . ' không đủ');
                }
                $inp['created_by'] = $request->user()->id;
                $inp['locator_id'] = $vi_tri;
                $inp['so_luong'] = $lo['so_luong'];
                $inp['lo_sx'] = $lo['lo_sx'];
                $inp['pallet_id'] = $lo['pallet_id'];
                $inp['type'] = 2;
                $inp['order_id'] = $lsx_pallet->order_id;
                $inp['delivery_note_id'] = $lo['delivery_note_id'];
                $log = WarehouseFGLog::where($inp)->get();
                if (!$lsx_pallet->remain_quantity) {
                    return $this->failure('', 'Đã xuất kho');
                } else {
                    WarehouseFGLog::create($inp);
                }
                $import = LSXPallet::where('lo_sx', $lo['lo_sx'])->first();
                if ($import) {
                    $remain = ($import->remain_quantity - $lo['so_luong']) > 0 ? ($import->remain_quantity - $lo['so_luong']) : 0;
                    $pallet_quantity += $remain;
                    $import->update(['remain_quantity' => $remain]);
                }
            }
            if (!$pallet_quantity) {
                LocatorFGMap::where('pallet_id', $input[0]['pallet_id'] ?? null)->delete();
            }

            DB::commit();
            return $this->success([], 'Xuất kho thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            ErrorLog::saveError($request, $e);
            throw $e;
        }
    }

    public function getWarehouseFGExportList(Request $request)
    {
        $page = $request->page - 1;
        $pageSize = $request->pageSize;
        $query = WareHouseFGExport::with('delivery_note.creator', 'delivery_note.exporters', 'order')->orderBy('mdh', 'ASC')->orderBy('mql', 'ASC');
        if (isset($request->start_date) && isset($request->end_date)) {
            $query->whereDate('ngay_xuat', '>=', date('Y-m-d 00:00:00', strtotime($request->start_date)))->whereDate('ngay_xuat', '<=', date('Y-m-d 23:59:59', strtotime($request->end_date)));
        }
        if (isset($request->customer_id)) {
            $query->where('warehouse_fg_export.customer_id', 'like', '%' . $request->customer_id . '%');
        }
        if (isset($request->mdh)) {
            if (is_array($request->mdh)) {
                $query->where(function ($q) use ($request) {
                    foreach ($request->mdh as $key => $mdh) {
                        $q->orWhere('mdh', 'like', "%$mdh%");
                    }
                });
            } else {
                $query->where('mdh', 'like', "%$request->mdh%");
            }
        }
        if (isset($request->mql)) {
            if (is_array($request->mql)) {
                $query->where(function ($q) use ($request) {
                    foreach ($request->mql as $key => $mql) {
                        $q->orWhere('mql', $mql);
                    }
                });
            } else {
                $query->where('mql', $request->mql);
            }
        }
        if (isset($request->delivery_note_id)) {
            $query->where('warehouse_fg_export.delivery_note_id', $request->delivery_note_id);
        }
        if (isset($request->created_by)) {
            $user_ids = CustomUser::where('name', 'like', '%' . $request->created_by . '%')->pluck('id')->toArray();
            $query = $query->whereIn('created_by', $user_ids);
        }

        $totalPage = $query->count();
        $records = $query->offset($page * $pageSize)->limit($pageSize)->get();
        foreach ($records as $record) {
            $record->so_luong_dh = $record->order->sl ?? '';
            $record->exporter_id = $record->delivery_note->exporter_id ?? '';
            $record->exporter_ids = $record->delivery_note ? ($record->delivery_note->exporters()->pluck('admin_user_id')->toArray() ?? []) : [];
            $record->vehicle_id = $record->delivery_note->vehicle_id ?? '';
            $record->driver_id = $record->delivery_note->driver_id ?? '';
        }
        return $this->success(['data' => $records, 'totalPage' => $totalPage]);
    }

    public function exportWarehouseFGExportList(Request $request)
    {
        $query = WareHouseFGExport::with('delivery_note', 'order', 'creator')->orderBy('mdh', 'ASC')->orderBy('mql', 'ASC');
        if (isset($request->start_date) && isset($request->end_date)) {
            $query->whereDate('ngay_xuat', '>=', date('Y-m-d 00:00:00', strtotime($request->start_date)))->whereDate('ngay_xuat', '<=', date('Y-m-d 23:59:59', strtotime($request->end_date)));
        }
        if (isset($request->customer_id)) {
            $query->where('warehouse_fg_export.customer_id', 'like', '%' . $request->customer_id . '%');
        }
        if (isset($request->mdh)) {
            $query->where('warehouse_fg_export.mdh', 'like', '%' . $request->mdh . '%');
        }
        if (isset($request->mql)) {
            $query->where('warehouse_fg_export.mql', $request->mql);
        }
        if (isset($request->delivery_note_id)) {
            $query->where('warehouse_fg_export.delivery_note_id', 'like', '%' . $request->delivery_note_id . '%');
        }
        if (isset($request->created_by)) {
            $user_ids = CustomUser::where('name', 'like', '%' . $request->created_by . '%')->pluck('id')->toArray();
            $query = $query->whereIn('created_by', $user_ids);
        }
        $records = $query->get();
        $data = [];
        foreach ($records as $record) {
            $obj = new stdClass;
            $obj->delivery_note_id = $record->delivery_note_id;
            $obj->created_by = $record->creator->name ?? "";
            $obj->ngay_xuat = date('d/m/Y', strtotime($record->ngay_xuat));
            $obj->thoi_gian_xuat = date('H:i:s', strtotime($record->ngay_xuat));
            $obj->khach_hang = $record->order->short_name ?? "";
            $obj->mdh = $record->mdh;
            $obj->mql = $record->mql;
            $obj->so_luong_dh = $record->order->sl ?? '';
            $obj->so_luong = $record->so_luong ?? '';
            $obj->xuong_giao = $record->xuong_giao;
            $obj->driver_name = $record->delivery_note->driver->name ?? '';
            $obj->vehicle_id = $record->delivery_note->vehicle->id ?? '';
            $obj->exporter_name = $record->delivery_note->exporter->name ?? '';
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
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $start_row = 2;
        $start_col = 1;
        $sheet = $spreadsheet->getActiveSheet();
        $header = [
            'Lệnh xuất',
            "Người báo xuất",
            'Ngày xuất',
            "Thời gian xuất",
            "Khách hàng",
            "MĐH",
            "MQL",
            'Số lượng ĐH',
            "Số lượng cần xuất",
            "FAC",
            "Tài xế",
            'Số xe',
            'Người xuất'
        ];
        foreach ($header as $key => $cell) {
            $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            $start_col += 1;
        }

        $sheet->setCellValue([1, 1], 'Kế hoạch xuất kho')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->fromArray($data, null, 'A3');
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
        header('Content-Disposition: attachment;filename="Kế hoạch xuất kho.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Kế hoạch xuất kho.xlsx');
        $href = '/exported_files/Kế hoạch xuất kho.xlsx';
        return $this->success($href);
    }

    public function updateExportFGLog(Request $request)
    {
        $input = $request->all();
        // return $input;
        try {
            DB::beginTransaction();
            $log = WarehouseFGLog::find($input['id']);
            if (!$log) {
                return $this->failure('', 'Không tìm thấy bản lịch sử nhập');
            }
            if ($input['sl_xuat'] > $log->so_luong) {
                return $this->failure('', 'Số lượng xuất không được lớn hơn số lượng nhập');
            }
            WarehouseFGLog::where('lo_sx', $log->lo_sx)->where('pallet_id', $log->pallet_id)->where('type', 2)->delete();
            $inp['created_by'] = $request->user()->id;
            $inp['created_at'] = isset($input['tg_xuat']) ? date('Y-m-d H:i:s', strtotime($input['tg_xuat'])) : date('Y-m-d H:i:s');
            $inp['locator_id'] = $input['locator_id'];
            $inp['so_luong'] = $input['sl_xuat'];
            $inp['lo_sx'] = $input['lo_sx'];
            $inp['pallet_id'] = $input['pallet_id'];
            $inp['type'] = 2;
            $inp['order_id'] = $input['order_id'];
            $inp['delivery_note_id'] = $input['delivery_note_id'] ?? null;
            WarehouseFGLog::create($inp);
            LocatorFGMap::where('pallet_id', $input['pallet_id'])->delete();
            DB::commit();
            return $this->success([], 'Xuất kho thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            ErrorLog::saveError($request, $e);
            throw $e;
            return $this->failure($e, 'Có lỗi xảy ra');
        }
    }

    public function updateWarehouseFGExport(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $fields = ['xuong_giao'];
            $record = WareHouseFGExport::find($input['id']);
            if ($record) {
                $record->update($input);
                if (isset($input['ids'])) {
                    $update_fileds = array_intersect_key($input, array_flip($fields));
                    WareHouseFGExport::whereIn('id', $input['ids'])->update($update_fileds);
                }
            }
            DB::commit();
            return $this->success($record);
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Xoá không thành công');
        }
    }

    public function createWarehouseFGExport(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            // $note_counter = DeliveryNote::count();
            // $delivery_note = DeliveryNote::create([
            //     'id' => date('d/m/y-') . ($note_counter + 1),
            //     'created_by' => $request->user()->id
            // ]);
            $data = [];
            foreach ($input['orders'] as $value) {
                $export_input = [];
                $export_input['customer_id'] = $value['short_name'];
                $export_input['ngay_xuat'] = date('Y-m-d H:i:s', strtotime($input['ngay_xuat']));
                $export_input['mdh'] = $value['mdh'];
                $export_input['mql'] = $value['mql'];
                $export_input['order_id'] = $value['id'];
                $export_input['so_luong'] = $value['sl'];
                $export_input['xuong_giao'] = $value['xuong_giao'] ?? "";
                $export_input['created_by'] = $request->user()->id;
                $data[] = $export_input;
            }
            $create = WareHouseFGExport::insert($data);
            DB::commit();
            return $this->success($create, 'Tạo thành công');
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
    }

    public function deleteWarehouseFGExport(Request $request, $id)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $delete = WareHouseFGExport::where('id', $id)->delete();
            DB::commit();
            return $this->success('', 'Xoá thành công');
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
    }

    public function getListPalletWarehouse(Request $request)
    {
        $records = Pallet::with('losxpallet')->select('*', 'id as pallet_id')->get();
        return $this->success($records);
    }

    public function importFGSave(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $pallet = Pallet::find($input['pallet_id']);
            if (!$pallet) {
                return $this->failure('', 'Mã pallet không phù hợp');
            }
            $locator_fg = LocatorFG::find($input['locator_id']);
            if (!$locator_fg) {
                return $this->failure('', 'Vị trí nhập kho không phù hợp');
            }
            if (count($pallet->warehouse_fg_log ?? []) > 0) {
                return $this->failure('', 'Pallet đã được nhập kho');
            }
            LocatorFGMap::updateOrCreate(['pallet_id' => $input['pallet_id']], ['locator_id' => $input['locator_id']]);
            $lsxs = LSXPallet::with('warehouseFGLog')->where('pallet_id', $input['pallet_id'])->get();
            foreach ($lsxs as $key => $lsx) {
                $inp = [];
                $inp['lo_sx'] = $lsx->lo_sx;
                $inp['pallet_id'] = $input['pallet_id'];
                $inp['type'] = 1;
                $inp['locator_id'] = $input['locator_id'];
                $inp['so_luong'] = $lsx->so_luong;
                $inp['created_by'] = $request->user()->id;
                $inp['order_id'] = $lsx->order_id;
                $inp['nhap_du'] = $this->calculateNhapDu($lsx->so_luong, $lsx->order_id);
                WarehouseFGLog::create($inp);
                $lsx->update(['remain_quantity' => $lsx->so_luong]);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
        return $this->success('Nhập kho thành công');
    }

    public function calculateNhapDu($so_luong, $order_id)
    {
        $order = Order::find($order_id);
        if ($order) {
            $so_luong_dh = $order->sl;
            $so_luong_nhap = WarehouseFGLog::where('order_id', $order_id)->where('type', 1)->sum('so_luong');
            $so_luong_nhap_du = $so_luong_dh - $so_luong_nhap - $so_luong;
            return $so_luong_nhap_du;
        }
        return 0;
    }

    public function createWarehouseFGLogs(Request $request)
    {
        if (isset($request->lot_id) && isset($request->locator_id)) {
            foreach ($request->lot_id ?? [] as $lot_id) {
                $lot = Lot::find($lot_id);
                if ($lot) {
                    $log = WarehouseFGLog::create([
                        'lot_id' => $lot_id,
                        'so_luong' => $lot->so_luong,
                        'locator_id' => $request->locator_id,
                        'type' => 1,
                        'created_by' => $request->user()->id
                    ]);
                    return $this->success($log);
                } else {
                    return $this->failure('', 'Không tìm thấy lot');
                }
            }
        }
        return $this->failure('', 'Không có lot và vị trí');
    }

    public function importMLTReimport(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            foreach ($input as $reimport_data) {
                $material = Material::find($reimport_data['material_id']);
                if ($material) {
                    $log = WarehouseMLTLog::where('material_id', $reimport_data['material_id'])->whereNull('tg_xuat')->first();
                    $reimport_data['so_m_toi'] = floor(($reimport_data['so_kg'] / ($material->kho_giay / 100)) / ($material->dinh_luong / 1000));
                    $material->update($reimport_data);
                    unset($reimport_data['so_m_toi']);
                    $inp['material_id'] = $reimport_data['material_id'];
                    $inp['locator_id'] = $reimport_data['locator_id'];
                    $inp['so_kg_nhap'] = $reimport_data['so_kg'];
                    $inp['tg_nhap'] = date('Y-m-d H:i:s');
                    $inp['importer_id'] = $request->user()->id;
                    $check = WarehouseMLTLog::where('material_id', $inp['material_id'])->whereNull('locator_id')->first();
                    if ($check) {
                        $check->update(['locator_id' => $inp['locator_id'], 'so_kg_nhap' => $inp['so_kg_nhap']]);
                    } else {
                        WarehouseMLTLog::create($inp);
                    }
                    LocatorMLTMap::updateOrCreate(
                        ['material_id' => $inp['material_id']],
                        ['locator_mlt_id' => $inp['locator_id']]
                    );
                    $locator = LocatorMLT::find($inp['locator_id']);
                    $locator->update(['capacity' => $locator->capacity + 1]);
                } else {
                    return $this->failure('', 'Không tìm thấy cuộn');
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }

        return $this->success([], 'Nhập lại thành công');
    }

    public function uploadNKNVL(Request $request)
    {
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
            //Lấy dứ liệu từ dòng thứ 3
            if ($key > 8) {
                $record = WareHouseMLTImport::where('ma_cuon_ncc', $row['B'])->first();
                if ($record) {
                    return $this->failure([], 'Hàng số ' . ($key) . ': Mã cuộn đã tồn tại');
                }
                if (!$row['B']) {
                    return $this->failure([], 'Hàng số ' . ($key) . ': Thiếu mã cuộn');
                }
                if (!$row['C']) {
                    return $this->failure([], 'Hàng số ' . ($key) . ': Thiếu loại giấy');
                }
                if (!$row['E']) {
                    return $this->failure([], 'Hàng số ' . ($key) . ': Thiếu khổ giấy');
                }
                if (!$row['F']) {
                    return $this->failure([], 'Hàng số ' . ($key) . ': Thiếu định lượng');
                }
            }
        }
        try {
            DB::beginTransaction();
            $warehouse_mlt_import = [];
            $warehouse_mlt_materials = [];
            // $receipt_note_query = GoodsReceiptNote::query();
            // $receipt_note_count = $receipt_note_query->where('id', 'like', "%" . date('ym-') . "%")->count();
            $latest_receipt_note = GoodsReceiptNote::where('id', 'like', date('ym-') . '%')->orderByraw('CHAR_LENGTH(id) DESC')->orderBy('id', 'DESC')->first();
            $id = '';
            if ($latest_receipt_note) {
                $id = (int)str_replace(date('ym-'), '', $latest_receipt_note->id);
            } else {
                $id = 0;
            }
            $receipt_note = GoodsReceiptNote::create([
                'id' => date('ym-') . str_pad($id + 1, 3, '0', STR_PAD_LEFT),
                'supplier_name' => $allDataInSheet[3]['B'],
                'vehicle_number' => $allDataInSheet[3]['E'],
                'total_weight' => $allDataInSheet[4]['E'],
                'vehicle_weight' => $allDataInSheet[5]['E'],
                'material_weight' => $allDataInSheet[6]['E'],
            ]);
            foreach ($allDataInSheet as $key => $row) {
                //Lấy dứ liệu từ dòng thứ 3
                if ($key > 8) {
                    $input = [];
                    $input['ma_cuon_ncc'] = trim($row['B']);
                    $input['ma_vat_tu'] = $row['C'] . '(' . $row['F'] . ')' . $row['E'];
                    $input['so_kg'] = str_replace([',', '.'], '', $row['G']);
                    $input['loai_giay'] = trim($row['C']);
                    $input['kho_giay'] = $row['E'];
                    $input['dinh_luong'] = $row['F'];
                    $input['fsc'] = (($row['D'] && strtolower($row['D']) === 'x') ? 1 : 0);
                    $input['goods_receipt_note_id'] = $receipt_note->id;
                    $warehouse_mlt_import = WareHouseMLTImport::create($input);
                    if (!empty($allDataInSheet[3]['B'])) {
                        Supplier::firstOrCreate(['id' => $input['loai_giay']], ['name' => $allDataInSheet[3]['B']]);
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Tải lên không thành công');
        }

        return $this->success($warehouse_mlt_import, 'Tải lên thành công');
    }

    public function importMLTOverall(Request $request)
    {
        $import_logs = WarehouseMLTLog::whereDate('tg_nhap', date('Y-m-d'))->get();
        $export_logs = WarehouseMLTLog::whereDate('tg_xuat', date('Y-m-d'))->get();
        $obj = new stdClass();
        $obj->sl_nhap = $import_logs->count();
        $obj->sl_xuat = $export_logs->count();
        $obj->sl_ton = LocatorMLTMap::count();
        $record = LocatorMLTMap::orderBy('created_at', 'ASC')->first();
        $obj->so_ngay_ton = round((strtotime(date('Y-m-d H:i:s')) - strtotime($record->created_at)) / 86400);
        return $this->success($obj);
    }

    public function getFGOverall(Request $request)
    {
        $import_query = WarehouseFGLog::query();
        $lsx_pallet_query = LSXPallet::query();
        if (isset($request->start_date) && isset($request->start_date)) {
            $import_query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)));
            $lsx_pallet_query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)));
        } else {
            $import_query->whereDate('created_at', date('Y-m-d'));
            $lsx_pallet_query->whereDate('created_at', date('Y-m-d'));
        }
        $export_query = clone $import_query;
        $import_logs = $import_query->where('type', 1)->select('pallet_id')->distinct('pallet_id')->count();
        $export_logs = $export_query->where('type', 2)->select('pallet_id')->distinct('pallet_id')->count();
        $obj = new stdClass();
        $obj->sl_nhap = $import_logs;
        $obj->sl_xuat = $export_logs;
        $obj->sl_pallet_tao =  $lsx_pallet_query->select('pallet_id')->distinct('pallet_id')->count();
        return $this->success($obj);
    }

    public function handleNGMaterial(Request $request)
    {
        $input = $request->all();
        $count = 0;
        try {
            DB::beginTransaction();
            foreach ($input['data'] as $reimport_data) {
                $material = Material::find($reimport_data['material_id']);
                if ($material) {
                    $reimport_data['so_m_toi'] = floor(($reimport_data['so_kg'] / ($material->kho_giay / 100)) / ($material->dinh_luong / 1000));
                    $record = $material->update($reimport_data);
                    $reimport_data['locator_id'] = 'C13';
                    unset($reimport_data['so_m_toi']);
                    $reimport_data['importer_id'] = $request->user()->id;
                    $reimport_data['tg_nhap'] = date('Y-m-d H:i:s');
                    $log = WarehouseMLTLog::create($reimport_data);
                    $count++;
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }

        return $this->success('', 'Đã nhập ' . $count . ' cuộn vào khu 13');
    }

    public function checkMapping(Request $request)
    {
        $lo_sx = $request->lo_sx;
        $role = $request->user()->roles()->first();
        $machine_id = $role->machine_id;
        $machine = Machine::find($machine_id);
        $plan = ProductionPlan::with('order.buyer')->where('lo_sx', $lo_sx)->first();
        if ($machine->line_id == Line::LINE_SONG) {
            $buyer =  (array)$plan->order->buyer->toArray();
            $order =  $plan->order;
            $arr_vt = ['S010501' => 'ma_cuon_f', 'S010402' => 'ma_cuon_le', 'S010401' => 'ma_cuon_se', 'S010302' => 'ma_cuon_lb', 'S010301' => 'ma_cuon_sb', 'S010202' => 'ma_cuon_lc', 'S010201' => 'ma_cuon_sc'];
            $material_id = $request->value[0];
            $ma_vat_tu = $buyer[$arr_vt[$request->vi_tri]] . $order->kho_tong;
            $count = 0;
            $material = Material::find($material_id);
            if (!$material) {
                return $this->failure([], 'Mã cuộn không tồn tại');
            }
            $inp = [];
            // if ($material->ma_vat_tu == $ma_vat_tu) {
            $checkmap = Mapping::where('machine_id', $machine->id)->where('lo_sx', $lo_sx)->where('position', $request->vi_tri)->first();
            if (!$checkmap) {
                $inp['machine_id'] =  $machine->id;
                $inp['lo_sx'] =  $lo_sx;
                $inp['position'] =  $request->vi_tri;
                $inp['user_id'] =  $request->user()->id;
                $inp['info'] =  $buyer[$arr_vt[$request->vi_tri]];
                Mapping::create($inp);
            }
            $count_map = Mapping::where('lo_sx', $lo_sx)->count();
            if ($count_map === $buyer['so_lop']) {
                LSXLog::where('lo_sx', $lo_sx)->update(['mapping' => 1, 'map_time' => date('Y-m-d H:i:s')]);
            }
            return $this->success($material->id, 'Mapping thành công');
            // }
        }
        // $record = Material::find($request->get('material_id'));
        // if (!$record) {
        //     return $this->failure([], 'Mã cuộn không tồn tại');
        // }
        return $this->success([]);
    }

    public function resultMapping(Request $request)
    {
        $input = $request->all();
        $role = $request->user()->roles()->first();
        $machine_id = $role->machine_id;
        Mapping::where('machine_id', $machine_id)->where('lo_sx', $input['lo_sx'])->update(['user_id' => $request->user()->id]);
        $machine = Machine::find($role->machine_id);
        if (!is_null($machine->parent_id)) {
            $plan = ProductionPlan::with('order.buyer')->where('lo_sx', $input['lo_sx'])->first();
            $buyer =  $plan->order->buyer;
            $count_map = Mapping::where('lo_sx', $input['lo_sx'])->count();
            if ($buyer->so_lop == $count_map) {
                LSXLog::where('machine_id', $machine->parent_id)->where('lo_sx', $input['lo_sx'])->update(['mapping' => 1, 'map_time' => date('Y-m-d H:i:s')]);
            }
        } else {
            $check = Mapping::where('machine_id', $machine_id)->where('lo_sx', $input['lo_sx'])->whereNull('user_id')->first();
            if (!$check) {
                LSXLog::where('machine_id', $machine_id)->where('lo_sx', $input['lo_sx'])->update(['mapping' => 1, 'map_time' => date('Y-m-d H:i:s')]);
            }
        }
        return $this->success([]);
    }

    public function getMachineParameterList(Request $request)
    {
        $input = $request->all();
        $machine_ids = Machine::where('id', $input['machine_id'])->orWhere('parent_id', $input['machine_id'])->pluck('id')->toArray();
        $machine_params = MachineParameter::whereIn('machine_id', $machine_ids)->where('is_if', 0)->get();
        foreach ($machine_params as $key => $param) {
            $col = new stdClass;
            $col->title = $param->name;
            $col->dataIndex = $param->parameter_id;
            $col->key = $param->parameter_id;
            $columns[] = $col;
        }
        $data = [];
        $lsx_ids = ProductionPlan::whereDate('ngay_sx', '>=', date('Y-m-d', strtotime($input['start_date'])))->whereDate('ngay_sx', '<=', date('Y-m-d', strtotime($input['end_date'])))->pluck('lo_sx');
        $lsxlogs = LSXLog::with('plan.order', 'tem.order')
            ->where('machine_id', $input['machine_id'])
            ->whereIn('lo_sx', $lsx_ids)
            ->get()->sortBy('plan.thu_tu_uu_tien');
        foreach ($lsxlogs as $key => $value) {
            $obj = new stdClass();
            $order = null;
            if ($value->plan) {
                $order = $value->plan->order;
            } else if ($value->tem) {
                $order = $value->tem->order;
            }
            $obj->lo_sx = $value->lo_sx;
            $obj->machine_id = $value->machine_id;
            $obj->mapping = $value->mapping;
            $obj->ma_khach_hang = $order->short_name ?? null;
            $obj->layout_id = $order->layout_id ?? null;
            $obj->so_lop = $order->buyer->so_lop ?? null;
            $obj->kho_tong = $order->kho_tong ?? null;
            $obj->dai_tam = $order->dai_tam ?? null;
            $obj->so_luong = ceil($value->plan->sl_kh / ($order->so_ra ?? 1))  ?? null;
            $obj->mdh = $order->mdh ?? null;
            $obj->mql = $order->mql ?? null;
            $data[] = is_null($value->params) ? (array)$obj : array_merge((array)$obj, $value->params);
        }
        $object = new stdClass();
        $object->columns = $columns ?? [];
        $object->data = $data;
        return $this->success($object);
    }

    public function importLocatorMLTMap(Request $request)
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
        $sheetCount = $spreadsheet->getSheetCount();
        $map = [];
        $locate = [];
        for ($i = 0; $i < $sheetCount; $i++) {
            $sheet = $spreadsheet->getSheet($i);
            $sheetData = $sheet->toArray(null, true, true, true);

            foreach ($sheetData as $key => $row) {
                //Lấy dứ liệu từ dòng thứ 2
                if ($key > 1) {
                    if ($row['B']) {
                        $locate = explode('.', $row['B']);
                    }
                    if (count($locate) === 2) {
                        $input['locator_mlt_id'] = $locate[0] . '.' . str_pad($locate[1], 3, '0', STR_PAD_LEFT);
                    } else {
                        $input['locator_mlt_id'] = $row['B'];
                    }
                    $input['material_id'] = $row['C'];
                    $input['created_at'] = date('Y-m-d H:i:s', $this->getStrtotime($row['D']));
                    $input['updated_at'] = date('Y-m-d H:i:s');
                    if ($input['locator_mlt_id'] && $input['material_id']) {
                        $map[] = $input;
                    }
                }
            }
        }
        // LocatorMLTMap::truncate();
        foreach ($map as $key => $input) {
            $material = Material::where('id', $input['material_id'])->first();
            if (!$material) {
                continue;
            }
            LocatorMLTMap::create($input);
            $input['so_kg'] = $material->so_kg;
            $input['locator_id'] = $input['locator_mlt_id'];
            $input['type'] = 1;
            WarehouseMLTLog::create($input);
        }
        return $this->success([], 'Upload thành công');
    }

    function getStrtotime($timeDateStr, $formatOfStr = "d/m/Y")
    {
        // Same as strtotime() but using the format $formatOfStr.
        // Works with PHP version 5.5 and later.
        // On error reading the time string, returns a date that never existed. 3/09/1752 Julian/Gregorian calendar switch.
        $timeStamp = DateTimeImmutable::createFromFormat($formatOfStr, $timeDateStr);
        if ($timeStamp === false) {
            // Bad date string or format string.
            return -6858133619; // 3/09/1752
        } else {
            // Date string and format ok.
            return $timeStamp->format("U"); // UNIX timestamp from 1/01/1970,  0:00:00 gmt
        }
    }

    public function importMaterial(Request $request)
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
        $materials = [];
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 4
            if ($key > 1) {
                $input = [];
                $input['id'] = $row['B'];
                if (in_array($input['id'], array_column($materials, 'id'))) {
                    continue;
                }
                $input['ma_cuon_ncc'] = $row['C'];
                $input['ma_vat_tu'] = $row['D'] . '(' . $row['G'] . ')' . $row['F'];
                $input['so_kg'] = $row['H'];
                $input['loai_giay'] = $row['D'];
                $input['kho_giay'] = $row['F'];
                $input['dinh_luong'] = $row['G'];
                $input['fsc'] = $row['E'] === "X" ? 1 : 0;
                if ($input['id']) {
                    $materials[] = $input;
                }
            }
        }
        Material::truncate();
        foreach ($materials as $key => $input) {
            Material::create($input);
        }
        return $this->success([], 'Upload thành công');
    }

    public function uploadBUYER(Request $request)
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
        try {
            DB::beginTransaction();
            Buyer::whereNotNull('id')->delete();
            foreach ($allDataInSheet as $key => $row) {
                //Lấy dứ liệu từ dòng thứ 4
                if ($key > 1 && !is_null($row['B'])) {
                    $buyer = Buyer::find($row['B']);
                    if ($buyer) {
                        continue;
                    }
                    $input['id'] = $row['B'];
                    $input['customer_id'] = $row['C'];
                    $input['buyer_vt'] = $row['D'];
                    $input['phan_loai_1'] = $row['E'];
                    $input['so_lop'] = $row['F'];
                    $input['ma_cuon_f'] = $row['G'];
                    $input['ma_cuon_se'] = $row['H'];
                    $input['ma_cuon_le'] = $row['I'];
                    $input['ma_cuon_sb'] = $row['J'];
                    $input['ma_cuon_lb'] = $row['K'];
                    $input['ma_cuon_sc'] = $row['L'];
                    $input['ma_cuon_lc'] = $row['M'];
                    $input['ket_cau_giay'] = $row['N'];
                    $input['note'] = $row['O'];
                    Buyer::create($input);
                }
            }
            DB::commit();
            return $this->success([], 'Upload thành công');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->failure([], 'File import có vấn đề vui lòng kiểm tra lại');
        }
    }

    public function uploadLAYOUT(Request $request)
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
        try {
            DB::beginTransaction();
            Layout::whereNotNull('id')->delete;
            foreach ($allDataInSheet as $key => $row) {
                //Lấy dứ liệu từ dòng thứ 4
                if ($key > 2 && $row['C']) {
                    $input['customer_id'] = $row['B'];
                    $input['machine_layout_id'] = $row['C'];
                    $input['machine_id'] = $row['D'];
                    $input['layout_id'] = $row['E'];
                    $input['toc_do'] = $row['F'];
                    $input['tg_doi_model'] = $row['G'];

                    $input['ma_film_1'] = $row['H'];
                    $input['ma_muc_1'] = $row['I'];
                    $input['do_nhot_1'] = $row['J'];
                    $input['vi_tri_film_1'] = $row['K'];
                    $input['al_film_1'] = $row['L'];
                    $input['al_muc_1'] = $row['M'];

                    $input['ma_film_2'] = $row['N'];
                    $input['ma_muc_2'] = $row['O'];
                    $input['do_nhot_2'] = $row['P'];
                    $input['vi_tri_film_2'] = $row['Q'];
                    $input['al_film_2'] = $row['R'];
                    $input['al_muc_2'] = $row['S'];

                    $input['ma_film_3'] = $row['T'];
                    $input['ma_muc_3'] = $row['V'];
                    $input['do_nhot_3'] = $row['U'];
                    $input['vi_tri_film_3'] = $row['W'];
                    $input['al_film_3'] = $row['X'];
                    $input['al_muc_3'] = $row['Y'];

                    $input['ma_film_4'] = $row['Z'];
                    $input['ma_muc_4'] = $row['AA'];
                    $input['do_nhot_4'] = $row['AB'];
                    $input['vi_tri_film_4'] = $row['AC'];
                    $input['al_film_4'] = $row['AD'];
                    $input['al_muc_4'] = $row['AE'];

                    $input['ma_film_5'] = $row['AF'];
                    $input['ma_muc_5'] = $row['AG'];
                    $input['do_nhot_5'] = $row['AH'];
                    $input['vi_tri_film_5'] = $row['AI'];
                    $input['al_film_5'] = $row['AJ'];
                    $input['al_muc_5'] = $row['AK'];

                    $input['ma_khuon'] = $row['AL'];
                    $input['vt_lo_bat_khuon'] = $row['AM'];
                    $input['vt_khuon'] = $row['AN'];
                    $input['al_khuon'] = $row['AO'];
                    Layout::create($input);
                }
            }
            DB::commit();
            return $this->success([], 'Upload thành công');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->failure([], 'File import có vấn đề vui lòng kiểm tra lại');
        }
    }

    public function uploadTem(Request $request)
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
        // Tem::truncate();
        try {
            DB::beginTransaction();
            $prefix = 'T' . date('ymd');
            Tem::where('display', 1)->where('created_by', $request->user()->id)->update(['display' => 0]);
            $newest_tem = Tem::where('lo_sx', 'like', "$prefix%")->orderBy('id', 'DESC')->first();
            $index = $newest_tem ? (int)str_replace($prefix, '', $newest_tem->lo_sx) : 0;
            foreach ($allDataInSheet as $key => $row) {
                //Lấy dứ liệu từ dòng thứ 4
                if ($key > 3 && $row['C'] && $row['D']) {
                    $input = [];
                    $input['ordering'] = $row['B'];
                    $input['lo_sx'] = $prefix . str_pad($index + 1, 4, '0', STR_PAD_LEFT);
                    $input['khach_hang'] = $row['C'];
                    $input['mdh'] = $row['D'];
                    $input['order'] = $row['E'];
                    $input['mql'] = $row['F'];
                    $dai = $row['G'] ? $row['G'] . 'X' : '';
                    $rong = $row['H'] ? $row['H'] . 'X' : '';
                    $cao = $row['I'] ? $row['I'] : '';
                    $input['quy_cach'] = $dai . $rong . $cao;
                    $input['so_luong'] = str_replace(',', '', $row['J']);
                    $input['gmo'] = $row['K'];
                    $input['po'] = $row['L'];
                    $input['style'] = $row['M'];
                    $input['style_no'] = $row['N'];
                    $input['color'] = $row['O'];
                    $input['note'] = $row['P'];
                    $input['machine_id'] = $row['Q'];
                    $user = CustomUser::where('username', $row['R'])->first();
                    $input['nhan_vien_sx'] = $user->id ?? null;
                    $input['sl_tem'] = $row['S'];
                    $input['display'] = 1;
                    Tem::create($input);
                    // $input['sl_dau_ra_hang_loat'] = $input['so_luong'];
                    // $input['dinh_muc'] = $input['so_luong'];
                    // $input['thoi_gian_bat_dau'] = date('Y-m-d H:i:s');
                    // $input['thoi_gian_ket_thuc'] = date('Y-m-d H:i:s');
                    $index += 1;
                    // InfoCongDoan::create($input);
                    // LSX::create(['id'=>$input['lo_sx'], 'so_luong'=>$input['so_luong']]);
                }
            }
            DB::commit();
            return $this->success([], 'Upload thành công');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->failure([], 'File import có vấn đề vui lòng kiểm tra lại');
        }
    }

    public function listMaterialExport(Request $request)
    {
        $page = $request->page - 1;
        $pageSize = $request->pageSize;
        $input = $request->all();
        $query = WarehouseMLTLog::with('material', 'warehouse_mtl_export')->orderBy('tg_xuat', 'DESC')->whereNotNull('tg_xuat');
        if (isset($input['start_date']) && $input['end_date']) {
            $query->whereDate('tg_xuat', '>=', date('Y-m-d', strtotime($input['start_date'])))
                ->whereDate('tg_xuat', '<=', date('Y-m-d', strtotime($input['end_date'])));
        } else {
            $query->whereDate('tg_xuat', '>=', date('Y-m-d'))
                ->whereDate('tg_xuat', '<=', date('Y-m-d'));
        }
        if (isset($input['material_id'])) {
            $query = $query->where('material_id', 'like', '%' . $input['material_id'] . '%');
        }
        if (isset($input['ma_vat_tu']) || isset($input['ma_cuon_ncc']) || isset($input['loai_giay'])) {
            $query = $query->whereHas('material', function ($q) use ($input) {
                if (isset($input['ma_vat_tu'])) $q->where('ma_vat_tu', 'like', '%' . $input['ma_vat_tu'] . '%');
                if (isset($input['ma_cuon_ncc'])) $q->where('ma_cuon_ncc', 'like', '%' . $input['ma_cuon_ncc'] . '%');
                if (isset($input['loai_giay'])) $q->where('loai_giay', 'like', '%' . $input['loai_giay'] . '%');
            });
        }
        if (isset($input['export_shift'])) {
            $shift_users = ShiftAssignment::where('shift_id', $input['export_shift'])->pluck('user_id')->toArray();
            $query->whereIn('exporter_id', $shift_users);
        }
        $totalPage = $query->count();
        $records = $query->offset($page * $pageSize)->limit($pageSize)->get();
        $data = [];
        foreach ($records as $key => $record) {
            $nextImportLog = WarehouseMLTLog::where('tg_nhap', '>=', $record->tg_xuat)->where('material_id', $record->material_id)->orderBy('tg_nhap')->first();
            $so_con_lai = $nextImportLog->so_kg_nhap ?? 0;
            $obj = new stdClass();
            $obj->stt = $key + 1;
            $obj->machine = "Sóng";
            $obj->dau_may = $record->position_name;
            $obj->ma_vat_tu = $record->material->ma_vat_tu ?? "";
            $obj->material_id = $record->material_id;
            $obj->locator_id = $record->locator_id;
            $obj->loai_giay = $record->material->loai_giay ?? "";
            $obj->fsc = isset($record->material->fsc) ? ($record->material->fsc ? "X" : "") : "";
            $obj->kho_giay = $record->material->kho_giay ?? "0";
            $obj->dinh_luong = $record->material->dinh_luong ?? "0";
            $obj->so_kg_ban_dau = $record->material->so_kg_dau ?? "0";
            $obj->so_kg_nhap = $record->so_kg_nhap ?? "0";
            $obj->so_kg_xuat = $obj->so_kg_nhap - $so_con_lai;
            $obj->so_kg_con_lai = $so_con_lai;
            $obj->so_m_toi = $record->material->so_m_toi ?? "0";
            $obj->thoi_gian_xuat = $record->tg_xuat ? date('d/m/Y H:i:s', strtotime($record->tg_xuat)) : "";
            $obj->nhan_vien_xuat = $record->exporter->name ?? "";
            $data[] = $obj;
        }
        return $this->success(['data' => $data, 'totalPage' => $totalPage]);
    }

    public function exportListMaterialExport(Request $request)
    {
        $input = $request->all();
        $query = WarehouseMLTLog::with('material', 'warehouse_mtl_export')->orderBy('tg_xuat', 'DESC')->whereNotNull('tg_xuat');
        if (isset($input['start_date']) && $input['end_date']) {
            $query->whereDate('tg_xuat', '>=', date('Y-m-d', strtotime($input['start_date'])))
                ->whereDate('tg_xuat', '<=', date('Y-m-d', strtotime($input['end_date'])));
        } else {
            $query->whereDate('tg_xuat', '>=', date('Y-m-d'))
                ->whereDate('tg_xuat', '<=', date('Y-m-d'));
        }
        if (isset($input['material_id'])) {
            $query = $query->where('material_id', 'like', '%' . $input['material_id'] . '%');
        }
        if (isset($input['ma_vat_tu']) || isset($input['ma_cuon_ncc']) || isset($input['loai_giay'])) {
            $query = $query->whereHas('material', function ($q) use ($input) {
                if (isset($input['ma_vat_tu'])) $q->where('ma_vat_tu', 'like', '%' . $input['ma_vat_tu'] . '%');
                if (isset($input['ma_cuon_ncc'])) $q->where('ma_cuon_ncc', 'like', '%' . $input['ma_cuon_ncc'] . '%');
                if (isset($input['loai_giay'])) $q->where('loai_giay', 'like', '%' . $input['loai_giay'] . '%');
            });
        }
        $records = $query->get();
        $data = [];
        foreach ($records as $key => $record) {
            $nextImportLog = WarehouseMLTLog::where('tg_nhap', '>=', $record->tg_xuat)->where('material_id', $record->material_id)->orderBy('tg_nhap')->first();
            $so_con_lai = $nextImportLog->so_kg_nhap ?? 0;
            $obj = new stdClass();
            $obj->stt = $key + 1;
            $obj->machine = "Sóng";
            $obj->dau_may = $record->position_name;
            $obj->ma_vat_tu = $record->material->ma_vat_tu ?? "";
            $obj->material_id = $record->material_id;
            $obj->locator_id = $record->locator_id;
            $obj->loai_giay = $record->material->loai_giay ?? "";
            $obj->fsc = isset($record->material->fsc) ? ($record->material->fsc ? "X" : "") : "";
            $obj->kho_giay = $record->material->kho_giay ?? "0";
            $obj->dinh_luong = $record->material->dinh_luong ?? "0";
            $obj->so_kg_ban_dau = $record->material->so_kg_dau ?? "0";
            $obj->so_kg_nhap = $record->so_kg_nhap ?? "0";
            $obj->so_kg_xuat = $obj->so_kg_nhap - $so_con_lai;
            $obj->so_kg_con_lai = $so_con_lai;
            $obj->so_m_toi = $record->material->so_m_toi ?? "0";
            $obj->thoi_gian_xuat = $record->tg_xuat ? date('d/m/Y H:i:s', strtotime($record->tg_xuat)) : "";
            $obj->nhan_vien_xuat = $record->exporter->name ?? "";
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
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $start_row = 2;
        $start_col = 1;
        $sheet = $spreadsheet->getActiveSheet();
        $header = [
            'STT',
            "Máy",
            'Đầu sóng',
            "Mã vật tư",
            "Mã cuộn",
            "Vị trí",
            "Loại giấy",
            "FSC",
            'Khổ giấy',
            "Định lượng",
            "Số ký ban đầu",
            "Số ký nhập",
            'Số ký xuất',
            'Số ký còn lại',
            "Số m",
            "Thời gian xuất",
            "Người xuất"
        ];
        foreach ($header as $key => $cell) {
            $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            $start_col += 1;
        }

        $sheet->setCellValue([1, 1], 'Theo dõi xuất hàng kho NVL')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->fromArray($data, null, 'A3', true);
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
        header('Content-Disposition: attachment;filename="Theo dõi xuất hàng kho NVL.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Theo dõi xuất hàng kho NVL.xlsx');
        $href = '/exported_files/Theo dõi xuất hàng kho NVL.xlsx';
        return $this->success($href);
    }

    public function createWarehouseMTLImport(Request $request)
    {
        $input = $request->all();
        $input['ma_vat_tu'] = $input['loai_giay'] . '(' . $input['dinh_luong'] . ')' . $input['kho_giay'];
        WareHouseMLTImport::create($input);
        return $this->success('Tạo thành công');
    }

    public function updateWarehouseMTLImport(Request $request)
    {
        $input = $request->all();
        $import = WareHouseMLTImport::where('id', $request->get('id'))->first();
        $check = WareHouseMLTImport::where('id', '<>', $import->id)->where('ma_cuon_ncc', $input['ma_cuon_ncc'])->first();
        if ($check) {
            return $this->failure('', 'Mã cuộn đã tồn tại');
        }
        if ($import) {
            try {
                DB::beginTransaction();
                $input['fsc'] = $input['fsc'] ? 1 : 0;
                $input['ma_vat_tu'] = $input['loai_giay'] . '(' . $input['dinh_luong'] . ')' . $input['kho_giay'];
                $update = $import->update($input);
                if ($update && $import->material_id) {
                    $input['so_kg_dau'] = $input['so_kg'];
                    $material = Material::where('id', $import->material_id)->first();
                    unset($input['id'], $input['material_id'], $input['so_kg']);
                    if ($material) {
                        $material_input = array_intersect_key($input, array_flip($material->getFillable()));
                        $material->update($material_input);
                    }
                }
                DB::commit();
                return $this->success($import, 'Cập nhật thành công');
            } catch (\Throwable $th) {
                DB::rollBack();
                ErrorLog::saveError($request, $th);
                return $this->failure($th, 'Cập nhật không thành công');
            }
        } else {
            return $this->failure('', 'Không tìm thấy bản ghi');
        }
    }

    public function deleteWarehouseMTLImport(Request $request)
    {
        try {
            DB::beginTransaction();
            $count = 0;
            $records = WareHouseMLTImport::with('material')->whereIn('id', $request->get('id') ?? [])->get();
            foreach ($records as $record) {
                if (!$record->material) {
                    $record->delete();
                    $count++;
                }
            }
            DB::commit();
            return $this->success('', 'Đã xoá ' . $count . ' bản ghi');
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, "Đã xảy ra lỗi");
        }
        return $this->success($records, 'Xoá thành công');
    }

    public function exportWarehouseTicket(Request $request)
    {
        $input = $request->all();
        $note = GoodsReceiptNote::find($input['id']);
        $mlt_imports = WareHouseMLTImport::where('goods_receipt_note_id', $note->id)->get();
        $materials = Material::whereIn('id', $mlt_imports->pluck('material_id')->toArray())->orderBy('ma_vat_tu')->get();
        // $data = [];
        $data = new stdClass;
        $index = 0;
        $mvt_counter = 0;
        $mvt_tong_kg = 0;
        foreach ($materials as $key => $material) {
            // $data[] = [$key+1, $material->loai_giay, $material->kho_giay, $material->dinh_luong, $material->so_kg, 1, 'cuộn', $material->ma_cuon_ncc, $material->id, '', ''];
            $prev_ma_vat_tu = $materials[$key - 1]->ma_vat_tu ?? null;
            $data->stt[$key] = $key + 1;
            $data->loai_giay[$key] = $material->loai_giay;
            $data->kho_giay[$key] = $material->kho_giay;
            $data->dinh_luong[$key] = $material->dinh_luong;
            $data->so_kg[$key] = (int)$material->so_kg;
            $data->so_luong[$key] = 1;
            $data->don_vi[$key] = 'cuộn';
            $data->ma_cuon_ncc[$key] = $material->ma_cuon_ncc;
            $data->material_id[$key] = $material->id;
            $mvt_counter += 1;
            $mvt_tong_kg += $material->so_kg;
            if ($prev_ma_vat_tu !== $material->ma_vat_tu) {
                $index = $key;
                $mvt_counter = 1;
                $mvt_tong_kg = (int)$material->so_kg;
            }
            $data->mvt_counter[$index] = $mvt_counter;
            $data->mvt_tong_kg[$index] = $mvt_tong_kg;
        }
        // return $data;
        if (count($materials) > 0) {
            $params = [
                '{goods_receipt_note_id}' => $note->id,
                '{date}' => date('d.m.Y'),
                '{supplier_name}' => $note->supplier_name ?? "",
                '{vehicle_number}' => $note->vehicle_number ?? "",
                '[stt]' => new ExcelParam(CellSetterArrayValueSpecial::class, $data->stt),
                '[loai_giay]' => new ExcelParam(CellSetterArrayValueSpecial::class, $data->loai_giay),
                '[kho_giay]' => new ExcelParam(CellSetterArrayValueSpecial::class, $data->kho_giay),
                '[dinh_luong]' => new ExcelParam(CellSetterArrayValueSpecial::class, $data->dinh_luong),
                '[so_kg]' => new ExcelParam(CellSetterArrayValueSpecial::class, $data->so_kg),
                '[so_luong]' => new ExcelParam(CellSetterArrayValueSpecial::class, $data->so_luong),
                '[don_vi]' => new ExcelParam(CellSetterArrayValueSpecial::class, $data->don_vi),
                '[ma_cuon_ncc]' => new ExcelParam(CellSetterArrayValueSpecial::class, $data->ma_cuon_ncc),
                '[material_id]' => new ExcelParam(CellSetterArrayValueSpecial::class, $data->material_id),
                '[mvt_counter]' => new ExcelParam(CellSetterArrayValueSpecial::class, $data->mvt_counter),
                '[mvt_tong_kg]' => new ExcelParam(CellSetterArrayValueSpecial::class, $data->mvt_tong_kg),
                '{sum_kg}' => number_format($materials->sum('so_kg')),
                '{counter}' => $materials->count(),
            ];
            $writer = new \alhimik1986\PhpExcelTemplator\PhpExcelTemplator;
            $writer->saveToFile('templates/phieu_nhap_kho_template.xlsx', 'exported_files/Phiếu nhập kho.xlsx', $params);
            $href = '/exported_files/Phiếu nhập kho.xlsx';
            return $this->success($href);
        } else {
            return $this->failure('', 'Không có cuộn nào');
        }
    }

    public function exportVehicleWeightTicket(Request $request)
    {
        $input = $request->all();
        $note = GoodsReceiptNote::find($input['id']);
        $params = [
            '{goods_receipt_note_id}' => $note->id,
            '{datetime}' => date('d.m.Y H:i', strtotime($note->created_at)),
            '{supplier_name}' => $note->supplier_name ?? "",
            '{vehicle_number}' => $note->vehicle_number ?? "",
            '{total_weight}' => $note->total_weight ?? "",
            '{vehicle_weight}' => $note->vehicle_weight ?? "",
            '{material_weight}' => $note->material_weight ?? "",
        ];
        $writer = new \alhimik1986\PhpExcelTemplator\PhpExcelTemplator;
        $writer->saveToFile('templates/phieu_can_xe_template.xlsx', 'exported_files/Phiếu cân xe.xlsx', $params);
        $href = '/exported_files/Phiếu cân xe.xlsx';
        return $this->success($href);
    }

    public function listMachineUI(Request $request)
    {
        $query = Machine::whereNull('parent_id');
        if (isset($request->is_iot)) {
            $query->where('is_iot', $request->is_iot ? 1 : 0);
        }
        $records = $query->get();
        return $this->success($records);
    }

    public function ui_getCustomers(Request $request)
    {
        $customers = CustomerShort::join('customer', 'customer.id', '=', 'customer_short.customer_id')->select('customer_short.*', 'short_name as value', 'short_name as label')->orderBy('short_name')->get();
        return $this->success($customers);
    }

    public function ui_getOrders(Request $request)
    {
        return $this->success(Order::all());
    }

    public function ui_getLoSanXuat()
    {
        return $this->success(ProductionPlan::all()->unique('lo_sx')->pluck('lo_sx'));
    }

    public function ui_getQuyCach()
    {
        return $this->success(ProductionPlan::all()->unique('lo_sx')->pluck('lo_sx'));
    }

    public function listBuyer(Request $request)
    {

        $query = Buyer::with('customershort')->orderBy('created_at', 'DESC');
        if ($request->customer_id) {
            $query = $query->where('customer_id', 'like', '%' . $request->customer_id . '%');
        }
        if ($request->customer_name) {
            $customer_ids = CustomerShort::where('short_name', 'like', '%' . $request->customer_name . '%')->pluck('customer_id')->toArray();
            $query = $query->whereIn('customer_id', $customer_ids);
        }
        if ($request->so_lop) {
            $query = $query->where('so_lop', $request->so_lop);
        }
        if ($request->phan_loai_1) {
            $query = $query->where('phan_loai_1', $request->phan_loai_1);
        }
        if ($request->id) {
            $query = $query->where('id', 'like', '%' . $request->id . '%');
        }
        if (isset($request->page) && isset($request->pageSize)) {
            $page = $request->page - 1;
            $pageSize = $request->pageSize;
            $totalPage = $query->count();
            $records = $query->offset($page * $pageSize)->limit($pageSize)->get();
        } else {
            $records = $query->get();
        }
        $arr = ['S0105' => ['ma_cuon_f'], 'S0104' => ['ma_cuon_se', 'ma_cuon_le'], 'S0103' => ['ma_cuon_sb', 'ma_cuon_lb'], 'S0102' => ['ma_cuon_sc', 'ma_cuon_lc']];
        $position = ['ma_cuon_f' => 'S010501', 'ma_cuon_se' => 'S010401', 'ma_cuon_le' => 'S010402', 'ma_cuon_sb' => 'S010301', 'ma_cuon_lb' => 'S010302', 'ma_cuon_sc' => 'S010201', 'ma_cuon_lc' => 'S010202'];
        foreach ($records as $k => $record) {
            $mapping = [];
            $result = $record->toArray();
            foreach ($arr as $ke => $value) {
                $obj = new stdClass();
                $obj->label = ['Vị trí', 'Mã cuộn'];
                $obj->key = ['vi_tri', 'ma_cuon'];
                $in = [];
                foreach ($value as $key => $val) {
                    if ($result[$val]) {
                        $in[] = $position[$val];
                    }
                }
                $obj->position = $in;
                if (count($in) > 0) {
                    $mapping[$ke] = $obj;
                }
            }
            $record->mapping = json_encode($mapping);
            $record->save();
        }
        if (isset($request->page) && isset($request->pageSize)) {
            return $this->success(['data' => $records, 'totalPage' => $totalPage]);
        } else {
            return $this->success($records);
        }
    }

    public function listLayout(Request $request)
    {
        $query = Layout::orderBy('created_at', 'DESC');
        if ($request->layout_id) {
            $query = $query->where('layout_id', 'like', '%' . $request->layout_id . '%');
        }
        if ($request->customer_id) {
            $query = $query->where('customer_id', 'like', '%' . $request->customer_id . '%');
        }
        if ($request->machine_id) {
            $query = $query->where('machine_id', 'like', '%' . $request->machine_id . '%');
        }
        if (isset($request->page) && isset($request->pageSize)) {
            $count = $query->count();
            $totalPage = $count;
            $page = $request->page - 1;
            $pageSize = $request->pageSize;
            $query->offset($page * $pageSize)->limit($pageSize ?? 10);
            $record = $query->get();
            $res = [
                "data" => $record,
                "totalPage" => $totalPage,
            ];
        } else {
            $res = $query->get();
        }
        return $this->success($res);
    }

    public function listTem(Request $request)
    {
        $query = Tem::with('user', 'order')->orderBy('mdh')->orderBy('mql');
        if (isset($request->show) && $request->show === 'new') {
            $query->where('tem.display', 1)->where('created_by', $request->user()->id);
        }
        if (isset($request->lo_sx)) {
            $query->where('tem.lo_sx', $request->lo_sx);
        }
        if (isset($request->mdh)) {
            if (is_array($request->mdh)) {
                $query->where(function ($custom_query) use ($request) {
                    foreach ($request->mdh as $mdh) {
                        $custom_query->orWhere('tem.mdh', 'like', "%$mdh%");
                    }
                });
            } else {
                $query->where('tem.mdh', 'like', "%$request->mdh%");
            }
        }
        if (isset($request->mql)) {
            if (is_array($request->mql)) {
                $query->where(function ($custom_query) use ($request) {
                    foreach ($request->mql as $mql) {
                        $custom_query->orWhere('tem.mql', $mql);
                    }
                });
            } else {
                $query->where('tem.mql', $request->mql);
            }
        }
        if (isset($request->machine_id)) {
            $query->where('tem.machine_id', $request->machine_id);
        }
        $records = $query->get();
        $data = [];
        foreach ($records as $key => $record) {
            $order = $record->order;
            if (!$order) {
                continue;
            }
            $obj = new stdClass();
            $obj->lo_sx = $record->lo_sx;
            $obj->so_luong = $record->so_luong;
            $record->quy_cach_kh = $order ? (!$order->kich_thuoc ? ($order->length . 'x' . $order->width . ($order->height ? ('x' . $order->height) : "")) : $order->kich_thuoc) : "";
            $record->quy_cach = $order ? ($order->dai . 'x' . $order->rong . ($order->cao ? ('x' . $order->cao) : "")) : "";
            $record->qr_code = json_encode($obj);
            $record->order_kh = $order->order ?? "";
            $record->slg_sx = $order ? $order->sl : $record->so_luong;
            $data[] = array_merge($order->toArray(), $record->toArray());
        }
        return $this->success($data);
    }

    public function updateTem(Request $request)
    {
        try {
            $input = $request->all();
            $tem = Tem::where('id', $input['id'])->first();
            if ($tem) {
                $tem->update($input);
                if (isset($input['ids']) && is_array($input['ids'])) {
                    $input['ids'] = array_diff($input['ids'], [$input['id']]);
                    $fillable = ['so_luong', 'sl_tem', 'machine_id', 'nhan_vien_sx', 'note'];
                    $fillable_innput = array_intersect_key($input, array_flip($fillable));
                    $tems = Tem::whereIn('id', $input['ids'])->update($fillable_innput);
                }
                return $this->success($tem);
            } else {
                return $this->success('', 'Không tìm thấy tem');
            }
        } catch (\Throwable $th) {
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
    }

    public function deleteTem($id)
    {
        $tem = Tem::find($id);
        if (!$tem) {
            return $this->failure('', 'Không tìm thấy tem');
        }
        $check = InfoCongDoan::where('lo_sx', $tem->lo_sx)->where('machine_id', $tem->machine_id)->first();
        if ($check) {
            return $this->failure('', 'Tem đã quét sản xuất');
        }
        $tem->delete();
        return $this->success('', 'Đã xoá thành công');
    }

    public function getOrderList(Request $request)
    {
        $query = Order::orderBy('mdh', 'ASC')->orderBy('mql', 'ASC');
        if (isset($request->short_name)) {
            $customer_short = CustomerShort::where('short_name', $request->short_name)->first();
            $query->where('customer_id', $customer_short->customer_id);
        }
        if (isset($request->mdh)) {
            if (is_array($request->mdh)) {
                $query->where(function ($custom_query) use ($request) {
                    foreach ($request->mdh as $mdh) {
                        $custom_query->orWhere('mdh', 'like', "%$mdh%");
                    }
                });
            } else {
                $query->where('mdh', 'like', "%$request->mdh%");
            }
        }
        if (isset($request->dot)) {
            $query->where('dot', $request->dot);
        }
        $machine = Machine::find($request->machine_id);
        if (!$machine) {
            return $this->failure([], 'Mã máy không tồn tại');
        }
        $line_id = $machine->line_id;
        $orders = [];
        switch ($line_id) {
            case Line::LINE_SONG:
                if (isset($request->start_date) && isset($request->end_date)) {
                    $query->whereDate('han_giao_sx', '>=', date('Y-m-d', strtotime($request->start_date)))->whereDate('han_giao_sx', '<=', date('Y-m-d', strtotime($request->end_date)));
                }
                $group_order = GroupPlanOrder::pluck('order_id')->toArray();
                $orders_array = array_flip($group_order);
                $query->whereNotNull(['dai', 'rong']);
                $orders = $query->with('buyer')->has('buyer')->get()->filter(function ($value) use ($orders_array) {
                    return !isset($orders_array[$value->id]);
                });
                break;
            case Line::LINE_XA_LOT:
                if (isset($request->start_date) && isset($request->end_date)) {
                    $query->whereDate('han_giao_sx', '>=', date('Y-m-d', strtotime($request->start_date)))->whereDate('han_giao_sx', '<=', date('Y-m-d', strtotime($request->end_date)));
                }
                $group_order = GroupPlanOrder::pluck('order_id')->toArray();
                $orders_array = array_flip($group_order);
                $query->whereNotNull(['dai', 'rong']);
                $orders = $query->with('buyer')->get()->filter(function ($value) use ($orders_array) {
                    return !isset($orders_array[$value->id]);
                });
                break;
            default:
                $plans = ProductionPlan::whereDate('ngay_sx', '>=', date('Y-m-d', strtotime($request->start_date)))->whereDate('ngay_sx', '<=', date('Y-m-d', strtotime($request->end_date)))
                    ->where('machine_id', 'So01')
                    ->get();
                $group_order = GroupPlanOrder::whereIn('plan_id', $plans->pluck('id')->toArray())->pluck('order_id')->toArray();
                $query->whereNotNull(['dai', 'rong'])
                    ->whereIn('id', $group_order)
                    ->withSum(['plan as sum_sl' => function ($plan_query) use ($line_id) {
                        $plan_query->where('machine_id', '<>', 'So01')->whereHas('machine', function ($q) use ($line_id) {
                            $q->where('line_id', $line_id);
                        });
                    }], 'sl_kh');
                $orders = $query->with('buyer')->get();
                break;
        }
        // $orders = $query->with('buyer')->get();
        $data = [];
        foreach ($orders as $key => $order) {
            $order->khach_hang = $order->short_name ?? '';
            $order->ket_cau_giay = $order->buyer->ket_cau_giay ?? '';
            if ($order->sl - ($order->sum_sl ?? 0) <= 0) {
                continue;
            } else {
                $order->sl = $order->sl - ($order->sum_sl ?? 0);
            }
            $data[] = $order;
        }
        return $this->success($data);
    }

    public function  handleOrder(Request $request)
    {
        $machine = Machine::find($request->machine_id);
        $line = $machine->line;
        $input = $request->all();
        $date = date('ymd', strtotime($input['start_time']));
        switch ($line->id) {
            case Line::LINE_SONG:
                $prefix = 'S' . $date;
                break;
            case Line::LINE_XA_LOT:
                $prefix = 'X' . $date;
                break;
            default:
                $prefix = $date;
        }
        $plan = ProductionPlan::where('machine_id', $input['machine_id'])->where('lo_sx', 'like', $prefix . "%")->orderBy('lo_sx', 'DESC')->first();
        $index = '';
        $thu_tu_uu_tien = 1;
        if ($plan) {
            $index = (int)str_replace($prefix, '', $plan->lo_sx) + 1;
            $thu_tu_uu_tien = $index;
        } else {
            $index = 1;
            $thu_tu_uu_tien = 1;
        }
        $data = [];
        switch ($line->id) {
            case Line::LINE_SONG:
                $group_orders = Order::whereIn('orders.id', $input['order_id'])
                    ->leftJoin('buyers', 'buyers.id', '=', 'orders.buyer_id')
                    ->select(
                        'orders.*',
                        'orders.customer_id',
                        'buyers.ket_cau_giay',
                        DB::raw("CONCAT_WS('', COALESCE(kho_tong, ''), COALESCE(ket_cau_giay, ''), COALESCE(layout_type, ''), COALESCE(orders.customer_id, ''), COALESCE(mdh, ''), COALESCE(dai, ''), COALESCE(rong, ''), COALESCE(cao, ''), COALESCE(note_3, ''), COALESCE(dot, '')) as concatenated_column ")
                    )
                    ->get()->sortBy('buyer_id')->sortByDesc('kho_tong')->groupBy('concatenated_column');
                $start_time = strtotime($input['start_time']);
                foreach ($group_orders as $key => $orders) {
                    $order = $orders[0] ?? null;
                    $order_ids = [];
                    $so_m_toi = 0;
                    foreach ($orders as $order) {
                        $order_ids[] = $order->id;
                        $so_m_toi += $order->so_met_toi;
                    }
                    $obj = new stdClass;
                    $obj->order_id = $order->id;
                    $obj->khach_hang = $order->short_name;
                    $obj->kich_thuoc = $order->kich_thuoc_chuan;
                    $obj->lo_sx = $prefix . str_pad($index, 4, '0', STR_PAD_LEFT);
                    $obj->thoi_gian_bat_dau = date('Y-m-d H:i:s', $start_time);
                    $obj->machine_id = $input['machine_id'];
                    $obj->thu_tu_uu_tien = $thu_tu_uu_tien;
                    $obj->mql = $order->mql;
                    $obj->kho_tong = $order->kho_tong;
                    $obj->mdh = $order->mdh;
                    $obj->so_m_toi = $so_m_toi;
                    $obj->sl_kh = $orders->sum('sl');
                    $obj->order_id = $order->id;
                    $obj->order_ids = $order_ids;
                    $obj->toc_do = $order->toc_do ?? 0;
                    $obj->tg_doi_model = $order->tg_doi_model ?? 0;
                    //end_hour = start_hours + thoi_gian_doi_model/(24*60) + so_met_toi/(toc_do*24*60) tính theo phút
                    $start_hours = $this->hoursPassedInADay($start_time);
                    $end_hours = $obj->toc_do ? ($start_hours + ($obj->tg_doi_model / (24 * 60)) + ($obj->so_m_toi / ($obj->toc_do * 24 * 60))) : $start_hours;
                    $bonus_hours = $end_hours - $start_hours;
                    $obj->thoi_gian_ket_thuc = date('Y-m-d H:i:s', $start_time + round($bonus_hours * 24 * 3600));
                    $start_time = strtotime($obj->thoi_gian_ket_thuc);
                    $thu_tu_uu_tien += 1;
                    $index += 1;
                    $data[] = $obj;
                }
                break;
            case Line::LINE_IN:
                $orders = Order::with('layout')->whereIn('orders.id', $input['order_id'])
                    ->withSum(['plan as sum_sl' => function ($plan_query) {
                        $plan_query->where('machine_id', '<>', 'So01');
                    }], 'sl_kh')->orderBy('kho', 'DESC')->get();
                $start_time = strtotime($input['start_time']);
                foreach ($orders as $key => $order) {
                    $layout = $order->layout;
                    $order->sl = $order->sl - $order->sum_sl;
                    $obj = new stdClass;
                    $obj->order_id = $order->id;
                    $obj->lo_sx = $prefix . str_pad($index, 4, '0', STR_PAD_LEFT);
                    $obj->thoi_gian_bat_dau = date('Y-m-d H:i:s', $start_time);
                    $obj->machine_id = $input['machine_id'];
                    $obj->thu_tu_uu_tien = $thu_tu_uu_tien;
                    $obj->khach_hang = $order->short_name;
                    $obj->kich_thuoc = $order->kich_thuoc_chuan;
                    $obj->mql = $order->mql;
                    $obj->mdh = $order->mdh;
                    $obj->layout = $order->layout;
                    $obj->kho_tong = $order->kho_tong;
                    $obj->sl_kh = $order->sl;
                    $obj->ma_don_hang = $order->id;
                    $obj->so_m_toi = $order->so_met_toi;
                    $obj->toc_do = $layout->toc_do ?? 40;
                    $obj->tg_doi_model = $layout->tg_doi_model ?? 0;
                    if ($order->phan_loai_1 === 'thung') {
                        $sl_tp_thung = $obj->sl_kh;
                    } else {
                        $sl_tp_thung = 0;
                    }
                    //end_time = start_time + thoi_gian_doi_model/(24*60) + sl_tp_thung/(toc_do*24*60)
                    $start_hours = $this->hoursPassedInADay($start_time);
                    $end_hours = $obj->toc_do ? ($start_hours + ($obj->tg_doi_model / (24 * 60)) + ($sl_tp_thung / ($obj->toc_do * 24 * 60))) : $start_hours;
                    if (!(ProductionPlan::START_LUNCH >= $end_hours || $start_hours >= ProductionPlan::END_LUNCH)) {
                        $end_hours += ProductionPlan::END_LUNCH - ProductionPlan::START_LUNCH;
                    }
                    if (!(ProductionPlan::START_AFTERNOON >= $end_hours || $start_hours >= ProductionPlan::END_AFTERNOON)) {
                        $end_hours += ProductionPlan::END_AFTERNOON - ProductionPlan::START_AFTERNOON;
                    }
                    $bonus_hours = $end_hours - $start_hours;
                    $obj->thoi_gian_ket_thuc = date('Y-m-d H:i:s', $start_time + round($bonus_hours * 24 * 3600));
                    $start_time = strtotime($obj->thoi_gian_ket_thuc);
                    $thu_tu_uu_tien += 1;
                    $index += 1;
                    $data[] = $obj;
                }
                break;
            case Line::LINE_XA_LOT:
                $group_orders = Order::whereIn('orders.id', $input['order_id'])
                    ->leftJoin('buyers', 'buyers.id', '=', 'orders.buyer_id')
                    ->select(
                        'orders.*',
                        'buyers.ket_cau_giay',
                        DB::raw("CONCAT_WS(COALESCE(ket_cau_giay, ''), COALESCE(note_3, ''), COALESCE(mdh, ''), COALESCE(dai, ''), COALESCE(rong, ''), COALESCE(cao, ''), COALESCE(dot, '')) as concatenated_column ")
                    )
                    ->orderBy('mdh', 'DESC')
                    ->get()->sortBy('buyer_id')->sortByDesc('kho_tong')->groupBy('concatenated_column');
                $start_time = strtotime($input['start_time']);
                foreach ($group_orders as $key => $orders) {
                    $order = $orders[0] ?? null;
                    $order_ids = [];
                    $so_m_toi = 0;
                    foreach ($orders as $order) {
                        $order_ids[] = $order->id;
                        $so_m_toi += $order->so_met_toi;
                    }
                    $obj = new stdClass;
                    $obj->order_id = $order->id;
                    $obj->lo_sx = $prefix . str_pad($index, 4, '0', STR_PAD_LEFT);
                    $obj->thoi_gian_bat_dau = date('Y-m-d H:i:s', $start_time);
                    $obj->khach_hang = $order->short_name;
                    $obj->kich_thuoc = $order->kich_thuoc_chuan;
                    $obj->machine_id = $input['machine_id'];
                    $obj->thu_tu_uu_tien = $thu_tu_uu_tien;
                    $obj->mql = $order->mql;
                    $obj->kho_tong = $order->kho_tong;
                    $obj->mdh = $order->mdh;
                    $obj->so_m_toi = $so_m_toi;
                    $obj->sl_kh = $orders->sum('sl');
                    $obj->order_id = $order->id;
                    $obj->order_ids = $order_ids;
                    $obj->toc_do = $order->toc_do ?? 0;
                    $obj->tg_doi_model = $order->tg_doi_model ?? 0;
                    //end_time = start_time + thoi_gian_doi_model/(24*60) + so_met_toi/(toc_do*24*60) tính theo phút
                    $start_hours = $this->hoursPassedInADay($start_time);
                    $end_hours = $obj->toc_do ? ($start_hours + ($obj->tg_doi_model / (24 * 60)) + ($so_m_toi / ($obj->toc_do * 24 * 60))) : $start_hours;
                    $bonus_hours = $end_hours - $start_hours;
                    $obj->thoi_gian_ket_thuc = date('Y-m-d H:i:s', $start_time + round($bonus_hours * 24 * 3600));
                    $start_time = strtotime($obj->thoi_gian_ket_thuc);
                    $thu_tu_uu_tien += 1;
                    $index += 1;
                    $data[] = $obj;
                }
                break;
            default:
                $prev_order = null;
                $orders = Order::with('layout')->whereIn('orders.id', $input['order_id'])
                    ->withSum(['plan as sum_sl' => function ($plan_query) {
                        $plan_query->where('machine_id', '<>', 'So01');
                    }], 'sl_kh')->orderBy('kho', 'DESC')->get();
                $start_time = strtotime($input['start_time']);
                foreach ($orders as $key => $order) {
                    $layout = $order->layout;
                    $order->sl = $order->sl - $order->sum_sl;
                    $obj = new stdClass;
                    $obj->order_id = $order->id;
                    $obj->lo_sx = $prefix . str_pad($index, 4, '0', STR_PAD_LEFT);
                    $obj->thoi_gian_bat_dau = date('Y-m-d H:i:s', $start_time);
                    $obj->machine_id = $input['machine_id'];
                    $obj->thu_tu_uu_tien = $thu_tu_uu_tien;
                    $obj->khach_hang = $order->short_name;
                    $obj->kich_thuoc = $order->kich_thuoc_chuan;
                    $obj->mql = $order->mql;
                    $obj->mdh = $order->mdh;
                    $obj->layout = $order->layout;
                    $obj->kho_tong = $order->kho_tong;
                    $obj->sl_kh = $order->sl;
                    $obj->ma_don_hang = $order->id;
                    $obj->so_m_toi = $order->so_met_toi;
                    $toc_do = 0;
                    $tg_doi_model = 0;
                    if (isset($order->dai) && isset($order->rong) && isset($order->cao)) {
                        if (($order->rong + $order->cao) > 45) {
                            $toc_do = 45;
                        } else {
                            $toc_do = 60;
                        }
                        if ($prev_order) {
                            if ($order->dai === $prev_order->dai && $order->rong === $prev_order->rong && $order->cao === $prev_order->cao) {
                                $tg_doi_model = 0;
                            } else {
                                $tg_doi_model = 10;
                            }
                        }
                    }
                    $obj->toc_do = $toc_do;
                    $obj->tg_doi_model = $tg_doi_model;
                    if ($order->phan_loai_1 === 'thung') {
                        $sl_tp_thung = $obj->sl_kh;
                    } else {
                        $sl_tp_thung = 0;
                    }
                    //end_time = start_time + thoi_gian_doi_model/(24*60) + so_m_toi/(toc_do*24*60);
                    $start_hours = $this->hoursPassedInADay($start_time);
                    $end_hours = $obj->toc_do ? ($start_hours + ($obj->tg_doi_model / (24 * 60)) + ($sl_tp_thung / ($obj->toc_do * 24 * 60))) : $start_hours;
                    $end_hours = $obj->toc_do ? ($start_hours + ($obj->tg_doi_model / (24 * 60)) + ($sl_tp_thung / ($obj->toc_do * 24 * 60))) : $start_hours;
                    if (ProductionPlan::START_LUNCH <= $end_hours && $end_hours <= ProductionPlan::END_LUNCH) {
                        $end_hours += ProductionPlan::END_LUNCH - ProductionPlan::START_LUNCH;
                    }
                    if (ProductionPlan::START_AFTERNOON <= $end_hours && $end_hours <= ProductionPlan::END_AFTERNOON) {
                        $end_hours += ProductionPlan::END_AFTERNOON - ProductionPlan::START_AFTERNOON;
                    }
                    $bonus_hours = $end_hours - $start_hours;
                    $obj->thoi_gian_ket_thuc = date('Y-m-d H:i:s', $start_time + round($bonus_hours * 24 * 3600));
                    $start_time = strtotime($obj->thoi_gian_ket_thuc);
                    $thu_tu_uu_tien += 1;
                    $index += 1;
                    $data[] = $obj;
                    $prev_order = $order;
                }
                break;
        }
        return $this->success($data);
    }

    function hoursPassedInADay($timestamp)
    {
        $start_of_day = strtotime(date("Y-m-d 00:00:00", $timestamp));
        $diff_in_sec = $timestamp - $start_of_day;
        $hours = $diff_in_sec / 3600 / 24;
        return $hours;
    }

    public function handlePlan(Request $request)
    {
        $input = $request->all();
        $machine = Machine::find($request->machine_id);
        $line = $machine->line;
        $data = [];
        $start_time = strtotime($input['start_time']);
        foreach ($input['plans'] as $key => $plan) {
            $order = Order::find($plan['order_id']);
            $so_luong = $plan['sl_kh'] ?? 0;
            switch ($line->id) {
                case Line::LINE_SONG:
                    $so_luong = $plan['sl_kh'];
                    break;
                case Line::LINE_XA_LOT:
                    $so_luong = $plan['so_m_toi'];
                    break;
                default:
                    if ($order->phan_loai_1 === 'thung') {
                        $so_luong = $plan['sl_kh'];
                    } else {
                        $so_luong = 0;
                    }
                    break;
            }
            $plan['thoi_gian_bat_dau'] = date('Y-m-d H:i:s', $start_time);
            $start_hours = $this->hoursPassedInADay($start_time);
            $end_hours = $plan['toc_do'] ? ($start_hours + ($plan['tg_doi_model'] / (24 * 60)) + ($so_luong / ($plan['toc_do'] * 24 * 60))) : $start_hours;
            if (ProductionPlan::START_LUNCH <= $end_hours && $end_hours <= ProductionPlan::END_LUNCH) {
                $end_hours += ProductionPlan::END_LUNCH - ProductionPlan::START_LUNCH;
            }
            if (ProductionPlan::START_AFTERNOON <= $end_hours && $end_hours <= ProductionPlan::END_AFTERNOON) {
                $end_hours += ProductionPlan::END_AFTERNOON - ProductionPlan::START_AFTERNOON;
            }
            $bonus_hours = $end_hours - $start_hours;
            $plan['thoi_gian_ket_thuc'] = date('Y-m-d H:i:s', $start_time + round($bonus_hours * 24 * 3600));
            $start_time = strtotime($plan['thoi_gian_ket_thuc']);
            $data[] = $plan;
        }
        return $this->success($data);
    }

    public function createBuyers(Request $request)
    {
        $input = $request->all();
        $record = Buyer::create($input);
        return $this->success($record, 'Cập nhật thành công');
    }

    public function updateBuyers(Request $request)
    {
        $input = $request->all();
        $record = Buyer::find($input['id'])->update($input);
        return $this->success($record, 'Cập nhật thành công');
    }

    public function deleteBuyers(Request $request)
    {
        Buyer::where('id', $request->id)->delete();
        return $this->success([], 'Xóa thành công');
    }

    function mappingSong($buyer_id, $lo_sx, $kho_tong)
    {
        $buyer = Buyer::find($buyer_id);
        if ($buyer) {
            if ($buyer->ma_cuon_f) {
                $obj1 = new Mapping();
                $obj1->lo_sx = $lo_sx;
                $obj1->machine_id = 'S0105';
                $info = new stdClass();
                $info->label = ['Vị trí F', 'Mã cuộn F'];
                $info->value = ['S010501', $buyer->ma_cuon_f . $kho_tong];
                $info->key = ['vi_tri_f', 'ma_cuon_f'];
                $info->check_api = [0, 1];
                $obj1->info = $info;
                $obj1->save();
                $material_ids = Material::where('ma_vat_tu', $buyer->ma_cuon_f . $kho_tong)->pluck('id')->toArray();
                $map = LocatorMLTMap::where('material_id', $material_ids)->orderBy('created_at', 'ASC')->first();
                if ($map) {
                    WareHouseMLTExport::create(['position_id' => 'S010501', 'position_name' => 'F', 'material_id' => $map->material_id, 'locator_id' => $map->locator_mlt_id]);
                }
            }
            if ($buyer->ma_cuon_se || $buyer->ma_cuon_le) {
                $obj1 = new Mapping();
                $obj1->lo_sx = $lo_sx;
                $obj1->machine_id = 'S0104';
                $info = new stdClass();
                $label = [];
                $value = [];
                $key = [];
                $check_api = [];
                if ($buyer->ma_cuon_se) {
                    array_push($label, 'Vị trí sE', 'Mã cuộn');
                    array_push($value, 'S010401', $buyer->ma_cuon_se . $kho_tong);
                    array_push($key, 'vi_tri_se', 'ma_cuon_se');
                    array_push($check_api, 0, 1);
                    $material_ids = Material::where('ma_vat_tu', $buyer->ma_cuon_se . $kho_tong)->pluck('id')->toArray();
                    $map = LocatorMLTMap::where('material_id', $material_ids)->orderBy('created_at', 'ASC')->first();
                    if ($map) {
                        WareHouseMLTExport::create(['position_id' => 'S010401', 'position_name' => 'sE', 'material_id' => $map->material_id, 'locator_id' => $map->locator_mlt_id]);
                    }
                }
                if ($buyer->ma_cuon_le) {
                    array_push($label, 'Vị trí lE', 'Mã cuộn');
                    array_push($value, 'S010402', $buyer->ma_cuon_le . $kho_tong);
                    array_push($key, 'vi_tri_le', 'ma_cuon_le');
                    array_push($check_api, 0, 1);
                    $material_ids = Material::where('ma_vat_tu', $buyer->ma_cuon_le . $kho_tong)->pluck('id')->toArray();
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
            if ($buyer->ma_cuon_sb || $buyer->ma_cuon_lb) {
                $obj1 = new Mapping();
                $obj1->lo_sx = $lo_sx;
                $obj1->machine_id = 'S0103';
                $info = new stdClass();
                $label = [];
                $value = [];
                $key = [];
                $check_api = [];
                if ($buyer->ma_cuon_sb) {
                    array_push($label, 'Vị trí sB', 'Mã cuộn');
                    array_push($value, 'S010301', $buyer->ma_cuon_sb . $kho_tong);
                    array_push($key, 'vi_tri_sb', 'ma_cuon_sb');
                    array_push($check_api, 0, 1);
                    $material_ids = Material::where('ma_vat_tu', $buyer->ma_cuon_sb . $kho_tong)->pluck('id')->toArray();
                    $map = LocatorMLTMap::where('material_id', $material_ids)->orderBy('created_at', 'ASC')->first();
                    if ($map) {
                        WareHouseMLTExport::create(['position_id' => 'S010301', 'position_name' => 'sB', 'material_id' => $map->material_id, 'locator_id' => $map->locator_mlt_id]);
                    }
                }
                if ($buyer->ma_cuon_lb) {
                    array_push($label, 'Vị trí lB', 'Mã cuộn');
                    array_push($value, 'S010302', $buyer->ma_cuon_lb . $kho_tong);
                    array_push($key, 'vi_tri_lb', 'ma_cuon_lb');
                    array_push($check_api, 0, 1);
                    $material_ids = Material::where('ma_vat_tu', $buyer->ma_cuon_lb . $kho_tong)->pluck('id')->toArray();
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
            if ($buyer->ma_cuon_sc || $buyer->ma_cuon_lc) {
                $obj1 = new Mapping();
                $obj1->lo_sx = $lo_sx;
                $obj1->machine_id = 'S0102';
                $info = new stdClass();
                $label = [];
                $value = [];
                $key = [];
                $check_api = [];
                if ($buyer->ma_cuon_sc) {
                    array_push($label, 'Vị trí sC', 'Mã cuộn');
                    array_push($value, 'S010201', $buyer->ma_cuon_sc . $kho_tong);
                    array_push($key, 'vi_tri_sc', 'ma_cuon_sc');
                    array_push($check_api, 0, 1);
                    $material_ids = Material::where('ma_vat_tu', $buyer->ma_cuon_sc . $kho_tong)->pluck('id')->toArray();
                    $map = LocatorMLTMap::where('material_id', $material_ids)->orderBy('created_at', 'ASC')->first();
                    if ($map) {
                        WareHouseMLTExport::create(['position_id' => 'S010201', 'position_name' => 'sC', 'material_id' => $map->material_id, 'locator_id' => $map->locator_mlt_id]);
                    }
                }
                if ($buyer->ma_cuon_lc) {
                    array_push($label, 'Vị trí lC', 'Mã cuộn');
                    array_push($value, 'S010202', $lo_sx);
                    array_push($key, 'vi_tri_lc', 'ma_cuon_lc');
                    array_push($check_api, 0, 1);
                    $material_ids = Material::where('ma_vat_tu', $buyer->ma_cuon_lc . $kho_tong)->pluck('id')->toArray();
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
        }
    }

    public function mappingIn($layout_id, $lo_sx, $machine_id)
    {
        $layout = Layout::where('layout_id', $layout_id)->first();
        if ($layout) {
            if (!is_null($layout->ma_film_1) && !is_null($layout->ma_muc_1)) {
                $obj1 = new Mapping();
                $obj1->lo_sx = $lo_sx;
                $obj1->machine_id = $machine_id;
                $obj1->position = 1;
                $info = new stdClass();
                $info->label = ['Vị trí lô 1', 'Mã film', 'Mã mực'];
                if ($machine_id == 'P06') {
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
                $obj1->lo_sx = $lo_sx;
                $obj1->position = 2;
                $obj1->machine_id = $machine_id;
                $info = new stdClass();
                $info->label = ['Vị trí lô 2', 'Mã film', 'Mã mực'];
                if ($machine_id == 'P06') {
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
                $obj1->lo_sx = $lo_sx;
                $obj1->machine_id = $machine_id;
                $obj1->position = 3;
                $info = new stdClass();
                $info->label = ['Vị trí lô 3', 'Mã film', 'Mã mực'];
                if ($machine_id == 'P06') {
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
                $obj1->lo_sx = $lo_sx;
                $obj1->machine_id = $machine_id;
                $obj1->position = 4;
                $info = new stdClass();
                $info->label = ['Vị trí lô 4', 'Mã film', 'Mã mực'];
                if ($machine_id == 'P06') {
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
                $obj1->lo_sx = $lo_sx;
                $obj1->machine_id = $machine_id;
                $obj1->position = 5;
                $info = new stdClass();
                $info->label = ['Vị trí lô 1', 'Mã film', 'Mã mực'];
                if ($machine_id == 'P06') {
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
                $obj1->lo_sx = $lo_sx;
                $obj1->machine_id = $machine_id;
                $obj1->position = 6;
                $info = new stdClass();
                $info->label = ['Vị trí khuôn', 'Mã khuôn'];
                if ($machine_id == 'P06') {
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

    public function createProductionPlan(Request $request)
    {
        $input = $request->all();
        $ordering = 0;
        try {
            DB::beginTransaction();
            foreach ($input['data'] as $plan_input) {
                $plan_input['ngay_sx'] = date('Y-m-d', strtotime($plan_input['thoi_gian_bat_dau']));
                $check = ProductionPlan::where('lo_sx', $plan_input['lo_sx'])->where('machine_id', $plan_input['machine_id'])->first();
                // $pl = ProductionPlan::whereDate('ngay_sx', $plan_input['ngay_sx'])->where('machine_id', $plan_input['machine_id'])->orderBy('ordering', 'DESC')->first();
                // if ($ordering == 0) {
                //     $ordering = $pl ? ($pl->ordering + 1) : 1;
                // }
                if (!$check) {
                    $plan_input['ordering'] = $plan_input['thu_tu_uu_tien'];
                    $plan_input['created_by'] = $request->user()->id;
                    $plan = ProductionPlan::create($plan_input);
                    LSXLog::updateOrCreate(['machine_id' => $plan_input['machine_id'], 'lo_sx' => $plan_input['lo_sx']], ['machine_id' => $plan_input['machine_id'], 'lo_sx' => $plan_input['lo_sx'], 'thu_tu_uu_tien' => $plan_input['thu_tu_uu_tien']]);
                    $machine = Machine::find($plan_input['machine_id']);
                    if (isset($plan_input['order_ids']) && count($plan_input['order_ids'])  > 0) {
                        $group_orders = [];
                        foreach ($plan_input['order_ids'] as $order_id) {
                            $group_orders[] = ['plan_id' => $plan->id, 'order_id' => $order_id, 'line_id' => $machine->line_id];
                        }
                        DB::table('group_plan_order')->insert($group_orders);
                    }
                    $order = Order::find($plan_input['order_id'] ?? "");
                    $formula = DB::table('formulas')->where('phan_loai_1', $order->phan_loai_1 ?? null)->where('phan_loai_2', $order->phan_loai_2 ?? null)->first();
                    $info_cong_doan = InfoCongDoan::create([
                        'lo_sx' => $plan->lo_sx,
                        'machine_id' => $plan->machine_id,
                        'thu_tu_uu_tien' => $plan->thu_tu_uu_tien,
                        'dinh_muc' => $plan->sl_kh,
                        'ngay_sx' => $plan->ngay_sx,
                        'so_ra' => $plan->order->so_ra ?? 1,
                        'order_id' => $plan->order_id,
                        'plan_id' => $plan->id,
                        'status' => 0,
                        'so_dao' => isset($order->so_ra) ? ceil($plan->sl_kh * ($formula->he_so ?? 1) / $order->so_ra) : $order->so_dao,
                    ]);
                    if ($machine->line_id == '31') {
                        if ($order) {
                            $this->mappingIn($order->layout_id, $plan_input['lo_sx'], $plan_input['machine_id']);
                        }
                    }
                }
            }
            DB::commit();
            $this->apiUIController->updateInfoCongDoanPriority();
            return $this->success('', "Tạo KHSX thành công");
        } catch (\Throwable $th) {
            throw $th;
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, "Tạo KHSX không thành công");
        }
    }

    public function createLayouts(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $record = Layout::create($input);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        return $this->success($record, 'Thêm mới thành công');
    }

    public function updateLayouts(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $record = Layout::find($input['id'])->update($input);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        return $this->success($record, 'Cập nhật thành công');
    }

    public function deleteLayouts(Request $request)
    {
        try {
            DB::beginTransaction();
            Layout::where('id', $request->id)->delete();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        return $this->success([], 'Xóa thành công');
    }

    public function listDRC(Request $request)
    {
        $record = DRC::all();
        return $this->success($record);
    }

    public function exportKHSX(Request $request)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $headerStyle = array_merge($centerStyle, [
            'font' => ['bold' => true, 'color' => array('rgb' => '632523')],
        ]);
        $headerStyle2 = array_merge($centerStyle, [
            'font' => ['bold' => true, 'color' => array('rgb' => 'FF0000'), 'size' => 18],
        ]);
        $titleStyle = array_merge($centerStyle, [
            'font' => ['bold' => true, 'color' => array('rgb' => '632523'), 'size' => 18],
        ]);
        $bold = [
            'font' => ['bold' => true],
        ];
        $red = [
            'font' => ['color' => array('rgb' => 'FF0000')],
        ];
        $blue = [
            'font' => ['color' => array('rgb' => '0070C0')],
        ];
        $header = [
            'STT',
            'Lô SX',
            'Đơn hàng',
            'Khách hàng',
            'Số lớp',
            'Mã buyer',
            'Kết cấu giấy',
            'Dài',
            'Rộng',
            'Cao',
            'SL',
            'Ra',
            'Tổng khổ',
            'Dài tấm',
            'Số dao',
            'Ghi chú',
            'Đợt',
        ];
        $table_key = [
            'A' => 'thu_tu_uu_tien',
            'B' => 'lo_sx',
            'C' => 'mdh',
            'D' => 'short_name',
            'E' => 'so_lop',
            'F' => 'buyer_id',
            'G' => 'ket_cau_giay',
            'H' => 'dai',
            'I' => 'rong',
            'J' => 'cao',
            'K' => 'sl_kh',
            'L' => 'so_ra',
            'M' => 'kho',
            'N' => 'dai_tam',
            'O' => 'so_dao',
            'P' => 'note_3',
            'Q' => 'dot',
        ];
        $tableStyle = [
            'A' => $centerStyle,
            'B' => $centerStyle,
            'C' => array_merge_recursive($centerStyle, $bold),
            'D' => array_merge_recursive($centerStyle, $bold),
            'E' => $centerStyle,
            'F' => $centerStyle,
            'G' => array_merge_recursive($centerStyle, $bold),
            'H' => $centerStyle,
            'I' => $centerStyle,
            'J' => $centerStyle,
            'K' => array_merge_recursive($centerStyle, $bold, $red),
            'L' => $centerStyle,
            'M' => array_merge_recursive($centerStyle, $blue),
            'N' => $centerStyle,
            'O' => array_merge_recursive($centerStyle, $bold, $red),
            'P' => $centerStyle,
            'Q' => $centerStyle,
        ];
        $start_row = 1;
        $number_days = round(((strtotime($request->end_date) - strtotime($request->start_date)) ?? 0) / (60 * 60 * 24));
        for ($i = 0; $i <= $number_days; $i++) {
            $index = 1;
            $query = ProductionPlan::with('order')->orderBy('thu_tu_uu_tien')->orderBy('created_at', 'DESC')->whereHas('machine', function ($query) {
                $query->where('line_id', Line::LINE_SONG);
            });
            if (isset($request->end_date) && isset($request->start_date)) {
                $query->whereDate('ngay_sx', '>=', date('Y-m-d', strtotime($request->start_date . ' +' . $i . ' day')))
                    ->whereDate('ngay_sx', '<=', date('Y-m-d', strtotime($request->start_date . ' +' . $i . ' day')));
            } else {
                $query->whereDate('ngay_sx', '>=', date('Y-m-d'))
                    ->whereDate('ngay_sx', '<=', date('Y-m-d'));
            }
            if (isset($request->plan_ids)) {
                $query->whereIn('id', $request->plan_ids ?? []);
            }
            if (isset($request->machine)) {
                $query->whereIn('machine_id', $request->machine);
            }
            if (isset($request->lo_sx)) {
                $query->where('lo_sx', $request->lo_sx);
            }
            if (isset($request->customer_id)) {
                $query->where('customer_id', $request->customer_id);
            }
            if (isset($request->lo_sx)) {
                $query->where('lo_sx', $request->lo_sx);
            }
            if (isset($request->order_id)) {
                $query->where('order_id', $request->order_id);
            }
            $plans = $query->get()->sortByDesc('order.kho_tong');
            $data = [];
            // return $plans;
            foreach ($plans as $plan) {
                $group_plan_order = GroupPlanOrder::where('plan_id', $plan->id);
                $orders = Order::whereIn('orders.id', $group_plan_order->pluck('order_id')->toArray())->get();
                $order = $plan->order;
                if (!$order) continue;
                $formula = DB::table('formulas')->where('phan_loai_1', $order->phan_loai_1)->where('phan_loai_2', $order->phan_loai_2)->first();
                $buyer = $order->buyer;
                $obj = $plan;
                $obj->so_lop = $buyer->so_lop ?? "";
                $obj->ket_cau_giay = $buyer->ket_cau_giay ?? "";
                $obj->buyer_id = $order->buyer_id ?? "";
                $obj->short_name = $order->short_name ?? "";
                $obj->dai = $order->dai ?? "";
                $obj->rong = $order->rong ?? "";
                $obj->cao = $order->cao ?? "";
                $obj->so_ra = $order->so_ra ?? "";
                $obj->kho = $order->kho ?? "";
                $obj->dai_tam = $order->dai_tam ?? "";
                $obj->so_dao = ceil(($orders->sum('sl') * ($formula->he_so ?? 1)) / ($order->so_ra ?? 1));
                $obj->mql = $order->mql ?? "";
                $obj->mdh = $order->mdh ?? "";
                $obj->note_3 = $order->note_3 ?? "";
                $parse_data = [];
                $obj->dot = $order->dot ?? "";
                $obj = $obj->toArray();
                foreach ($table_key as $key_col => $col) {
                    if (isset($obj[$col])) {
                        $parse_data[$key_col] = $obj[$col];
                    }
                }
                $data[($buyer->ket_cau_giay ?? "") . '-' . $order->kho_tong][] = $parse_data;
            }
            // return $data;
            $start_col = 1;
            foreach ($header as $key => $cell) {
                if (!is_array($cell)) {
                    $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
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
            $start_row += 1;
            // return $request->start_date
            $sheet->setCellValue([1, $start_row], 'KE HOACH SAN XUAT NGAY ' . date('d.m.Y', strtotime($request->start_date . ' +' . $i . ' day')))->mergeCells([1, $start_row, count($header) - 2, $start_row])->getStyle([1, $start_row, count($header) - 2, $start_row])->applyFromArray($titleStyle);
            $sheet->setCellValue([count($header) - 1, $start_row], 'STT')->mergeCells([count($header) - 1, $start_row, count($header), $start_row])->getStyle([count($header) - 1, $start_row, count($header), $start_row])->applyFromArray($titleStyle);
            $sheet->getRowDimension($start_row)->setRowHeight(30);
            $table_col = 1;
            $table_row = $start_row + 1;
            foreach ($data as $key => $row) {
                $table_col = 1;
                $sheet->setCellValue([2, $table_row], explode('-', $key)[0] ?? "")->mergeCells([2, $table_row, count($header) - 2, $table_row])->getStyle([1, $table_row, count($header) - 2, $table_row])->applyFromArray($headerStyle2);
                $sheet->setCellValue([count($header) - 1, $table_row], explode('-', $key)[1] ?? "")->mergeCells([count($header) - 1, $table_row, count($header), $table_row])->getStyle([count($header) - 1, $table_row, count($header), $table_row])->applyFromArray($headerStyle2);
                $sheet->getRowDimension($table_row)->setRowHeight(25);
                foreach ($row as $k => $plan) {
                    $sheet->setCellValue([1, $table_row + 1], $index);
                    foreach ($plan as $key_index => $value) {
                        $table_col += 1;
                        $sheet->setCellValue($key_index . ($table_row + 1), $value)->getStyle($key_index . ($table_row + 1))->applyFromArray($tableStyle[$key_index]);
                    }
                    $sheet->getStyle([1, $table_row + 1, count($header), $table_row + 1])->applyFromArray($centerStyle);
                    $table_row += 1;
                    $index += 1;
                }
                $table_row += 1;
            }
            $start_row = $table_row + 1;
        }

        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            // $sheet->getStyle($column->getColumnIndex() . ($start_row) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Kế hoạch sản xuất MES.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Kế hoạch sản xuất MES ' . date('d-m-Y', strtotime($request->start_date)) . '.xlsx');
        $href = '/exported_files/Kế hoạch sản xuất MES ' . date('d-m-Y', strtotime($request->start_date)) . '.xlsx';
        return $this->success($href);
    }

    public function exportPreviewPlan(Request $request)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $headerStyle = array_merge($centerStyle, [
            'font' => ['bold' => true, 'color' => array('rgb' => '632523')],
        ]);
        $headerStyle2 = array_merge($centerStyle, [
            'font' => ['bold' => true, 'color' => array('rgb' => 'FF0000'), 'size' => 18],
        ]);
        $titleStyle = array_merge($centerStyle, [
            'font' => ['bold' => true, 'color' => array('rgb' => '632523'), 'size' => 18],
        ]);
        $bold = [
            'font' => ['bold' => true],
        ];
        $red = [
            'font' => ['color' => array('rgb' => 'FF0000')],
        ];
        $blue = [
            'font' => ['color' => array('rgb' => '0070C0')],
        ];
        $header = [
            'STT',
            'Lô SX',
            'Đơn hàng',
            'Khách hàng',
            'Số lớp',
            'Mã buyer',
            'Kết cấu giấy',
            'Dài',
            'Rộng',
            'Cao',
            'SL',
            'Ra',
            'Tổng khổ',
            'Dài tấm',
            'Số dao',
            'Ghi chú',
            'Phế',
        ];
        $table_key = [
            'A' => 'thu_tu_uu_tien',
            'B' => 'lo_sx',
            'C' => 'mdh',
            'D' => 'short_name',
            'E' => 'so_lop',
            'F' => 'buyer_id',
            'G' => 'ket_cau_giay',
            'H' => 'dai',
            'I' => 'rong',
            'J' => 'cao',
            'K' => 'sl_kh',
            'L' => 'so_ra',
            'M' => 'kho',
            'N' => 'dai_tam',
            'O' => 'so_dao',
            'P' => 'note_3',
            'Q' => 'so_phe',
        ];
        $tableStyle = [
            'A' => $centerStyle,
            'B' => $centerStyle,
            'C' => array_merge_recursive($centerStyle, $bold),
            'D' => array_merge_recursive($centerStyle, $bold),
            'E' => $centerStyle,
            'F' => $centerStyle,
            'G' => array_merge_recursive($centerStyle, $bold),
            'H' => $centerStyle,
            'I' => $centerStyle,
            'J' => $centerStyle,
            'K' => array_merge_recursive($centerStyle, $bold, $red),
            'L' => $centerStyle,
            'M' => array_merge_recursive($centerStyle, $blue),
            'N' => $centerStyle,
            'O' => array_merge_recursive($centerStyle, $bold, $red),
            'P' => $centerStyle,
            'Q' => $centerStyle,
        ];
        $start_row = 1;
        $index = 1;
        $plans = $request->plans;
        $data = [];
        foreach ($plans as $plan) {
            if ($plan['machine_id'] === 'So01') {
                $orders = Order::whereIn('orders.id', $plan['order_ids'])->get();
                $order = $orders[0];
                $formula = DB::table('formulas')->where('phan_loai_1', $order->phan_loai_1)->where('phan_loai_2', $order->phan_loai_2)->first();
                $buyer = $order->buyer;
                $obj = new ProductionPlan($plan);
                $obj->so_lop = $buyer->so_lop ?? "";
                $obj->short_name = $order->short_name ?? "";
                $obj->ket_cau_giay = $buyer->ket_cau_giay ?? "";
                $obj->buyer_id = $order->buyer_id ?? "";
                $obj->dai = $order->dai ?? "";
                $obj->rong = $order->rong ?? "";
                $obj->cao = $order->cao ?? "";
                $obj->so_ra = $order->so_ra ?? "";
                $obj->kho_tong = $order->kho_tong ?? "";
                $obj->dai_tam = $order->dai_tam ?? "";
                $obj->so_dao = ceil($orders->sum('sl') * ($formula->he_so ?? 1) / ($order->so_ra ?? 1));
                $obj->mql = $order->mql ?? "";
                $obj->mdh = $order->mdh ?? "";
                $obj->note_3 = $order->note_3 ?? "";
                $parse_data = [];
                $obj = $obj->toArray();
                foreach ($table_key as $key_col => $col) {
                    if (isset($obj[$col])) {
                        $parse_data[$key_col] = $obj[$col];
                    }
                }
                $data[($buyer->ket_cau_giay ?? "") . '-' . $order->kho_tong][] = $parse_data;
            } else {
                $order = Order::find($plan['order_id']);
                $buyer = $order->buyer;
                $obj = new ProductionPlan($plan);
                $obj->short_name = $order->short_name ?? "";
                $obj->ket_cau_giay = $buyer->ket_cau_giay ?? "";
                $obj->buyer_id = $order->buyer_id ?? "";
                $obj->dai = $order->dai;
                $obj->rong = $order->rong;
                $obj->cao = $order->cao;
                $obj->so_ra = $order->so_ra;
                $obj->kho_tong = $order->kho_tong;
                $obj->dai_tam = $order->dai_tam;
                $obj->so_dao = $order->so_dao;
                $obj->so_lop = $buyer->so_lop ?? "";
                $obj->mql = $order->mql;
                $obj->mdh = $order->mdh;
                $parse_data = [];
                $obj = $obj->toArray();
                foreach ($table_key as $key_col => $col) {
                    if (isset($obj[$col])) {
                        $parse_data[$key_col] = $obj[$col];
                    }
                }
                $data[($buyer->ket_cau_giay ?? "") . '-' . $order->kho_tong][] = $parse_data;
            }
        }
        $start_col = 1;
        foreach ($header as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
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
        $start_row += 1;
        // return $request->start_date
        $sheet->setCellValue([1, $start_row], 'KE HOACH SAN XUAT NGAY ' . date('d.m.Y', strtotime($request->start_time)))->mergeCells([1, $start_row, count($header) - 2, $start_row])->getStyle([1, $start_row, count($header) - 2, $start_row])->applyFromArray($titleStyle);
        $sheet->setCellValue([count($header) - 1, $start_row], 'STT')->mergeCells([count($header) - 1, $start_row, count($header), $start_row])->getStyle([count($header) - 1, $start_row, count($header), $start_row])->applyFromArray($titleStyle);
        $table_col = 1;
        $table_row = $start_row + 1;
        foreach ($data as $key => $row) {
            $table_col = 1;
            $sheet->setCellValue([2, $table_row], explode('-', $key)[0] ?? "")->mergeCells([2, $table_row, count($header) - 2, $table_row])->getStyle([2, $table_row, count($header) - 2, $table_row])->applyFromArray($headerStyle2);
            $sheet->setCellValue([count($header) - 1, $table_row], explode('-', $key)[1] ?? "")->mergeCells([count($header) - 1, $table_row, count($header), $table_row])->getStyle([count($header) - 1, $table_row, count($header), $table_row])->applyFromArray($headerStyle2);
            $sheet->getRowDimension($table_row)->setRowHeight(25);
            foreach ($row as $k => $plan) {
                $sheet->setCellValue([1, $table_row + 1], $index);
                foreach ($plan as $key_index => $value) {
                    $table_col += 1;
                    $sheet->setCellValue($key_index . ($table_row + 1), $value)->getStyle($key_index . ($table_row + 1))->applyFromArray($tableStyle[$key_index]);
                }
                $sheet->getStyle([1, $table_row + 1, count($header), $table_row + 1])->applyFromArray($centerStyle);
                $table_row += 1;
                $index += 1;
            }
            $table_row += 1;
        }
        $start_row = $table_row + 1;

        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            // $sheet->getStyle($column->getColumnIndex() . ($start_row) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Preview KHSX.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Preview KHSX.xlsx');
        $href = '/exported_files/Preview KHSX.xlsx';
        return $this->success($href);
    }
    function exportPreviewPlanXaLot(Request $request)
    {
        try {
            ini_set('memory_limit', '1024M');
            ini_set('max_execution_time', 0);
            $centerStyle = [
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
                'borders' => array(
                    'allBorders' => array(
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => array('argb' => '000000'),
                    ),
                ),
            ];
            $headerStyle = array_merge($centerStyle, [
                'font' => ['bold' => true, 'color' => array('rgb' => '632523')],
            ]);
            $titleStyle = array_merge($centerStyle, [
                'font' => ['bold' => true, 'color' => array('rgb' => '632523'), 'size' => 18],
            ]);
            $bold = [
                'font' => ['bold' => true],
            ];
            $red = [
                'font' => ['color' => array('rgb' => 'FF0000')],
            ];
            //Xả lót
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Xả Lót');
            $plans = $request->plans;
            $headerXL = [
                'STT',
                'MĐH',
                'KH',
                'Số lớp',
                'Kết cấu giấy',
                'Đơn hàng' => [
                    'Dài (lấy từ cột L)',
                    'Rộng (lấy từ cột W)',
                    'Cao (lấy từ cột H)',
                    'Kích thước ĐH'
                ],
                'Kế hoạch' => [
                    'Dài (Lấy từ KT chuẩn)',
                    'Rộng (Lấy từ KT chuẩn)',
                    'Cao (Lấy từ KT chuẩn)',
                ],
                'Số lượng',
                'Dài tấm',
                'Ghi chú',
                'Đợt'
            ];
            $table_keyXL = [
                'A' => 'stt',
                'B' => 'mdh',
                'C' => 'short_name',
                'D' => 'so_lop',
                'E' => 'ket_cau_giay',
                'F' => 'length',
                'G' => 'width',
                'H' => 'height',
                'I' => 'kich_thuoc',
                'J' => 'dai',
                'K' => 'rong',
                'L' => 'cao',
                'M' => 'sl_kh',
                'N' => 'dai_tam',
                'O' => 'note_3',
                'P' => 'dot',
            ];
            $tableStyleXL = [
                'A' => $centerStyle,
                'B' => array_merge_recursive($centerStyle, $bold),
                'C' => array_merge_recursive($centerStyle, $bold),
                'D' => $centerStyle,
                'E' => array_merge_recursive($centerStyle, $bold),
                'F' => $centerStyle,
                'G' => $centerStyle,
                'H' => $centerStyle,
                'I' => $centerStyle,
                'J' => array_merge_recursive($centerStyle, $bold),
                'K' => array_merge_recursive($centerStyle, $bold),
                'L' => array_merge_recursive($centerStyle, $bold),
                'M' => array_merge_recursive($centerStyle, $bold, $red),
                'N' => $centerStyle,
                'O' => array_merge_recursive($centerStyle, $red),
                'P' => $centerStyle,
            ];
            $headerXT = [
                'STT',
                'MĐH',
                'KH',
                'Số lớp',
                'Kết cấu giấy',
                'Dài',
                'Rộng',
                'Cao',
                'Số lượng',
                'Dài tấm',
                'Ghi chú',
            ];
            $table_keyXT = [
                'A' => 'stt',
                'B' => 'mdh',
                'C' => 'short_name',
                'D' => 'so_lop',
                'E' => 'ket_cau_giay',
                'F' => 'dai',
                'G' => 'rong',
                'H' => 'cao',
                'I' => 'sl_kh',
                'J' => 'dai_tam',
                'K' => 'note_3',
            ];
            $tableStyleXT = [
                'A' => $centerStyle,
                'B' => array_merge_recursive($centerStyle, $bold),
                'C' => array_merge_recursive($centerStyle, $bold),
                'D' => $centerStyle,
                'E' => array_merge_recursive($centerStyle, $bold),
                'F' => $centerStyle,
                'G' => $centerStyle,
                'H' => $centerStyle,
                'I' => array_merge_recursive($centerStyle, $bold, $red),
                'J' => $centerStyle,
                'K' => array_merge_recursive($centerStyle, $red),
            ];
            $dataXL = [];
            $dataXT = [];
            $index = 1;
            foreach ($plans as $key => $plan) {
                $plan = new ProductionPlan($plan);
                $order = $plan->order;
                $buyer = $order->buyer;
                $obj = $plan ?? new stdClass;
                $obj->stt = $index++;
                $obj->so_lop = $buyer->so_lop ?? "";
                $obj->short_name = $order->short_name ?? "";
                $obj->length = $order->length ?? "";
                $obj->width = $order->width ?? "";
                $obj->height = $order->height ?? "";
                $obj->kich_thuoc = $order->kich_thuoc ?? "";
                $obj->dai = $order->dai ?? "";
                $obj->rong = $order->rong ?? "";
                $obj->cao = $order->cao ?? "";
                $obj->mql = $order->mql ?? "";
                $obj->mdh = $order->mdh ?? "";
                $obj->note_3 = $order->note_3 ?? "";
                $obj->dot = $order->dot ?? "";
                $obj->dai_tam = $order->dai_tam ?? "";
                $obj->ket_cau_giay = $order->buyer->ket_cau_giay ?? "";
                $parse_data = [];
                $obj = $obj->toArray();
                if (!$obj['cao']) {
                    foreach ($table_keyXL as $key_col => $col) {
                        if (isset($obj[$col])) {
                            $parse_data[$key_col] = $obj[$col];
                        }
                    }
                    $dataXL[] = $parse_data;
                } else {
                    foreach ($table_keyXT as $key_col => $col) {
                        if (isset($obj[$col])) {
                            $parse_data[$key_col] = $obj[$col];
                        }
                    }
                    $dataXT[] = $parse_data;
                }
            }
            $start_col = 1;
            $start_row = 1;
            $sheet->setCellValue([1, $start_row], 'KẾ HOẠCH XẢ LÓT ' . date('d.m.Y', strtotime($request->start_time)))->mergeCells([1, $start_row, count($table_keyXL), $start_row])->getStyle([1, $start_row, count($table_keyXL), $start_row])->applyFromArray($titleStyle);
            $start_row += 1;
            foreach ($headerXL as $key => $cell) {
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
            // return $request->start_date

            $table_col = 1;
            $table_row = $start_row + 2;
            foreach ($dataXL as $key => $row) {
                $table_col = 1;
                foreach ($row as $k => $value) {
                    $sheet->setCellValue($k . $table_row, $value)->getStyle($k . $table_row)->applyFromArray($tableStyleXL[$k]);
                    $table_col += 1;
                }
                $table_row += 1;
            }
            $start_row = $table_row + 1;
            foreach ($sheet->getColumnIterator() as $column) {
                $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
                // $sheet->getStyle($column->getColumnIndex() . ($start_row) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
            }
            //Xả thùng
            $spreadsheet->createSheet();
            $sheet = $spreadsheet->setActiveSheetIndex(1);
            $sheet->setTitle('Xả Thùng');
            $start_row = 1;
            $data = [];
            $index = 1;
            foreach ($plans as $key => $plan) {
                $plan = new ProductionPlan($plan);
                $order = $plan->order;
                $buyer = $order->buyer;
                $obj = $plan;
                $obj->stt = $index++;
                $obj->so_lop = $buyer->so_lop ?? "";
                $obj->short_name = $order->short_name ?? "";
                $obj->dai = $order->dai ?? "";
                $obj->rong = $order->rong ?? "";
                $obj->cao = $order->cao ?? "";
                $obj->mql = $order->mql ?? "";
                $obj->mdh = $order->mdh ?? "";
                $obj->dai_tam = $order->dai_tam ?? "";
                $obj->note_3 = $order->note_3 ?? "";
                $obj->ket_cau_giay = $order->buyer->ket_cau_giay ?? "";
                $parse_data = [];
                $obj = $obj->toArray();
                foreach ($table_keyXT as $key_col => $col) {
                    if (isset($obj[$col])) {
                        $parse_data[$key_col] = $obj[$col];
                    }
                }
                $data[] = $parse_data;
            }
            $start_col = 1;
            $start_row = 1;
            $sheet->setCellValue([1, $start_row], 'KẾ HOẠCH XẢ THÙNG ' . date('d.m.Y', strtotime($request->start_time)))->mergeCells([1, $start_row, count($headerXT), $start_row])->getStyle([1, $start_row, count($headerXL), $start_row])->applyFromArray($titleStyle);
            $start_row += 1;
            foreach ($headerXT as $key => $cell) {
                if (!is_array($cell)) {
                    $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
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

            $table_col = 1;
            $table_row = $start_row + 1;
            foreach ($dataXT as $key => $row) {
                $table_col = 1;
                foreach ($row as $k => $value) {
                    $sheet->setCellValue($k . $table_row, $value)->getStyle($k . $table_row)->applyFromArray($tableStyleXT[$k]);
                    $table_col += 1;
                }
                $table_row += 1;
            }
            $start_row = $table_row + 1;
            foreach ($sheet->getColumnIterator() as $column) {
                $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
                // $sheet->getStyle($column->getColumnIndex() . ($start_row) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
            }
            header("Content-Description: File Transfer");
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="Kế hoạch sản xuất xả lót - thùng.xlsx"');
            header('Cache-Control: max-age=0');
            header("Content-Transfer-Encoding: binary");
            header('Expires: 0');
            $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('exported_files/Kế hoạch xả lót - thùng.xlsx');
            $href = '/exported_files/Kế hoạch xả lót - thùng.xlsx';
            return $this->success($href);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function importKHSX(Request $request)
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
        $plans = [];
        $lsx_array = [];
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 4
            if ($key > 1 && $row['B']) {
                if (empty($row['A'])) {
                    continue;
                }
                $input = [];
                $input['thu_tu_uu_tien'] = $row['A'];
                $input['lo_sx'] = $row['B'];
                $input['buyer_id'] = $row['F'];
                $input['so_ra'] = $row['L'];
                $input['kho'] = $row['M'];
                $input['dai_tam'] = $row['N'];
                $input['so_dao'] = $row['O'];
                $input['note_3'] = $row['P'];
                if ($input['kho']) {
                    $kho_array = range(0, 200, 5);
                    $kho_array = array_merge($kho_array, [88, 92]);
                    $input['kho_tong'] = $this->lamTronLen($input['kho'], $kho_array);
                }
                if ($input['dai_tam'] && $input['so_dao']) {
                    $input['so_met_toi'] = round($input['dai_tam'] * $input['so_dao'] / 100);
                }
                if ($input['lo_sx']) {
                    $plans[] = $input;
                    if (!in_array($input['lo_sx'], $lsx_array)) {
                        $lsx_array[] = $input['lo_sx'];
                    } else {
                        return $this->failure('', 'Lô ' . $input['lo_sx'] . ' bị trùng');
                    }
                }
            }
        }
        // return $plans;
        foreach ($plans as $plan_input) {
            $plan = ProductionPlan::where('lo_sx', $plan_input['lo_sx'])->first();
            if ($plan) {
                $plan_input['ordering'] = $plan_input['thu_tu_uu_tien'];
                $plan->update($plan_input);
                $plan->info_losx()->update(['thu_tu_uu_tien' => $plan_input['thu_tu_uu_tien']]);
                LSXLog::where('lo_sx', $plan->lo_sx)->where('machine_id', 'So01')->update(['thu_tu_uu_tien' => $plan_input['thu_tu_uu_tien']]);
                $plan->infoCongDoan()->update(['thu_tu_uu_tien' => $plan_input['thu_tu_uu_tien']]);
                $group_orders = GroupPlanOrder::where('plan_id', $plan->id)->pluck('order_id')->toArray();
                unset($plan_input['thu_tu_uu_tien'], $plan_input['lo_sx'], $plan_input['ordering']);
                Order::whereIn('id', $group_orders)->update($plan_input);
            }
        }
        $this->apiUIController->updateInfoCongDoanPriority();
        return $this->success([], 'Upload thành công');
    }

    function lamTronLen($so, $mangSo)
    {
        $soGanNhat = count($mangSo) ? max($mangSo) : null;
        foreach ($mangSo as $soTrongMang) {
            if ($soTrongMang >= $so && ($soGanNhat === null || $soTrongMang < $soGanNhat)) {
                $soGanNhat = $soTrongMang;
            }
        }
        return $soGanNhat < $so ? $so : $soGanNhat;
    }

    public function exportKHXaLot(Request $request)
    {
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $headerStyle = array_merge($centerStyle, [
            'font' => ['bold' => true, 'color' => array('rgb' => '632523')],
        ]);
        $titleStyle = array_merge($centerStyle, [
            'font' => ['bold' => true, 'color' => array('rgb' => '632523'), 'size' => 18],
        ]);
        $bold = [
            'font' => ['bold' => true],
        ];
        $red = [
            'font' => ['color' => array('rgb' => 'FF0000')],
        ];
        $headerXL = [
            'STT',
            'MĐH',
            'KH',
            'Số lớp',
            'Kết cấu giấy',
            'Đơn hàng' => [
                'Dài (lấy từ cột L)',
                'Rộng (lấy từ cột W)',
                'Cao (lấy từ cột H)',
                'Kích thước ĐH'
            ],
            'Kế hoạch' => [
                'Dài (Lấy từ KT chuẩn)',
                'Rộng (Lấy từ KT chuẩn)',
                'Cao (Lấy từ KT chuẩn)',
            ],
            'Số lượng',
            'Dài tấm',
            'Ghi chú',
            'Đợt'
        ];
        $table_keyXL = [
            'A' => 'stt',
            'B' => 'mdh',
            'C' => 'short_name',
            'D' => 'so_lop',
            'E' => 'ket_cau_giay',
            'F' => 'length',
            'G' => 'width',
            'H' => 'height',
            'I' => 'kich_thuoc',
            'J' => 'dai',
            'K' => 'rong',
            'L' => 'cao',
            'M' => 'sl_kh',
            'N' => 'dai_tam',
            'O' => 'note_3',
            'P' => 'dot',
        ];
        $tableStyleXL = [
            'A' => $centerStyle,
            'B' => array_merge_recursive($centerStyle, $bold),
            'C' => array_merge_recursive($centerStyle, $bold),
            'D' => $centerStyle,
            'E' => array_merge_recursive($centerStyle, $bold),
            'F' => $centerStyle,
            'G' => $centerStyle,
            'H' => $centerStyle,
            'I' => $centerStyle,
            'J' => array_merge_recursive($centerStyle, $bold),
            'K' => array_merge_recursive($centerStyle, $bold),
            'L' => array_merge_recursive($centerStyle, $bold),
            'M' => array_merge_recursive($centerStyle, $bold, $red),
            'N' => $centerStyle,
            'O' => array_merge_recursive($centerStyle, $red),
            'P' => $centerStyle,
        ];
        $headerXT = [
            'STT',
            'MĐH',
            'KH',
            'Số lớp',
            'Kết cấu giấy',
            'Dài',
            'Rộng',
            'Cao',
            'Số lượng',
            'Dài tấm',
            'Ghi chú',
        ];
        $table_keyXT = [
            'A' => 'stt',
            'B' => 'mdh',
            'C' => 'short_name',
            'D' => 'so_lop',
            'E' => 'ket_cau_giay',
            'F' => 'dai',
            'G' => 'rong',
            'H' => 'cao',
            'I' => 'sl_kh',
            'J' => 'dai_tam',
            'K' => 'note_3',
        ];
        $tableStyleXT = [
            'A' => $centerStyle,
            'B' => array_merge_recursive($centerStyle, $bold),
            'C' => array_merge_recursive($centerStyle, $bold),
            'D' => $centerStyle,
            'E' => array_merge_recursive($centerStyle, $bold),
            'F' => $centerStyle,
            'G' => $centerStyle,
            'H' => $centerStyle,
            'I' => array_merge_recursive($centerStyle, $bold, $red),
            'J' => $centerStyle,
            'K' => array_merge_recursive($centerStyle, $red),
        ];
        //Xả lót
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Xả Lót');
        $number_days = round(((strtotime($request->end_date) - strtotime($request->start_date)) ?? 0) / (60 * 60 * 24));
        for ($i = 0; $i <= $number_days; $i++) {
            $index = 1;
            $query = ProductionPlan::with('order')->whereHas('machine', function ($query) {
                $query->where('line_id', '33');
            })->whereHas('order', function ($query) {
                $query->whereNotNull(['dai', 'rong'])->whereNull('cao');
            });
            if (isset($request->end_date) && isset($request->start_date)) {
                $query->whereDate('ngay_sx', '>=', date('Y-m-d', strtotime($request->start_date . ' +' . $i . ' day')))
                    ->whereDate('ngay_sx', '<=', date('Y-m-d', strtotime($request->start_date . ' +' . $i . ' day')));
            } else {
                $query->whereDate('ngay_sx', '>=', date('Y-m-d'))
                    ->whereDate('ngay_sx', '<=', date('Y-m-d'));
            }
            if (isset($request->plan_ids)) {
                $query->whereIn('id', $request->plan_ids ?? []);
            }
            if (isset($request->lo_sx)) {
                $query->where('lo_sx', $request->lo_sx);
            }
            if (isset($request->customer_id)) {
                $query->where('customer_id', $request->customer_id);
            }
            if (isset($request->lo_sx)) {
                $query->where('lo_sx', $request->lo_sx);
            }
            if (isset($request->order_id)) {
                $query->where('order_id', $request->order_id);
            }
            $plans = $query->get()->sortBy('order.buyer.ket_cau_giay');
            $data = [];
            $index = 1;
            foreach ($plans as $key => $plan) {
                $order = $plan->order;
                $buyer = $order->buyer;
                $obj = $plan ?? new stdClass;
                $obj->stt = $index++;
                $obj->so_lop = $buyer->so_lop ?? "";
                $obj->short_name = $order->short_name ?? "";
                $obj->length = $order->length ?? "";
                $obj->width = $order->width ?? "";
                $obj->height = $order->height ?? "";
                $obj->kich_thuoc = $order->kich_thuoc ?? "";
                $obj->dai = $order->dai ?? "";
                $obj->rong = $order->rong ?? "";
                $obj->cao = $order->cao ?? "";
                $obj->mql = $order->mql ?? "";
                $obj->mdh = $order->mdh ?? "";
                $obj->note_3 = $order->note_3 ?? "";
                $obj->dot = $order->dot ?? "";
                $obj->dai_tam = $order->dai_tam ?? "";
                $obj->ket_cau_giay = $order->buyer->ket_cau_giay ?? "";
                $parse_data = [];
                $obj = $obj->toArray();
                foreach ($table_keyXL as $key_col => $col) {
                    if (isset($obj[$col])) {
                        $parse_data[$key_col] = $obj[$col];
                    }
                }
                $data[] = $parse_data;
            }
            $start_col = 1;
            $start_row = 1;
            $sheet->setCellValue([1, $start_row], 'KẾ HOẠCH XẢ LÓT ' . date('d.m.Y', strtotime($request->start_date . ' +' . $i . ' day')))->mergeCells([1, $start_row, count($table_keyXL), $start_row])->getStyle([1, $start_row, count($table_keyXL), $start_row])->applyFromArray($titleStyle);
            $start_row += 1;
            foreach ($headerXL as $key => $cell) {
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
            $table_col = 1;
            $table_row = $start_row + 2;
            // return $data;
            foreach ($data as $key => $row) {
                $table_col = 1;
                foreach ($row as $k => $value) {
                    $sheet->setCellValue($k . $table_row, $value)->getStyle($k . $table_row)->applyFromArray($tableStyleXL[$k]);
                    $table_col += 1;
                }
                $table_row += 1;
            }
            $start_row = $table_row + 1;
        }
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            // $sheet->getStyle($column->getColumnIndex() . ($start_row) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        // //Xả thùng
        $spreadsheet->createSheet();
        $sheet = $spreadsheet->setActiveSheetIndex(1);
        $sheet->setTitle('Xả Thùng');
        $start_row = 1;
        $number_days = round(((strtotime($request->end_date) - strtotime($request->start_date)) ?? 0) / (60 * 60 * 24));
        for ($i = 0; $i <= $number_days; $i++) {
            $query = ProductionPlan::with('order')->orderBy('thu_tu_uu_tien')->orderBy('created_at', 'DESC')->whereHas('machine', function ($query) {
                $query->where('line_id', '33');
            })->whereHas('order', function ($query) {
                $query->whereNotNull(['dai', 'rong', 'cao']);
            });
            if (isset($request->end_date) && isset($request->start_date)) {
                $query->whereDate('ngay_sx', '>=', date('Y-m-d', strtotime($request->start_date . ' +' . $i . ' day')))
                    ->whereDate('ngay_sx', '<=', date('Y-m-d', strtotime($request->start_date . ' +' . $i . ' day')));
            } else {
                $query->whereDate('ngay_sx', '>=', date('Y-m-d'))
                    ->whereDate('ngay_sx', '<=', date('Y-m-d'));
            }
            if (isset($request->plan_ids)) {
                $query->whereIn('id', $request->plan_ids ?? []);
            }
            if (isset($request->lo_sx)) {
                $query->where('lo_sx', $request->lo_sx);
            }
            if (isset($request->customer_id)) {
                $query->where('customer_id', $request->customer_id);
            }
            if (isset($request->lo_sx)) {
                $query->where('lo_sx', $request->lo_sx);
            }
            if (isset($request->order_id)) {
                $query->where('order_id', $request->order_id);
            }
            $plans = $query->get()->sortBy('order.buyer.ket_cau_giay');
            $data = [];
            $index = 1;
            foreach ($plans as $key => $plan) {
                $order = $plan->order;
                $buyer = $order->buyer;
                $obj = $plan;
                $obj->stt = $index++;
                $obj->so_lop = $buyer->so_lop ?? "";
                $obj->short_name = $order->short_name ?? "";
                $obj->dai = $order->dai ?? "";
                $obj->rong = $order->rong ?? "";
                $obj->cao = $order->cao ?? "";
                $obj->mql = $order->mql ?? "";
                $obj->mdh = $order->mdh ?? "";
                $obj->dai_tam = $order->dai_tam ?? "";
                $obj->note_3 = $order->note_3 ?? "";
                $obj->ket_cau_giay = $order->buyer->ket_cau_giay ?? "";
                $parse_data = [];
                $obj = $obj->toArray();
                foreach ($table_keyXT as $key_col => $col) {
                    if (isset($obj[$col])) {
                        $parse_data[$key_col] = $obj[$col];
                    }
                }
                $data[] = $parse_data;
            }
            $start_col = 1;
            $start_row = 1;
            $sheet->setCellValue([1, $start_row], 'KẾ HOẠCH XẢ THÙNG ' . date('d.m.Y', strtotime($request->start_date . ' +' . $i . ' day')))->mergeCells([1, $start_row, count($table_keyXT), $start_row])->getStyle([1, $start_row, count($table_keyXT), $start_row])->applyFromArray($titleStyle);
            $start_row += 1;
            foreach ($headerXT as $key => $cell) {
                if (!is_array($cell)) {
                    $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
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

            $table_col = 1;
            $table_row = $start_row + 1;
            foreach ($data as $key => $row) {
                $table_col = 1;
                foreach ($row as $k => $value) {
                    $sheet->setCellValue($k . $table_row, $value)->getStyle($k . $table_row)->applyFromArray($tableStyleXT[$k]);
                    $table_col += 1;
                }
                $table_row += 1;
            }
            $start_row = $table_row + 1;
        }
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            // $sheet->getStyle($column->getColumnIndex() . ($start_row) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Kế hoạch sản xuất xả lót - thùng.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Kế hoạch xả lót - thùng.xlsx');
        $href = '/exported_files/Kế hoạch xả lót - thùng.xlsx';
        return $this->success($href);
    }

    function findKeysByValue($array, $searchValue)
    {
        $matchingKeys = array();
        foreach ($array as $key => $value) {
            if ($value === $searchValue) {
                $matchingKeys[] = $key;
            }
        }
        return $matchingKeys;
    }

    public function createStampFromOrder(Request $request)
    {
        $input = $request->all();
        $machine = Machine::with('line')->find($input['machine_id']);
        if (!$machine) return $this->failure('', 'Không tìm thấy máy');
        try {
            DB::beginTransaction();
            $prefix = 'T' . date('ymd');
            Tem::where('display', 1)->where('created_by', $request->user()->id)->update(['display' => 0]);
            $newest_tem = Tem::where('lo_sx', 'like', "$prefix%")->orderBy('id', 'DESC')->first();
            // $orders = Order::whereIn('id', $input['order_ids'])->get();
            $index = $newest_tem ? (int)str_replace($prefix, '', $newest_tem->lo_sx) : 0;
            foreach (($request->orders ?? []) as $key => $order) {
                if (!$order) continue;
                if (!isset($order['sl_dinh_muc']) || $order['sl_dinh_muc'] <= 0) {
                    continue;
                }
                $quantityArray = $this->getQuantityArray($order['sl'], $order['sl_dinh_muc']);
                foreach ($quantityArray as $key => $value) {
                    $tem_input = [];
                    $tem_input = $order;
                    $tem_input['order_id'] = $order['id'];
                    $tem_input['machine_id'] = $machine->id;
                    $tem_input['sl_tem'] = 1;
                    $tem_input['created_by'] = $request->user()->id;
                    $tem_input['khach_hang'] = $order['short_name'];
                    $tem_input['quy_cach'] = $order['kich_thuoc_chuan'];
                    $tem_input['so_luong'] = $value;
                    $tem_input['nhan_vien_sx'] = $input['nhan_vien_sx'];
                    $tem_input['note'] = $order['note_3'];
                    $tem_input['lo_sx'] = $prefix . str_pad($index + 1, 4, '0', STR_PAD_LEFT);
                    unset($tem_input['id']);
                    $tem = Tem::create($tem_input);
                    $index += 1;
                }
            }
            DB::commit();
            return $this->success('', 'Tạo thành công');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::debug($th);
            return $this->failure($th->getMessage(), 'Tạo không thành công');
        }
    }

    function getQuantityArray($quantity, $default)
    {
        // Calculate the full parts and the remainder
        $fullParts = intdiv($quantity, $default);
        $remainder = $quantity % $default;
        // Create an array filled with full parts
        $result = array_fill(0, $fullParts, $default);
        // Add the remainder if it is not zero
        if ($remainder !== 0) {
            $result[] = $remainder;
        }
        return $result;
    }

    public function exportQCHistory(Request $request)
    {
        $query = $this->queryQuality($request);
        $infos = $query->with("plan", "line", 'tem', 'qc_log')->get();
        $data = [];
        foreach ($infos as $key => $info_cong_doan) {
            $plan = $info_cong_doan->plan;
            $tem = $info_cong_doan->tem;
            $order = null;
            if ($tem) {
                $order = $tem->order;
            } else if ($plan) {
                $order = $plan->order;
            }
            $obj = new stdClass;
            $obj->stt = $key + 1;
            $obj->ngay_sx = $info_cong_doan->ngay_sx ? date('d-m-Y', strtotime($info_cong_doan->ngay_sx)) : "";
            $obj->qc_person = $info_cong_doan->qc_log ? ($info_cong_doan->qc_log->info['user_name'] ?? "") : "";
            $obj->machine_id = $info_cong_doan->machine_id;
            $obj->khach_hang = $order->short_name ?? "";
            $obj->mdh = $order->mdh ?? "";
            $obj->quy_cach = $order ? $order->dai . 'x' . $order->rong . ($order->cao ? 'x' . $order->cao : "") : "";
            $obj->mql = $order->mql ?? "";
            $obj->sl_dau_ra_hang_loat = $info_cong_doan->sl_dau_ra_hang_loat;
            $obj->sl_ok = $info_cong_doan->sl_dau_ra_hang_loat - $info_cong_doan->sl_ng_qc - $info_cong_doan->sl_ng_sx;
            $obj->sl_phe = $info_cong_doan->sl_ng_qc + $info_cong_doan->sl_ng_sx;
            $obj->ty_le_ng = $info_cong_doan->sl_dau_ra_hang_loat ? (floor($obj->sl_phe / $info_cong_doan->sl_dau_ra_hang_loat * 100) . '%') : '0%';
            $obj->sl_tinh_nang = $info_cong_doan->sl_tinh_nang;
            $obj->sl_ngoai_quan = $info_cong_doan->sl_ngoai_quan;
            $obj->phan_dinh = $info_cong_doan->phan_dinh === 1 ? "OK" : ($info_cong_doan->phan_dinh === 2 ? "NG" : "pass");
            $obj->lo_sx = $info_cong_doan->lo_sx;
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
            'Ngày SX',
            'QC kiểm',
            "Máy",
            'Khách hàng',
            "MĐH",
            "Kích thước chuẩn",
            "MQL",
            "Sản lượng đếm được",
            'Sản lượng sau QC',
            "Số phế",
            "Tỷ lệ phế",
            "KQ kiểm tra tính năng",
            'KQ kiểm tra ngoại quan',
            'Phán định',
            'Lô SX'
        ];
        foreach ($header as $key => $cell) {
            $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            $start_col += 1;
        }

        $sheet->setCellValue([1, 1], 'DANH SÁCH KIỂM TRA CHẤT LƯỢNG')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->fromArray($data, null, 'A3', true);
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
        header('Content-Disposition: attachment;filename="Chi tiết QC.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Chi tiết QC.xlsx');
        $href = '/exported_files/Chi tiết QC.xlsx';
        return $this->success($href);
    }

    public function exportIQCHistory(Request $request)
    {
        $input = $request->all();
        $sorted_ids = ['IT-06', 'IT-03', 'IT-01', 'IT-05', 'IT-04', 'IT-02', 'IT-07'];
        $test_criteria = TestCriteria::where('line_id', 38)->where('hang_muc', 'tinh_nang')->get()->sortBy(function ($model) use ($sorted_ids) {
            return array_search($model->getKey(), $sorted_ids);
        });
        $columns = [];
        foreach ($test_criteria as $key => $value) {
            $columns[] = [
                'title' => $value->name,
                'dataIndex' => $value->id,
            ];
        }
        $query = WareHouseMLTImport::orderBy('created_at');
        if (isset($input['start_date']) && $input['end_date']) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($input['start_date'])))
                ->whereDate('created_at', '<=', date('Y-m-d', strtotime($input['end_date'])));
        } else {
            $query->whereDate('created_at', '>=', date('Y-m-d'))
                ->whereDate('created_at', '<=', date('Y-m-d'));
        }
        if (isset($input['material_id'])) {
            $query = $query->where('material_id', 'like', '%' . $input['material_id'] . '%');
        }
        if (isset($input['ma_vat_tu'])) {
            $query = $query->where('ma_vat_tu', 'like', '%' . $input['ma_vat_tu'] . '%');
        }
        if (isset($input['ma_cuon_ncc'])) {
            $query = $query->where('ma_cuon_ncc', 'like', '%' . $input['ma_cuon_ncc'] . '%');
        }
        if (isset($input['loai_giay'])) {
            $query = $query->where('loai_giay', 'like', '%' . $input['loai_giay'] . '%');
        }
        $records = $query->get();
        $data = [];
        foreach ($records as $key => $value) {
            $row = [];
            $row['stt'] = $key + 1;
            $row['ngay_kiem'] = date('d-m-Y', strtotime($value->created_at));

            $row['user_name'] = $value->log['user_name'] ?? "";
            $row['material_id'] = $value->material_id;
            $row['ten_ncc'] = $value->supplier->name ?? "";
            $row['so_kg'] = $value->so_kg;
            $row['loai_giay'] = $value->loai_giay;
            $row['dinh_luong'] = $value->dinh_luong;
            $row['kho_giay'] = $value->kho_giay;
            $result = collect($value->log['tinh_nang'])->keyBy('id');
            foreach ($columns as $key => $col) {
                $row[$col['dataIndex']] = $result[$col['dataIndex']]['value'] ?? "";
            }
            $row['phan_dinh'] = $value->log['phan_dinh'] === 1 ? "OK" : ($value->log['phan_dinh'] === 2 ? "NG" : "");
            $data[] = $row;
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
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $start_row = 2;
        $start_col = 1;
        $sheet = $spreadsheet->getActiveSheet();
        $header = [
            'STT',
            "Ngày kiểm",
            "Người kiểm",
            "Mã cuộn",
            "Mã nhà cung cấp",
            "Khối lượng cuộn",
            "Loại giấy",
            'Định lượng',
            "Khổ giấy",
            "Kết quả kiểm tra" => array_map(function ($col) {
                return $col['title'];
            }, $columns),
            "Phán định",
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
        $sheet->setCellValue([1, 1], 'BÁO CÁO KẾT QUẢ KIỂM TRA CHẤT LƯỢNG GIẤY CUỘN ĐẦU VÀO')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->fromArray($data, null, 'A4');
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
        $start_row_table = 4;
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
        header('Content-Disposition: attachment;filename="Chi tiết IQC.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Chi tiết IQC.xlsx');
        $href = '/exported_files/Chi tiết IQC.xlsx';
        return $this->success($href);
    }
    //End UI

    public function phanKhuTheoNCC(Request $request)
    {
        $input = $request->all();
        foreach ($input['supplier_id'] as $supplier_id) {
            foreach ($input['warehouse_mlt_id'] as $warehouse_mlt_id) {
                $phan_khu = DB::table('phan_khu')->insert(['supplier_id' => $supplier_id, 'warehouse_mlt_id' => $warehouse_mlt_id]);
            }
        }
        return $this->success('Phân khu thành công');
    }

    public function importTieuChuanNCC(Request $request)
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
        $tieu_chuan_ncc = [];
        $params = [];
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 4
            if ($key === 2) {
                $params = [$row['D'], $row['E'], $row['F'], $row['G'], $row['H'], $row['I']];
            }
            if ($key > 1) {
                $input = [];
                $input['ma_ncc'] = str_replace(' ', '', $row['C']);
                $input['requirements'] = [
                    $params[0] => $row['D'],
                    $params[1] => $row['E'],
                    $params[2] => $row['F'],
                    $params[3] => $row['G'],
                    $params[4] => $row['H'],
                    $params[5] => $row['I'],
                ];
                if ($input['ma_ncc']) {
                    $tieu_chuan_ncc[] = $input;
                }
            }
        }
        return $tieu_chuan_ncc;
        TieuChuanNCC::truncate();
        foreach ($tieu_chuan_ncc as $key => $input) {
            TieuChuanNCC::create($input);
        }
        return $this->success([], 'Upload thành công');
    }

    public function getUIItemMenu(Request $request)
    {
        $lines = Line::where('display', 1)->with(['children' => function ($query) {
            $query->whereNull('parent_id')->select('machines.*', 'id as key', 'id as title');
        }])->select('lines.*', 'id as key', 'name as title')->get();
        return $this->success($lines);
    }

    public function getGoodsReceiptNote(Request $request)
    {
        $phieu_nhap_kho = GoodsReceiptNote::all();
        return $this->success($phieu_nhap_kho);
    }

    function customQueryWarehouseMLTLog($request)
    {
        $input = $request->all();
        $ids = WarehouseMLTLog::has('material')
            ->selectRaw("id, material_id, MAX(tg_nhap) as latest_tg_nhap")
            ->groupBy('material_id')
            ->pluck('id')->toArray();
        $query = WarehouseMLTLog::whereIn('id', $ids)->orderBy('tg_nhap', 'DESC');
        if (isset($input['loai_giay']) || isset($input['kho_giay']) || isset($input['dinh_luong']) || isset($input['ma_cuon_ncc']) || isset($input['ma_vat_tu']) || isset($input['so_kg']) || isset($input['so_cuon'])) {
            $query->whereHas('material', function ($q) use ($input) {
                if (isset($input['loai_giay'])) $q->where('loai_giay', 'like', "%" . $input['loai_giay'] . "%");
                if (isset($input['kho_giay'])) $q->where('kho_giay', 'like', "%" . $input['kho_giay'] . "%");
                if (isset($input['dinh_luong'])) $q->where('dinh_luong', 'like', "%" . $input['dinh_luong'] . "%");
                if (isset($input['ma_cuon_ncc'])) $q->where('ma_cuon_ncc', 'like', "%" . $input['ma_cuon_ncc'] . "%");
                if (isset($input['ma_vat_tu'])) $q->where('ma_vat_tu', 'like', "%" . $input['ma_vat_tu'] . "%");
                if (isset($input['so_kg'])) $q->where('so_kg', $input['so_kg']);
                if (isset($input['so_cuon'])) $q->whereColumn('so_kg', '=', 'so_kg_dau');
            });
        }
        if (isset($input['material_id'])) {
            $query->where('material_id', 'like', "%" . $input['material_id'] . "%");
        }
        if (isset($input['tg_nhap'])) {
            $query->whereDate('tg_nhap', date('Y-m-d', strtotime($input['tg_nhap'])));
        }
        if (isset($input['tg_xuat'])) {
            $query->whereDate('tg_xuat', date('Y-m-d', strtotime($input['tg_xuat'])));
        }
        if (isset($input['locator_id'])) {
            $query->where('locator_id', 'like', "%" . $input['locator_id'] . "%");
        }
        if (isset($input['khu_vuc'])) {
            $query->where('locator_id', 'like', "%C" . str_pad($input['khu_vuc'], 2, '0', STR_PAD_LEFT) . "%");
        }
        return $query->with('material.supplier', 'warehouse_mlt_import');
    }
    public function warehouseMLTLog(Request $request)
    {
        $page = $request->page - 1;
        $pageSize = $request->pageSize;
        $query = $this->customQueryWarehouseMLTLog($request);
        $totalPage = $query->count();
        $query->offset($page * $pageSize)->limit($pageSize ?? 20);
        $records = $query->get();
        foreach ($records as $key => $record) {
            // if ($record->tg_xuat) {
            //     $nextImportLog = WarehouseMLTLog::where('tg_nhap', '>=', $record->tg_xuat)->where('material_id', $record->material_id)->orderBy('tg_nhap')->first();
            // } else {
            //     $nextImportLog = null;
            // }
            // $so_con_lai = $nextImportLog->so_kg_nhap ?? 0;
            $record->ten_ncc = ($record->material && $record->material->supplier) ? $record->material->supplier->name : '';
            $record->loai_giay = $record->material->loai_giay ?? '';
            $record->fsc = ($record->material && $record->material->fsc) ? 'X' : '';
            $record->kho_giay = $record->material->kho_giay ?? '';
            $record->dinh_luong = $record->material->dinh_luong ?? '';
            $record->ma_cuon_ncc = $record->material->ma_cuon_ncc ?? "";
            $record->ma_vat_tu = $record->material->ma_vat_tu ?? '';
            $record->tg_nhap = $record->tg_nhap ? date('d/m/Y', strtotime($record->tg_nhap)) : "";
            $record->so_phieu_nhap_kho = $record->warehouse_mlt_import ? $record->warehouse_mlt_import->goods_receipt_note_id : '';
            $record->so_kg_dau = $record->material->so_kg_dau ?? "0";
            $record->so_kg_cuoi = $record->material->so_kg ?? 0;
            $record->so_kg_xuat = $record->so_kg_nhap -  $record->so_kg_cuoi;
            $record->tg_xuat = $record->tg_xuat ? date('d/m/Y', strtotime($record->tg_xuat)) : '';
            $record->so_cuon = ($record->material && $record->material->so_kg == $record->material->so_kg_dau) ? 1 : 0;
            $record->khu_vuc = str_contains($record->locator_id, 'C') ? ('Khu' . (int)str_replace('C', '', $record->locator_id)) : "";
            $record->locator_id = $record->locator_id;
        }
        return $this->success(['data' => $records, 'totalPage' => $totalPage]);
    }

    public function exportWarehouseMLTLog(Request $request)
    {
        $query = $this->customQueryWarehouseMLTLog($request);
        $records = $query->with('material', 'warehouse_mlt_import', 'locatorMlt.warehouse_mlt')->get();
        $data = [];
        foreach ($records as $key => $record) {
            // if ($record->tg_xuat) {
            //     $nextImportLog = WarehouseMLTLog::where('tg_nhap', '>=', $record->tg_xuat)->where('material_id', $record->material_id)->orderBy('tg_nhap')->first();
            // } else {
            //     $nextImportLog = null;
            // }
            // $so_con_lai = $nextImportLog->so_kg_nhap ?? 0;
            $obj = new stdClass;
            $obj->stt = $key + 1;
            $obj->material_id = $record->material_id;
            $obj->ten_ncc = $record->material->supplier->name ?? "";
            $obj->loai_giay = $record->material->loai_giay ?? "";
            $obj->fsc = $record->material->fsc ? 'X' : '';
            $obj->kho_giay = $record->material->kho_giay ?? "";
            $obj->dinh_luong = $record->material->dinh_luong ?? "";
            $obj->ma_cuon_ncc = $record->warehouse_mlt_import->ma_cuon_ncc ?? "";
            $obj->ma_vat_tu = $record->material->ma_vat_tu ?? "";
            $obj->so_phieu_nhap_kho = $record->warehouse_mlt_import ? $record->warehouse_mlt_import->goods_receipt_note_id : '';
            $obj->tg_nhap = $record->tg_nhap ? date('d/m/Y', strtotime($record->tg_nhap)) : "";
            $obj->so_kg_dau = $record->material->so_kg_dau ?? "0";
            $obj->so_kg_nhap = $record->so_kg_nhap ?? "0";
            $obj->so_kg_xuat = $obj->so_kg_nhap - ($record->material->so_kg ?? 0);
            $obj->so_kg_cuoi = $record->material->so_kg ?? 0;
            $obj->tg_xuat = $record->tg_xuat ? date('d/m/Y', strtotime($record->tg_xuat)) : '';
            $obj->so_cuon = $record->material->so_kg == $record->material->so_kg_dau ? 1 : 0;
            $obj->khu_vuc = $record->locatorMlt->warehouse_mlt->name ?? "";
            $obj->locator_id = $record->locator_id;
            $data[] = (array)$obj;
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
        $header = [
            'STT',
            'Mã cuộn TBDX',
            'Tên NCC',
            'Loại giấy',
            'FSC',
            'Khổ giấy (cm)',
            'Định lượng',
            'Mã cuộn NCC',
            'Mã vật tư',
            'Số phiếu nhập kho',
            'Ngày nhập',
            'SL đầu (kg)',
            'Số kg nhập',
            'SL xuất (kg)',
            'SL cuối (kg)',
            'Ngày xuất',
            'Số cuộn',
            'Khu vực',
            'Vị trí',
        ];
        foreach ($header as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
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
        $sheet->setCellValue([1, 1], 'Quản lý kho NVL')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->fromArray($data, NULL, 'A3', true);
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            // $sheet->getStyle($column->getColumnIndex() . ($start_row) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        $sheet->getStyle([1, 3, count($header), count($data) + 2])->applyFromArray(
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
        header('Content-Disposition: attachment;filename="Quản lý kho NVL.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Quản lý kho NVL.xlsx');
        $href = '/exported_files/Quản lý kho NVL.xlsx';
        return $this->success($href);
    }

    function customQueryWarehouseFGLog($request)
    {
        $input = $request->all();
        $query = WarehouseFGLog::where('type', 1)->with(['user', 'exportRecord.user'])->orderBy('created_at');
        if (isset($input['start_date']) && isset($input['end_date'])) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($input['start_date'])))->whereDate('created_at', '<=', date('Y-m-d', strtotime($input['end_date'])));
        }
        if (isset($input['locator_id'])) {
            $query->where('locator_id', 'like', '%' . $input['locator_id'] . '%');
        }
        if (isset($input['pallet_id'])) {
            $query->where('pallet_id', 'like', $input['pallet_id'] . '%');
        }
        if (isset($input['lo_sx'])) {
            $query->where('lo_sx', 'like', $input['lo_sx'] . "%");
        }
        if (isset($input['khach_hang']) || isset($input['mdh']) || isset($input['mql']) || isset($input['kich_thuoc']) || isset($input['length']) || isset($input['width']) || isset($input['height'])) {
            $order_query = Order::withTrashed();
            if (isset($input['khach_hang'])) {
                $order_query->where('short_name', $input['khach_hang']);
            }
            if (isset($input['mdh'])) {
                $order_query->where('mdh', 'like', $input['mdh'] . "%");
            }
            if (isset($input['mql'])) {
                $order_query->where('mql', $input['mql']);
            }
            if (isset($input['kich_thuoc'])) {
                $order_query->where('kich_thuoc', $input['kich_thuoc']);
            }
            if (isset($input['length'])) {
                $order_query->where('length', $input['length']);
            }
            if (isset($input['width'])) {
                $order_query->where('width', $input['width']);
            }
            if (isset($input['height'])) {
                $order_query->where('height', $input['height']);
            }
            $orders = $order_query->pluck('id')->toArray();
            if (count($orders) > 0) {
                $query->whereIn('order_id', $orders);
            }
        }
        if (isset($input['sl_ton_min']) || isset($input['sl_ton_max']) || isset($input['so_ngay_ton_min']) || isset($input['so_ngay_ton_max'])) {
            $query->whereHas('lo_sx_pallet', function ($q) use ($input) {
                $q->where('remain_quantity', '>', 0);
                if (isset($input['sl_ton_min'])) {
                    $q->where('remain_quantity', '>=', $input['sl_ton_min']);
                }
                if (isset($input['sl_ton_max'])) {
                    $q->where('remain_quantity', '<=', $input['sl_ton_max']);
                }
            });
            if (isset($input['so_ngay_ton_min'])) {
                $query->whereRaw('DATEDIFF(NOW(), created_at) >= ?', [$input['so_ngay_ton_min']]);
            }
            if (isset($input['so_ngay_ton_max'])) {
                $query->whereRaw('DATEDIFF(NOW(), created_at) <= ?', [$input['so_ngay_ton_max']]);
            }
        }
        return $query;
    }
    public function warehouseFGLog(Request $request)
    {
        $input = $request->all();
        $page = $request->page - 1;
        $pageSize = $request->pageSize;
        $query = $this->customQueryWarehouseFGLog($request);
        $totalPage = $query->count();
        $records = $query->offset(($page - 1) * $pageSize)->limit($pageSize)->get();
        foreach ($records as $key => $record) {
            $export = $record->exportRecord;
            $record->khu_vuc = $record->locator_id ? "Khu " . ((int)substr($record->locator_id, 1, 2) ?? "") : "";
            $record->vi_tri = $record->locator_id;
            $record->pallet_id = $record->pallet_id;
            $record->lo_sx = $record->lo_sx;
            $record->khach_hang = $record->order->short_name ?? "";
            $record->mdh = $record->order->mdh ?? "";
            $record->mql = $record->order->mql ?? "";
            $record->length = $record->order->length ?? "";
            $record->height = $record->order->height ?? "";
            $record->width = $record->order->width ?? "";
            $record->kich_thuoc = $record->order->kich_thuoc ?? "";
            $record->nhap_du = $record->nhap_du < 0 ? abs($record->nhap_du) : "Không";
            $record->tg_nhap = $record->created_at;
            $record->tg_xuat = $export[0]->created_at ?? "";
            $record->sl_nhap = $record->so_luong ?? 0;
            $record->sl_xuat = $export->sum('so_luong') ?? 0;
            $record->nguoi_nhap = $record->user->name ?? "";
            $record->nguoi_xuat = $export[0]->user->name ?? "";
            $record->sl_ton = $record->sl_nhap - $record->sl_xuat;
            $record->so_ngay_ton = $record->sl_ton ? $this->datediff($record->created_at, now()) : "11";
        }
        return $this->success(['data' => $records, 'totalPage' => $totalPage]);
    }

    function datediff($date1, $date2) {
        $d1 = Carbon::parse($date1);
        $d2 = Carbon::parse($date2);
        // Nếu hai thời điểm nằm trong cùng ngày lịch
        if ($d1->isSameDay($d2)) {
            return 0;
        }
        // Nếu khác ngày, trả về số ngày chênh lệch
        return $d1->diffInDays($d2);
    }

    public function exportWarehouseFGLog(Request $request)
    {
        $input = $request->all();
        $query = $this->customQueryWarehouseFGLog($request);
        $records = $query->get();
        $data = [];
        foreach ($records as $key => $record) {
            $export = $record->exportRecord;
            $obj = new stdClass;
            $obj->stt = $key + 1;
            $obj->khu_vuc = $record->locator_id ? "Khu " . ((int)substr($record->locator_id, 1, 2) ?? "") : "";
            $obj->khach_hang = $record->order->short_name ?? "";
            $obj->mdh = $record->order->mdh ?? "";
            $obj->mql = $record->order->mql ?? "";
            $obj->length = $record->order->length ?? "";
            $obj->width = $record->order->width ?? "";
            $obj->height = $record->order->height ?? "";
            $obj->kich_thuoc = $record->order->kich_thuoc ?? "";
            $sl_da_xuat = $export->sum('so_luong') ?? 0;
            $obj->sl_ton = $record->so_luong - $sl_da_xuat;
            $obj->so_ngay_ton = $obj->sl_ton ? $this->datediff(date('Y-m-d H:i:s'), $record->created_at) : "";
            $obj->ngay_nhap = $record->created_at ? date('d/m/Y', strtotime($record->created_at)) : '';
            $obj->gio_nhap = $record->created_at ? date('H:i', strtotime($record->created_at)) : '';
            $obj->sl_nhap = $record->so_luong ?? 0;
            $obj->nhap_du = $record->nhap_du < 0 ? abs($record->nhap_du) : "Không";
            $obj->nguoi_nhap = $record->user->name ?? "";
            $obj->ngay_xuat = isset($export[0]->created_at) ? date('d/m/Y', strtotime($export[0]->created_at)) : '';
            $obj->gio_xuat = isset($export[0]->created_at) ? date('H:i', strtotime($export[0]->created_at)) : '';
            $obj->sl_xuat = $sl_da_xuat;
            $obj->nguoi_xuat = $export[0]->user->name ?? "";
            $obj->vi_tri = $record->locator_id;
            $obj->pallet_id = $record->pallet_id;
            $obj->lo_sx = $record->lo_sx;
            $data[] = (array)$obj;
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
        $header = [
            'STT',
            'Khu vực',
            'Tên KH',
            'Đơn hàng',
            'MQL',
            'L',
            'W',
            'H',
            'Kích thước',
            'Tồn kho' => [
                'SL tồn',
                'Số ngày tồn'
            ],
            'Nhập kho' => [
                'Ngày nhập',
                'Thời gian nhập',
                'SL nhập',
                'Nhập dư',
                'Người nhập'
            ],
            'Xuất kho' => [
                'Ngày xuất',
                'Thời gian xuất',
                'SL xuất',
                'Người xuất'
            ],
            'Vị trí',
            'Mã tem (pallet)',
            'Lô SX',
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
        $start_row_table = $start_row + 2;
        $sheet->setCellValue([1, 1], 'Quản lý kho thành phẩm')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->fromArray($data, NULL, 'A' . ($start_row_table));
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            // $sheet->getStyle($column->getColumnIndex() . ($start_row) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
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
        header('Content-Disposition: attachment;filename="Quản lý kho TP.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Quản lý kho TP.xlsx');
        $href = '/exported_files/Quản lý kho TP.xlsx';
        return $this->success($href);
    }

    public function warehouseFGExportList(Request $request)
    {
        $input = $request->all();
        $page = $request->page - 1;
        $pageSize = $request->pageSize;
        $query = WarehouseFGLog::with('lo_sx_pallet.order')->where('type', 2)->orderBy('created_at');
        if (isset($input['start_date']) && isset($input['end_date'])) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($input['start_date'])))->whereDate('created_at', '<=', date('Y-m-d', strtotime($input['end_date'])));
        }
        if (isset($input['customer_id']) || isset($input['mdh']) || isset($input['mql'])) {
            $query->whereHas('lo_sx_pallet', function ($q) use ($input) {
                if (isset($input['customer_id'])) $q->where('customer_id', 'like', "%" . $input['customer_id'] . "%");
                if (isset($input['mdh'])) $q->where('mdh', 'like', $input['mdh']);
                if (isset($input['mql'])) $q->where('mql', $input['mql']);
            });
        }
        $totalPage = $query->count();
        $records = $query->offset($page * $pageSize)->limit($pageSize)->get();
        foreach ($records as $record) {
            $lo_sx_pallet = $record->lo_sx_pallet;
            $record->mdh = $lo_sx_pallet->mdh ?? "";
            $record->mql = $lo_sx_pallet->mql ?? "";
            $record->customer_id = $lo_sx_pallet->customer_id ?? "";
            $record->xuong_giao = $lo_sx_pallet->order->xuong_giao ?? "";
            $record->ngay_xuat = $record->created_at;
        }
        return $this->success(['data' => $records, 'totalPage' => $totalPage]);
    }

    public function getWarehouseFGExportPlan(Request $request)
    {
        $page = $request->page - 1;
        $pageSize = $request->pageSize;
        $query = WareHouseFGExport::whereNull('delivery_note_id')->whereNotNull('order_id');
        if (isset($request->created_by)) {
            $query->where('created_by', $request->created_by);
        }
        if (isset($request->xuong_giao)) {
            $query->where('xuong_giao', $request->xuong_giao);
        }
        if (isset($request->ngay_xuat)) {
            $query->whereDate('ngay_xuat', date('Y-m-d', strtotime($request->ngay_xuat)));
        }
        $input = $request->all();
        unset($input['created_by'], $input['page'], $input['pageSize'], $input['xuong_giao'], $input['ngay_xuat'], $input['sl_ton_min'], $input['sl_ton_max']);
        $input = array_filter($input);
        $order_test = [];
        if (count($input) > 0) {
            $order_query = Order::withTrashed();
            if (isset($input['customer_id'])) {
                $order_query->where('customer_id', 'like', "%" . $input['customer_id'] . "%");
            }
            if (isset($input['short_name'])) {
                $order_query->where('short_name', 'like', "%" . $input['short_name'] . "%");
            }
            if (isset($input['ngay_dat_hang'])) {
                $order_query->whereDate('ngay_dat_hang', date('Y-m-d', strtotime($input['ngay_dat_hang'])));
            }
            if (isset($input['start_date']) && isset($input['end_date'])) {
                $order_query->whereDate('ngay_dat_hang', '>=', date('Y-m-d', strtotime($input['start_date'])))->whereDate('ngay_dat_hang', '<=', date('Y-m-d', strtotime($input['end_date'])));
            }
            if (isset($input['mdh'])) {
                if (is_array($input['mdh'])) {
                    $order_query->whereIn('mdh', $input['mdh']);
                } else {
                    $order_query->where('mdh', $input['mdh']);
                }
            }
            if (isset($input['order'])) {
                $order_query->where('order', 'like', "%" . $input['order'] . "%");
            }
            if (isset($input['mql'])) {
                if (is_array($input['mql'])) {
                    $order_query->whereIn('mql', $input['mql']);
                } else {
                    $order_query->where('mql', $input['mql']);
                }
            }
            if (isset($input['kich_thuoc'])) {
                $order_query->where('kich_thuoc', 'like', "%" . $input['kich_thuoc'] . "%");
            }
            if (isset($input['length'])) {
                $order_query->where('length', 'like', $input['length']);
            }
            if (isset($input['width'])) {
                $order_query->where('width', 'like', $input['width']);
            }
            if (isset($input['height'])) {
                $order_query->where('height', 'like', $input['height']);
            }
            if (isset($input['po'])) {
                $order_query->where('po', 'like', "%" . $input['po'] . "%");
            }
            if (isset($input['style'])) {
                $order_query->where('style', 'like', "%" . $input['style'] . "%");
            }
            if (isset($input['style_no'])) {
                $order_query->where('style_no', 'like', "%" . $input['style_no'] . "%");
            }
            if (isset($input['color'])) {
                $order_query->where('color', 'like', "%" . $input['color'] . "%");
            }
            if (isset($input['item'])) {
                $order_query->where('item', 'like', "%" . $input['item'] . "%");
            }
            if (isset($input['rm'])) {
                $order_query->where('rm', 'like', "%" . $input['rm'] . "%");
            }
            if (isset($input['size'])) {
                $order_query->where('size', 'like', "%" . $input['size'] . "%");
            }
            if (isset($input['note_2'])) {
                $order_query->where('note_2', 'like', "%" . $input['note_2'] . "%");
            }
            if (isset($input['han_giao'])) {
                $order_query->whereDate('han_giao', date('Y-m-d', strtotime($input['han_giao'])));
            }
            if (isset($input['dot'])) {
                $order_query->where('dot', $input['dot']);
            }
            if (isset($input['tmo'])) {
                $order_query->where('tmo', $input['tmo']);
            }
            $orders = $order_query->pluck('id')->toArray();
            if (count($orders)) $query->whereIn('order_id', $orders);
        }
        $query->whereHas('lsxpallets', function ($q) use ($request) {
            $q->selectRaw('SUM(remain_quantity) as sl_ton');
            $q->having('sl_ton', '>', 0);
            if (isset($request->sl_ton_min)) {
                $q->having('sl_ton', '>=', $request->sl_ton_min);
            }
            if (isset($request->sl_ton_max)) {
                $q->having('sl_ton', '<=', $request->sl_ton_max);
            }
        });
        $count = $query->count();
        $totalPage = $count;
        if (isset($request->page) && isset($request->pageSize)) {
            $page = $request->page - 1;
            $pageSize = $request->pageSize;
            $query->offset($page * $pageSize)->limit($pageSize);
        }
        $records = $query->with(['lsxpallets', 'order'])->get();
        $data = [];
        foreach ($records as $record) {
            $record->so_luong_xuat = $record->so_luong;
            $record->sl_ton = (int)$record->lsxpallets->sum('remain_quantity');
            if ($record->order) {
                $data[] = array_merge($record->order->toArray(), $record->toArray());
            } else {
                $data[] = $record->toArray();
            }
        }
        $res = [
            "data" => array_values($data),
            "totalPage" => $totalPage,
            'order' => $order_test,
        ];
        return $this->success($res);
    }

    public function exportWarehouseFGDeliveryNote(Request $request)
    {
        $params = [];
        $delivery_note = DeliveryNote::find($request->delivery_note_id);
        if (!$delivery_note) {
            return $this->failure('', 'Không tìm thấy phiếu giao hàng');
        }
        $query = WarehouseFGLog::where('type', 2)
            ->where('delivery_note_id', $delivery_note->id)
            ->with('order', 'warehouse_fg_export')
            ->orderBy('created_at');
        $records = $query->get()->sortBy('order_id', SORT_NATURAL);
        if (count($records) <= 0) {
            return $this->failure('', 'Không có bản ghi nào');
        }
        // return $records;
        $group_records = [];
        foreach ($records as $index => $record) {
            $order = $record->order ?? null;
            $data_index = ($order->mdh ?? "") . ($order->mql ?? "");
            if (!isset($so_luong[$data_index])) $so_luong[$data_index] = 0;
            $so_luong[$data_index] += $record->so_luong;
            $record->so_luong = $so_luong[$data_index];
            $group_records[$data_index] = $record;
        }
        $group_records = array_values($group_records);
        $params['khach_hang'] = $records[0]->order->customer->name ?? "";
        $mau = Customer::FORM_LIST[$records[0]->order->short_name ?? ""] ?? "";
        if (!$mau) {
            $mau = 'mau_1';
        }
        $warehouse_fg_export = array_column($records->toArray(), 'warehouse_fg_export');
        $orders = array_column($records->toArray(), 'order');
        $xuong_giao = array_column($warehouse_fg_export, 'xuong_giao');
        $xuat_tai_kho = array_column($orders, 'xuat_tai_kho');

        $writer = new \Kaxiluo\PhpExcelTemplate\PhpExcelTemplate;
        $writer->save('templates/' . $mau . '.xlsx', 'exported_files/Phiếu giao hàng.xlsx', $params);
        $params['so_xe'] = $delivery_note->vehicle_id;
        $params['so_phieu'] = $delivery_note->id;
        $params['now'] = date('d/m/Y H:i:s');
        $params['xuat_tai_kho'] = array_values(array_filter($xuat_tai_kho))[0] ?? 'CTY CP THÁI BÌNH DƯƠNG XANH';
        $params['xuong_giao'] = array_values(array_filter($xuong_giao))[0] ?? "";
        // return $params;
        $writer = new \Kaxiluo\PhpExcelTemplate\PhpExcelTemplate;
        $writer->save('templates/' . $mau . '.xlsx', 'templates/ExportTemplate.xlsx', $params);
        $spreadsheet = IOFactory::load('templates/ExportTemplate.xlsx');
        $worksheet = $spreadsheet->getActiveSheet()->setTitle('Template');
        $worksheet->getPageMargins()->setTop(0.75)->setBottom(0.75)->setLeft(0.25)->setRight(0.25)->setHeader(0.3)->setFooter(0.3);
        $sheet_data = [];
        $number_of_rows = 30;
        switch ($mau) {
            case 'mau_3':
                //ngang
                $number_of_rows = 9;
                $sheet_data = array_chunk($group_records, $number_of_rows);
                $worksheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                break;
            case 'mau_2':
                //ngang
                $number_of_rows = 20;
                $sheet_data = array_chunk($group_records, $number_of_rows);
                $worksheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                break;
            default:
                //doc
                $number_of_rows = 30;
                $sheet_data = array_chunk($group_records, $number_of_rows);
                $worksheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
                break;
        }
        $sheet_index = 0;
        foreach ($sheet_data as $data) {
            $sheet_index++;
            $clonedWorksheet = clone $spreadsheet->getSheetByName('Template');
            $clonedWorksheet->setTitle('Sheet ' . $sheet_index);
            $spreadsheet->addSheet($clonedWorksheet);
            $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            $row_index = 0;
            $base_keys = [];
            foreach ($allDataInSheet as $index => $value) {
                if (isset($value['A']) && $value['A'] === "[stt]") {
                    $row_index = $index;
                    $base_keys = $value;
                    break;
                }
            }
            if (!$row_index) {
                return $this->failure('Form xuất không đúng định dạng');
            }
            $clonedWorksheet->insertNewRowBefore($row_index + 1, $number_of_rows);
            $array = [];
            $sum_sl = 0;
            foreach ($data as $index => $record) {
                $sum_sl += $record['so_luong'] ?? 0;
                $order = $record['order'];
                $obj = [];
                foreach ($base_keys as $col_key => $key) {
                    switch ($key) {
                        case '[stt]':
                            $obj[$col_key] = $index + 1;
                            break;
                        case '[sl]':
                            $obj[$col_key] = $record['so_luong'];
                            break;
                        default:
                            $obj[$col_key] = $order[str_replace(array('[', ']'), '', $key)] ?? "";
                            break;
                    }
                }
                $array[] = $obj;
            }
            // return $data;
            $clonedWorksheet->fromArray($array, "-", 'A' . $row_index);
            $total_row_key = $row_index + $number_of_rows;
            $total_col_key = Coordinate::columnIndexFromString(array_search('[sl]', $base_keys));
            $clonedWorksheet->setCellValue([$total_col_key - 3, $total_row_key], 'TOTAL')->mergeCells([$total_col_key - 3, $total_row_key, $total_col_key - 1, $total_row_key]);
            $clonedWorksheet->setCellValue([$total_col_key, $total_row_key], $sum_sl);
        }
        $spreadsheet->removeSheetByIndex(0);
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Phiếu giao hàng.xlsx');
        $href = '/exported_files/Phiếu giao hàng.xlsx';
        return $this->success($href);
    }

    public function divideFGExportPlan(Request $request)
    {
        $fg_export_plan = WareHouseFGExport::where('id', $request->id)->first();
        if (!$fg_export_plan) {
            return $this->failure('', 'Không tìm thấy bản ghi');
        }
        $so_luong_tach = array_filter($request->so_luong_tach);
        $sum = array_sum($so_luong_tach);
        if ($sum > $fg_export_plan->so_luong) {
            return $this->failure('', 'Số lượng tách lớn hơn số lượng cần xuất của kế hoạch');
        }
        try {
            DB::beginTransaction();
            foreach ($so_luong_tach as $new_so_luong) {
                $input = $fg_export_plan->toArray();
                $input['so_luong'] = $new_so_luong;
                unset($input['id']);
                WareHouseFGExport::create($input);
            }
            $fg_export_plan->update(['so_luong' => $fg_export_plan->so_luong - $sum]);
            WareHouseFGExport::where('so_luong', 0)->delete();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
        return $this->success($fg_export_plan, 'Đã tách thành công');
    }

    public function updateGoodsReceiptNote(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $note = GoodsReceiptNote::where('id', $input['id'])->first();
            $note->update($input);
            DB::commit();
            return $this->success($note, 'Cập nhật thành công');
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('Cập nhật không thành công');
        }
    }

    public function deleteGoodsReceiptNote(Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $note = GoodsReceiptNote::where('id', $input['id'])->withCount(['warehouse_mlt_import' => function ($q) {
                $q->whereNotNull('material_id');
            }])->having('warehouse_mlt_import_count', '<=', 0)->first();
            if ($note) {
                $note->warehouse_mlt_import()->delete();
                $note->delete();
                DB::commit();
                return $this->success('', 'Xoá thành công');
            } else {
                return $this->failure('', 'Không thể xoá');
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->failure($th, 'Xoá không thành công');
        }
    }
    public function getDeliveryNoteList(Request $request)
    {
        $query = DeliveryNote::orderBy('created_at', 'DESC');
        if (isset($request->start_date) && isset($request->end_date)) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->start_date)))->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->end_date)));
        }
        if ($request->user()->username !== 'admin') {
            $deliveryNotes = DB::table('admin_user_delivery_note')
                ->where('admin_user_id', $request->user()->id) // Thay 1 bằng ID của note bạn muốn tìm
                ->pluck('delivery_note_id');
            $query->whereIn('id', $deliveryNotes);
        }
        $records = $query->get();
        return $this->success($records);
    }

    public function exportBuyers(Request $request)
    {
        $query = Buyer::orderBy('created_at', 'DESC');
        if ($request->customer_id) {
            $query = $query->where('customer_id', 'like', '%' . $request->customer_id . '%');
        }
        if ($request->customer_name) {
            $customer_ids = Customer::where('name', 'like', '%' . $request->customer_name . '%')->pluck('id')->toArray();
            $query = $query->whereIn('customer_id', $customer_ids);
        }
        if ($request->so_lop) {
            $query = $query->where('so_lop', $request->so_lop);
        }
        if ($request->phan_loai_1) {
            $query = $query->where('phan_loai_1', $request->phan_loai_1);
        }
        if ($request->id) {
            $query = $query->where('id', 'like', '%' . $request->id . '%');
        }
        $records = $query->get()->map(function ($record, $index) {
            return [
                $index + 1,
                $record->id,
                $record->customer_id,
                $record->buyer_vt,
                $record->phan_loai_1,
                $record->so_lop,
                $record->ma_cuon_f,
                $record->ma_cuon_se,
                $record->ma_cuon_le,
                $record->ma_cuon_sb,
                $record->ma_cuon_lb,
                $record->ma_cuon_sc,
                $record->ma_cuon_lc,
                $record->ket_cau_giay,
                $record->ghi_chu
            ];
        })->toArray();
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
        $header = [
            'STT',
            'Mã buyer',
            'Mã khách hàng',
            'Buyer viết tắt',
            'Phân loại 1',
            'Số lớp',
            'Mặt',
            'Sóng E sóng',
            'Sóng E láng',
            "Sóng B sóng",
            'Sóng B láng',
            'Sóng C sóng',
            'Sóng C đáy',
            'Kết cấu chạy giấy',
            'Ghi chú'
        ];
        $table_key = [
            'A' => 'stt',
            'B' => 'buyer_id',
            'C' => 'customer_id',
            'D' => 'buyer_vt',
            'E' => 'phan_loai_1',
            'F' => 'so_lop',
            'G' => 'ma_cuon_f',
            'H' => 'ma_cuon_se',
            'I' => 'ma_cuon_le',
            'J' => 'ma_cuon_sb',
            'K' => 'ma_cuon_lb',
            'L' => 'ma_cuon_sc',
            'M' => 'ma_cuon_lc',
            'N' => 'ket_cau_giay',
            'O' => 'ghi_chu',
        ];
        foreach ($header as $key => $cell) {
            $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            $start_col += 1;
        }
        $sheet->setCellValue([1, 1], 'Danh sách Buyer')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row + 1;
        $sheet->fromArray($records, null, 'A3');
        $sheet->getStyle([1, $table_row, $start_col - 1, count($records) + $table_row - 1])->applyFromArray(
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
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            $sheet->getStyle($column->getColumnIndex() . ($start_row) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Danh sách buyer.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Danh sách buyer.xlsx');
        $href = '/exported_files/Danh sách buyer.xlsx';
        return $this->success($href);
    }
}
