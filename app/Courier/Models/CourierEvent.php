<?php

namespace App\Courier\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierEvent extends Model
{
    protected $fillable = [
        'event',
        'section',
        'reference_table',
        'reference_id',
        'courier_id',
    ];

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }
}
