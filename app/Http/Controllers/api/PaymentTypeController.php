<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentTypeRequest;
use App\Models\PaymentType;
use Inertia\Inertia;
use Inertia\Response;

class PaymentTypeController extends Controller
{
    public function index(): Response
    {
        $paymentTypes = auth()->user()->paymentTypes()->get();

        return Inertia::render('PaymentType/Index', ['paymentTypes' => $paymentTypes]);
    }

    public function create(): Response
    {
        return Inertia::render('PaymentType/Form', ['paymentType' => null, 'mode' => 'create']);
    }

    public function store(PaymentTypeRequest $request)
    {
        auth()->user()->paymentTypes()->create($request->validated());

        return redirect()->route('payment-types.index')->with('success', 'Tipo de pago creado correctamente.');
    }

    public function show(PaymentType $paymentType): Response
    {
        $this->authorizeOwner($paymentType);

        return Inertia::render('PaymentType/Show', ['paymentType' => $paymentType]);
    }

    public function edit(PaymentType $paymentType): Response
    {
        $this->authorizeOwner($paymentType);

        return Inertia::render('PaymentType/Form', ['paymentType' => $paymentType, 'mode' => 'edit']);
    }

    public function update(PaymentTypeRequest $request, PaymentType $paymentType)
    {
        $this->authorizeOwner($paymentType);

        $paymentType->update($request->validated());

        return redirect()->route('payment-types.index')->with('success', 'Tipo de pago actualizado correctamente.');
    }

    public function destroy(PaymentType $paymentType)
    {
        $this->authorizeOwner($paymentType);

        $paymentType->delete();

        return redirect()->route('payment-types.index')->with('success', 'Tipo de pago eliminado correctamente.');
    }

    private function authorizeOwner(PaymentType $paymentType): void
    {
        abort_if($paymentType->user_id !== auth()->id(), 403, 'No tienes permiso para acceder a este tipo de pago.');
    }
}
