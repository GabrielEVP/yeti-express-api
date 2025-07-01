<?php

namespace App\Employee\Services;

use App\Employee\DTO\EmployeeDTO;
use App\Employee\DTO\SimpleEmployeeDTO;
use App\Employee\Models\Employee;
use App\Employee\Repositories\IEmployeeRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EmployeeService implements IEmployeeRepository
{
    private const SELECT_SIMPLE_FIELDS = ['id', 'name', 'email', 'role'];

    private function baseQuery()
    {
        return Employee::query()
            ->where('user_id', Auth::id());
    }

    public function all(): array
    {
        $employees = $this->baseQuery()
            ->select(self::SELECT_SIMPLE_FIELDS)
            ->get()
            ->toArray();

        return SimpleEmployeeDTO::fromCollection($employees);
    }

    public function create(array $data): EmployeeDTO
    {
        $data['password'] = Hash::make($data['password']);
        $employee = Auth::user()->employees()->create($data);

        return EmployeeDTO::fromModel($employee);
    }

    public function find(string $id): EmployeeDTO
    {
        $employee = $this->baseQuery()->findOrFail($id);
        return EmployeeDTO::fromModel($employee);
    }

    public function update(string $id, array $data): EmployeeDTO
    {
        $employee = $this->baseQuery()->findOrFail($id);
        $employee->update($data);

        return EmployeeDTO::fromModel($employee);
    }

    public function updatePassword(string $id, string $password): EmployeeDTO
    {
        $employee = $this->baseQuery()->findOrFail($id);
        $employee->update(['password' => Hash::make($password)]);

        return EmployeeDTO::fromModel($employee);
    }

    public function delete(string $id): void
    {
        $this->baseQuery()->findOrFail($id)->delete();
    }

    public function search(string $query): array
    {
        $employees = $this->baseQuery()
            ->select(self::SELECT_SIMPLE_FIELDS)
            ->where('name', 'like', "%{$query}%")
            ->get()
            ->toArray();

        return SimpleEmployeeDTO::fromCollection($employees);
    }
}
