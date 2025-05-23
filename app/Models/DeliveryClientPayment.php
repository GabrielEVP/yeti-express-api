<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryClientPayment extends Model
{
    use HasFactory;

    protected $table = 'delivery_client_payments';

    protected $fillable = [
        'date',
        'method',
        'amount',
        'delivery_id',
        'user_id',
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

