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
        $ttl = 10;

        if (!Cache::add($key, true, $ttl)) {
            return $this->failure([], 'Bản ghi trùng lặp');
        }

        try {
            return $next($request);
        } finally {
            Cache::forget($key);
        }
    }

    protected function getRequestCacheKey(Request $request)
    {
        $payload = $request->all();
        $payload['_files'] = $this->normalizeUploadedFiles($request->allFiles());
        $this->ksortRecursive($payload);

        $base = [
            'method' => $request->method(),
            'path' => $request->path(),
            'user_id' => optional($request->user())->id,
            'payload' => $payload,
        ];

        $hashValue = json_encode($base, JSON_UNESCAPED_UNICODE);
        if ($hashValue === false) {
            $hashValue = serialize($base);
        }

        return 'dedupe:' . sha1($hashValue);
    }

    protected function normalizeUploadedFiles(array $files)
    {
        $normalized = [];

        foreach ($files as $key => $file) {
            if (is_array($file)) {
                $normalized[$key] = $this->normalizeUploadedFiles($file);
            } elseif ($file) {
                $normalized[$key] = [
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime' => $file->getClientMimeType(),
                ];
            }
        }

        return $normalized;
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
