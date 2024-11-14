<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use App\Traits\UUID;

class Maintenance extends Model
{
    use HasFactory, UUID;
    protected $table = "maintenance";
    protected $fillable = ['id', 'name', 'machine_id'];

    static function validateUpdate($input, $is_update = true)
    {
        $validated = Validator::make(
            $input,
            [
                'name'=>'required',
                'machine_id'=>'required',
            ],
            [
                'name.required'=>'Không có tên',
                'machine_id.required'=>'Không có mã máy',
            ]
        );
        return $validated;
    }

    public function detail()
    {
        return $this->hasMany(MaintenanceDetail::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_id', 'id');
    }
}
