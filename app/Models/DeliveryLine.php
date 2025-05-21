<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'quantity',
        'unit_price',
        'total',
        'delivery_id',
    ];

    public $timestamps = false;

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }
}