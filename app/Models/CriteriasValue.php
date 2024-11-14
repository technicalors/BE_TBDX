<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CriteriasValue extends Model
{
    use HasFactory,UUID;
    protected $fillable = ['type_id','value'];
    const TYPE_SELECT = 1; // Chọn select
    const TYPE_INPUT_NUMBER_INTERGER = 2; // Ô input number
    const TYPE_INPUT_NUMBER_FLOAT_1 = 3;
    const TYPE_INPUT_NUMBER_FLOAT_4 = 4;
    const TYPE_INPUT_TEXT = 5; // Ô input text
    const TYPE_SELECT_CATEGORY = 6; // Chọn Đạt/ Không đạt. Nếu không đạt thì chọn lỗi trong danh sách mã lỗi

}
