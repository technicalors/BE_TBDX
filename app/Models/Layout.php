<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Layout extends Model
{
    use HasFactory;
    protected $table = "layouts";
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = [
        'id', 'customer_id', 'machine_id', 'layout_id', 'machine_layout_id','toc_do','tg_doi_model',
        'ma_film_1', 'ma_muc_1', 'do_nhot_1', 'vi_tri_film_1', 'al_muc_1', 'al_film_1',
        'ma_film_2', 'ma_muc_2', 'do_nhot_2', 'vi_tri_film_2', 'al_muc_2', 'al_film_2',
        'ma_film_3', 'ma_muc_3', 'do_nhot_3', 'vi_tri_film_3', 'al_muc_3', 'al_film_3',
        'ma_film_4', 'ma_muc_4', 'do_nhot_4', 'vi_tri_film_4', 'al_muc_4', 'al_film_4',
        'ma_film_5', 'ma_muc_5', 'do_nhot_5', 'vi_tri_film_5', 'al_muc_5', 'al_film_5',
        'ma_khuon', 'vt_lo_bat_khuon', 'vt_khuon', 'al_khuon'
    ];
}
