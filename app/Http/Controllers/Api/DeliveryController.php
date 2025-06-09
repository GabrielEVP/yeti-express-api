<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryRequest;
use App\Http\Requests\DeliveryStatusRequest;
use App\Models\Delivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

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

    public function filter(Request $request): JsonResponse
    {
        $search = $request->string("search")->toString();
        $sort = $request->input("sortBy", "date");
        $order = strtolower($request->input("sortDirection", "desc"));
        $perPage = $request->input("perPage", 15);
        $page = $request->input("page", 1);

        $validColumns = [
            "number",
            "date",
            "status",
            "payment_status",
            "payment_type",
            "amount"
        ];

        if (
            !in_array($sort, $validColumns) ||
            !in_array($order, ["asc", "desc"])
        ) {
            return response()->json(
                ["error" => "Invalid sort parameters"],
                400
            );
        }

        $query = Auth::user()->deliveries()->with($this->relations);

        if ($search) {
            $query->where("number", "LIKE", "%{$search}%");
        }

        if ($request->has("status")) {
            $query->where("status", $request->input("status"));
        }

        if ($request->has("paymentStatus")) {
            $query->where("payment_status", strtolower($request->input("paymentStatus")));
        }

        if ($request->has("paymentMethod")) {
            $query->where("payment_type", strtolower($request->input("paymentMethod")));
        }

        // Filtros de fecha
        if ($request->has("startDate")) {
            $query->whereDate("date", ">=", $request->input("startDate"));
        }

        if ($request->has("endDate")) {
            $query->whereDate("date", "<=", $request->input("endDate"));
        }

        // Ordenamiento
        if ($sort === 'client') {
            $query->join('clients', 'deliveries.client_id', '=', 'clients.id')
                ->orderBy('clients.legal_name', $order)
                ->select('deliveries.*');
        } elseif ($sort === 'courier') {
            $query->join('couriers', 'deliveries.courier_id', '=', 'couriers.id')
                ->orderBy('couriers.first_name', $order)
                ->select('deliveries.*');
        } elseif ($sort === 'service') {
            $query->join('services', 'deliveries.service_id', '=', 'services.id')
                ->orderBy('services.name', $order)
                ->select('deliveries.*');
        } elseif ($sort === 'amount') {
            $query->join('services', 'deliveries.service_id', '=', 'services.id')
                ->orderBy('services.amount', $order)
                ->select('deliveries.*');
        } else {
            $query->orderBy($sort, $order);
        }

        $deliveries = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($deliveries, 200);
    }

    public function store(DeliveryRequest $request): JsonResponse
    {
        $user = Auth::user();

        $data = $request->safe()->except('receipt');
        $data['user_id'] = $user->id;
        $data['date'] = now()->toDateString();
        $data['number'] = $this->generateDeliveryNumber();

        $client = \App\Models\Client::findOrFail($data['client_id']);
        if (!$client->allow_credit) {
            $data['payment_type'] = 'full';
        }

        $delivery = $user->deliveries()->create($data);

        $this->syncRelatedReceipt($delivery, $request->input('receipt'));

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

        $oldStatus = $delivery->status;
        $newStatus = $request->input('status');

        $updateData = ['status' => $newStatus];

        if ($newStatus === 'delivered') {
            if ($delivery->payment_type === 'partial') {
                $delivery->debt()->create([
                    'amount' => $delivery->service->amount,
                    'status' => 'pending',
                    'client_id' => $delivery->client_id,
                    'delivery_id' => $delivery->id,
                ]);
            } else if ($delivery->payment_type === 'full') {
                $updateData['payment_status'] = 'paid';
            }
        }

        $delivery->update($updateData);

        $delivery->events()->create([
            'event' => 'status_update',
            'section' => 'deliveries',
            'reference_table' => 'deliveries',
            'reference_id' => $delivery->id,
        ]);

        $deliveries = Auth::user()
            ->deliveries()
            ->with($this->relations)
            ->get();

        return response()->json($deliveries, 200);
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

    public function getWithDebt(): JsonResponse
    {
        $deliveries = Auth::user()
            ->deliveries()
            ->whereHas('debt')
            ->with($this->relations)
            ->get();

        return response()->json($deliveries, 200);
    }

    public function getWithDebtByClient(int $clientId): JsonResponse
    {
        $deliveries = Auth::user()
            ->deliveries()
            ->where('client_id', $clientId)
            ->whereHas('debt')
            ->with($this->relations)
            ->get();


        return response()->json($deliveries, 200);
    }
}
