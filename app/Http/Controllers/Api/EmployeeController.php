<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\employeeRequest;
use App\Models\employee;
use App\Models\employeeEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class employeeController extends Controller
{
    public function index(): JsonResponse
    {
        $employees = Auth::user()->employees()->get();
        return response()->json($employees, 200);
    }

    public function show(employee $employee): JsonResponse
    {
        $this->authorizeOwner($employee);
        return response()->json($employee, 200);
    }

    public function store(employeeRequest $request): JsonResponse
    {
        $data = $request->safe()->except('password');
        $data['user_id'] = Auth::id();
        $data['password'] = Hash::make($request->password);

        $employee = Auth::user()->employees()->create($data);
        return response()->json($employee, 201);
    }

    public function update(employeeRequest $request, employee $employee): JsonResponse
    {
        $this->authorizeOwner($employee);
        $data = $request->safe()->except('password');
        $data['user_id'] = Auth::id();

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $employee->update($data);

        employeeEvent::create([
            'event' => "update_employee",
            "section" => "employees",
            'reference_table' => null,
            'reference_id' => null,
            'client_id' => $employee->id,
        ]);

        return response()->json($employee, 200);
    }

    public function destroy(employee $employee): JsonResponse
    {
        $this->authorizeOwner($employee);
        $employee->delete();
        return response()->json(['message' => "employee with ID {$employee->id} has been deleted"], 200);
    }

    private function authorizeOwner(employee $employee): void
    {
        abort_if($employee->user_id !== Auth::id(), 403, 'No tienes permiso para acceder a este empleado.');
    }
}
