<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class CustomerSpecification extends Model
{
    use HasFactory;
    protected $table = "customer_specifications";
    public $timestamps = false;
    protected $fillable = ['id', 'customer_id', 'phan_loai_1', 'drc_id'];

    public function drc(){
        return $this->belongsTo(DRC::class, 'drc_id');
    }
}
