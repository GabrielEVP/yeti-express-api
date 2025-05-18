<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Http\Requests\BoxRequest;
use App\Models\Box;
use Inertia\Inertia;
use Inertia\Response;

class BoxController extends Controller
{
    public function index(): Response
    {
        $boxes = auth()->user()->boxes()->with(['openDeliveries', 'closeDeliveries'])->get();
        return Inertia::render('Box/Index', ['boxes' => $boxes]);
    }

    public function show(Box $box): Response
    {
        $this->authorizeBoxOwner($box);
        return Inertia::render('Box/Show', ['box' => $box->load(['openDeliveries', 'closeDeliveries'])]);
    }

    public function create(): Response
    {
        return Inertia::render('Box/Form', ['box' => null, 'mode' => 'create']);
    }

    public function store(BoxRequest $request)
    {
        auth()->user()->boxes()->create($request->validated());
        return redirect()->route('boxes.index')->with('success', 'Caja creada correctamente.');
    }

    public function edit(Box $box): Response
    {
        $this->authorizeBoxOwner($box);
        return Inertia::render('Box/Form', ['box' => $box->load(['openDeliveries', 'closeDeliveries']), 'mode' => 'edit']);
    }

    public function update(BoxRequest $request, Box $box)
    {
        $this->authorizeBoxOwner($box);
        $box->update($request->validated());
        return redirect()->route('boxes.index')->with('success', 'Caja actualizada correctamente.');
    }

    public function destroy(Box $box)
    {
        $this->authorizeBoxOwner($box);
        $box->delete();
        return redirect()->route('boxes.index')->with('success', 'Caja eliminada correctamente.');
    }

    private function authorizeBoxOwner(Box $box): void
    {
        abort_if($box->user_id !== auth()->id(), 403, 'No tienes permiso para acceder a esta caja.');
    }
}
