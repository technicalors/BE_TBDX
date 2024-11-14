<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WareHouseLog extends Model
{
    use HasFactory;
    protected $table = 'warehouse_logs';
    protected $fillable = ['cell_id', 'type', 'lot_id','created_by','so_luong'];
    
    public function cell(){
        return $this->belongsTo(Cell::class);
    }
     public function lot()
    {
        return $this->belongsTo(Product::class,'lot_id');
    }
    public function creator(){
        return $this->belongsTo(CustomUser::class,'created_by');
    }
}
