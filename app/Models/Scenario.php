<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scenario extends Model
{
    use HasFactory;

    protected $table="scenario";

    protected $fillable = ['hang_muc','tieu_chuan','tieu_chuan_max','tieu_chuan_min','tieu_chuan_kiem_soat_tren','tieu_chuan_kiem_soat_duoi'];
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
