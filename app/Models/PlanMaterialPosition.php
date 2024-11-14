<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanMaterialPosition extends Model
{
    use HasFactory;

    protected $table="plan_material_position";
    protected $fillable = ['plan_id', 'position', 'value'];
}
