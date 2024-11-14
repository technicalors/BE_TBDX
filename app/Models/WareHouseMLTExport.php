<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WareHouseMLTExport extends Model

{
    use HasFactory;
    protected $table = 'warehouse_mlt_export';
    protected $fillable = ['material_id', 'position_id', 'position_name', 'locator_id', 'time_need','status'];
    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
    public function material()
    {
        return $this->belongsTo(Material::class,'material_id');
    }
}
