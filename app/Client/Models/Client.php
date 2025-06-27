<?php

namespace App\Client\Models;

use App\Delivery\Models\Delivery;
use App\Models\Debt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        "legal_name",
        "type",
        "registration_number",
        "notes",
        "allow_credit",
        "user_id",
    ];

    protected $casts = [
        'type' => Type::class,
        'allow_credit' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(ClientEvent::class);
    }

    public function addresses()
    {
        return $this->hasMany(ClientAddress::class);
    }

    public function phones()
    {
        return $this->hasMany(ClientPhone::class);
    }

    public function emails()
    {
        return $this->hasMany(ClientEmail::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function Debts()
    {
        return $this->hasMany(Debt::class);
    }
}
