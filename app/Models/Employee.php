<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Employee extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'employees';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'active',
        'user_id',
    ];


    protected $hidden = [
        'password'
    ];

    protected $casts = [
        'active' => 'boolean',
        'role' => 'string',
        'created_at' => 'datetime',
        'password' => 'hashed'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(EmployeeEvent::class);
    }

    public function couriers()
    {
        return $this->user->couriers();
    }

    public function deliveries()
    {
        return $this->user->deliveries();
    }

    public function clients()
    {
        return $this->user->clients();
    }

    public function services()
    {
        return $this->user->services();
    }

    public function companyBills()
    {
        return $this->user->companyBills();
    }
}