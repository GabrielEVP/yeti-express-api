<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use App\Models\EmployeeEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Auth::user()->employees()->get(), 200);
    }

    public function show(Employee $employee): JsonResponse
    {
        $this->authorizeOwner($employee);
        return response()->json($employee, 200);
    }

    public function store(EmployeeRequest $request): JsonResponse
    {
        $data = $request->safe()->except('password');
        $data['user_id'] = Auth::id();
        $data['password'] = Hash::make($request->password);

        $employee = Auth::user()->employees()->create($data);

        $this->logEvent('create_employee', $employee->id);

        return response()->json($employee, 201);
    }

    public function update(EmployeeRequest $request, Employee $employee): JsonResponse
    {
        $this->authorizeOwner($employee);

        $data = $request->safe()->except('password');
        $data['user_id'] = Auth::id();

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $employee->update($data);

        $this->logEvent('update_employee', $employee->id);

        return response()->json($employee, 200);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $this->authorizeOwner($employee);
        $employee->delete();

        $this->logEvent('delete_employee', $employee->id);

        return response()->json(['message' => "Employee with ID {$employee->id} has been deleted"], 200);
    }

    public function search(string $query): JsonResponse
    {
        $employees = Auth::user()->employees()
            ->when($query !== '', fn($q) => $q->where('name', 'LIKE', "%{$query}%"))
            ->get();

        return response()->json($employees, 200);
    }

    private function authorizeOwner(Employee $employee): void
    {
        abort_if($employee->user_id !== Auth::id(), 403, 'No tienes permiso para acceder a este empleado.');
    }

    private function logEvent(string $event, int $employeeId): void
    {
        EmployeeEvent::create([
            'event' => $event,
            'section' => 'employees',
            'reference_table' => null,
            'reference_id' => null,
            'employee_id' => $employeeId,
        ]);
    }
}
