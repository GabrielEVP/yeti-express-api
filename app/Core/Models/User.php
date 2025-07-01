<?php

namespace App\Core\Models;

class User extends \App\Auth\Models\User
{
    // This class extends the new User model to maintain backward compatibility
    // with code that still references the old namespace
}
