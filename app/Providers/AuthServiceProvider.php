<?php

namespace App\Providers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Definir el guard para empleados
        config([
            'auth.guards.employee' => [
                'driver' => 'sanctum',
                'provider' => 'employees',
            ]
        ]);

        // Definir el provider para empleados
        config([
            'auth.providers.employees' => [
                'driver' => 'eloquent',
                'model' => Employee::class,
            ]
        ]);

        // Definir el guard por defecto para rutas de empleados
        Gate::before(function ($user, $ability) {
            if ($user instanceof Employee) {
                return true;
            }
        });
    }
}