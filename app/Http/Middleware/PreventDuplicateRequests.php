<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Traits\API;

class PreventDuplicateRequests
{
    use API;

    public function handle(Request $request, Closure $next)
    {
        $key = $this->getRequestCacheKey($request);

        // Use atomic add to avoid race conditions when concurrent duplicate requests arrive.
        if (!Cache::add($key, true, now()->addSeconds(10))) {
            return $this->failure([], 'Bản ghi trùng lặp');
        }

        return $next($request);
    }

    protected function getRequestCacheKey(Request $request)
    {
        $payload = $request->all();
        $this->ksortRecursive($payload);

        $base = [
            'method' => $request->method(),
            'path' => $request->path(),
            'user_id' => optional($request->user())->id,
            'payload' => $payload,
        ];

        return 'dedupe:' . sha1(json_encode($base));
    }

    protected function ksortRecursive(array &$array)
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
    }
}
