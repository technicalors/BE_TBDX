<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Support\Facades\Validator;

class Role extends Model
{
    use HasFactory;
    protected $table = 'admin_roles';
    protected $fillable = ['id', 'name', 'slug', 'parent_id', 'machine_id'];

    public function permissions(){
        return $this->hasManyThrough(Permission::class, RolePermission::class, 'role_id', 'id', 'id', 'permission_id');
    }
    
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->select('*', 'id as key');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id')->select('*', 'id as key');
    }

    public function machine()
    {
        return $this->hasOne(Machine::class, 'id', 'machine_id');
    }

    static function validateUpdate($input, $is_update = true)
    {
        $validated = Validator::make(
            $input,
            [
                'name' => $is_update ? 'required' : 'required|unique:admin_roles',
                'parent_id' => 'different:id',
            ],
            [
                'name.required'=>'Không có tên bộ phận',
                'name.unique'=>'Tên bộ phận đã tồn tại',
                'parent_id.different'=>'Bộ phận trực thuộc không phù hợp'
            ]
        );
        return $validated;
    }
}
