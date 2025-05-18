<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryRequest;
use App\Models\Delivery;
use Inertia\Inertia;
use Inertia\Response;

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
        'items',
        'recipients'
    ];

    public function index(): Response
    {
        $deliveries = auth()->user()->deliveries()->with($this->relations)->get();

        return Inertia::render('Delivery/Index', ['deliveries' => $deliveries]);
    }

    public function create(): Response
    {
        return Inertia::render('Delivery/Form', ['delivery' => null, 'mode' => 'create']);
    }

    public function store(DeliveryRequest $request)
    {
        $delivery = auth()->user()->deliveries()->create($request->safe()->except(['items', 'recipients']));

        $this->syncRelated($delivery, 'items', $request->input('items', []));
        $this->syncRelated($delivery, 'recipients', $request->input('recipients', []));

        return redirect()->route('deliveries.index')->with('success', 'Entrega creada correctamente.');
    }

    public function show(Delivery $delivery): Response
    {
        $this->authorizeOwner($delivery);

        $delivery->load($this->relations);

        return Inertia::render('Delivery/Show', ['delivery' => $delivery]);
    }

    public function edit(Delivery $delivery): Response
    {
        $this->authorizeOwner($delivery);

        $delivery->load($this->relations);

        return Inertia::render('Delivery/Form', ['delivery' => $delivery, 'mode' => 'edit']);
    }

    public function update(DeliveryRequest $request, Delivery $delivery)
    {
        $this->authorizeOwner($delivery);

        $delivery->update($request->safe()->except(['items', 'recipients']));

        if ($request->has('items')) {
            $this->syncRelated($delivery, 'items', $request->items);
        }

        if ($request->has('recipients')) {
            $this->syncRelated($delivery, 'recipients', $request->recipients);
        }

        return redirect()->route('deliveries.index')->with('success', 'Entrega actualizada correctamente.');
    }

    public function destroy(Delivery $delivery)
    {
        $this->authorizeOwner($delivery);

        $delivery->delete();

        return redirect()->route('deliveries.index')->with('success', 'Entrega eliminada correctamente.');
    }

    private function syncRelated(Delivery $delivery, string $relation, array $items): void
    {
        $ids = collect($items)->pluck('id')->filter();

        $delivery->$relation()->whereNotIn('id', $ids)->delete();

        foreach ($items as $item) {
            if (isset($item['id'])) {
                $delivery->$relation()->where('id', $item['id'])->update($item);
            } else {
                $delivery->$relation()->create([
                    ...$item,
                    'user_id' => auth()->id(),
                ]);
            }
        }
    }

    private function authorizeOwner(Delivery $delivery): void
    {
        abort_if($delivery->user_id !== auth()->id(), 403, 'No tienes permiso para acceder a esta entrega.');
    }
}
