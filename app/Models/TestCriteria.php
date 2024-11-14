<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class TestCriteria extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $fillable = ['id', 'phan_loai', 'line_id', 'tieu_chuan', 'nguyen_tac', 'frequency', 'name', 'popup_select', 'popup_scan', 'popup_input', 'master_data', 'ghi_chu', 'hang_muc', 'phan_dinh'];
    protected $hidden = ['created_at', 'updated_at'];
    public function line()
    {
        return $this->belongsTo(Line::class);
    }

    public function ref_line()
    {
        return $this->hasOne(Line::class, 'id', 'reference');
    }

    static function validateUpdate($input, $is_update = true)
    {
        $validated = Validator::make(
            $input,
            [
                'id'=>'required|unique:test_criterias',
                'line_id' => 'required',
            ],
            [
                'id.required' => 'Không có mã chỉ tiêu',
                'id.unique' => 'Mã chỉ tiêu đã tồn tại',
                'line_id.required'=>'Không tìm thấy công đoạn',
            ]
        );
        return $validated;
    }
}
