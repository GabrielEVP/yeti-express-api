<?php

namespace App\Employee\Providers;

use App\Employee\Repositories\IEmployeeRepository;
use App\Employee\Services\EmployeeService;
use Illuminate\Support\ServiceProvider;

class EmployeeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Employee/database/migrations'));
        $this->loadRoutesFrom(app_path('Employee/Routes/EmployeeRoutes.php'));
    }

    public function register(): void
    {
        $this->app->bind(
            IEmployeeRepository::class,
            EmployeeService::class
        );
    }
}
