<?php

namespace App\Service\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bill extends Model
{
    protected $fillable = [
        "service_id",
        "name",
        "amount"
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
