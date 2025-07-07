<?php

namespace App\Employee\Services;

use App\Core\DTO\FilterRequestPaginatedDTO;
use App\Core\DTO\PaginatedDTO;
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

    public function filter(FilterRequestPaginatedDTO $filters): PaginatedDTO
    {
        $query = $this->baseQuery()
            ->select(self::SELECT_SIMPLE_FIELDS)
            ->when($filters->search !== '', function ($q) use ($filters) {
                $q->where(function ($query) use ($filters) {
                    $query->where('name', 'LIKE', "%{$filters->search}%")
                        ->orWhere('email', 'LIKE', "%{$filters->search}%");
                });
            })
            ->orderBy($filters->sortBy, $filters->sortDirection);

        $paginator = $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );

        $items = collect($paginator->items());

        return new PaginatedDTO(
            $items,
            $paginator->currentPage(),
            $paginator->perPage(),
            $paginator->total()
        );
    }
}
