<?php

namespace App\Delivery\Models;

use App\Client\Models\Client;
use App\Courier\Models\Courier;
use App\Models\Debt;
use App\Models\User;
use App\Service\Models\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        "number",
        "date",
        "status",
        "payment_type",
        "payment_status",
        "amount",
        "pickup_address",
        "cancellation_notes",
        "notes",
        "service_id",
        "client_id",
        "courier_id",
        "user_id",
    ];

    protected $casts = [
        'date' => 'date',
        'status' => Status::class,
        'payment_type' => PaymentType::class,
        'payment_status' => PaymentStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function receipt()
    {
        return $this->hasOne(DeliveryReceipt::class);
    }

    public function events()
    {
        return $this->hasMany(DeliveryEvent::class);
    }

    public function debt()
    {
        return $this->hasOne(Debt::class);
    }
}
