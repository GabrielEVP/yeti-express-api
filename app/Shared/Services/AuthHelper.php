<?php

namespace App\Shared\Services;

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
}