<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Broadcast::routes();

        // prefix 'api' nếu bạn đặt routes/api.php có prefix
        Broadcast::routes([
            'middleware' => ['auth:sanctum'], 
            // 'prefix'     => 'api',
        ]);

        require base_path('routes/channels.php');
    }
}
