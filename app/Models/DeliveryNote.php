<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class DeliveryNote extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $fillable = ['id', 'created_by', 'driver_id', 'vehicle_id', 'exporter_id'];
    protected $casts = ['id' => 'string'];
    public function creator()
    {
        return $this->hasOne(CustomUser::class, 'id', 'created_by');
    }
    public function exporter()
    {
        return $this->hasOne(CustomUser::class, 'id', 'exporter_id');
    }
    public function driver()
    {
        return $this->hasOne(CustomUser::class, 'id', 'driver_d');
    }
    public function vehicle()
    {
        return $this->hasOne(Vehicle::class, 'id', 'vehicle_id');
    }
    public function warehouse_fg_logs()
    {
        return $this->hasMany(WarehouseFGLog::class, 'delivery_note_id', 'id');
    }
    public function exporters()
    {
        return $this->belongsToMany(CustomUser::class, 'admin_user_delivery_note', 'delivery_note_id', 'admin_user_id');
    }
}
