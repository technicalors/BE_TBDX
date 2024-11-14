<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spec extends Model
{
    use HasFactory;
    protected $table = "spec";

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function line(){
        return $this->belongsTo(Line::class);
    }
}
