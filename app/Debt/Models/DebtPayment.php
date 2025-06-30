<?php

namespace App\Debt\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebtPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'amount',
        'method',
        'notes',
        'debt_id',
        'user_id',
    ];

    protected $casts = [
        'date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'amount' => 'float',
    ];

    public function debt(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
