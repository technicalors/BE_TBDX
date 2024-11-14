<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldRole extends Model
{
    use HasFactory;
    protected $table = 'field_roles';
    protected $fillable = ['role_id','field_id','table_id'];
    public $timestamps = false;
}
