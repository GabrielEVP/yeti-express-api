<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        "id",
        "name",
        "description",
        "amount",
        "comision",
        "user_id",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function events()
    {
        return $this->hasMany(ServiceEvent::class);
    }

    public function getTotalEarning(): float
    {
        $totalBills = $this->bills()->sum('amount');
        return $this->amount - $this->comision - $totalBills;
    }
}
