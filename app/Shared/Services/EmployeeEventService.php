<?php

namespace App\Shared\Services;

use App\Employee\Models\EmployeeEvent;
use Illuminate\Support\Facades\Auth;

class EmployeeEventService
{
    public static function log(string $event, string $section = null, string $referenceTable = null, int $referenceId = null, string $message = null): ?EmployeeEvent
    {
        $employee = Auth::user();
        if (!$employee instanceof \App\Employee\Models\Employee) {
            return null;
        }

        return EmployeeEvent::create(array_merge([
            'employee_id' => $employee->id,
            'event' => $event,
            'section' => $section,
            'reference_table' => $referenceTable,
            'reference_id' => $referenceId,
            'message' => $message,
        ]));
    }
}
