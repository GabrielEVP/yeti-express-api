<?php

namespace App\Core\Providers;

use Illuminate\Support\ServiceProvider;

class CoreServiceProviders extends ServiceProvider
{
    public function register(): void
    {
        $this->loadMigrationsFrom(app_path('Core/Database/migrations'));

    }
}
