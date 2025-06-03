<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientDebtPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'amount',
        'method',
        'client_delivery_debt_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'float',
    ];

    public function clientDeliveryDebt()
    {
        return $this->belongsTo(ClientDeliveryDebt::class);
    }
}