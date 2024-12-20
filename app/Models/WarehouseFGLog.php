<?php

namespace App\Models;

use App\Traits\IDTimestamp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;
use Awobaz\Compoships\Compoships;
use Illuminate\Support\Facades\Validator;

class WarehouseFGLog extends Model
{
    use Compoships;
    use HasFactory;
    public $incrementing = false;
    protected $table = "warehouse_fg_logs";
    protected $fillable = ['id', 'locator_id', "pallet_id", "lo_sx", "so_luong", "type", "created_by", 'order_id', 'delivery_note_id', 'created_at'];

    public function lsx()
    {
        return $this->hasOne(LSX::class, 'id', 'lo_sx');
    }

    public function pallet()
    {
        return $this->belongsTo(Pallet::class);
    }

    public function locator()
    {
        return $this->hasOne(LocatorFG::class, 'locator_id');
    }

    public function plan()
    {
        return $this->belongsTo(ProductionPlan::class);
    }

    public function user()
    {
        return $this->hasOne(CustomUser::class, 'id', 'created_by');
    }

    public function lo_sx_pallet()
    {
        return $this->belongsTo(LSXPallet::class, ['lo_sx', 'pallet_id'], ['lo_sx', 'pallet_id']);
    }

    public function order()
    {
        return $this->hasOne(Order::class, 'id', 'order_id')->withTrashed();
    }

    public function warehouse_fg_export()
    {
        return $this->belongsTo(WareHouseFGExport::class, ['order_id', 'delivery_note_id'], ['order_id', 'delivery_note_id']);
    }

    // Quan hệ để lấy bản ghi nhập (import) liên quan đến bản ghi xuất (export)
    public function importRecord()
    {
        return $this->hasOne(WarehouseFGLog::class, 'lo_sx', 'lo_sx')->where('type', 1); // Bản ghi nhập
    }

    // Quan hệ để lấy bản ghi xuất (export) liên quan đến bản ghi nhập (import)
    public function exportRecord()
    {
        return $this->hasOne(WarehouseFGLog::class, 'lo_sx', 'lo_sx')->where('type', 2); // Bản ghi xuất
    }
}
