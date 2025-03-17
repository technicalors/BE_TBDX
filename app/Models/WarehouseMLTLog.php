<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseMLTLog extends Model
{
    use HasFactory;
    protected $table = 'warehouse_mlt_logs';
    protected $fillable = ['locator_id', 'material_id', 'so_kg_nhap', 'so_kg_xuat', 'tg_nhap', 'tg_xuat', 'position_id', 'position_name', 'importer_id', 'exporter_id'];
    public function material()
    {
        return $this->hasOne(Material::class, 'id', 'material_id');
    }
    public function locatorMlt()
    {
        return $this->belongsTo(LocatorMLT::class, 'locator_id');
    }
    public function warehouse_mlt_import()
    {
        return $this->hasOne(WareHouseMLTImport::class, 'material_id', 'material_id');
    }
    public function warehouse_mtl_export()
    {
        return $this->hasOne(WareHouseMLTExport::class, 'material_id', 'material_id');
    }
    public function import()
    {
        return $this->belongsTo(CustomUser::class, 'importer_id', 'id');
    }
    public function exporter()
    {
        return $this->belongsTo(CustomUser::class, 'exporter_id', 'id');
    }
    public function exporter()
    {
        return $this->belongsTo(CustomUser::class, 'exporter_id', 'id');
    }
}
