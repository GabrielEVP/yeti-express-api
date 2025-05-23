<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'date',
        'status',
        'currency',
        'payment_type',
        'total',
        'comision',
        'notes',
        'client_id',
        'client_address_id',
        'courier_id',
        'open_box_id',
        'close_box_id',
        'user_id',
    ];

    public $timestamps = true;

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

    public function lines()
    {
        return $this->hasMany(DeliveryLine::class);
    }

    public function receipt()
    {
        return $this->hasOne(DeliveryReceipt::class);
    }

    public function events()
    {
        return $this->hasMany(DeliveryEvent::class)->latest()->limit(7);
    }
}