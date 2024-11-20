<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class KhuonLink extends Model
{
    use HasFactory;
    protected $table = "khuon_link";
    protected $fillable = [
        'id', 'customer_id', 'dai', 'rong', 'cao','kich_thuoc','phan_loai_1',
        'buyer_id', 'kho_khuon', 'dai_khuon', 'so_con', 'so_manh_ghep', 'khuon_id',
        'sl_khuon', 'machine_id', 'buyer_note', 'note', 'layout', 'supplier',
        'ngay_dat_khuon', 'pad_xe_ranh', 'designer_id'
    ];
    static function validateUpdate($input, $is_update = true)
    {
        $validated = Validator::make(
            $input,
            [
                'customer_id' => 'required',
                'khuon_id' => 'required',
                'phan_loai_1'=> 'required',
                'buyer_id'=>'required',
            ],
            [
                'customer_id.required'=>'Không có tên khách hàng rút gọn',
                'khuon_id.required'=>'Không có mã khuôn',
                'phan_loai_1.required'=>'Không có phân loại 1',
                'buyer_id.required'=>'Không có mã buyer',
            ]
        );
        return $validated;
    }
    public function khuon(){
        return $this->belongsTo(Khuon::class, 'khuon_id');
    }
    public function designer(){
        return $this->belongsTo(CustomUser::class, 'designer_id');
    }
}
