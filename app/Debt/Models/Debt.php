<?php

namespace App\Debt\Models;

use App\Client\Models\Client;
use App\Delivery\Models\Delivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Debt extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'status',
        'client_id',
        'delivery_id',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function payments()
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function delivery(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
