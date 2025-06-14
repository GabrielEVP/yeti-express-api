<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DebtFullPaymentRequest;
use App\Http\Requests\DebtPartialPaymentRequest;
use App\Models\DebtPayment;
use App\Models\Debt;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DebtPaymentController extends Controller
{
    public function index(): JsonResponse
    {
        $payments = DebtPayment::where("user_id", Auth::id())
            ->with("debt")
            ->get();
        return response()->json($payments, 200);
    }

    public function storeFullPayment(DebtFullPaymentRequest $request): JsonResponse
    {
        $debt = Debt::findOrFail($request->input("debt_id"));
        $this->authorizeOwner($debt);

        $payment = $debt->payments()->create([
            "date" => now(),
            "amount" => $debt->amount,
            "method" => $request->input("method"),
            "user_id" => Auth::id(),
        ]);

        $debt->updateStatusBasedOnPayments();

        return response()->json($payment, 201);
    }

    public function storePartialPayment(DebtPartialPaymentRequest $request): JsonResponse
    {
        $debt = Debt::findOrFail($request->input("debt_id"));
        $this->authorizeOwner($debt);

        $payment = $debt->payments()->create([
            "date" => now(),
            "amount" => $request->input("amount"),
            "method" => $request->input("method"),
            "user_id" => Auth::id(),
        ]);

        $debt->updateStatusBasedOnPayments();

        return response()->json($payment, 201);
    }

    public function payAllDebts(Request $request): JsonResponse
    {
        $payData = $request->input('pay');
        $clientId = $payData['clientId'] ?? null;
        $method = $payData['method'] ?? null;

        $client = Client::findOrFail($clientId);
        $this->authorizeClientOwner($client);

        $debts = $client->debts()->where("status", "!=", "paid")->get();

        $payments = [];

        foreach ($debts as $debt) {
            $payment = $debt->payments()->create([
                "date" => now(),
                "amount" => $debt->amount,
                "method" => $method,
                "user_id" => Auth::id(),
            ]);

            $debt->updateStatusBasedOnPayments();
            $payments[] = $payment;
        }

        return response()->json($payments, 201);
    }

    public function payPartialAmount(Request $request): JsonResponse
    {
        $payData = $request->input('pay');
        $clientId = $payData['clientId'] ?? null;
        $method = $payData['method'] ?? null;
        $totalAmount = $payData['amount'] ?? null;

        if ($totalAmount <= 0) {
            return response()->json(
                ["message" => "El monto debe ser mayor a cero."],
                422
            );
        }

        $client = Client::findOrFail($clientId);
        $this->authorizeClientOwner($client);

        $debts = $client
            ->debts()
            ->where("status", "!=", "paid")
            ->orderBy("created_at")
            ->get();

        $payments = [];

        foreach ($debts as $debt) {
            $remaining = $debt->amount - $debt->payments()->sum("amount");

            if ($remaining <= 0) {
                continue;
            }

            $paymentAmount = min($remaining, $totalAmount);

            $payment = $debt->payments()->create([
                "date" => now(),
                "amount" => $paymentAmount,
                "method" => $method,
                "user_id" => Auth::id(),
            ]);

            $debt->updateStatusBasedOnPayments();
            $payments[] = $payment;

            $totalAmount -= $paymentAmount;

            if ($totalAmount <= 0) {
                break;
            }
        }

        return response()->json($payments, 201);
    }

    private function authorizeClientOwner(Client $client): void
    {
        abort_if(
            $client->user_id !== Auth::id(),
            403,
            "No tienes permiso para acceder a este cliente."
        );
    }

    public function show(DebtPayment $debtPayment): JsonResponse
    {
        $this->authorizeOwner($debtPayment->debt);
        return response()->json($debtPayment->load("debt"), 200);
    }

    private function authorizeOwner(Debt $debt): void
    {
        abort_if(
            $debt->user_id !== Auth::id(),
            403,
            "No tienes permiso para acceder a esta deuda."
        );
    }
}
