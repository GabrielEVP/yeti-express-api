<?php

namespace App\Client\Providers;

use App\Client\Repositories\IClientRepository;
use App\Client\Services\ClientService;
use Illuminate\Support\ServiceProvider;

class ClientServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Client/Database/migrations'));
        $this->loadRoutesFrom(app_path('Client/Routes/ClientRoutes.php'));
    }

    public function register(): void
    {
        $this->app->bind(
            IClientRepository::class,
            ClientService::class
        );
    }
}
