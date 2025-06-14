<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryRequest;
use App\Http\Requests\DeliveryStatusRequest;
use App\Models\Delivery;
use App\Models\Service;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    private array $relations = [
        'events',
        'service',
        'client',
        'clientAddress',
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
        $data['amount'] = $service->getTotalEarning();

        $client = Client::findOrFail($data['client_id']);
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

    public function filter(Request $request): JsonResponse
    {
        $search = (string) $request->input("search", "");
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
            $query->where(function ($q) use ($search) {
                $q->where("number", "LIKE", "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('legal_name', 'LIKE', "%{$search}%");
                    });
            });
        }

        foreach ($request->input('filters', []) as $field => $value) {
            if ($value === null || $value === '')
                continue;

            switch ($field) {
                case 'status':
                    $query->where('status', $value);
                    break;
                case 'paymentStatus':
                    $query->where('payment_status', $value);
                    break;
                case 'startDate':
                    $query->whereDate('date', '>=', $value);
                    break;
                case 'endDate':
                    $query->whereDate('date', '<=', $value);
                    break;
            }
        }

        $query->orderBy($sort, $order);
        $deliveries = $query->get();

        return response()->json($deliveries, 200);
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
                    'amount' => $delivery->amount,
                    'status' => 'pending',
                    'client_id' => $delivery->client_id,
                    'delivery_id' => $delivery->id,
                    'user_id' => Auth::id(),
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
