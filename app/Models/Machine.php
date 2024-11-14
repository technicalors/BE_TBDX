<?php

namespace App\Models;

use App\Traits\UUID;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use function Complex\rho;

class Machine extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $fillable = ['id', 'name', 'code', 'is_iot', 'line_id', 'kieu_loai', 'hidden', 'ordering', 'device_id'];
    protected $hidden = ['created_at', 'updated_at'];

    public function plan()
    {
        return $this->hasMany(ProductionPlan::class, 'machine_id', 'device_id');
    }

    public function parent()
    {
        return $this->hasOne(self::class, 'id', 'parent_id');
    }

    public function reason()
    {
        return $this->belongsToMany(Reason::class, 'reason_machine')->withTimestamps()->wherePivot("created_at", ">=", Carbon::today());
    }

    public function parameter()
    {
        return $this->hasMany(MachineParameter::class);
    }
    public function line()
    {
        return $this->belongsTo(Line::class, 'line_id');
    }

    public function latest()
    {
        return $this->hasOne(MachineParameter::class)->latestOfMany();
    }


    public function lsxLog()
    {
        $lsx_ids = ProductionPlan::where('machine_id', $this->id)->get()->pluck('soLSX');
        return LSXLog::whereIn("lsx", $lsx_ids);
    }

    public function parameters()
    {
        return $this->hasManyThrough(Parameters::class, MachineParameter::class, 'machine_id', 'id', 'code', 'parameter_id');
    }

    public function status()
    {
        return $this->hasOne(MachineStatus::class, 'machine_id', 'code');
    }

    public function info_cong_doan()
    {
        return $this->hasMany(InfoCongDoan::class, 'line_id', 'line_id');
    }

    static function validateUpdate($input, $is_update = true)
    {
        $validated = Validator::make(
            $input,
            [
                'id'=>$is_update ? 'required' : 'required|unique:error_machine',
                'line_id' => 'required',
                'name'=>'required', 
                'line_id'=>'required', 
            ],
            [
                'id.required' => 'Không có mã máy',
                'id.unique' => 'Mã máy đã tồn tại',
                'line_id.required' => 'Không tìm thấy công đoạn',
                'name.required'=>'Không có tên máy', 
            ]
        );
        return $validated;
    }
}
