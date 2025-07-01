<?php

namespace App\Debt\Services;

use App\Client\Models\Client;
use App\Debt\DTO\ClientPaymentRequestDTO;
use App\Debt\DTO\DebtPaymentCollectionDTO;
use App\Debt\DTO\DebtPaymentDTO;
use App\Debt\DTO\FullPaymentRequestDTO;
use App\Debt\DTO\PartialPaymentRequestDTO;
use App\Debt\Models\Debt;
use App\Debt\Repositories\IDebtPaymentRepository;
use Illuminate\Support\Facades\Auth;

class DebtPaymentService implements IDebtPaymentRepository
{
    public function getAll(): DebtPaymentCollectionDTO
    {
        $payments = Auth::user()->debtPayments()->with('debt')->get();
        return DebtPaymentCollectionDTO::fromCollection($payments);
    }

    public function storeFullPayment(FullPaymentRequestDTO $request): DebtPaymentDTO
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

    public function storePartialPayment(PartialPaymentRequestDTO $request): DebtPaymentDTO
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

    public function payAllDebtsForClient(ClientPaymentRequestDTO $request): DebtPaymentCollectionDTO
    {
        $client = Client::findOrFail($request->clientId);

        $debts = $client->debts()->where('status', '!=', 'paid')->get();

        $payments = [];

        foreach ($debts as $debt) {
            $payment = $debt->payments()->create([
                'date' => now(),
                'amount' => $debt->amount,
                'method' => $request->method,
                'user_id' => Auth::id(),
            ]);

            $this->updateDebtStatus($debt);
            $payments[] = $payment;
        }

        return DebtPaymentCollectionDTO::fromArray($payments);
    }

    public function payPartialAmountForClient(ClientPaymentRequestDTO $request): DebtPaymentCollectionDTO
    {
        if ($request->amount <= 0) {
            throw new \InvalidArgumentException('El monto debe ser mayor a cero.');
        }

        $client = Client::findOrFail($request->clientId);

        $debts = $client->debts()
            ->where('status', '!=', 'paid')
            ->orderBy('created_at')
            ->get();

        $payments = [];
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
            $payments[] = $payment;

            $remainingAmount -= $paymentAmount;

            if ($remainingAmount <= 0) {
                break;
            }
        }

        return DebtPaymentCollectionDTO::fromArray($payments);
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

