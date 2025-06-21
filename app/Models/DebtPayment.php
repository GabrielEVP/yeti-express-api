<?php

namespace App\Models;

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
        'date' => 'date',
        'amount' => 'float',
    ];

    public function debt()
    {
        return $this->belongsTo(Debt::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}