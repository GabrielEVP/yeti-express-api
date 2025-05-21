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
        'courier',
        'openBox',
        'closeBox',
        'lines',
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
        $data = $request->safe()->except(['lines', 'receipt']);
        $data['user_id'] = Auth::id();

        $lastNumber = Delivery::max('id') ?? 0;
        $nextNumber = $lastNumber + 1;
        $data['number'] = 'DEV-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        $delivery = Auth::user()
            ->deliveries()
            ->create($data);

        $this->syncRelatedLines($delivery, $request->input('lines', []));
        $this->syncRelatedReceipt($delivery, $request->input('receipt'));

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

        $data = $request->safe()->except(['lines', 'receipt']);
        $data['user_id'] = Auth::id();

        $delivery->update($data);

        if ($request->has('lines')) {
            $this->syncRelatedLines($delivery, $request->input('lines', []));
        }

        if ($request->has('receipt')) {
            $this->syncRelatedReceipt($delivery, $request->input('receipt'));
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

    private function syncRelatedLines(Delivery $delivery, array $lines): void
    {
        $ids = collect($lines)->pluck('id')->filter();

        $delivery->lines()->whereNotIn('id', $ids)->delete();

        foreach ($lines as $item) {
            if (isset($item['id'])) {
                $delivery->lines()->where('id', $item['id'])->update($item);
            } else {
                $delivery->lines()->create([
                    ...$item,
                    'user_id' => Auth::id(),
                ]);
            }
        }
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
                'user_id' => Auth::id(),
            ]);
        } else {
            $delivery->receipt()->create([
                ...$receipt,
                'delivery_id' => $delivery->id,
                'user_id' => Auth::id(),
            ]);
        }
    }

    private function authorizeOwner(Delivery $delivery): void
    {
        abort_if($delivery->user_id !== Auth::id(), 403, 'No tienes permiso para acceder a esta entrega.');
    }
}
