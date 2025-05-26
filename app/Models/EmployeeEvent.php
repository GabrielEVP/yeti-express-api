<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeEvent extends Model
{
    use HasFactory;

    protected $table = 'employee_events';

    protected $fillable = [
        'event',
        'section',
        'reference_table',
        'reference_id',
        'employee_id',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
