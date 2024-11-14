<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class GroupPlanOrder extends Model
{
    use HasFactory;
    protected $table = "group_plan_order";
    public $timestamps = false;
    protected $fillable = ['plan_id', 'order_id', 'line_id'];

    public function order()
    {
        return $this->hasOne(Order::class, 'id', 'order_id');
    }
    public function plan()
    {
        return $this->hasOne(ProductionPlan::class, 'id', 'plan_id');
    }
}
