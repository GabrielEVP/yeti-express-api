<?php

namespace App\Cash\Providers;

use Illuminate\Support\ServiceProvider;

class CashServiceProviders extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(app_path('Cash/Routes/CashRoutes.php.php'));
        $this->loadViewsFrom(app_path('Cash/Resources/views'), 'cash');
    }

    public function register(): void
    {
       
    }
}
