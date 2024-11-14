<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use App\Traits\API;

class PreventDuplicateRequests
{
    use API;
    public function handle(Request $request, Closure $next)
    {
        $key = $this->getRequestCacheKey($request);
        if (Cache::has($key)) {
            return $this->failure('Bản ghi trùng lặp');
        }
        Cache::put($key, true, 10); // Cache trong 10 giây
        return $next($request);
    }
    protected function getRequestCacheKey($request)
    {
        $route = $request->path();
        $body = json_encode($request->all());
        return $route . $body;
    }
}
