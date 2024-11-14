<?php

namespace App\Models;

use App\Traits\IDTimestamp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;
use Illuminate\Support\Facades\Validator;

class LocatorFGMap extends Model
{
    use HasFactory;
    protected $primaryKey = null;
    public $incrementing = false;
    protected $table = "locator_fg_map";
    protected $fillable = ['locator_id', 'pallet_id'];
    public $timestamps = false;
}
