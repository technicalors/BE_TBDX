<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait API
{
    protected function success($data = [], $message = 'success', $status = 200)
    {
        return response([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $status);
    }

    protected function failure($data = [], $message = 'failed', $status = 200)
    {
        return response([
            'success' => false,
            'data' => $data,
            'message' => $message,
        ], $status);
    }
}
