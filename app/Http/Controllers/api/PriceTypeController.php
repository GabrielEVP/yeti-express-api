<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Http\Requests\PriceTypeRequest;
use App\Models\PriceType;
use Inertia\Inertia;
use Inertia\Response;

class PriceTypeController extends Controller
{
    public function index(): Response
    {
        $priceTypes = auth()->user()->priceTypes()->get();

        return Inertia::render('PriceType/Index', ['priceTypes' => $priceTypes]);
    }

    public function create(): Response
    {
        return Inertia::render('PriceType/Form', ['priceType' => null, 'mode' => 'create']);
    }

    public function store(PriceTypeRequest $request)
    {
        auth()->user()->priceTypes()->create($request->validated());

        return redirect()->route('price-types.index')->with('success', 'Tipo de precio creado correctamente.');
    }

    public function show(PriceType $priceType): Response
    {
        $this->authorizeOwner($priceType);

        return Inertia::render('PriceType/Show', ['priceType' => $priceType]);
    }

    public function edit(PriceType $priceType): Response
    {
        $this->authorizeOwner($priceType);

        return Inertia::render('PriceType/Form', ['priceType' => $priceType, 'mode' => 'edit']);
    }

    public function update(PriceTypeRequest $request, PriceType $priceType)
    {
        $this->authorizeOwner($priceType);

        $priceType->update($request->validated());

        return redirect()->route('price-types.index')->with('success', 'Tipo de precio actualizado correctamente.');
    }

    public function destroy(PriceType $priceType)
    {
        $this->authorizeOwner($priceType);

        $priceType->delete();

        return redirect()->route('price-types.index')->with('success', 'Tipo de precio eliminado correctamente.');
    }

    private function authorizeOwner(PriceType $priceType): void
    {
        abort_if($priceType->user_id !== auth()->id(), 403, 'No tienes permiso para acceder a este tipo de precio.');
    }
}
