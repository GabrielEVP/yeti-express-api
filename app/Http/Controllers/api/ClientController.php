<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequest;
use App\Models\Client;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index()
    {
        $clients = auth()->user()->clients()->with(['addresses', 'phones', 'emails', 'deliveries'])->get();
        return Inertia::render('Client/Index', ['clients' => $clients]);
    }

    public function show(Client $client)
    {
        $this->authorizeClient($client);

        $client->load(['addresses', 'phones', 'emails']);
        return Inertia::render('Client/Show', ['client' => $client]);
    }

    public function create()
    {
        return Inertia::render('Client/Form', [
            'client' => null,
            'mode' => 'create',
        ]);
    }

    public function store(ClientRequest $request)
    {
        $client = auth()->user()->clients()->create($request->validated());

        foreach ($request->input('addresses', []) as $address) {
            $client->addresses()->create($address);
        }

        foreach ($request->input('phones', []) as $phone) {
            $client->phones()->create($phone);
        }

        foreach ($request->input('emails', []) as $email) {
            $client->emails()->create($email);
        }

        return redirect()->route('clients.index')->with('success', 'Cliente creado correctamente.');
    }

    public function edit(Client $client)
    {
        $this->authorizeClient($client);

        $client->load(['addresses', 'phones', 'emails', 'bankAccounts']);
        return Inertia::render('Client/Form', [
            'client' => $client,
            'mode' => 'edit',
        ]);
    }

    public function update(ClientRequest $request, Client $client)
    {
        $this->authorizeClient($client);

        $client->update($request->validated());

        $client->addresses()->delete();
        foreach ($request->input('addresses', []) as $address) {
            $client->addresses()->create($address);
        }

        $client->phones()->delete();
        foreach ($request->input('phones', []) as $phone) {
            $client->phones()->create($phone);
        }

        $client->emails()->delete();
        foreach ($request->input('emails', []) as $email) {
            $client->emails()->create($email);
        }

        $client->bankAccounts()->delete();
        foreach ($request->input('bank_accounts', []) as $bankAccount) {
            $client->bankAccounts()->create($bankAccount);
        }

        return redirect()->route('clients.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Client $client)
    {
        $this->authorizeClient($client);

        $client->addresses()->delete();
        $client->phones()->delete();
        $client->emails()->delete();
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Cliente eliminado correctamente.');
    }

    private function authorizeClient(Client $client): void
    {
        if ($client->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para acceder a este cliente.');
        }
    }
}
