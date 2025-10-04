<?php

namespace App\Debt\Services;

use App\Client\Models\Client;
use App\Debt\DTO\DebtPaymentDTO;
use App\Debt\DTO\FormRequestFullPaymentDTO;
use App\Debt\DTO\FormRequestPartialPaymentDTO;
use App\Debt\DTO\FormRequestPayAllDTO;
use App\Debt\DTO\FormRequestPayPartialDTO;
use App\Debt\Models\Debt;
use App\Debt\Repositories\IDebtPaymentRepository;
use App\Shared\Services\AuthHelper;
use App\Shared\Services\EmployeeEventService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class DebtPaymentService implements IDebtPaymentRepository
{
    public function getAll(): Collection
    {
        return AuthHelper::getActualUser()->debtPayments()->get();
    }

    public function storeFullPayment(FormRequestFullPaymentDTO $request): DebtPaymentDTO
    {
        $debt = AuthHelper::getActualUser()->debts()->findOrFail($request->debt_id);

        $payment = $debt->payments()->create([
            'date' => now(),
            'amount' => $debt->amount,
            'method' => $request->method,
            'user_id' => AuthHelper::getUserId(),
        ]);

        $this->updateDebtStatus($debt);

        EmployeeEventService::log(
            'full_debt_payment',
            'debts',
            'debt_payments',
            (int)$payment->id,
            'Full payment made for debt ID: ' . $debt->id . ' with amount: ' . $debt->amount
        );

        return DebtPaymentDTO::fromModel($payment);
    }

    public function storePartialPayment(FormRequestPartialPaymentDTO $request): DebtPaymentDTO
    {
        $debt = AuthHelper::getActualUser()->debts()->findOrFail($request->debt_id);

        $payment = $debt->payments()->create([
            'date' => now(),
            'amount' => $request->amount,
            'method' => $request->method,
            'user_id' => AuthHelper::getUserId(),
        ]);

        $this->updateDebtStatus($debt);

        EmployeeEventService::log(
            'partial_debt_payment',
            'debts',
            'debt_payments',
            (int)$payment->id,
            'Partial payment made for debt ID: ' . $debt->id . ' with amount: ' . $request->amount
        );

        return DebtPaymentDTO::fromModel($payment);
    }

    public function payAllDebtsForClient(FormRequestPayAllDTO $request): void
    {
        $client = Client::findOrFail($request->client_id);

        $debts = $client->debts()->where('status', '!=', 'paid')->get();
        $totalAmount = 0;

        foreach ($debts as $debt) {
            $payment = $debt->payments()->create([
                'date' => now(),
                'amount' => $debt->amount,
                'method' => $request->method,
                'user_id' => AuthHelper::getUserId(),
            ]);

            $totalAmount += $debt->amount;
            $this->updateDebtStatus($debt);
        }

        if ($debts->count() > 0) {
            EmployeeEventService::log(
                'pay_all_client_debts',
                'debts',
                'clients',
                (int)$client->id,
                'All debts paid for client: ' . $client->legal_name . ' with total amount: ' . $totalAmount
            );
        }
    }

    public function payPartialAmountForClient(FormRequestPayPartialDTO $request): void
    {
        $client = Client::findOrFail($request->client_id);

        $debts = $client->debts()
            ->where('status', '!=', 'paid')
            ->orderBy('created_at')
            ->get();

        $remainingAmount = $request->amount;
        $totalPaid = 0;
        $debtsPaid = 0;

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
                'user_id' => AuthHelper::getUserId(),
            ]);

            $this->updateDebtStatus($debt);
            $totalPaid += $paymentAmount;
            $debtsPaid++;

            $remainingAmount -= $paymentAmount;

            if ($remainingAmount <= 0) {
                break;
            }
        }

        if ($debtsPaid > 0) {
            EmployeeEventService::log(
                'pay_partial_client_debts',
                'debts',
                'clients',
                (int)$client->id,
                'Partial payment of ' . $totalPaid . ' made for client: ' . $client->legal_name . ' across ' . $debtsPaid . ' debts'
            );
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

