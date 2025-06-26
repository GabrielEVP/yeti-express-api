<?php

namespace App\Courier\Providers;

use App\Courier\Repositories\ICourierRepository;
use App\Courier\Services\CourierService;
use Illuminate\Support\ServiceProvider;

class CourierServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Courier/database/migrations'));
        $this->loadRoutesFrom(app_path('Courier/Routes/CourierRoutes.php'));
        $this->loadViewsFrom(app_path('Courier/resources/views'), 'courier');
    }

    public function register(): void
    {
        $this->app->bind(
            ICourierRepository::class,
            CourierService::class
        );
    }
}
