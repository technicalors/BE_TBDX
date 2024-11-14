<?php

namespace App\Models;

use App\Traits\IDTimestamp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;
use Illuminate\Support\Facades\Validator;

class Khuon extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $table = 'khuon';
    protected $fillable = ['id', 'khach_hang', 'dai', 'rong', 'cao', 'so_kg', 'so_luong', 'so_manh_ghep', 'ghi_chu'];
    protected $hidden=['created_at','updated_at'];

    static function validateUpdate($input, $is_update = true)
    {
        $validated = Validator::make(
            $input,
            [
                'id' => $is_update ? 'required' : 'required|unique:khuon',
                'khach_hang' => 'required',
            ],
            [
                'id.required'=>'Không có mã khuôn',
                'id.unique'=>'Mã khuôn đã tồn tại',
                'khach_hang.required'=>'Không có khách hàng',
            ]
        );
        return $validated;
    }
    

}