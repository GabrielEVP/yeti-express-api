<?php

namespace App\Service\Providers;

use App\Service\Repositories\IServiceRepository;
use App\Service\Services\Services;
use Illuminate\Support\ServiceProvider;

class ServicesProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Service/database/migrations'));
        $this->loadRoutesFrom(app_path('Service/Routes/ServiceRoutes.php'));
    }

    public function register(): void
    {
        $this->app->bind(
            IServiceRepository::class,
            Services::class
        );
    }
}

