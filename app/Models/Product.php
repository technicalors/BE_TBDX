<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Product extends Model
{
    use HasFactory;
    protected $fillable = ['id', 'name', 'material_id', 'dinh_muc', 'customer_id', 'ver', 'his', 'nhiet_do_phong', 'do_am_phong', 'do_am_giay', 'thoi_gian_bao_on', 'chieu_dai_thung', 'chieu_rong_thung', 'chieu_cao_thung', 'the_tich_thung', 'dinh_muc_thung', 'u_nhiet_do_phong', 'u_do_am_phong', 'u_do_am_giay', 'u_thoi_gian_u', 'number_of_bin', 'kt_kho_dai', 'kt_kho_rong'];
    protected $hidden = ['created_at', 'updated_at'];
    protected $casts = ['info' => 'json', "id" => "string"];


    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function materialLog()
    {
        return $this->hasOne(MaterialLog::class, 'material_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function spec()
    {
        return $this->hasMany(Spec::class);
    }

    public function warehouseLog()
    {
        return $this->hasManyThrough(WareHouseLog::class, Lot::class);
    }

    public function lots(){
        return $this->hasMany(Lot::class);
    }

    static function validateUpdate($input, $is_update = true)
    {
        $validated = Validator::make(
            $input,
            [
                'id'=>$is_update ? 'required' : 'required|unique:products',
                'name'=>'required',
                'material_id'=>'required',
                'dinh_muc'=>'required',
                'customer_id'=>'required',
                'ver'=> 'required',
                'his'=> 'required',
                'nhiet_do_phong'=>'required',
                'do_am_phong'=> 'required',
                'do_am_giay'=> 'required',
                'thoi_gian_bao_on'=> 'required',
                'chieu_dai_thung'=> 'required',
                'chieu_rong_thung'=> 'required',
                'chieu_cao_thung'=> 'required',
                'the_tich_thung'=> 'required',
                'dinh_muc_thung'=>'required',
                'u_nhiet_do_phong'=> 'required',
                'u_do_am_phong'=> 'required',
                'u_do_am_giay'=> 'required',
                'u_thoi_gian_u'=> 'required',
                'number_of_bin'=> 'required',
            ],
            [
                'id.required'=>'Không tìm thấy mã sản phẩm',
                'id.unique'=>'Mã sản phẩm đã tồn tại',
                'name.required'=>'Không tìm thấy tên sản phẩm',
                'material_id.required'=>'Không có mã nguyên liệu',
                'dinh_muc.required'=>'Không có số tờ/pallet',
                'customer_id.required'=>'Không tìm thấy khách hàng',
                'ver.required'=>'Không có ver',
                'his.required'=>'Không có his',
                'nhiet_do_phong.required'=>'Không có nhiệt độ phòng',
                'do_am_phong.required'=>'Không có độ ẩm phòng',
                'do_am_giay.required'=>'Không có độ ẩm giấy',
                'thoi_gian_bao_on.required'=>'Không có thời gian bảo ôn',
                'chieu_dai_thung.required'=>'Không có chiều dài thùng',
                'chieu_rong_thung.required'=>'Không có chiều rộng thùng',
                'chieu_cao_thung.required'=>'Không có chiều cao thùng',
                'the_tich_thung.required'=>'Không có thể tích thùng',
                'number_of_bin.required'=>'Không có  number of bin',
                'dinh_muc_thung.required'=>'Không có định mức thùng',
                'u_nhiet_do_phong.required'=>'Không có nhiệt độ phòng ủ',
                'u_do_am_phong.required'=>'Không có độ ẩm phòng ủ',
                'u_do_am_giay.required'=>'Không có độ ẩm giấy ủ',
                'u_thoi_gian_u.required'=>'Không có thời gian ủ',
            ]
        );
        return $validated;
    }
}
