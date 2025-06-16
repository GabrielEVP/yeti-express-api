<?php
namespace App\Http\Services;

use App\Models\EmployeeEvent;
use Illuminate\Support\Facades\Auth;

class EmployeeEventService
{
    public static function log(string $event, string $section = null, string $referenceTable = null, int $referenceId = null): ?EmployeeEvent
    {
        $employee = Auth::user();
        if (!$employee || !$employee instanceof \App\Models\Employee) {
            return null;
        }

        return EmployeeEvent::create(array_merge([
            'employee_id' => $employee->id,
            'event' => $event,
            'section' => $section,
            'reference_table' => $referenceTable,
            'reference_id' => $referenceId,
        ]));
    }
}