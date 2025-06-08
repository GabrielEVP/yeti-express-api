<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryRequest;
use App\Http\Requests\DeliveryStatusRequest;
use App\Models\Delivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DeliveryController extends Controller
{
    private array $relations = [
        'events',
        'service',
        'service.bills',
        'client',
        'clientAddress',
        'debt',
        'debt.payments',
        'courier',
        'receipt',
    ];

    public function index(): JsonResponse
    {
        $deliveries = Auth::user()
            ->deliveries()
            ->with($this->relations)
            ->get();

        return response()->json($deliveries, 200);
    }

    public function store(DeliveryRequest $request): JsonResponse
    {
        $user = Auth::user();

        $data = $request->safe()->except('receipt');
        $data['user_id'] = $user->id;
        $data['date'] = now()->toDateString();
        $data['number'] = $this->generateDeliveryNumber();

        $delivery = $user->deliveries()->create($data);

        $this->syncRelatedReceipt($delivery, $request->input('receipt'));

        if ($delivery->payment_type === 'partial') {
            $delivery->debt()->create([
                'amount' => $delivery->service->amount,
                'status' => 'pending',
                'client_id' => $delivery->client_id,
                'delivery_id' => $delivery->id,
            ]);
        }

        return response()->json($delivery->load($this->relations), 200);
    }

    public function show(Delivery $delivery): JsonResponse
    {
        $this->authorizeOwner($delivery);

        return response()->json($delivery->load($this->relations), 200);
    }

    public function update(DeliveryRequest $request, Delivery $delivery): JsonResponse
    {
        $this->authorizeOwner($delivery);

        $data = $request->safe()->except('receipt');
        $data['user_id'] = Auth::id();

        $delivery->update($data);

        if ($request->has('receipt')) {
            $this->syncRelatedReceipt($delivery, $request->input('receipt'));
        }

        return response()->json($delivery->load($this->relations), 200);
    }

    public function destroy(Delivery $delivery): JsonResponse
    {
        $this->authorizeOwner($delivery);

        $delivery->delete();

        return response()->json([
            'message' => "Delivery with ID {$delivery->id} has been deleted",
        ], 200);
    }

    public function updateStatus(DeliveryStatusRequest $request, Delivery $delivery): JsonResponse
    {
        $this->authorizeOwner($delivery);

        $delivery->update([
            'status' => $request->input('status'),
        ]);

        return response()->json($delivery->load($this->relations), 200);
    }

    private function generateDeliveryNumber(): string
    {
        $lastId = Delivery::max('id') ?? 0;

        return 'DEV-' . str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
    }

    private function syncRelatedReceipt(Delivery $delivery, ?array $receipt): void
    {
        if (is_null($receipt)) {
            $delivery->receipt()->delete();
            return;
        }

        if ($delivery->receipt) {
            $delivery->receipt()->update(array_merge($receipt, ['delivery_id' => $delivery->id]));
        } else {
            $delivery->receipt()->create(array_merge($receipt, ['delivery_id' => $delivery->id]));
        }
    }

    private function authorizeOwner(Delivery $delivery): void
    {
        abort_if($delivery->user_id !== Auth::id(), 403, 'No tienes permiso para acceder a esta entrega.');
    }

    public function getReceived(): JsonResponse
    {
        $deliveries = Auth::user()
            ->deliveries()
            ->received()
            ->with($this->relations)
            ->get();

        return response()->json($deliveries, 200);
    }

    public function getCancelled(): JsonResponse
    {
        $deliveries = Auth::user()
            ->deliveries()
            ->cancelled()
            ->with($this->relations)
            ->get();

        return response()->json($deliveries, 200);
    }

    public function getPending(): JsonResponse
    {
        $deliveries = Auth::user()
            ->deliveries()
            ->pending()
            ->with($this->relations)
            ->get();

        return response()->json($deliveries, 200);
    }

    public function getInTransit(): JsonResponse
    {
        $deliveries = Auth::user()
            ->deliveries()
            ->inTransit()
            ->with($this->relations)
            ->get();

        return response()->json($deliveries, 200);
    }

    public function getPaymentPending(): JsonResponse
    {
        $deliveries = Auth::user()
            ->deliveries()
            ->paymentPending()
            ->with($this->relations)
            ->get();

        return response()->json($deliveries, 200);
    }

    public function getPartiallyPaid(): JsonResponse
    {
        $deliveries = Auth::user()
            ->deliveries()
            ->partiallyPaid()
            ->with($this->relations)
            ->get();

        return response()->json($deliveries, 200);
    }

    public function getPaid(): JsonResponse
    {
        $deliveries = Auth::user()
            ->deliveries()
            ->paid()
            ->with($this->relations)
            ->get();

        return response()->json($deliveries, 200);
    }
}
