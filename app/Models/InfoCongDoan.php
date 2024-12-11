<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class InfoCongDoan extends Model
{
    use HasFactory;
    use \Awobaz\Compoships\Compoships;

    protected $table = "info_cong_doan";


    protected $fillable = ['id', 'lot_id', 'machine_id', 'thoi_gian_bat_dau', 'thoi_gian_bam_may', 'thoi_gian_ket_thuc', 'sl_dau_vao_chay_thu', 'sl_dau_ra_chay_thu', 'sl_dau_vao_hang_loat', 'sl_dau_ra_hang_loat', 'sl_ng_sx', 'sl_ng_qc', 'sl_loi', 'phan_dinh', 'loi_tinh_nang', 'loi_ngoai_quan', 'dinh_muc', 'parent_id', 'lo_sx', 'nhan_vien_sx', 'status', 'step', 'thu_tu_uu_tien', 'ngay_sx', 'plan_id', 'order_id', 'so_ra', 'so_dao'];

    static function validateStore($input)
    {
        $validated = Validator::make(
            $input,
            [
                'lot_id' => ['required'],
                'machine_id' => ['required'],
            ],
            [
                'lot_id.required' => 'Cần có mã pallet/thùng',
                'machine_id.unique' => 'Cần có công đoạn',
            ]
        );
        return $validated;
    }

    static function validateUpdate($input)
    {
        $validated = Validator::make(
            $input,
            [
                'machine_id' => 'required',
                'thoi_gian_bat_dau' => 'nullable|date_format:Y-m-d H:i:s',
                'thoi_gian_bam_may' => 'nullable|date_format:Y-m-d H:i:s',
                'thoi_gian_ket_thuc' => 'nullable|date_format:Y-m-d H:i:s',
                'sl_dau_vao_chay_thu' => 'nullable|numeric',
                'sl_dau_ra_chay_thu' => 'nullable|numeric',
                'sl_dau_vao_hang_loat' => 'nullable|numeric',
                'sl_dau_ra_hang_loat' => 'nullable|numeric',
                'sl_tem_vang' => 'nullable|numeric',
                'sl_ng' => 'nullable|numeric',
            ],
            [
                'machine_id.required' => 'Không tìm thấy công đoạn',
                'thoi_gian_bat_dau.date_format' => 'Thời gian bắt đầu không đúng định dạng',
                'thoi_gian_bam_may.date_format' => 'Thời gian bấm máy không đúng định dạng',
                'thoi_gian_ket_thuc.date_format' => 'Thời gian kết thúc không đúng định dạng',
                'sl_dau_vao_chay_thu.numeric' => 'Số lượng đầu vào vào hàng phải là số',
                'sl_dau_ra_chay_thu.numeric' => 'Số lượng đầu ra vào hàng phải là số',
                'sl_dau_vao_hang_loat.numeric' => 'Số lượng đầu vào thực tế phải là số',
                'sl_dau_ra_hang_loat.numeric' => 'Số lượng đầu ra thực tế phải là số',
                'sl_tem_vang.numeric' => 'Số lượng tem vàng phải là số',
                'sl_ng.numeric' => 'Số lượng NG phải là số',
            ]
        );
        return $validated;
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }
    public function qc_log()
    {
        return $this->belongsTo(QCLog::class, ['lo_sx', 'machine_id'], ['lo_sx', 'machine_id']);
    }
    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
    public function line()
    {
        return $this->hasOneThrough(Line::class, Machine::class, 'id', 'id', 'machine_id', 'line_id');
    }
    // public function plan()
    // {
    //     return $this->hasOne(ProductionPlan::class, ['lo_sx', 'machine_id'], ['lo_sx', 'machine_id']);
    // }
    public function plan()
    {
        return $this->belongsTo(ProductionPlan::class, 'plan_id');
    }
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
    public function tem()
    {
        return $this->hasOne(Tem::class, 'lo_sx', 'lo_sx');
    }
    public function user()
    {
        return $this->belongsTo(CustomUser::class, 'nhan_vien_sx');
    }
    public function lsx()
    {
        return $this->belongsTo(LSX::class, 'lo_sx');
    }
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
    public function infoCongDoanPriority()
    {
        return $this->hasOne(InfoCongDoanPriority::class, 'info_cong_doan_id');
    }
    public function lsxpallet()
    {
        return $this->hasOne(LSXPallet::class, 'lo_sx', 'lo_sx');
    }
    public function warehouseFGLog()
    {
        return $this->hasOne(WarehouseFGLog::class, 'lo_sx', 'lo_sx');
    }
}
