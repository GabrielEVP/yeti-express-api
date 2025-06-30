<?php

namespace App\Debt\Services;

use App\Client\Models\Client;
use App\Debt\Models\Debt;
use App\Debt\Models\DebtPayment;
use App\Debt\Repositories\IDebtPaymentRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DebtPaymentService implements IDebtPaymentRepository
{
    public function getAll(): Collection
    {
        return Auth::user()->debtPayments()->with('debt')->get();
    }

    public function storeFullPayment(int $debtId, string $method): DebtPayment
    {
        $debt = Auth::user()->debts()->findOrFail($debtId);

        $payment = $debt->payments()->create([
            'date' => now(),
            'amount' => $debt->amount,
            'method' => $method,
            'user_id' => Auth::id(),
        ]);

        $this->updateDebtStatus($debt);

        return $payment;
    }

    public function storePartialPayment(int $debtId, float $amount, string $method): DebtPayment
    {
        $debt = Auth::user()->debts()->findOrFail($debtId);

        $payment = $debt->payments()->create([
            'date' => now(),
            'amount' => $amount,
            'method' => $method,
            'user_id' => Auth::id(),
        ]);

        $this->updateDebtStatus($debt);

        return $payment;
    }

    public function payAllDebtsForClient(int $clientId, string $method): array
    {
        $client = Client::findOrFail($clientId);

        $debts = $client->debts()->where('status', '!=', 'paid')->get();

        $payments = [];

        foreach ($debts as $debt) {
            $payment = $debt->payments()->create([
                'date' => now(),
                'amount' => $debt->amount,
                'method' => $method,
                'user_id' => Auth::id(),
            ]);

            $this->updateDebtStatus($debt);
            $payments[] = $payment;
        }

        return $payments;
    }

    public function payPartialAmountForClient(int $clientId, float $amount, string $method): array
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('El monto debe ser mayor a cero.');
        }

        $client = Client::findOrFail($clientId);

        $debts = $client->debts()
            ->where('status', '!=', 'paid')
            ->orderBy('created_at')
            ->get();

        $payments = [];

        foreach ($debts as $debt) {
            $remaining = $debt->amount - $debt->payments()->sum('amount');

            if ($remaining <= 0) {
                continue;
            }

            $paymentAmount = min($remaining, $amount);

            $payment = $debt->payments()->create([
                'date' => now(),
                'amount' => $paymentAmount,
                'method' => $method,
                'user_id' => Auth::id(),
            ]);

            $this->updateDebtStatus($debt);
            $payments[] = $payment;

            $amount -= $paymentAmount;

            if ($amount <= 0) {
                break;
            }
        }

        return $payments;
    }

    public function updateDebtStatus(Debt $debt): void
    {
        $totalPaid = $debt->payments()->sum('amount');
        $debtAmount = $debt->amount;

        if ($totalPaid >= $debtAmount) {
            if ($totalPaid > $debtAmount) {
                $this->adjustPaymentsForOverpayment($debt, $debtAmount);
            }
            $debt->status = 'paid';
            if ($debt->delivery) {
                $debt->delivery->update(['payment_status' => 'paid']);
            }
        } elseif ($totalPaid > 0) {
            $debt->status = 'partial_paid';
            if ($debt->delivery) {
                $debt->delivery->update(['payment_status' => 'partial_paid']);
            }
        } else {
            $debt->status = 'pending';
            if ($debt->delivery) {
                $debt->delivery->update(['payment_status' => 'pending']);
            }
        }

        $debt->save();
    }

    private function adjustPaymentsForOverpayment(Debt $debt, float $debtAmount): void
    {
        $payments = $debt->payments()->orderBy('date', 'asc')->get();
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
}

