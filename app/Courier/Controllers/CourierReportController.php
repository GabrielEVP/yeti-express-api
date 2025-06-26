<?php

namespace App\Courier\Controllers;

use App\Courier\DomPDF\DomPDFCourier;
use App\Courier\Services\PDFCourierService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CourierReportController extends Controller
{
    protected DomPDFCourier $pdfService;
    private PDFCourierService $pdfCourierService;

    public function __construct(DomPDFCourier $pdfService, PDFCourierService $pdfCourierService)
    {
        $this->pdfService = $pdfService;
        $this->pdfCourierService = $pdfCourierService;
    }

    public function allCouriersDeliveriesReport(Request $request): Response
    {
        $dto = $this->pdfCourierService->getAllReportCourier($request);
        $pdf = $this->pdfService->generateAllCouriersDeliveriesReport($dto);

        return $pdf->stream("all-couriers-deliveries-report.pdf");
    }

    public function courierDeliveriesReport(string $id, Request $request): Response
    {
        $dto = $this->pdfCourierService->getReportByCourier($id, $request);
        $pdf = $this->pdfService->generateCourierDeliveriesReport($dto);

        return $pdf->stream("courier-deliveries-report-{$dto->id}.pdf");
    }
}
