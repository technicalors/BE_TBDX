<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sheft extends Model
{
    use HasFactory, UUID;
    protected $fillable = ['id', 'name', 'note', 'warehouse_id'];
    protected $hidden = ['node', 'warehouse_id', 'created_at', 'updated_at', 'note'];

    public function cell()
    {
        return $this->hasMany(Cell::class);
    }

    public function warehouse(){
        return $this->belongsTo(WareHouse::class,"warehouse_id");
    }
}
