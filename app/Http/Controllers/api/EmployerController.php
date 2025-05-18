<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployerRequest;
use App\Models\Employer;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class EmployerController extends Controller
{
    public function index(): Response
    {
        $employers = auth()->user()->employers()->get();

        return Inertia::render('Employer/Index', ['employers' => $employers]);
    }

    public function create(): Response
    {
        return Inertia::render('Employer/Form', ['employer' => null, 'mode' => 'create']);
    }

    public function store(EmployerRequest $request)
    {
        $data = $request->safe()->except('password');
        $data['password'] = Hash::make($request->password);

        auth()->user()->employers()->create($data);

        return redirect()->route('employers.index')->with('success', 'Empleado creado correctamente.');
    }

    public function show(Employer $employer): Response
    {
        $this->authorizeOwner($employer);

        return Inertia::render('Employer/Show', ['employer' => $employer]);
    }

    public function edit(Employer $employer): Response
    {
        $this->authorizeOwner($employer);

        return Inertia::render('Employer/Form', ['employer' => $employer, 'mode' => 'edit']);
    }

    public function update(EmployerRequest $request, Employer $employer)
    {
        $this->authorizeOwner($employer);

        $data = $request->safe()->except('password');
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $employer->update($data);

        return redirect()->route('employers.index')->with('success', 'Empleado actualizado correctamente.');
    }

    public function destroy(Employer $employer)
    {
        $this->authorizeOwner($employer);

        $employer->delete();

        return redirect()->route('employers.index')->with('success', 'Empleado eliminado correctamente.');
    }

    private function authorizeOwner(Employer $employer): void
    {
        abort_if($employer->user_id !== auth()->id(), 403, 'No tienes permiso para acceder a este empleado.');
    }
}
