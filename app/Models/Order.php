<?php

namespace App\Models;

use App\Traits\IDTimestamp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;
use Awobaz\Compoships\Compoships;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $primaryKey = 'id'; // Đảm bảo khoá chính đúng
    protected $keyType = 'string'; // Nếu khoá chính là kiểu chuỗi
    protected $fillable = [
        'id', 'ngay_dat_hang', 'customer_id', 'nguoi_dat_hang', 'mdh', 'order', 'mql', 'width', 'height', 'length', 'dai', 'rong', 'cao', 'sl', 'slg',
        'slt', 'tmo', 'po', 'style', 'style_no', 'color', 'item', 'rm', 'size', 'note_1', 'han_giao', 'note_2', 'note_3', 'price', 'into_money', 'layout_type', 'dot',
        'kho', 'layout_id', 'buyer_id', 'so_dao', 'dai_tam', 'so_met_toi', 'tg_doi_model', 'toc_do', 'so_ra', 'kich_thuoc', 'unit', 'xuong_giao', 'kich_thuoc_chuan',
        'phan_loai_1', 'phan_loai_2', 'kho_tong', 'quy_cach_drc', 'han_giao_sx', 'is_plan', 'short_name', 'xuat_tai_kho', 'khuon_id', 'created_by'
    ];
    protected $casts = ["id" => "string"];
    static function validateUpdate($input, $is_update = true)
    {
        $validated = Validator::make(
            $input,
            [
                'id' => $is_update ? 'required' : 'required|unique:order',
                'customer_id' => 'required',
            ],
            [
                'id.required' => 'Không có mã khuôn',
                'id.unique' => 'Mã khuôn đã tồn tại',
                'customer_id.required' => 'Không có khách hàng',
            ]
        );
        return $validated;
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
    public function buyer()
    {
        return $this->belongsTo(Buyer::class, 'buyer_id');
    }
    public function layout()
    {
        return $this->belongsTo(Layout::class, 'layout_id', 'layout_id');
    }
    public function customer_specifications()
    {
        return $this->hasMany(CustomerSpecification::class, 'customer_id', 'customer_id');
    }
    public function group_plan_order()
    {
        return $this->hasOne(GroupPlanOrder::class, 'order_id', 'id');
    }
    public function plan()
    {
        return $this->hasMany(ProductionPlan::class, 'order_id', 'id');
    }
    public function warehouse_fg_export()
    {
        return $this->hasOne(WareHouseFGExport::class, 'order_id', 'id');
    }
    public function creator()
    {
        return $this->belongsTo(CustomUser::class, 'created_by');
    }
}
