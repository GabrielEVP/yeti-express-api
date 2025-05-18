<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'client_address_id',
        'payment_id',
        'prices_id',
        'courier_id',
        'delivery_date',
        'total_amount',
        'comision',
        'open_box_id',
        'close_box_id',
        'status',
        'notes',
        'user_id'
    ];

    public $timestamps = false;

    protected $casts = [
        'delivery_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'comision' => 'decimal:2',
        'status' => 'string'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function clientAddress()
    {
        return $this->belongsTo(ClientAddress::class);
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class, 'payment_id');
    }

    public function priceType()
    {
        return $this->belongsTo(PriceType::class, 'prices_id');
    }

    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }

    public function openBox()
    {
        return $this->belongsTo(Box::class, 'open_box_id');
    }

    public function closeBox()
    {
        return $this->belongsTo(Box::class, 'close_box_id');
    }

    public function items()
    {
        return $this->hasMany(DeliveryItem::class);
    }

    public function recipients()
    {
        return $this->hasMany(DeliveryRecipient::class);
    }
}