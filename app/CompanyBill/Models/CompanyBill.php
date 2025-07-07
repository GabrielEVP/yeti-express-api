<?php

namespace App\CompanyBill\Models;

use App\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyBill extends Model
{
    use HasFactory;

    protected $fillable = [
        "date",
        "name",
        "description",
        "method",
        "amount",
        "user_id",
    ];

    protected $casts = [
        "date" => "date:Y-m-d",
        "amount" => "decimal:2",
        'method' => Method::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
