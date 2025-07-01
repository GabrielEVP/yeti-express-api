<?php

namespace App\Core\Providers;

use Illuminate\Support\ServiceProvider;

class CoreServiceProviders extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->loadMigrationsFrom(app_path('Core/Database/migrations'));

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
