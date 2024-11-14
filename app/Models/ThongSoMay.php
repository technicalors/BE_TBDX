<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThongSoMay extends Model
{
    use HasFactory;

    use HasFactory;
    protected $table = "thong_so_may";
    protected $fillable = ['id', 'ngay_sx', 'ca_sx', 'xuong', 'lo_sx', 'lot_id', 'line_id', 'machine_code', 'data_if', 'date_if', 'data_input', 'date_input'];
    protected $casts = ['data_if' => 'json', 'data_input'=>'json'];
    public function machine(){
        return $this->belongsTo(Machine::class,'machine_code','code');
    }
}
