<?php

namespace App\Delivery\Models;

use App\Client\Models\Type;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryAnonymousClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'legal_name',
        'type',
        'registration_number',
        'phone',
        'delivery_id',
    ];

    protected $casts = [
        'type' => Type::class,
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }
}
