<?php

namespace App\Shared\Providers;

use App\Employee\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AuthExtensionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Extend Auth facade to provide user_id method that works with both User and Employee
        Auth::macro('userId', function () {
            $user = Auth::user();
            
            if ($user instanceof Employee) {
                return $user->user_id;
            }
            
            return $user ? $user->id : null;
        });
    }

    public function register(): void
    {
        //
    }
}