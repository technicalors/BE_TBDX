<?php

namespace App\Models;

use App\Traits\IDTimestamp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;
use Illuminate\Support\Facades\Validator;

class WarehouseFG extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $table = "warehouse_fg";
    protected $fillable = ['id', 'name', 'created_at', 'updated_at'];
}
