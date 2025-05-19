<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployerRequest;
use App\Models\Employer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EmployerController extends Controller
{
    public function index(): JsonResponse
    {
        $employers = Auth::user()->employers()->get();

        return response()->json($employers, 200);
    }

    public function show(Employer $employer): JsonResponse
    {
        $this->authorizeOwner($employer);

        return response()->json($employer, 200);
    }

    public function store(EmployerRequest $request): JsonResponse
    {
        $data = $request->safe()->except('password');
        $data['password'] = Hash::make($request->password);

        $employer = Auth::user()->employers()->create($data);

        return response()->json($employer, 201);
    }

    public function update(EmployerRequest $request, Employer $employer): JsonResponse
    {
        $this->authorizeOwner($employer);

        $data = $request->safe()->except('password');
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $employer->update($data);

        return response()->json($employer, 200);
    }

    public function destroy(Employer $employer): JsonResponse
    {
        $this->authorizeOwner($employer);

        $employer->delete();

        return response()->json(['message' => "Employer with ID {$employer->id} has been deleted"], 200);
    }

    private function authorizeOwner(Employer $employer): void
    {
        abort_if($employer->user_id !== Auth::id(), 403, 'No tienes permiso para acceder a este empleado.');
    }
}
