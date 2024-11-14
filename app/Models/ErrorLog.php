<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;
use Illuminate\Support\Facades\Validator;

class ErrorLog extends Model
{
    use HasFactory;
    protected $fillable = ['route', 'input', 'method', 'messages', 'created_by'];
    protected $casts=[
        "message"=>"string",
        "input"=>"string"
    ];
    static function saveError($request, $error){
        $res = new ErrorLog();
        $res->route = $request->path();
        $res->method = $request->method();
        $res->input = json_encode($request->all());
        $res->messages = $error;
        $res->created_by = $request->user()->id ?? "";
        $res->save();
        return $res;
    }
}
