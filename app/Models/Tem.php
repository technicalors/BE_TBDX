<?php

namespace App\Models;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tem extends Model
{
    use HasFactory;
    use \Awobaz\Compoships\Compoships;
    protected $table = "tem";
    protected $fillable = ['ordering', 'lo_sx', 'khach_hang', 'mdh', 'order_id', 'mql', 'quy_cach', 'so_luong', 'gmo', 'po', 'style', 'style_no', 'color', 'note', 'machine_id', 'nhan_vien_sx', 'sl_tem', 'display', 'created_by'];
    public function user()
    {
        return $this->belongsTo(CustomUser::class, 'nhan_vien_sx');
    }
    public function lsx()
    {
        return $this->belongsTo(LSX::class, 'lo_sx');
    }
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
