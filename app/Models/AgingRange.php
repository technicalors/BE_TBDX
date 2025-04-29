<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgingRange extends Model
{
    use HasFactory;
    protected $table="aging_ranges";
    protected $fillable = ['id','label','code','day_min','day_max'];
}
