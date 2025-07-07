<?php

namespace App\Employee\Services;

use App\Employee\DTO\EmployeeEventFilterDTO;
use App\Employee\DTO\EmployeeEventReportDTO;
use App\Employee\Models\EmployeeEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class EmployeeEventReportService
{
    public function getEventsByEmployee(EmployeeEventFilterDTO $filters): Collection
    {
        $query = EmployeeEvent::query()
            ->with(['employee'])
            ->join('employees', 'employee_events.employee_id', '=', 'employees.id')
            ->where('employees.user_id', Auth::id())
            ->select('employee_events.*');

        if ($filters->employeeId) {
            $query->where('employee_events.employee_id', $filters->employeeId);
        }

        if ($filters->startDate) {
            $query->whereDate('employee_events.created_at', '>=', $filters->startDate);
        }

        if ($filters->endDate) {
            $query->whereDate('employee_events.created_at', '<=', $filters->endDate);
        }

        $events = $query->orderBy('employee_events.created_at', 'desc')->get();

        return collect(EmployeeEventReportDTO::fromCollection($events));
    }
}
