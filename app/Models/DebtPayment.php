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
        'date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'amount' => 'float',
    ];

    /**
     * Método personalizado para formatear la fecha en formato español d/m/Y
     *
     * @return string Fecha formateada
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->date ? $this->date->format('Y-m-d') : '';
    }

    public function debt()
    {
        return $this->belongsTo(Debt::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
