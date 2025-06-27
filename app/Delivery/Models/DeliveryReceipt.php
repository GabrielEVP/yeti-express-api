<?php

namespace App\Delivery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryReceipt extends Model
{
    use HasFactory;

    protected $table = "delivery_receipt";

    protected $fillable = [
        "full_name",
        "phone",
        "address",
        "delivery_id"
    ];

    public $timestamps = false;

    protected $casts = [
        "received_at" => "datetime",
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }
}
