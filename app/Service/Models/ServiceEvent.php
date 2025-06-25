<?php
namespace App\Service\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceEvent extends Model
{
    protected $fillable = [
        'event',
        'section',
        'reference_table',
        'reference_id',
        'service_id',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
