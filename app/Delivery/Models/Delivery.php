<?php

namespace App\Delivery\Models;

use App\Courier\Models\Courier;
use App\Models\Client;
use App\Models\Debt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Service\Models\Service::class, 'service_id');
    }

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function courier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
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
