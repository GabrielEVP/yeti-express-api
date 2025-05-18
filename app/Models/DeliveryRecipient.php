<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_id',
        'full_name',
        'phone',
        'id_number',
        'relationship',
        'received_at',
        'signature_url',
        'user_id'
    ];

    public $timestamps = false;

    protected $casts = [
        'received_at' => 'datetime'
    ];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}