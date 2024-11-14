<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class WareHouse extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $fillable = ['id', 'name'];
    protected $hidden = ['created_at', 'updated_at'];
    public function sheft()
    {
        return $this->hasMany(Sheft::class, "warehouse_id");
    }
    public function cell()
    {
        return $this->hasManyThrough(Cell::class, Sheft::class,'warehouse_id');
    }

    static function validateUpdate($input, $is_update = true)
    {
        $validated = Validator::make(
            $input,
            [
                'id' => $is_update ? 'required' : 'required|unique:ware_houses',
            ],
            [
                'id.required'=>'Không có mã kho',
                'id.unique'=>'Mã kho đã tồn tại',
            ]
        );
        return $validated;
    }
}
