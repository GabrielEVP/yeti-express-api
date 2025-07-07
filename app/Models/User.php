<?php

namespace App\Models;

class User extends \App\Auth\Models\User
{
    // This class extends the Auth User model to maintain compatibility with Laravel Sanctum
    // which expects the User model to be in the App\Models namespace
}
