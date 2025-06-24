<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryRequest;
use App\Http\Requests\DeliveryStatusRequest;
use App\Http\Services\DeliveryEventService;
use App\Http\Services\EmployeeEventService;
use App\Models\Client;
use App\Models\Delivery;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryController extends Controller
{
    private array $relations = [
        'events',
        'service',
        'client',
        'courier',
        'receipt',
    ];

    public function index(): JsonResponse
    {
        $deliveries = Auth::user()->deliveries()->get();
        return response()->json($deliveries, 200);
    }

    public function store(DeliveryRequest $request): JsonResponse
    {
        $user = Auth::user();

        $data = $request->safe()->except('receipt');
        $data['user_id'] = $user->id;
        $data['date'] = now()->toDateString();
        $data['number'] = $this->generateDeliveryNumber();

        $service = Service::findOrFail($data['service_id']);
        $data['amount'] = $service->amount;

        $client = Client::findOrFail($data['client_id']);
        if (!$client->allow_credit) {
            $data['payment_type'] = 'full';
        }

        $delivery = $user->deliveries()->create($data);

        $this->syncRelatedReceipt($delivery, $request->input('receipt'));

        EmployeeEventService::log(
            'create_delivery',
            'deliveries',
            'deliveries',
            $delivery->id
        );

        return response()->json($delivery->load($this->relations), 200);
    }

    public function show(Delivery $delivery): JsonResponse
    {

        $this->authorizeOwner($delivery);

        $delivery->load([
            'events',
            'receipt',
            'courier',
            'client:id,legal_name',
            'courier:id,first_name,last_name',
            'service:id,name',
        ]);

        $data = $delivery->toArray();

        $data['client_legal_name'] = optional($delivery->client)?->legal_name;
        $data['courier_name'] = $delivery->courier ? "{$delivery->courier->first_name} {$delivery->courier->last_name}" : null;
        $data['service_name'] = optional($delivery->service)?->name;

        return response()->json($data, 200);
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

        EmployeeEventService::log(
            'update_delivery',
            'deliveries',
            'deliveries',
            $delivery->id
        );
        DeliveryEventService::log('update_delivery', $delivery);

        return response()->json($delivery->load($this->relations), 200);
    }

    public function destroy(Delivery $delivery): JsonResponse
    {
        $this->authorizeOwner($delivery);

        $delivery->delete();

        EmployeeEventService::log(
            'delete_delivery',
            'deliveries',
            'deliveries',
            $delivery->id
        );

        return response()->json([
            'message' => "Delivery with ID {$delivery->id} has been deleted",
        ], 200);
    }

    public function filter(Request $request): JsonResponse
    {
        $search = (string)$request->input("search", "");
        $sort = $request->input("sortBy", "id");
        $order = strtolower($request->input("sortDirection", "desc"));

        $validColumns = [
            "id",
            "number",
            "date",
            "status",
            "payment_status",
            "amount"
        ];

        if (!in_array($sort, $validColumns) || !in_array($order, ["asc", "desc"])) {
            return response()->json(["error" => "Invalid sort parameters"], 400);
        }

        $query = Auth::user()->deliveries()->with(['client:id,legal_name', 'courier:id,first_name', 'service:id,name,amount', 'receipt']);

        $filters = $request->input('filters', []);
        $hasStatusFilter = isset($filters['status']) && $filters['status'] !== '';

        if (!$hasStatusFilter) {
            $query->whereIn('status', ['pending', 'in_transit']);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where("number", "LIKE", "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('legal_name', 'LIKE', "%{$search}%");
                    });
            });
        }

        foreach ($filters as $field => $value) {
            if ($value === null || $value === '')
                continue;

            match ($field) {
                'status' => $query->where('status', $value),
                'paymentStatus' => $query->where('payment_status', $value),
                'startDate' => $query->whereDate('date', '>=', $value),
                'endDate' => $query->whereDate('date', '<=', $value),
                'serviceId' => $query->where('service_id', $value),
                default => null
            };
        }

        $query->orderBy($sort, $order);

        $deliveries = $query->get();

        $result = $deliveries->map(function ($delivery) {
            return [
                'id' => $delivery->id,
                'number' => $delivery->number,
                'date' => $delivery->date,
                'status' => $delivery->status,
                'payment_status' => $delivery->payment_status,
                'pickup_address' => $delivery->pickup_address,
                'receipt' => $delivery->receipt,
                'amount' => $delivery->amount,
                'client_legal_name' => optional($delivery->client)->legal_name,
                'courier_name' => optional($delivery->courier)?->first_name . ' ' . optional($delivery->courier)?->last_name,
                'service_name' => optional($delivery->service)->name,
                'service_amount' => optional($delivery->service)->amount,
            ];
        });

        return response()->json($result, 200);
    }


    public function updateStatus(DeliveryStatusRequest $request, Delivery $delivery): JsonResponse
    {
        $this->authorizeOwner($delivery);

        $newStatus = $request->input('status');
        $updateData = ['status' => $newStatus];
        $eventKey = 'status_update';

        if ($newStatus === 'delivered') {
            if ($delivery->payment_type === 'partial') {
                $delivery->debt()->create([
                    'amount' => $delivery->amount,
                    'status' => 'pending',
                    'client_id' => $delivery->client_id,
                    'delivery_id' => $delivery->id,
                    'user_id' => Auth::id(),
                ]);
            } else if ($delivery->payment_type === 'full') {
                $updateData['payment_status'] = 'paid';
            }
            $eventKey = 'update_status_delivered_delivery';
        } elseif ($newStatus === 'cancelled') {
            $eventKey = 'update_status_cancelled_delivery';
        } elseif ($newStatus === 'in_transit') {
            $eventKey = 'update_status_transit_delivery';
        }

        $delivery->update($updateData);

        EmployeeEventService::log($eventKey, 'deliveries', 'deliveries', $delivery->id);
        DeliveryEventService::log($eventKey, $delivery);

        $deliveries = Auth::user()
            ->deliveries()
            ->with($this->relations)
            ->get();

        return response()->json($deliveries, 200);
    }

    public function cancelDelivery(Request $request, Delivery $delivery): JsonResponse
    {
        $this->authorizeOwner($delivery);

        $request->validate([
            'cancellation_notes' => 'required|string|max:500'
        ]);

        $cancellationNotes = $request->input('cancellation_notes');

        $updateData = [
            'status' => 'cancelled',
            'cancellation_notes' => $cancellationNotes
        ];

        $delivery->update($updateData);

        EmployeeEventService::log(
            'cancel_delivery',
            'deliveries',
            'deliveries',
            $delivery->id
        );

        return response()->json($delivery->load($this->relations), 200);
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
        abort_if(!Auth::user(), 403, 'No tienes permiso para acceder a esta entrega.');
    }
}
