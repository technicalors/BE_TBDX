<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Pallet extends Model
{
    public $incrementing = false;
    protected $table = 'pallet';
    protected $fillable = ['id', 'so_luong', 'number_of_lot', 'created_at', 'updated_at', 'deleted_at'];
    protected $casts = ["id" => "string"];
    public function losxpallet()
    {
        return $this->hasMany(LSXPallet::class, "pallet_id");
    }
    public function locator_fg_map(){
        return $this->hasOne(LocatorFGMap::class, 'pallet_id', 'id');
    }
    public function warehouse_fg_log(){
        return $this->hasMany(WarehouseFGLog::class, 'pallet_id', 'id');
    }
}
