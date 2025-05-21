<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryReceipt extends Model
{
    use HasFactory;
    protected $table = 'delivery_receipt';

    protected $fillable = [
        'full_name',
        'phone',
        'address',
        'state',
        'city',
        'municipality',
        'postal_code',
        'delevery_id',
    ];

    public $timestamps = false;

    protected $casts = [
        'received_at' => 'datetime'
    ];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }
}