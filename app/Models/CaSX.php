<?php

namespace App\Models;

use App\Traits\IDTimestamp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class CaSX extends Model
{
    protected $table = 'ca_sx';
    protected $fillable = ['id','note','name','sheft_id','number_of_bin','product_id'];
    //key = ca_name, value = ca_id
    const CA_SX = [
        '0'=>'0h-2h', 
        '1'=>'0h-2h', 
        '2'=>'2h-4h', 
        '3'=>'2h-4h', 
        '4'=>'4h-6h', 
        '5'=>'4h-6h', 
        '6'=>'',
        '7'=>'7h-9h', 
        '8'=>'7h-9h', 
        '9'=>'9h-11h', 
        '10'=>'9h-11h', 
        '11'=>'11h-13h', 
        '12'=>'11h-13h', 
        '13'=>'13h-15h', 
        '14'=>'13h-15h', 
        '15'=>'15h-17h', 
        '16'=>'15h-17h', 
        '17'=>'17h-19h', 
        '18'=>'17h-19h', 
        '19'=>'', 
        '20'=>'20h-22h', 
        '21'=>'20h-22h', 
        '22'=>'22h-24h', 
        '23'=>'22h-24h',
    ];

    
    

}
