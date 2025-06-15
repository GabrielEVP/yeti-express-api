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
        'user_id',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function updateStatusBasedOnPayments(): void
    {
        $totalPaid = $this->payments()->sum('amount');
        $debtAmount = $this->amount;

        if ($totalPaid >= $debtAmount) {
            if ($totalPaid > $debtAmount) {
                $this->adjustPaymentsForOverpayment($debtAmount);
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

    private function adjustPaymentsForOverpayment(float $debtAmount): void
    {
        $payments = $this->payments()->orderBy('date', 'asc')->get();
        $remainingAmount = $debtAmount;

        foreach ($payments as $payment) {
            if ($remainingAmount <= 0) {
                $payment->delete();
            } elseif ($payment->amount > $remainingAmount) {
                $payment->update(['amount' => $remainingAmount]);
                $remainingAmount = 0;
            } else {
                $remainingAmount -= $payment->amount;
            }
        }
    }

    private function adjustPaymentsWithRefund(float $debtAmount): void
    {
        $totalPaid = $this->payments()->sum('amount');
        $overpayment = $totalPaid - $debtAmount;

        if ($overpayment > 0) {
            $this->payments()->create([
                'amount' => -$overpayment,
                'date' => now(),
                'payment_method' => 'refund',
                'notes' => 'Reembolso por sobrepago de ' . number_format($overpayment, 2)
            ]);
        }
    }

    public function updateStatusBasedOnPaymentsRobust(): void
    {
        $payments = $this->payments()->orderBy('date', 'asc')->get();
        $debtAmount = $this->amount;
        $totalValidPayments = 0;
        $remainingDebt = $debtAmount;

        foreach ($payments as $payment) {
            if ($remainingDebt <= 0) {
                $payment->update([
                    'amount' => 0,
                    'notes' => ($payment->notes ?? '') . ' [Ajustado: sobrepago]'
                ]);
            } elseif ($payment->amount > $remainingDebt) {
                $originalAmount = $payment->amount;
                $payment->update([
                    'amount' => $remainingDebt,
                    'notes' => ($payment->notes ?? '') . " [Ajustado: de {$originalAmount} a {$remainingDebt}]"
                ]);
                $totalValidPayments += $remainingDebt;
                $remainingDebt = 0;
            } else {
                $totalValidPayments += $payment->amount;
                $remainingDebt -= $payment->amount;
            }
        }

        if ($totalValidPayments >= $debtAmount) {
            $this->status = 'paid';
            $this->delivery->update(['payment_status' => 'paid']);
        } elseif ($totalValidPayments > 0) {
            $this->status = 'partial_paid';
            $this->delivery->update(['payment_status' => 'partial_paid']);
        } else {
            $this->status = 'pending';
            $this->delivery->update(['payment_status' => 'pending']);
        }

        $this->save();
    }
}
