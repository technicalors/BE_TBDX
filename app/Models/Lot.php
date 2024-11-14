<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Lot extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $table = 'lot';
    protected $fillable = ['type', 'lo_sx', 'so_luong', 'p_id'];
    protected $casts = [
        "id" => "string"
    ];

    static public function findById($id)
    {
        return Lot::where('id', 'like', $id)->first();
    }

    public function parrent()
    {
        return $this->belongsTo(Lot::class, "p_id", "id");
    }

    public function children()
    {
        return $this->hasMany(Lot::class, "p_id", "id");
    }

    public function pallet()
    {
        return $this->belongsTo(Pallet::class);
    }


    public function getPlanByLine($line_id)
    {
        $line = Line::find($line_id);
        if (!isset($line)) return null;
        $line_key = Str::slug($line->name);
        foreach ($this->plans as $item) {
            if (Str::slug($item->cong_doan_sx) == $line_key) return $item;
        }
        return null;
    }

    public function plans()
    {
        return $this->hasMany(ProductionPlan::class, "lo_sx", "lo_sx");
    }

    public function plan()
    {
        return $this->hasOne(ProductionPlan::class, "lo_sx", "lo_sx");
    }

    public function log()
    {
        return $this->hasOne(LSXLog::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function infoCongDoan()
    {
        return $this->hasMany(InfoCongDoan::class);
    }

    public function getStatusProcess($log, $product)
    {
        /*
        1: Mới đưa vào bảo ôn
        2: đủ điều kiện nhưng chưa xuất kho
        3; đã xuất kho bảo ôn.
        */
        if (isset($log["in"]['thoi_gian_vao'])) {
            return 3;
        }
        $startTime = isset($log['kho-bao-on']) ? $log['kho-bao-on']['thoi_gian_vao'] : '';
        $startTime = new Carbon($startTime);
        $now = Carbon::now();
        $cnt = $now->diffInHours($startTime);
        $t = filter_var($product->thoi_gian_bao_on, FILTER_SANITIZE_NUMBER_INT);
        if ($cnt < $t) return 2;
        return 1;
    }


    public function thongTinBaoOn()
    {
        $plan = $this->plan;
        $product = $this->product;
        $log = $this->log->info;

        $lots = $product->lots;
        $daxuat = 0;
        $tong_so_luong = 0;
        $so_luong_con_lai = 0;
        foreach ($lots as $item) {
            $lg = $item->log;
            if (isset($lg->info['kho-bao-on']['thoi_gian_ra'])) {
                $daxuat += $item->so_luong;
            }
            if (isset($lg->info['kho-bao-on']['thoi_gian_vao'])) {
                $tong_so_luong += $item->so_luong;
            }
        }
        $so_luong_con_lai = $tong_so_luong - $daxuat;

        // dd($log);
        $insulation = Insulation::find(1);
        $data =   [
            "lo_sx" => $this->lo_sx,
            "lot_id" => $this->id,
            "ma_hang" => $product->id,
            "ten_sp" => $product->name,
            "dinh_muc" => $this->so_luong,
            "sl_ke_hoach" => $plan->sl_nvl ?? 0,
            "thoi_gian_bat_dau" => $log['kho-bao-on']['thoi_gian_vao'] ?? "",
            "thoi_gian_bao_on" => "",
            "thoi_gian_bao_on_tieu_chuan" => $product->thoi_gian_bao_on === '-' ? 0 : filter_var($product->thoi_gian_bao_on, FILTER_SANITIZE_NUMBER_INT),
            "do_am_phong" => (isset($log['kho-bao-on']["thoi_gian_xuat_kho"]) && isset($log['kho-bao-on']['input']["e_hum"])) ? $log['kho-bao-on']['input']["e_hum"] : $insulation->e_hum,
            "nhiet_do_phong" => (isset($log['kho-bao-on']["thoi_gian_xuat_kho"]) && isset($log['kho-bao-on']['input']["t_ev"])) ? $log['kho-bao-on']['input']["t_ev"] : $insulation->t_ev,
            "do_am_phong_tieu_chuan" => $product->do_am_phong,
            "do_am_giay" => $log['kho-bao-on']['input']['do_am_giay'] ?? "",
            "do_am_giay_tieu_chuan" => $product->do_am_giay,
            "thoi_gian_xuat_kho_bao_on" => $log['kho-bao-on']["thoi_gian_xuat_kho"] ?? "",
            "sl_da_xuat" => $daxuat,
            "sl_con_lai" => $so_luong_con_lai,
            "uph_an_dinh" => $plan->UPH ?? 0,
            "uph_thuc_te" => "",
            "status" => $this->getStatusProcess($log, $product),

        ];
        try {
            $min = (int)filter_var(explode("~", $product->do_am_giay)[0], FILTER_SANITIZE_NUMBER_INT);
            $max = (int) filter_var(explode("~", $product->do_am_giay)[1], FILTER_SANITIZE_NUMBER_INT);
            $data['do_am_giay_max'] = $max;
            $data['do_am_giay_min'] = $min;
        } catch (Exception $ex) {
        }
        return $data;
    }


    public function thongTinU()
    {
        $plan = $this->plan;
        $product = $this->product;
        $log = $this->log->info;

        $lots = $product->lots;
        $daxuat = 0;
        $tong_so_luong = 0;
        $so_luong_con_lai = 0;
        foreach ($lots as $item) {
            $lg = $item->log;
            if (isset($lg->info['u']['thoi_gian_ra'])) {
                $daxuat += $item->so_luong;
            }
            if (isset($lg->info['u']['thoi_gian_vao'])) {
                $tong_so_luong += $item->so_luong;
            }
        }
        $so_luong_con_lai = $tong_so_luong - $daxuat;

        // dd($log);
        $insulation = Insulation::find(1);
        $data =   [
            "lo_sx" => $this->lo_sx,
            "lot_id" => $this->id,
            "ma_hang" => $product->id,
            "ten_sp" => $product->name,
            "dinh_muc" => $this->so_luong,
            "sl_ke_hoach" => $plan->sl_nvl ?? 0,
            "thoi_gian_bat_dau" => $log['u']['thoi_gian_vao'] ?? "",
            "thoi_gian_u" => "",
            "thoi_gian_u_tieu_chuan" => $product->u_thoi_gian_u === '-' ? 0 : filter_var($product->u_thoi_gian_u, FILTER_SANITIZE_NUMBER_INT),
            "do_am_phong" => (isset($log['u']["thoi_gian_xuat_kho"]) && isset($log['u']['input']["e_hum"])) ? $log['u']['input']["e_hum"] : $insulation->e_hum,
            "nhiet_do_phong" => (isset($log['u']["thoi_gian_xuat_kho"]) && isset($log['u']['input']["t_ev"])) ? $log['u']['input']["t_ev"] : $insulation->t_ev,
            "do_am_phong_tieu_chuan" => $product->do_am_phong,
            "do_am_giay" => $log['u']['input']['do_am_giay'] ?? "",
            "do_am_giay_tieu_chuan" => $product->u_do_am_giay,
            "thoi_gian_xuat_kho_u" => $log['u']["thoi_gian_xuat_kho"] ?? "",
            "sl_da_xuat" => $daxuat,
            "sl_con_lai" => $so_luong_con_lai ,
            "uph_an_dinh" => $plan->UPH ?? 0,
            "uph_thuc_te" => "",
            "status" => $this->getStatusProcess($log, $product),

        ];
        try {
            $min = (int)filter_var(explode("~", $product->do_am_giay)[0], FILTER_SANITIZE_NUMBER_INT);
            $max = (int) filter_var(explode("~", $product->do_am_giay)[1], FILTER_SANITIZE_NUMBER_INT);
            $data['do_am_giay_max'] = $max;
            $data['do_am_giay_min'] = $min;
        } catch (Exception $ex) {
        }
        return $data;
    }



    public function thongTinQC($line)
    {
        $plan = $this->plan;
        $product = $this->product;
        $san_luong = $this->infoCongDoan()->where('type', 'sx')->where('line_id', $line->id)->first();
        if(!$san_luong){
            return [];
        }
        // return $this;
        $line_key = Str::slug($line->name);
        // $sl_ng = 0;
        // $errors = array_map(function ($e) {
        //     return array_values($e)[0];
        // }, ($this->log->info['qc'][$line_key]['errors'] ?? []));
        // foreach ($errors as $val) {
        //     $sl_ng += (int)$val;
        // }
        // if ($san_luong) {
        //     $san_luong['sl_ng'] = $sl_ng;
        //     $san_luong->save();
        // }
        // return $this->log->info['qc'];
        if ($line->id == 10 || $line->id == 11 || $line->id == 12 || $line->id == 14 || $line->id == 22) {
            return [
                "ngay_sx"=>date('d/m/Y',strtotime($san_luong->thoi_gian_bat_dau)) ?? "",
                "lot_id" => $this->id,
                "ma_hang" => $product->id,
                "ten_sp" => $product->name,
                "lo_sx" => $this->lo_sx,
                'luong_sx' => ($san_luong && $plan->so_bat) ? (($san_luong->sl_dau_ra_hang_loat + $san_luong->sl_dau_ra_chay_thu) / $plan->so_bat) : 0,
                'sl_ok' => ($san_luong && $plan->so_bat) ? (($san_luong->sl_dau_ra_hang_loat / $plan->so_bat) - ($san_luong->sl_tem_vang ? $san_luong->sl_tem_vang / $plan->so_bat : 0) - ($san_luong->sl_ng ? $san_luong->sl_ng / $plan->so_bat : 0)) : 0,
                'sl_tem_vang' => ($san_luong && $plan->so_bat) ? $san_luong->sl_tem_vang / $plan->so_bat : 0,
                'sl_ng' => ($san_luong && $plan->so_bat) ? $san_luong->sl_ng / $plan->so_bat : 0,
                'sl_dau_ra' => ($san_luong && $plan->so_bat)? $san_luong->sl_dau_ra_hang_loat / $plan->so_bat : 0,
                'ver' => $product->ver,
                'his' => $product->his,
            ];
        } elseif($line->id == 20) { //OQC
            return [
                "ngay_sx"=>date('d/m/Y',strtotime($san_luong->thoi_gian_bat_dau)) ?? "",
                "lot_id" => $this->id,
                "ma_hang" => $product->id,
                "ten_sp" => $product->name,
                "lo_sx" => $this->lo_sx,
                'luong_sx' => $san_luong ? $san_luong->sl_dau_ra_hang_loat : 0,
                'sl_ok' =>  ($san_luong && $san_luong->sl_tem_vang == 0 && $san_luong->sl_ng == 0 ) ? ($san_luong->sl_dau_ra_hang_loat - $san_luong->sl_tem_vang - $san_luong->sl_ng ) : 0,
                'sl_tem_vang' => $san_luong ? $san_luong->sl_tem_vang : 0,
                'sl_ng' => $san_luong ? $san_luong->sl_ng : 0,
                'sl_dau_ra' => $san_luong ? $san_luong->sl_dau_ra_hang_loat : 0,
                'ver' => $product->ver,
                'his' => $product->his,
            ];
        } else {
            return [
                "ngay_sx"=>date('d/m/Y',strtotime($san_luong->thoi_gian_bat_dau)) ?? "",
                "lot_id" => $this->id,
                "ma_hang" => $product->id,
                "ten_sp" => $product->name,
                "lo_sx" => $this->lo_sx,
                'luong_sx' => $san_luong ? ($san_luong->sl_dau_ra_hang_loat + $san_luong->sl_dau_ra_chay_thu) : 0,
                'sl_ok' => $san_luong ? ($san_luong->sl_dau_ra_hang_loat - ($this->log->info['qc'][$line_key]['sl_tem_vang'] ?? 0) - ($this->log->info['qc'][$line_key]['sl_ng'] ?? 0)) : 0,
                'sl_tem_vang' => $san_luong ? $san_luong->sl_tem_vang : 0,
                'sl_ng' => $san_luong ? $san_luong->sl_ng : 0,
                'sl_dau_ra' => $san_luong ? $san_luong->sl_dau_ra_hang_loat : 0,
                'ver' => $product->ver,
                'his' => $product->his,
            ];
        }
    }

    public function thongTin($line = "in")
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

        $plan = ProductionPlan::where('cong_doan_sx',$line)->where('lo_sx',$this->lo_sx)->first();
        if(!$plan){
            $plan = $this->plan;
        }
        $product = $this->product;
        $log = $this->log->info;


        $info_cong_doan = $this->infoCongDoan()->where('line_id', $linex[$line])->where('type', 'sx')->first();

        $sl_dau_ra = 0;

        foreach ($this->infoCongDoan()->where("line_id", $linex[$line])->get() as $item) {
            if ($line == 'in' || $line == 'phu' || $line == 'be' || $line == 'in-luoi' || $line == 'boc') {
                $sl_dau_ra += $plan ? $item->sl_dau_ra_hang_loat / $plan->so_bat : 0;
            } else {
                $sl_dau_ra += $item->sl_dau_ra_hang_loat;
            }
        }

        $data =  [
            "lo_sx" => $this->lo_sx,
            "lot_id" => $this->id,
            "ma_hang" => $product->id,
            "ten_sp" => $product->name,
            "dinh_muc" => $product->dinh_muc,
            "sl_ke_hoach" => $plan->sl_nvl ?? 0,
            'thoi_gian_bat_dau_kh' => $plan->thoi_gian_bat_dau ?? "",
            'thoi_gian_bat_dau' => $log[$line]['thoi_gian_vao'] ? date('Y-m-d H:i:s',strtotime($log[$line]['thoi_gian_vao'])) : "",
            "thoi_gian_ket_thuc_kh" => $plan->thoi_gian_ket_thuc ?? "",
            'thoi_gian_ket_thuc' => $log[$line]['thoi_gian_ra'] ?? "",
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
            "status" => (int)isset($log[$line]['thoi_gian_ra']),
            "nguoi_sx" => $log[$line]['user_name'] ?? "",
            "thoi_gian_bam_may"=>$info_cong_doan->thoi_gian_bam_may,
        ];
        $sl_tv = 0;
        $sl_ng = 0;
        if (isset($info_cong_doan)) {
            if ($line == 'in' || $line == 'phu' || $line == 'be' || $line == 'in-luoi' || $line == 'boc' ) {
                $data['sl_dau_vao'] = $plan ? $info_cong_doan->sl_dau_vao_hang_loat / $plan->so_bat : 0;
                $data['sl_dau_ra'] = $plan ? $info_cong_doan->sl_dau_ra_hang_loat / $plan->so_bat : 0;
                $data['sl_tem_vang'] = $plan ? $info_cong_doan->sl_tem_vang / $plan->so_bat : 0;
                $data['sl_tem_ng'] = $plan ? $info_cong_doan->sl_ng / $plan->so_bat : 0;
            } else {
                $data['sl_dau_vao'] = $info_cong_doan->sl_dau_vao_hang_loat;
                $data['sl_dau_ra'] = $info_cong_doan->sl_dau_ra_hang_loat;
                $data['sl_tem_vang'] = $info_cong_doan->sl_tem_vang;
                $data['sl_tem_ng'] = $info_cong_doan->sl_ng;
            }
            $sl_tv = $data['sl_tem_vang'];
            $sl_ng = $data['sl_tem_ng'];
            $data['sl_dau_ra_ok'] = $data['sl_dau_ra'] - $data['sl_tem_vang'] - $data['sl_tem_ng'];
        }
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
                $upm = $plan ? (int)($info_cong_doan->sl_dau_ra_hang_loat / ($d_time * $plan->so_bat)) : 0;
            } else {
                $upm = (int)($info_cong_doan->sl_dau_ra_hang_loat / $d_time);
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

        return $data;
    }
}
