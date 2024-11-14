<?php

namespace App\Admin\Controllers;

use App\Models\Shift;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use App\Traits\API;
use stdClass;

class ShiftController extends AdminController
{
    use API;

    public function getShift(Request $request){
        $query = Shift::query();
        $shifts = $query->get();
        return $this->success($shifts);
    }
}
