<?php

use App\Models\Cell;
use App\Models\CustomUser;
use App\Models\Error;
use App\Models\InfoCongDoan;
use App\Models\IOTLog;
use App\Models\Line;
use App\Models\Lot;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\ProductionPlan;
use App\Models\Sheft;
use App\Models\TestCriteria;
use App\Models\WareHouse;
use Carbon\Carbon;
use Encore\Admin\Auth\Database\Permission;
use Encore\Admin\Auth\Database\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

use App\Models\MachineParameterLogs;
use App\Models\Shift as ModelsShift;
use App\Models\ThongSoMay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::get('/preview-log', function () {
  $logs = IOTLog::orderBy('created_at', 'DESC')->get();
  return view('preview', compact('logs'));
});

Route::get('/preview', function (Request $request) {
  // return view('preview', compact('logs')); 
  $machine = Machine::with('status')->where('code', $request->machine_id)->first();
  if ($machine && ($machine->status->status === 1 || $machine->status->status === 0)) {
    $line = $machine->line;
    $info_cd = InfoCongDoan::where('line_id', $machine->line_id)->whereDate('created_at', date('Y-m-d', $request->timestamp))->first();
    // dd($machine->);
    $lot = Lot::find($info_cd->lot_id);
    $thong_so_may = new ThongSoMay();
    $date = date('H', $request->timestamp);
    $thong_so_may['ngay_sx'] = date('Y-m-d H:m:i');
    $thong_so_may['ca_sx'] = ($date >= 7 && $date <= 17) ? 1 : (($date >= 19 && $date <= 6) ? 2 : null);
    $thong_so_may['xuong'] = '';
    $thong_so_may['line_id'] = $line->id;
    $thong_so_may['lot_id'] = $lot->id;
    $thong_so_may['lo_sx'] = $lot->lo_sx;
    $thong_so_may['machine_code'] = $machine->code;
    $thong_so_may['params'] = date('Y-m-d H:i:s');
    dd($thong_so_may);
  }
});

Route::get('/', function () {
  return redirect('/admin');
});


// function readFilex($activeIndex = 0, $file_name = "msdata.xlsx")
// {
//   $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

//   $spreadsheet = $reader->load("../document/" . $file_name);
//   $spreadsheet->setActiveSheetIndex($activeIndex);
//   $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
//   return $allDataInSheet;
// }
// function initUser()
// {
//   $dataSheet = readFilex();

//   $roles = [];
//   $pers = [];

//   for ($i = 6; $i <= count($dataSheet); $i++) {
//     $row = $dataSheet[$i];
//     $phongban = $row['B'];
//     $to = $row['C'];
//     $mnv = $row['D'];
//     $ten = $row['E'];



//     if (!isset($pers[$to])) {
//       $p = new Permission();
//       $p->name = $to;
//       $p->slug = Str::slug($to);
//       $p->save();
//       $pers[$to] = $p;
//     } else {
//       $p = $pers[$to];
//     }

//     if (!isset($roles[$phongban])) {
//       $r = new Role();
//       $r->name = $phongban;
//       $r->slug = Str::slug($phongban);
//       $r->save();
//       $roles[$phongban] = $r;
//     } else {
//       $r = $roles[$phongban];
//     }


//     $arr = explode('-', Str::slug($ten));
//     $user = new CustomUser();
//     $user->username = $arr[0][0] . $arr[1][0] . $arr[2];
//     $user->password = Hash::make("12345678");

//     $user->name = $ten;
//     $user->mnv = $mnv;
//     $user->save();
//     $user->permissions()->attach([$p->id]);
//     $user->roles()->attach([$r->id]);
//     $user->save();
//   }
// }

// function initError()
// {
//   $dataSheet = readFilex(4);
//   $pairs = [
//     "BO" => "Kho bảo ôn",
//     "IN" => "In",
//     "PH" => "Phủ",
//     "BE" => "Bế",
//     "GD" => "Gấp dán",
//     "" => "Xước",
//   ];

//   $mark = [];

//   for ($i = 2; $i <= count($dataSheet); $i++) {


//     try {
//       $row = $dataSheet[$i];

//       $ma_loi = $row['B'];
//       $noi_dung = $row['C'];
//       $nguyen_nhan = $row['D'];
//       $khac_phuc = $row['E'];
//       $phong_ngua = $row['F'];

//       if (!isset($ma_loi)) continue;
//       $tm = $ma_loi[0] . $ma_loi[1];

//       if (!isset($mark[$tm])) {
//         $line = new Line();
//         $line->name = $pairs[$tm];
//         $line->save();
//         $mark[$tm] = $line;
//       } else {
//         $line = $mark[$tm];
//       }
//       $err = new Error();

//       $err->name = "";
//       $err->noi_dung = $noi_dung ?? "";
//       $err->line_id = $line->id;
//       $err->nguyen_nhan = $nguyen_nhan ?? "";
//       $err->khac_phuc = $khac_phuc ?? "";
//       $err->phong_ngua = $phong_ngua ?? "";

//       $err->id = $ma_loi;
//       $err->save();
//     } catch (Exception $ex) {
//     }
//   }
// }


// function initMachine()
// {
//   $dataSheet = readFilex(3);

//   for ($i = 5; $i < count($dataSheet); $i++) {
//     $row = $dataSheet[$i];
//     $machine = new Machine();
//     $machine->line_id = -1;
//     $machine->name = $row['B'];
//     $machine->code = $row['C'];
//     $machine->ma_so = $row['D'];
//     $machine->cong_suat = $row['E'];
//     $machine->hang_sx = $row['F'];
//     $machine->nam_sd = $row['G'];
//     $machine->don_vi_sd = $row['H'];
//     $machine->tinh_trang = $row['I'];
//     $machine->vi_tri = $row['J'];
//     $machine->save();
//   }
// }


// function initWareHouse()
// {
//   $dataSheet = readFilex(3);
// }

// function initTest()
// { // chi tieu kiem tra

//   $arr = ["Kích thước", "Ngoại quan", "Đặc tính"];
//   $lines  = [16, 10, 11, 12, 13, 15, 20];

//   TestCriteria::truncate();
//   for ($k = 0; $k <= 6; $k++) {
//     $dataSheet = readFilex($k, "chi_tieu_kiem_tra.xlsx");
//     for ($i = 5; $i <= count($dataSheet); $i++) {
//       $row = $dataSheet[$i];
//       $test1 = new TestCriteria();
//       $test1->hang_muc = $row['A'] ?? " ";
//       $test1->tieu_chuan = $row['B'] ?? " ";
//       $test1->chi_tieu = 'Kích thước';
//       $test1->line_id = $lines[$k];
//       $test1->save();


//       $test2 = new TestCriteria();
//       $test2->hang_muc = $row['E'] ?? " ";
//       $test2->tieu_chuan = $row['F'] ?? " ";
//       $test2->chi_tieu = 'Ngoại quan';
//       $test2->line_id = $lines[$k];
//       $test2->save();

//       $test2 = new TestCriteria();
//       $test2->hang_muc = $row['I'] ?? " ";
//       $test2->tieu_chuan = $row['J'] ?? " ";
//       $test2->chi_tieu = 'Đặc tính';
//       if ($lines[$k] == 15 || $lines[$k] == 20) {
//         $test2->chi_tieu = 'Ngoại quan';
//       }

//       $test2->line_id = $lines[$k];
//       $test2->save();
//     }
//   }
// }

Route::get('/test', function () {



  // $machine_id = "SH";
  // $res = MachineLog::where("machine_id", $machine_id)->orderBy("created_at", "desc")->get()->first();
  // return $res;
  // $lot = Lot::find("8/1/23.TCR514.pl1");


  // return $lot->plans;


  // initUser();
  // initError();
  // initMachine();
  initTest();


  /*  
      [{
        "manvl":"xxxx",
        "soluong":200,

        }]

        lsx.pl01
        */

  // $input = $request->all();

  // $input = [
  //   [
  //     "manvl" => "GIAY0933",
  //     "soluong" => 2000,
  //   ],
  //   [
  //     "manvl" => "GIAY0936",
  //     "soluong" => 1000,
  //   ]
  // ];
  // $arr_nvl = [];
  // foreach ($input as $item) {
  //   $arr_nvl[$item['manvl']] = $item['soluong'];
  // }

  // $plans = ProductionPlan::whereColumn("nvl_da_cap", "<>", "sl_nvl")->with('product.material')->get();

  // $res = [];
  // foreach ($plans as $plan) {
  //   $ma_nvl = $plan->product->material->id;
  //   $need = $plan->sl_nvl - $plan->nvl_da_cap;
  //   $quota  = $plan->product->dinh_muc;
  //   $total_pallet = Lot::where("lsx", $plan->lo_sx)->count();

  //   if ($need > $arr_nvl[$ma_nvl]) {
  //     $need = $arr_nvl[$ma_nvl];
  //   }
  //   $num_pallet = (int)($need / $quota) + ($need % $quota > 0);



  //   for ($i = 1; $i <= $num_pallet; $i++) {
  //     $pallet = new Lot();
  //     $pallet->lsx = $plan->lo_sx;
  //     $pallet->so_luong = $quota;
  //     // if ($num_pallet == 1) $pallet->so_luong = $need;


  //     if ($num_pallet === $i && ($need % $quota) > 0) {
  //       $pallet->so_luong = $need % $quota;
  //       // dd($need, $ma_nvl);
  //     }
  //     $pallet->id = $ma_nvl . $pallet->lsx . "-pl" . ($total_pallet + $i);
  //     $res[] = $pallet;
  //     $pallet->save();
  //   }

  //   $plan->nvl_da_cap  = $plan->nvl_da_cap + $need;
  //   $plan->save();
  // }
  // return $res;
});
