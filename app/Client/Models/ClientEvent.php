<?php

namespace App\Client\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientEvent extends Model
{
    protected $fillable = [
        'event',
        'section',
        'reference_table',
        'reference_id',
        'client_id',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
