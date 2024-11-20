<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PQCProcessing extends Model
{
    use HasFactory;
    protected $table = "pqc_processing";
    public $timestamps = false;
    protected $fillable = ['number_of_pqc', 'date', 'number_of_ok_pqc'];
}
