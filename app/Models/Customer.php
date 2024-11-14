<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Customer extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $table = "customer";
    protected $fillable = ['id', 'name', 'name_input'];
    protected $casts = [
        'id' => 'string',
    ];
    public function customer_short(){
        return $this->hasOne(CustomerShort::class, 'customer_id', 'id');
    }
    const FORM_LIST = [
        'THUAN PHUONG'=>'mau_1',
        'GLO'=>'mau_1',
        'HSDN'=>'mau_1',
        'HIMARU'=>'mau_1',
        'TAICERA'=>'mau_1',
        'K-J'=>'mau_1',
        'SAKIRA'=>'mau_1',
        'HSTG 7'=>'mau_1',
        'HSTN 1'=>'mau_1',
        'HSCC'=>'mau_1',
        'SIGMA'=>'mau_1',
        'HAUTECH'=>'mau_1',
        'MEKONG'=>'mau_1',
        'JY VINA'=>'mau_1',
        'UNISOLL'=>'mau_1',
        'TBG'=>'mau_2',
        'MEKONG'=>'mau_2',
        'KSA'=>'mau_2',
        'SMTG 1'=>'mau_2',
        'COH'=>'mau_3',
        'PUNGKOOK'=>'mau_4',
        'SIMONE'=>'mau_4',
        'J-S'=>'mau_4',
        'MEKONG'=>'mau_4',
        'KSA'=>'mau_4',
        'SHILLA BAGS'=>'mau_4',
        'SHILLA GLOVIS'=>'mau_4',
        'Afirst'=>'mau_4',
        'GIA PHU'=>'mau_4',
        'KANAAN'=>'mau_5',
        'KANAAN BAO LOC'=>'mau_5',
        'KANAAN VIET NAM'=>'mau_5',
        'SIMONE AL'=>'mau_5',
        'SIMONE AD'=>'mau_5',
        'SIMONE AH'=>'mau_5',
        'SMTG 2'=>'mau_5',
        'KHANH TRUNG'=>'mau_5',
        'HUANG ZONG'=>'mau_5',
        'NONG SAN CAN THO'=>'mau_5',
    ];
}
