<?php

namespace App\Employee\Controllers;

use App\Employee\DomPDF\DomPDFEmployee;
use App\Employee\DTO\EmployeeEventFilterDTO;
use App\Employee\Requests\EmployeeEventReportRequest;
use App\Employee\Services\EmployeeEventReportService;
use Illuminate\Routing\Controller;

class EmployeeEventReportController extends Controller
{
    private EmployeeEventReportService $reportService;

    protected DomPDFEmployee $domPDFEmployee;


    public function __construct(EmployeeEventReportService $reportService, DomPDFEmployee $domPDFEmployee)
    {
        $this->reportService = $reportService;
        $this->domPDFEmployee = $domPDFEmployee;
    }

    public function getEvents(EmployeeEventReportRequest $request): \Illuminate\Http\Response
    {
        $filters = EmployeeEventFilterDTO::fromArray($request->validated());
        $events = $this->reportService->getEventsByEmployee($filters);

        $pdf = $this->domPDFEmployee->generateEventReport($events, $filters->startDate, $filters->endDate);

        return $pdf->stream('Employee_event.pdf');
    }
}
