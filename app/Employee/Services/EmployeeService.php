<?php

namespace App\Employee\Services;

use App\Core\DTO\FilterRequestPaginatedDTO;
use App\Core\DTO\PaginatedDTO;
use App\Employee\DTO\EmployeeDTO;
use App\Employee\DTO\SimpleEmployeeDTO;
use App\Employee\Models\Employee;
use App\Employee\Repositories\IEmployeeRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class EmployeeService implements IEmployeeRepository
{
    private const SELECT_SIMPLE_FIELDS = ['id', 'name', 'email', 'role'];
    private const CACHE_KEY = 'employees.filter.';
    private const CACHE_TTL = 3600; // 1 hour in seconds

    private function baseQuery()
    {
        return Employee::query()
            ->where('user_id', Auth::id());
    }

    private function getCacheKey(FilterRequestPaginatedDTO $filters): string
    {
        return self::CACHE_KEY . Auth::id() . '.' . md5(serialize($filters));
    }

    private function clearFilterCache(): void
    {
        Cache::tags(['employees'])->flush();
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

        $this->clearFilterCache();

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

        $this->clearFilterCache();

        return EmployeeDTO::fromModel($employee);
    }

    public function updatePassword(string $id, string $password): EmployeeDTO
    {
        $employee = $this->baseQuery()->findOrFail($id);
        $employee->update(['password' => Hash::make($password)]);

        $this->clearFilterCache();

        return EmployeeDTO::fromModel($employee);
    }

    public function delete(string $id): void
    {
        $this->baseQuery()->findOrFail($id)->delete();
        $this->clearFilterCache();
    }

    public function filter(FilterRequestPaginatedDTO $filters): PaginatedDTO
    {
        $cacheKey = $this->getCacheKey($filters);

        return Cache::tags(['employees'])->remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
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
        });
    }
}
