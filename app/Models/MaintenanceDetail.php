<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use App\Traits\UUID;

class MaintenanceDetail extends Model
{
    use HasFactory, UUID;
    protected $table = "maintenance_detail";
    protected $fillable = ['id', 'name', 'start_date', 'type_repeat', 'period', 'type_criteria', 'maintenance_id'];

    static function validateUpdate($input, $is_update = true)
    {
        $validated = Validator::make(
            $input,
            [
                'name'=>'required',
                'start_date'=>'required',
            ],
            [
                'name.required'=>'Không có tên',
                'start_date.required'=>'Không có thời gian bắt đầu hạng mục',
                'maintenance_id.required'=>'Không tìm thấy mã bảo dưỡng',
                'type_repeat.required'=>'Không có kiểu lặp',
                'period.required'=>'Không có chu kỳ lặp',
                'type_criteria.required'=>'Không có loại đánh giá',
            ]
        );
        return $validated;
    }
}
