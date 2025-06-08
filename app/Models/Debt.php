<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function payments()
    {
        return $this->hasMany(DebtPayment::class);
    }


    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function updateStatusBasedOnPayments(): void
    {
        $totalPaid = $this->payments()->sum('amount');
        $debtAmount = $this->amount;

        if ($totalPaid >= $debtAmount) {
            if ($totalPaid > $debtAmount) {
                $difference = $totalPaid - $debtAmount;
                $lastPayment = $this->payments()->latest('date')->first();

                if ($lastPayment) {
                    $lastPayment->amount -= $difference;
                    $lastPayment->save();
                }

                $totalPaid = $debtAmount;
            }

            $this->status = 'paid';
            $this->delivery->update(['payment_status' => 'paid']);
        } elseif ($totalPaid > 0) {
            $this->status = 'partial_paid';
            $this->delivery->update(['payment_status' => 'partial_paid']);
        } else {
            $this->status = 'pending';
            $this->delivery->update(['payment_status' => 'pending']);
        }

        $this->save();
    }

}
