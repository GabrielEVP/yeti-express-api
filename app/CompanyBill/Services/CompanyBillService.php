<?php

namespace App\CompanyBill\Services;

use App\CompanyBill\DTO\SimpleCompanyBillDTO;
use App\CompanyBill\Models\CompanyBill;
use App\CompanyBill\Repositories\ICompanyBillRepository;
use App\Core\DTO\FilterRequestPaginatedDTO;
use App\Core\DTO\PaginatedDTO;
use App\Shared\Services\AuthHelper;
use App\Shared\Services\EmployeeEventService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CompanyBillService implements ICompanyBillRepository
{
    private const SELECT_SIMPLE_FIELDS = ['id', 'name', 'date', 'method', 'amount'];

    private function baseQuery()
    {
        return CompanyBill::query()
            ->where('user_id', AuthHelper::getUserId());
    }

    public function all(): Collection
    {
        return $this->baseQuery()
            ->select(self::SELECT_SIMPLE_FIELDS)
            ->get();
    }

    public function create(array $data): CompanyBill
    {
        $bill = CompanyBill::create($data);

        EmployeeEventService::log(
            'create_company_bill',
            'company_bills',
            'company_bills',
            (int)$bill->id,
            'Company bill created: ' . $bill->name
        );

        return $bill;
    }

    public function find(string $id): CompanyBill
    {
        return $this->baseQuery()->findOrFail($id);
    }

    public function update(string $id, array $data): CompanyBill
    {
        $bill = $this->find($id);
        $bill->update($data);

        EmployeeEventService::log(
            'update_company_bill',
            'company_bills',
            'company_bills',
            (int)$id,
            'Company bill updated: ' . $bill->name
        );

        return $bill;
    }

    public function delete(string $id): void
    {
        $bill = $this->find($id);
        $name = $bill->name;
        $bill->delete();

        EmployeeEventService::log(
            'delete_company_bill',
            'company_bills',
            'company_bills',
            (int)$id,
            'Company bill deleted: ' . $name
        );
    }

    public function filter(FilterRequestPaginatedDTO $filters): PaginatedDTO
    {
        $query = $this->baseQuery()
            ->select(self::SELECT_SIMPLE_FIELDS)
            ->when($filters->search !== '', function ($q) use ($filters) {
                $q->where(function ($query) use ($filters) {
                    $query->where('name', 'LIKE', "%{$filters->search}%")
                        ->orWhere('description', 'LIKE', "%{$filters->search}%");
                });
            })
            ->orderBy($filters->sortBy, $filters->sortDirection);

        $paginator = $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );

        $items = collect($paginator->items())->map(function ($bill) {
            return new SimpleCompanyBillDTO($bill);
        });

        return new PaginatedDTO(
            $items,
            $paginator->currentPage(),
            $paginator->perPage(),
            $paginator->total()
        );
    }
}
