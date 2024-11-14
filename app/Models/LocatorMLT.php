<?php

namespace App\Models;

use App\Traits\IDTimestamp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;
use Illuminate\Support\Facades\Validator;

class LocatorMLT extends Model
{
    use HasFactory;
    protected $table = "locator_mlt";
    public $incrementing = false;
    protected $fillable = ['id', 'name', 'capacity', 'warehouse_mlt_id'];
    protected $hidden = ['created_at', 'updated_at'];

    public function materials(){
        return $this->hasManyThrough(Material::class, LocatorMLTMap::class, 'locator_mlt_id', 'id', 'id', 'material_id');
    }

    public function locator_mlt_map(){
        return $this->hasMany(LocatorMLTMap::class, 'locator_mlt_id');
    }

    public function warehouse_mlt(){
        return $this->belongsTo(WareHouseMLT::class, 'warehouse_mlt_id');
    }
}
