<?php

namespace App\Models;

use App\Traits\IDTimestamp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;
use Illuminate\Support\Facades\Validator;

class Cell extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $fillable = ['id','note','name','sheft_id','number_of_bin','product_id', 'warehouse_id'];
    protected $hidden=['created_at','updated_at'];
    public function lot(){
        return $this->belongsToMany(Lot::class,'cell_lot','cell_id','lot_id');
    }
    public function sheft(){
        return $this->belongsTo(Sheft::class,'sheft_id');
    }

    static function validateUpdate($input, $is_update = true)
    {
        $validated = Validator::make(
            $input,
            [
                'id' => $is_update ? 'required' : 'required|unique:cells',
                'name' => 'required',
                'sheft_id' => 'required',
            ],
            [
                'id.required'=>'Không có mã vị trí kho',
                'id.unique'=>'Mã vị trí kho đã tồn tại',
                'name.required'=>'Không có tên vị trí kho',
                'sheft_id.required'=>'Không có kệ vị trí kho',
            ]
        );
        return $validated;
    }
    

}