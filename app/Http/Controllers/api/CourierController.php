<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourierRequest;
use App\Models\Courier;
use Inertia\Inertia;
use Inertia\Response;

class CourierController extends Controller
{
    public function index(): Response
    {
        $couriers = auth()->user()->couriers()->get();

        return Inertia::render('Courier/Index', ['couriers' => $couriers]);
    }

    public function show(Courier $courier): Response
    {
        $this->authorizeOwner($courier);

        return Inertia::render('Courier/Show', ['courier' => $courier]);
    }

    public function create(): Response
    {
        return Inertia::render('Courier/Form', ['courier' => null, 'mode' => 'create']);
    }

    public function store(CourierRequest $request)
    {
        auth()->user()->couriers()->create($request->validated());

        return redirect()->route('couriers.index')->with('success', 'Mensajero creado correctamente.');
    }

    public function edit(Courier $courier): Response
    {
        $this->authorizeOwner($courier);

        return Inertia::render('Courier/Form', ['courier' => $courier, 'mode' => 'edit']);
    }

    public function update(CourierRequest $request, Courier $courier)
    {
        $this->authorizeOwner($courier);

        $courier->update($request->validated());

        return redirect()->route('couriers.index')->with('success', 'Mensajero actualizado correctamente.');
    }

    public function destroy(Courier $courier)
    {
        $this->authorizeOwner($courier);

        $courier->delete();

        return redirect()->route('couriers.index')->with('success', 'Mensajero eliminado correctamente.');
    }

    private function authorizeOwner(Courier $courier): void
    {
        abort_if($courier->user_id !== auth()->id(), 403, 'No tienes permiso para acceder a este mensajero.');
    }
}
