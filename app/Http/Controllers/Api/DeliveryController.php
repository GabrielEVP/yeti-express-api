<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryRequest;
use App\Http\Requests\DeliveryStatusRequest;
use App\Http\Requests\DeliveryCourierPaymentRequest;
use App\Http\Requests\DeliveryClientPaymentRequest;
use App\Models\Delivery;
use App\Models\DeliveryEvent;
use App\Models\ClientEvent;
use App\Models\CourierEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DeliveryController extends Controller
{
    private array $relations = [
        'client',
        'clientAddress',
        'events',
        'courier',
        'service',
        'openBox',
        'closeBox',
        'receipt',
    ];

    private function getCurrentDate(): string
    {
        return now()->toDateString();
    }

    private function generateDeliveryNumber(): string
    {
        $lastId = Delivery::max('id') ?? 0;
        return 'DEV-' . str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
    }

    public function index(): JsonResponse
    {
        $user = Auth::user();

        $deliveries = $user->deliveries()
            ->with($this->relations)
            ->get();

        return response()->json($deliveries, 200);
    }

    public function store(DeliveryRequest $request): JsonResponse
    {
        $user = Auth::user();
        $data = $request->safe()->except('receipt');

        $data['user_id'] = $user->id;
        $data['date'] = $this->getCurrentDate();
        $data['number'] = $this->generateDeliveryNumber();

        $delivery = $user->deliveries()->create($data);

        $this->syncRelatedReceipt($delivery, $request->input('receipt'));

        $this->logClientEvent('create_delivery', $delivery, 'clients', 'deliveries', $delivery->id);

        return response()->json($delivery->load($this->relations), 201);
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

        $this->logDeliveryEvent('update_delivery', $delivery);

        return response()->json($delivery->load($this->relations), 200);
    }

    public function destroy(Delivery $delivery): JsonResponse
    {
        $this->authorizeOwner($delivery);

        $delivery->delete();

        return response()->json([
            'message' => "Delivery with ID {$delivery->id} has been deleted"
        ], 200);
    }

    public function latestByClient(string $clientId): JsonResponse
    {
        $deliveries = Delivery::with($this->relations)
            ->where('client_id', $clientId)
            ->orderByDesc('date')
            ->get();

        return response()->json($deliveries, 200);
    }

    public function latestByCourier(string $courierId): JsonResponse
    {
        $deliveries = Delivery::with('receipt')
            ->where('courier_id', $courierId)
            ->orderByDesc('date')
            ->get();

        return response()->json($deliveries, 200);
    }

    public function updateStatus(DeliveryStatusRequest $request, Delivery $delivery): JsonResponse
    {
        $this->authorizeOwner($delivery);

        $delivery->update([
            'status' => $request->input('status'),
        ]);

        $this->logDeliveryEvent('status_updated', $delivery, 'deliveries', 'clients', $delivery->id);

        return response()->json($delivery->load($this->relations), 200);
    }

    public function storeClientPayment(DeliveryClientPaymentRequest $request, Delivery $delivery): JsonResponse
    {
        $this->authorizeOwner($delivery);

        $payment = $delivery->clientPayments()->create([
            'date' => $request->input('date'),
            'method' => $request->input('method'),
            'amount' => $request->input('amount'),
            'user_id' => Auth::id(),
        ]);

        $this->logDeliveryEvent('client_payment_added', $delivery, 'deliveries', 'delivery_client_payments', $payment->id);

        $this->logClientEvent('client_payment_added', $delivery, 'clients', 'delivery_client_payments', $payment->id);

        return response()->json($payment, 201);
    }

    public function storeCourierPayment(DeliveryCourierPaymentRequest $request, Delivery $delivery): JsonResponse
    {
        $this->authorizeOwner($delivery);

        $payment = $delivery->courierPayments()->create([
            'date' => $request->input('date'),
            'method' => $request->input('method'),
            'amount' => $request->input('amount'),
            'user_id' => Auth::id(),
        ]);

        $this->logDeliveryEvent('courier_payment_added', $delivery, 'deliveries', 'delivery_courier_payments', $payment->id);

        $this->logCourierEvent('courier_payment_added', $delivery, 'couriers', 'delivery_courier_payments', $payment->id);

        return response()->json($payment, 201);
    }

    private function syncRelatedReceipt(Delivery $delivery, ?array $receipt): void
    {
        if ($receipt === null) {
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

    private function logDeliveryEvent(string $event, Delivery $delivery, ?string $section = 'deliveries', ?string $referenceTable = null, ?int $referenceId = null): void
    {
        DeliveryEvent::create([
            'event' => $event,
            'section' => $section,
            'reference_table' => $referenceTable,
            'reference_id' => $referenceId,
            'delivery_id' => $delivery->id,
        ]);
    }

    private function logClientEvent(string $event, Delivery $delivery, ?string $section = 'clients', ?string $referenceTable = null, ?int $referenceId = null): void
    {
        ClientEvent::create([
            'event' => $event,
            'section' => $section,
            'reference_table' => $referenceTable,
            'reference_id' => $referenceId,
            'client_id' => $delivery->client->id,
        ]);
    }

    private function logCourierEvent(string $event, Delivery $delivery, ?string $section = 'couriers', ?string $referenceTable = null, ?int $referenceId = null): void
    {
        CourierEvent::create([
            'event' => $event,
            'section' => $section,
            'reference_table' => $referenceTable,
            'reference_id' => $referenceId,
            'courier_id' => $delivery->courier->id,
        ]);
    }
}
