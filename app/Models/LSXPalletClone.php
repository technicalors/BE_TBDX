<?php

namespace App\Models;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LSXPalletClone extends Model
{
    use Compoships;
    use HasFactory;
    public $incrementing = false;
    protected $table = 'lsx_pallet_clone';
    protected $fillable = ['lo_sx', 'so_luong', 'pallet_id', 'mdh', 'mql', 'customer_id', 'order_id', 'created_at', 'remain_quantity'];
    public function pallet()
    {
        return $this->belongsTo(Pallet::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function locator_fg_map(){
        return $this->hasOne(LocatorFGMap::class, 'pallet_id', 'pallet_id');
    }
    public function warehouseFGLog(){
        return $this->hasMany(WarehouseFGLog::class, 'lo_sx', 'lo_sx');
    }
}
