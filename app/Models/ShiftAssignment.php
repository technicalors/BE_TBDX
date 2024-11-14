<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class ShiftAssignment extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'shift_id'];

    static function validate($input, $is_update = true)
    {
        $validated = Validator::make(
            $input,
            [
                'user_id' => 'required|unique:shift_assignments,user_id,'.($input['id']??""),
                'shift_id' => 'required',
            ],
            [
                'user_id.required'=>'Không tìm thấy mã nhân viên',
                'user_id.unique'=>'Mã nhân viên đã tồn tại',
                'shift_id.required'=>'Không tìm thấy ca',
            ]
        );
        return $validated;
    }

    public function user(){
        return $this->belongsTo(CustomUser::class, 'user_id');
    }

    public function shift(){
        return $this->belongsTo(Shift::class, 'shift_id');
    }
}
