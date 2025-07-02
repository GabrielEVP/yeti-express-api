<?php

namespace App\Courier\Models;

use App\Auth\Models\User;
use App\Delivery\Models\Delivery;
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

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries(): \Illuminate\Database\Eloquent\Relations\HasMany|Courier
    {
        return $this->hasMany(Delivery::class);
    }
}
