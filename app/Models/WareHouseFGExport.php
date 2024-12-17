<?php

namespace App\Models;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WareHouseFGExport extends Model
{
    use HasFactory, Compoships;
    protected $table = 'warehouse_fg_export';
    protected $fillable = [
        'customer_id', 'ngay_xuat', 'mdh', 'mql', 'so_luong', 'tai_xe', 'so_xe', 'nguoi_xuat', 'order_id', 'delivery_note_id', 'created_by', 'xuong_giao'
    ];
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
    // public function lsxpallet()
    // {
    //     return $this->hasMany(LSXPallet::class, 'order_id', 'order_id');
    // }
    public function lsxpallets()
    {
        return $this->hasMany(LSXPallet::class, ['mdh', 'mql'], ['mdh', 'mql']);
    }
    public function delivery_note()
    {
        return $this->belongsTo(DeliveryNote::class, 'delivery_note_id');
    }
    public function creator(){
        return $this->hasOne(CustomUser::class, 'id', 'created_by');
    }
}
