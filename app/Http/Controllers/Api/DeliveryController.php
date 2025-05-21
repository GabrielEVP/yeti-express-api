<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryRequest;
use App\Models\Delivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DeliveryController extends Controller
{
    private array $relations = [
        'client',
        'clientAddress',
        'paymentType',
        'priceType',
        'courier',
        'openBox',
        'closeBox',
        'lines',
        'recipients'
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
        $data = $request->safe()->except(['lines', 'recipients']);

        $data['user_id'] = Auth::id();
        $delivery = Auth::user()
            ->deliveries()
            ->create($data);

        $this->syncRelated($delivery, 'lines', $request->input('lines', []));
        $this->syncRelated($delivery, 'recipients', $request->input('recipients', []));

        return response()->json(
            $delivery->load($this->relations),
            201
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

        $data = $request->safe()->except(['lines', 'recipients']);
        $data['user_id'] = Auth::id();

        $delivery->update($data);

        if ($request->has('lines')) {
            $this->syncRelated($delivery, 'lines', $request->input('lines', []));
        }

        if ($request->has('recipients')) {
            $this->syncRelated($delivery, 'recipients', $request->input('recipients', []));
        }

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

    private function syncRelated(Delivery $delivery, string $relation, array $lines): void
    {
        $ids = collect($lines)->pluck('id')->filter();

        $delivery->$relation()->whereNotIn('id', $ids)->delete();

        foreach ($lines as $item) {
            if (isset($item['id'])) {
                $delivery->$relation()->where('id', $item['id'])->update($item);
            } else {
                $delivery->$relation()->create([
                    ...$item,
                    'user_id' => Auth::id(),
                ]);
            }
        }
    }

    private function authorizeOwner(Delivery $delivery): void
    {
        abort_if($delivery->user_id !== Auth::id(), 403, 'No tienes permiso para acceder a esta entrega.');
    }
}
