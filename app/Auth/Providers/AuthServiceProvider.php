<?php

namespace App\Auth\Providers;

use App\Auth\Repositories\IAuthRepository;
use App\Auth\Services\AuthService;
use App\Employee\Models\Employee;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        $this->registerPolicies();

        $this->loadMigrationsFrom(app_path('Auth/Database/migrations'));
        $this->loadRoutesFrom(app_path('Auth/Routes/AuthLoginRoutes.php'));
        $this->loadRoutesFrom(app_path('Auth/Routes/AuthRoutes.php'));

        config([
            'auth.guards.employee' => [
                'driver' => 'sanctum',
                'provider' => 'employees',
            ]
        ]);

        config([
            'auth.providers.employees' => [
                'driver' => 'eloquent',
                'model' => Employee::class,
            ]
        ]);

        Gate::before(function ($user, $ability) {
            if ($user instanceof Employee) {
                return true;
            }
        });
    }

    public function register(): void
    {
        $this->app->bind(
            IAuthRepository::class,
            AuthService::class
        );
    }
}
