<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'date',
        'status',
        'payment_type',
        'payment_status',
        'notes',
        'service_id',
        'client_id',
        'client_address_id',
        'courier_id',
        'user_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function clientAddress()
    {
        return $this->belongsTo(ClientAddress::class);
    }

    public function courier()
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

    // Scopes para filtrar por estado
    public function scopeReceived(Builder $query): Builder
    {
        return $query->where('status', 'received');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeInTransit(Builder $query): Builder
    {
        return $query->where('status', 'in_transit');
    }

    public function scopePaymentPending(Builder $query): Builder
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopePartiallyPaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'partial_paid');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'paid');
    }

    public static function getReceived(): Collection
    {
        return static::received()->get();
    }

    public static function getCancelled(): Collection
    {
        return static::cancelled()->get();
    }

    public static function getPending(): Collection
    {
        return static::pending()->get();
    }

    public static function getInTransit(): Collection
    {
        return static::inTransit()->get();
    }

    public static function getPaymentPending(): Collection
    {
        return static::paymentPending()->get();
    }

    public static function getPartiallyPaid(): Collection
    {
        return static::partiallyPaid()->get();
    }

    public static function getPaid(): Collection
    {
        return static::paid()->get();
    }
}
