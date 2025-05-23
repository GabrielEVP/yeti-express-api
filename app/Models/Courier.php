<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Courier extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'commission',
        'active',
        'user_id'
    ];

    public $timestamps = true;

    protected $casts = [
        'commission' => 'decimal:2',
        'active' => 'boolean'
    ];

    public function events()
    {
        return $this->hasMany(CourierEvent::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }
}