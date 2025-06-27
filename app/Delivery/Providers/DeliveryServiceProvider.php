<?php

namespace App\Delivery\Providers;

use App\Delivery\Repositories\IDeliveryRepository;
use App\Delivery\Services\DeliveryService;
use Illuminate\Support\ServiceProvider;

class DeliveryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Delivery/database/migrations'));
        $this->loadRoutesFrom(app_path('Delivery/Routes/DeliveryRoutes.php'));
        $this->loadViewsFrom(app_path('Delivery/Resources/views'), 'Delivery');;
    }

    public function register(): void
    {
        $this->app->bind(
            IDeliveryRepository::class,
            DeliveryService::class
        );
    }
}
