<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Material extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $casts = ["id" => "string"];
    protected $table = "material";
    protected $fillable = ['id', 'ma_vat_tu', 'ma_cuon_ncc', 'so_kg', 'so_kg_dau', 'loai_giay', 'kho_giay', 'dinh_luong', 'parent_id', 'so_m_toi', 'fsc'];

    static function validateUpdate($input, $is_update = true)
    {
        $validated = Validator::make(
            $input,
            [
                'id' => 'required|unique:material,id,'.($input['key']??""),
                'kho_giay' => 'required|min:1',
                'dinh_luong' => 'required|min:1',
                'ma_cuon_ncc' => 'required',
                'loai_giay' => 'required',
                'so_kg' => 'required',
            ],
            [
                'id.required' => 'Không có mã NVL',
                'id.unique' => 'Mã NVL đã tồn tại',
                'kho_giay.required' => 'Thiếu khổ giấy',
                'dinh_luong.required' => 'Thiếu định lượng',
                'loai_giay.required' => 'Thiếu loại giấy',
                'ma_cuon_ncc.required' => 'Thiếu mã cuộn NCC',
                'so_kg.required' => 'Thiếu số KG',
                'kho_giay.min' => 'Khổ giấy phải lớn hơn 0',
                'dinh_luong.min' => 'Định lượng phải lớn hơn 0',
            ]
        );
        return $validated;
    }

    public function supplier()
    {
        return $this->hasOne(Supplier::class, 'id', 'loai_giay');
    }

    public function locator()
    {
        return $this->hasOne(LocatorMLTMap::class, 'material_id')->orderBy('created_at', 'DESC');
    }

    public function warehouse_mlt_import()
    {
        return $this->hasOne(WareHouseMLTImport::class, 'ma_cuon_ncc', 'ma_cuon_ncc');
    }

    public function warehouse_mlt_logs()
    {
        return $this->hasMany(WarehouseMLTLog::class, 'material_id')->orderBy('created_at', 'DESC');
    }

    public function scopeWhereFirstImportDate($query, $date)
    {
        $sub = WarehouseMLTLog::select('warehouse_mlt_logs.tg_nhap')
            ->whereColumn('warehouse_mlt_logs.material_id', 'material.id')
            ->orderBy('warehouse_mlt_logs.tg_nhap', 'asc')
            ->limit(1);

        return $query->whereDate(DB::raw("({$sub->toSql()})"), '=', $date)
                    ->mergeBindings($sub->getQuery());
    }

    public function scopeWhereLastExportDate($query, $date)
    {
        $sub = WarehouseMLTLog::select('warehouse_mlt_logs.tg_xuat')
            ->whereColumn('warehouse_mlt_logs.material_id', 'material.id')
            ->orderBy('warehouse_mlt_logs.tg_nhap', 'desc')
            ->limit(1);

        return $query->whereDate(DB::raw("({$sub->toSql()})"), '=', $date)
                    ->mergeBindings($sub->getQuery());
    }
}
