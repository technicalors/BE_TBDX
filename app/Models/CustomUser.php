<?php

namespace App\Models;

use Encore\Admin\Auth\Database\Permission;
use Encore\Admin\Auth\Database\Role;
use Encore\Admin\Middleware\Authenticate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Validator;

class CustomUser extends Model
{
    use HasApiTokens, Notifiable;
    protected $table = 'admin_users';
    protected $fillable = [
        'name',
        'username',
        'id',
        'password',
        'phone_number',
        'login_times_in_day',
        'last_use_at',
        'usage_time_in_day',
        'deleted_at'
    ];
    protected $casts = [
        'id' => 'string'
    ];
    protected $guarded = [];

    public function permissions()
    {
        $pivotTable = config('admin.database.user_permissions_table');

        $relatedModel = config('admin.database.permissions_model');

        return $this->belongsToMany($relatedModel, $pivotTable, 'user_id', 'permission_id');
    }
    public function roles()
    {
        $pivotTable = config('admin.database.role_users_table');

        $relatedModel = config('admin.database.roles_model');

        return $this->belongsToMany($relatedModel, $pivotTable, 'user_id', 'role_id');
    }

    public function qc_permission()
    {
        $roles = $this->roles;
        $permissions = [];
        foreach ($roles as $role) {
            foreach ($role->permissions as $permission) {
                $permissions[] = $permission->slug;
            }
        }
        $qc_permission = ['iqc', 'pqc', 'oqc'];
        return array_intersect($qc_permission, $permissions);
    }


    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {});
    }

    static function validateUpdate($input, $is_update = true)
    {
        $validated = Validator::make(
            $input,
            [
                'username' => 'required',
                'name' => 'required',
            ],
            [
                'username.required' => 'Không tìm thấy tài khoản',
                'name.required' => 'Không có tên',
            ]
        );
        return $validated;
    }

    public function user_line()
    {
        return $this->hasOne(UserLine::class, 'user_id');
    }
    public function user_machine()
    {
        return $this->hasMany(UserMachine::class, 'user_id');
    }

    public function hasPermission($permission)
    {
        foreach ($this->roles as $role) {
            if ($role->permissions()->where('slug', $permission)->first()) {
                return true;
            }
        }
        return false;
    }

    public function deliveryNotes()
    {
        return $this->belongsToMany(DeliveryNote::class, 'admin_user_delivery_note', 'admin_user_id', 'delivery_note_id');
    }
}
