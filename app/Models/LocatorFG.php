<?php

namespace App\Models;

use App\Traits\IDTimestamp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;
use Illuminate\Support\Facades\Validator;

class LocatorFG extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $table = "locator_fg";
    protected $fillable = ['id', 'name', 'capacity', 'warehouse_fg_id'];
    protected $hidden = ['created_at', 'updated_at'];
}
