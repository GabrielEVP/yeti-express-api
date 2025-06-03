<?php

namespace App\Models;

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
}
