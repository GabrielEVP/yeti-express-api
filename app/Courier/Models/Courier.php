<?php

namespace App\Courier\Models;

use App\Delivery\Models\Delivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Courier extends Model
{
    use HasFactory;

    protected $fillable = [
        "first_name",
        "last_name",
        "phone",
        "commission",
        "active",
        "user_id",
    ];

    public $timestamps = true;

    protected $casts = [
        "commission" => "decimal:2",
        "active" => "boolean",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(CourierEvent::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }
}
