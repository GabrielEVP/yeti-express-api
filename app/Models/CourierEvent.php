<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierEvent extends Model
{
    use HasFactory;

    protected $table = 'courier_events';

    protected $fillable = [
        'event',
        'reference_table',
        'reference_id',
        'courier_id',
    ];

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }
}

