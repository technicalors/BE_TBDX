<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Buyer extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $fillable = ['id', 'customer_id', 'buyer_vt', 'so_lop', 'phan_loai_1', 'ket_cau_giay', 'ghi_chu', 'ma_cuon_f', 'ma_cuon_se', 'ma_cuon_le', 'ma_cuon_sb', 'ma_cuon_lb', 'ma_cuon_sc', 'ma_cuon_lc', 'mapping'];
    protected $hidden = ['created_at', 'updated_at'];
    protected $casts = ['mapping' => 'json'];
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
    public function customershort()
    {
        return $this->hasOne(CustomerShort::class, 'customer_id', 'customer_id');
    }
}
