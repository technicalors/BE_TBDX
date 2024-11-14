<?php

namespace App\Models;

use App\Traits\IDTimestamp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;
use Illuminate\Support\Facades\Validator;

class Jig extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $table = 'jig';
    protected $fillable = ['id','name'];
    protected $hidden=['created_at','updated_at'];

    static function validateUpdate($input, $is_update = true)
    {
        $validated = Validator::make(
            $input,
            [
                'id' => $is_update ? 'required' : 'required|unique:khuon',
                'name' => 'required',
            ],
            [
                'id.required'=>'Không có mã jig',
                'id.unique'=>'Mã jig đã tồn tại',
                'name.required'=>'Không có tên jig',
            ]
        );
        return $validated;
    }
    

}