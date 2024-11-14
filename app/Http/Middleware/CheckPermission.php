<?php

namespace App\Http\Middleware;

use App\Traits\API;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckPermission
{
    use API;
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        if ($request->user()) {
            $user = $request->user();
            // Kiểm tra xem người dùng có ít nhất một trong các quyền yêu cầu
            foreach ($permissions as $permission) {
                // Log::debug($user->hasPermission($permission));
                if ($user->hasPermission($permission)) {
                    return $next($request);
                }
            }
        }
        return $this->failure('', 'Bạn không có quyền thực hiện thao tác này');
    }
}
