<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class GoodsReceiptNote extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $fillable = ['id','supplier_name', 'vehicle_number','total_weight','vehicle_weight', 'material_weight'];
    protected $casts = ['id'=>'string'];

    public function warehouse_mlt_import(){
        return $this->hasMany(WareHouseMLTImport::class, 'goods_receipt_note_id', 'id');
    }
}
