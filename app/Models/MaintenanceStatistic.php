<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceStatistic extends Model
{
    use HasFactory;
    // public $timestamps = false;
    protected $fillable = ['registered_machine', 'date', 'maintained_machine'];
}
