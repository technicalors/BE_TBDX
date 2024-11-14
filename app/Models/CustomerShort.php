<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerShort extends Model
{
    use HasFactory;
    protected $table = "customer_short";
    protected $fillable = ['customer_id', 'short_name'];
    public function customer(){
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
}
