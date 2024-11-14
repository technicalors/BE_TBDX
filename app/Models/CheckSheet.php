<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckSheet extends Model
{
    use HasFactory;

    protected $table = "check_sheet";


    public function checkSheetWork()
    {
        return $this->hasMany(CheckSheetWork::class, 'check_sheet_id');
    }
    public function line(){
        return $this->belongsTo(Line::class, 'line_id');
    }

}
