<?php

namespace App\Http\Middleware;

use App\Models\CustomUser;
use Closure;
use Exception;
use Illuminate\Http\Request;
use App\Models\RequestLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LogRequest
{
    public function handle(Request $request, Closure $next)
    {
        
        $response = $next($request);
        if(!auth()->user()){
            return $response;
        }
        $payload = $request->all();
        array_walk_recursive($payload, function (&$item) {
            if (is_string($item) && !mb_check_encoding($item, 'UTF-8')) {
                $item = 'truncated';
            }
        });
        $middleware = optional($request->route())->middleware();
        if(is_array($middleware)){
            $middleware = implode(', ', $middleware);
        }
        $uri = $request->getRequestUri();
        if (str_contains($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }
        $controllerAction = optional($request->route())->getActionName() ?? 'Closure';
        $controllerAction = str_replace('App\Http\Controllers\\', '', $controllerAction);
        $logData = [
            'ip_address' => $request->ip(),
            'uri' => $uri,
            'method' => $request->getMethod(),
            'controller_action' => $controllerAction,
            'middleware' => $middleware,
            'headers' => json_encode($request->headers->all()),
            'payload' => json_encode($payload),
            'response_status' => $response->status(),
            'duration' => microtime(true) - LARAVEL_START,
            'memory' => memory_get_usage(),
            'requested_by' => optional(auth()->user())->id,
            'response' => $response->getContent()
        ];
        try {
            RequestLog::query()->create($logData);
            $user = CustomUser::find(auth()->user()->id ?? "");
            if($user && $user->last_use_at){
                $now = Carbon::now();
                $diff = $now->diffInSeconds($user->last_use_at);
                $user->update(['usage_time_in_day'=>$user->usage_time_in_day + $diff, 'last_use_at' => $now]);
            }
            
        } catch (Exception $e) {
            Log::error('Failed to log request at ' . now()->toDateTimeString() . ' with error: ' . $e->getMessage());
        }
        return $response;
    }
}