<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocatorMLTMap extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $table = "locator_mlt_map";
    protected $fillable = ['locator_mlt_id', 'material_id'];
    public $timestamps = false;
    public $primaryKey = 'material_id';

    public function material(){
        return $this->belongsTo(Material::class, 'material_id');
    }
}
