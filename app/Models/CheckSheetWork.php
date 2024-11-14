<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckSheetWork extends Model
{
    use HasFactory;
    protected $table = "check_sheet_works";

    public function checksheet()
    {
        return $this->belongsTo(CheckSheet::class, 'check_sheet_id');
    }
}
