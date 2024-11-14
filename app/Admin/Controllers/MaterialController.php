<?php

namespace App\Admin\Controllers;

use App\Models\ErrorLog;
use App\Models\LocatorFGMap;
use App\Models\LocatorMLT;
use App\Models\LocatorMLTMap;
use App\Models\Material;
use App\Models\WareHouseMLTImport;
use App\Models\WarehouseMLTLog;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Illuminate\Support\Facades\DB;

class MaterialController extends AdminController
{
    use API;
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Material';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Material());
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        });

        $grid->column('id', __('Id'));
        $grid->column('code', __('Code'));
        $grid->column('ten', __('Ten'));
        $grid->column('thong_so', __('Thong so'));

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Material::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('code', __('Code'));
        $show->field('ten', __('Ten'));
        $show->field('thong_so', __('Thong so'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Material());

        $form->text('code', __('Code'));
        $form->text('ten', __('Ten'));
        $form->text('thong_so', __('Thong so'));

        return $form;
    }

    public function getMaterials(Request $request)
    {
        $page = $request->page - 1;
        $pageSize = $request->pageSize;
        $query = Material::with('locator')->orderByRaw('CHAR_LENGTH(id) DESC')->orderBy('id', 'desc');
        if (isset($request->loai_giay)) {
            $query->where('loai_giay', 'like', "%$request->loai_giay%");
        }
        if (isset($request->ma_cuon_ncc)) {
            $query->where('ma_cuon_ncc', 'like', "%$request->ma_cuon_ncc%");
        }
        if (isset($request->id)) {
            $query->where('id', 'like', "%$request->id%");
        }
        if (isset($request->phan_loai)) {
            if ($request->phan_loai == 1) {
                $query->has('locator');
            } else if ($request->phan_loai == 0) {
                $query->doesntHave('locator');
            }
        }
        if (isset($request->locator_id)) {
            $query->whereHas('locator', function ($q) use ($request) {
                $q->where('locator_mlt_id', 'like', "%$request->locator_id%");
            });
        }
        $count = $query->count();
        $totalPage = $count;
        $materials = $query->offset($page * $pageSize)->limit($pageSize)->get();;

        // $materials = $query->get();
        foreach ($materials as $material) {
            $material->fsc = $material->fsc ? "X" : "";
            $material->ten_ncc = $material->supplier->name ?? "";
            $material->locator_id = $material->locator->locator_mlt_id ?? "";
        }
        $res = [
            "data" => $materials,
            "totalPage" => $totalPage,
        ];
        return $this->success($res);
    }
    public function updateMaterial(Request $request)
    {
        $input = $request->all();
        $material = Material::where('id', $input['key'])->first();
        if ($material) {
            try {
                DB::beginTransaction();
                $input['fsc'] = isset($input['fsc']) ? 1 : 0;
                $validated = Material::validateUpdate($input);
                if ($validated->fails()) {
                    return $this->failure('', $validated->errors()->first());
                }
                $input['so_m_toi'] = floor(($input['so_kg'] / ($input['kho_giay'] / 100)) / ($input['dinh_luong'] / 1000));
                $material->update($input);
                if (isset($input['locator_id'])) {
                    $locator = LocatorMLT::find($input['locator_id']);
                    if (!$locator) {
                        return $this->failure('', 'Vị trí không phù hợp');
                    } else {
                        $locator_input = ['locator_mlt_id' => $locator->id, 'material_id' => $material->id];
                        LocatorMLTMap::updateOrCreate(['material_id' => $material->id], $locator_input);
                        $log = WarehouseMLTLog::where('material_id', $material->id)->whereNull('tg_xuat')->orderBy('created_at', 'DESC')->first();
                        if($log){
                            $log_input = [
                                'locator_id' => $locator->id, 
                                'material_id' => $material->id, 
                                'so_kg_nhap'=>$material->so_kg,
                            ];
                            $log->update($log_input);
                        }else{
                            $log_input = [
                                'locator_id' => $locator->id, 
                                'material_id' => $material->id, 
                                'so_kg_nhap'=>$material->so_kg, 
                                'tg_nhap'=>date('Y-m-d H:i:s'),
                                'importer_id'=>$request->user()->id,
                            ];
                            WarehouseMLTLog::create($log_input);
                        }
                        
                    }
                }
                DB::commit();
            } catch (\Throwable $th) {
                DB::rollBack();
                ErrorLog::saveError($request, $th);
                return $this->failure($th, 'Đã xảy ra lỗi');
            }
        } else {
            return $this->failure('', 'Không tìm thấy nguyên vật liệu');
        }
        return $this->success('', 'Cập nhật thành công');
    }

    public function createMaterial(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $input['fsc'] = isset($input['fsc']) ? 1 : 0;
            $validated = Material::validateUpdate($input, false);
            if ($validated->fails()) {
                return $this->failure('', $validated->errors()->first());
            }
            $input['so_m_toi'] = floor(($input['so_kg'] / ($input['kho_giay'] / 100)) / ($input['dinh_luong'] / 1000));
            $material = Material::create($input);
            if (isset($input['locator_id'])) {
                $locator = LocatorMLT::find($input['locator_id']);
                if (!$locator) {
                    return $this->failure('', 'Vị trí không phù hợp');
                } else {
                    $locator_input = ['locator_mlt_id' => $locator->id, 'material_id' => $material->id];
                    LocatorMLTMap::updateOrCreate(['material_id' => $material->id], $locator_input);
                    $log = WarehouseMLTLog::where('material_id', $material->id)->whereNull('tg_xuat')->orderBy('created_at', 'DESC')->first();
                    if($log){
                        $log_input = [
                            'locator_id' => $locator->id, 
                            'material_id' => $material->id, 
                            'so_kg_nhap'=>$material->so_kg,
                        ];
                        $log->update($log_input);
                    }else{
                        $log_input = [
                            'locator_id' => $locator->id, 
                            'material_id' => $material->id, 
                            'so_kg_nhap'=>$material->so_kg, 
                            'tg_nhap'=>date('Y-m-d H:i:s'),
                            'importer_id'=>$request->user()->id,
                        ];
                        WarehouseMLTLog::create($log_input);
                    }
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
        return $this->success($material, 'Tạo thành công');
    }

    public function deleteMaterials(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            foreach ($input as $material_id) {
                $log = WarehouseMLTLog::where('material_id', $material_id)->first();
                if ($log) return $this->failure('', 'Cuộn ' . $material_id . ' đã vào sản xuất');
                Material::where('id', $material_id)->delete();
                WareHouseMLTImport::whereIn('material_id', $material_id)->delete();
                LocatorMLTMap::where('material_id', $material_id)->delete();
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
        return $this->success('Xoá thành công');
    }
}
