<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckSheetLog extends Model
{
    use HasFactory;
    protected $fillable = ['info'];
    protected $casts = ['info' => 'json'];
    protected  $table = "check_sheet_log";
}
