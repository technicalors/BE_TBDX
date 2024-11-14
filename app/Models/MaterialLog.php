<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialLog extends Model
{
    use HasFactory;
    protected $fillable = [
        "material_id", "type", "quantity", "cell_id", "product_id"
    ];

    public function cell()
    {
        return $this->belongsTo(Cell::class);
    }

    public function product()
    {
        return $this->hasOne(Product::class);
    }
}
