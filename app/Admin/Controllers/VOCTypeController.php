<?php

namespace App\Admin\Controllers;

use App\Models\CustomUser;
use App\Models\DRC;
use App\Models\GroupPlanOrder;
use App\Models\LocatorMLTMap;
use App\Models\Material;
use App\Models\Order;
use App\Models\Vehicle;
use App\Models\VOCType;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VOCTypeController extends AdminController
{
    use API;

    public function getList(Request $request)
    {
        $records = VOCType::all();
        return $this->success($records);
    }
}
