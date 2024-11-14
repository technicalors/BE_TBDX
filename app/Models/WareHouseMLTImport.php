<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WareHouseMLTImport extends Model
{
    use HasFactory;
    protected $table = 'warehouse_mlt_import';
    protected $fillable = ['material_id', 'ma_vat_tu','ma_cuon_ncc','so_kg','loai_giay','kho_giay','dinh_luong','iqc','fsc', 'log', 'goods_receipt_note_id'];
    protected $casts = ['log' => 'json'];

    
    public function material(){
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function warehouse_mtl_log(){
        return $this->hasOne(WarehouseMLTLog::class, 'material_id', 'material_id');
    }

    public function supplier(){
        return $this->hasOne(Supplier::class, 'id', 'loai_giay');
    }
} 
