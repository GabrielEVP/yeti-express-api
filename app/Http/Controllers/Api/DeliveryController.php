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
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DeliveryController extends Controller
{
    private array $relations = [
        'client',
        'clientAddress',
        'events',
        'courier',
        'openBox',
        'closeBox',
        'receipt'
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
        $data = $request->safe()->except(['receipt']);
        $data['user_id'] = Auth::id();

        $lastNumber = Delivery::max('id') ?? 0;
        $nextNumber = $lastNumber + 1;
        $data['number'] = 'DEV-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        $delivery = Auth::user()
            ->deliveries()
            ->create($data);

        $this->syncRelatedReceipt($delivery, $request->input('receipt'));

        ClientEvent::create([
            'event' => "create_delivery",
            "section" => "clients",
            'reference_table' => "deliveries",
            'reference_id' => $delivery->id,
            'client_id' => $delivery->client->id,
        ]);

        return response()->json(
            $delivery->load($this->relations),
            200
        );
    }

    public function show(Delivery $delivery): JsonResponse
    {
        $this->authorizeOwner($delivery);
        return response()->json($delivery->load($this->relations), 200);
    }

    public function update(DeliveryRequest $request, Delivery $delivery): JsonResponse
    {
        $this->authorizeOwner($delivery);

        $data = $request->safe()->except(['receipt']);
        $data['user_id'] = Auth::id();

        $delivery->update($data);



        if ($request->has('receipt')) {
            $this->syncRelatedReceipt($delivery, $request->input('receipt'));
        }

        DeliveryEvent::create([
            'event' => "update_delivery",
            "section" => "deliveries",
            'reference_table' => null,
            'reference_id' => null,
            'delivery_id' => $delivery->id,
        ]);

        return response()->json(
            $delivery->load($this->relations),
            200
        );
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
        $delivery = Delivery::with(['receipt'])
            ->where('client_id', $clientId)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($delivery, 200);
    }

    public function latestByCourier(string $courier_id): JsonResponse
    {
        $delivery = Delivery::with(['receipt'])
            ->where('courier_id', $courier_id)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($delivery, 200);
    }
    public function updateStatus(DeliveryStatusRequest $request, Delivery $delivery): JsonResponse
    {
        $this->authorizeOwner($delivery);

        $delivery->update([
            'status' => $request->input('status')
        ]);

        DeliveryEvent::create([
            'event' => "status_updated",
            "section" => "deliveries",
            'reference_table' => "clients",
            'reference_id' => $delivery->id,
            'delivery_id' => $delivery->id,
        ]);

        return response()->json(
            $delivery->load($this->relations),
            200
        );
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

        DeliveryEvent::create([
            'event' => "client_payment_added",
            "section" => "deliveries",
            'reference_table' => "delivery_client_payments",
            'reference_id' => $payment->id,
            'delivery_id' => $delivery->id,
        ]);

        ClientEvent::create([
            'event' => "client_payment_added",
            "section" => "clients",
            'reference_table' => "delivery_client_payments",
            'reference_id' => $payment->id,
            'client_id' => $delivery->client->id,
        ]);


        return response()->json(
            $payment,
            200
        );
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

        DeliveryEvent::create([
            'event' => "courier_payment_added",
            "section" => "deliveries",
            'reference_table' => "delivery_courier_payments",
            'reference_id' => $payment->id,
            'delivery_id' => $delivery->id,
        ]);

        return response()->json(
            $payment,
            200
        );
    }

    private function syncRelatedReceipt(Delivery $delivery, ?array $receipt): void
    {
        if ($receipt === null) {
            $delivery->receipt()->delete();
            return;
        }

        if ($delivery->receipt) {
            $delivery->receipt()->update([
                ...$receipt,
                'delivery_id' => $delivery->id,
            ]);
        } else {
            $delivery->receipt()->create([
                ...$receipt,
                'delivery_id' => $delivery->id,
            ]);
        }
    }

    private function authorizeOwner(Delivery $delivery): void
    {
        abort_if($delivery->user_id !== Auth::id(), 403, 'No tienes permiso para acceder a esta entrega.');
    }
}
