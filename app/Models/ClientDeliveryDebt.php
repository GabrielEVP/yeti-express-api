<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientDeliveryDebt extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'status',
        'client_id',
        'delivery_id',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function payments()
    {
        return $this->hasMany(ClientDebtPayment::class);
    }


    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }
}
