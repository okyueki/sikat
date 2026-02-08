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
        // Web (session) dan API (Sanctum token) bisa auth untuk private channel
        Broadcast::routes(['middleware' => ['auth:web,sanctum']]);

        require base_path('routes/channels.php');
    }
}
