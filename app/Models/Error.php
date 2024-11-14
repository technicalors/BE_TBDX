<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Error extends Model
{
    use HasFactory;
    protected $fillable = ['id', 'name', 'noi_dung', 'nguyen_nhan', 'khac_phuc', 'phong_ngua', 'line_id'];
    public $incrementing = false;
    protected $casts = [
        "id" => "string"
    ];

    public function line()
    {
        return $this->belongsTo(Line::class, 'line_id');
    }

    static function validateUpdate($input, $is_update = true)
    {
        $validated = Validator::make(
            $input,
            [
                'id'=>$is_update ? 'required' : 'required|unique:errors',
                'name'=>'required',
                'line_id' => 'required',
            ],
            [
                'id.required' => 'Không có mã lỗi',
                'id.unique' => 'Mã lỗi đã tồn tại',
                'name.required'=>'Không có nội dung', 
                'line_id.required'=>'Không tìm thấy công đoạn', 
            ]
        );
        return $validated;
    }
}
