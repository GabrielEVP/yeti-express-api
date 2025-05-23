<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployerEvent extends Model
{
    use HasFactory;

    protected $table = 'employer_events';

    protected $fillable = [
        'event',
        'reference_table',
        'reference_id',
        'employer_id',
    ];

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }
}
