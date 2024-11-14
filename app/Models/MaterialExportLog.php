<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialExportLog extends Model
{
    use HasFactory;
    protected $table = 'material_export_log';
    protected $fillable = [
        "material_id", "sl_kho_xuat", "sl_thuc_te","status","file"
    ];
}
