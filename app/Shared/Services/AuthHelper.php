<?php

namespace App\Shared\Services;

use App\Auth\Models\User;
use App\Employee\Models\Employee;
use Illuminate\Support\Facades\Auth;

class AuthHelper
{
    /**
     * Get the user_id for filtering data based on the authenticated user type
     * 
     * @return int|null
     */
    public static function getUserId(): ?int
    {
        $user = Auth::user();
        
        if ($user instanceof Employee) {
            return $user->user_id;
        }
        
        return $user ? $user->id : null;
    }

    /**
     * Get the actual User model (not Employee) for relationship access
     * 
     * @return User|null
     */
    public static function getActualUser(): ?User
    {
        $authUser = Auth::user();
        
        if ($authUser instanceof Employee) {
            return $authUser->user;
        }
        
        return $authUser;
    }
}