<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionPlan extends Model
{
    use HasFactory;
    use \Awobaz\Compoships\Compoships;

    const START_LUNCH = 11.5 / 24;
    const END_LUNCH = 12.5 / 24;
    const START_AFTERNOON = 16.5 / 24;
    const END_AFTERNOON = 17 / 24;
    protected $table = 'production_plans';
    protected $fillable = [
        'id', 'lo_sx', 'machine_id', 'thu_tu_uu_tien', 'ngay_dat_hang', 'toc_do', 'tg_doi_model',
        'sl_kh', 'so_m_toi', 'ghi_chu', 'thoi_gian_bat_dau', 'thoi_gian_ket_thuc', 'file', 'order_id', 'ngay_sx','ordering', 'created_by', 'loss_quantity',
        'sl_tem'
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function lot()
    {
        return $this->hasMany(Lot::class, "lo_sx", "lo_sx");
    }
    // public function infocongdoan()
    // {
    //     return $this->hasMany(InfoCongDoan::class, ['lo_sx', 'machine_id'], ['lo_sx', 'machine_id']);
    // }
    public function info_losx()
    {
        return $this->hasOne(InfoCongDoan::class, ['lo_sx', 'machine_id'], ['lo_sx', 'machine_id']);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
    public function order()
    {
        return $this->hasOne(Order::class, 'id', 'order_id');
    }
    public function group_plan_order()
    {
        return $this->hasMany(GroupPlanOrder::class, 'plan_id', 'id');
    }
    public function orders()
    {
        /**
         * hasManyThrough($target, $through, id_of_this_in_$through, id_of_this, id_of_$target, id_of_$target_in_$through)
         */
        return $this->hasManyThrough(Order::class, GroupPlanOrder::class, 'plan_id', 'id', 'id', 'order_id');
    }
    public function lsx()
    {
        return $this->belongsTo(LSX::class, 'lo_sx');
    }
    public function l_s_x_log()
    {
        return $this->hasOne(LSXLog::class, 'lo_sx', 'lo_sx');
    }
    public function mapping()
    {
        return $this->hasOne(Mapping::class, 'lo_sx', 'lo_sx');
    }
    public function creator()
    {
        return $this->belongsTo(CustomUser::class, 'created_by');
    }
    public function infoCongDoan(){
        return $this->hasOne(InfoCongDoan::class, 'plan_id');
    }
}
