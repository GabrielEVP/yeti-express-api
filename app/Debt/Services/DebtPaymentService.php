<?php

namespace App\Debt\Services;

use App\Client\Models\Client;
use App\Debt\DTO\DebtPaymentDTO;
use App\Debt\DTO\FormRequestPayAllDTO;
use App\Debt\DTO\FormRequestPayPartialDTO;
use App\Debt\Models\Debt;
use App\Debt\Repositories\IDebtPaymentRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class DebtPaymentService implements IDebtPaymentRepository
{
    public function getAll(): Collection
    {
        return Auth::user()->debtPayments()->get();
    }

    public function storeFullPayment(FormRequestPayAllDTO $request): DebtPaymentDTO
    {
        $debt = Auth::user()->debts()->findOrFail($request->debt_id);

        $payment = $debt->payments()->create([
            'date' => now(),
            'amount' => $debt->amount,
            'method' => $request->method,
            'user_id' => Auth::id(),
        ]);

        $this->updateDebtStatus($debt);

        return DebtPaymentDTO::fromModel($payment);
    }

    public function storePartialPayment(FormRequestPayPartialDTO $request): DebtPaymentDTO
    {
        $debt = Auth::user()->debts()->findOrFail($request->debt_id);

        $payment = $debt->payments()->create([
            'date' => now(),
            'amount' => $request->amount,
            'method' => $request->method,
            'user_id' => Auth::id(),
        ]);

        $this->updateDebtStatus($debt);

        return DebtPaymentDTO::fromModel($payment);
    }

    public function payAllDebtsForClient(FormRequestPayAllDTO $request): void
    {
        $client = Client::findOrFail($request->clientId);

        $debts = $client->debts()->where('status', '!=', 'paid')->get();


        foreach ($debts as $debt) {
            $payment = $debt->payments()->create([
                'date' => now(),
                'amount' => $debt->amount,
                'method' => $request->method,
                'user_id' => Auth::id(),
            ]);

            $this->updateDebtStatus($debt);
        }
    }

    public function payPartialAmountForClient(FormRequestPayPartialDTO $request): void
    {
        $client = Client::findOrFail($request->clientId);

        $debts = $client->debts()
            ->where('status', '!=', 'paid')
            ->orderBy('created_at')
            ->get();

        $remainingAmount = $request->amount;

        foreach ($debts as $debt) {
            $debtRemaining = $debt->amount - $debt->payments()->sum('amount');

            if ($debtRemaining <= 0) {
                continue;
            }

            $paymentAmount = min($debtRemaining, $remainingAmount);

            $payment = $debt->payments()->create([
                'date' => now(),
                'amount' => $paymentAmount,
                'method' => $request->method,
                'user_id' => Auth::id(),
            ]);

            $this->updateDebtStatus($debt);

            $remainingAmount -= $paymentAmount;

            if ($remainingAmount <= 0) {
                break;
            }
        }
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

