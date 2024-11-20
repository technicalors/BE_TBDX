<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KhuonData extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['total_cells', 'date', 'cells_has_data'];
    public function user(){
        return $this->hasMany(CustomUser::class, 'user_id');
    }
}
