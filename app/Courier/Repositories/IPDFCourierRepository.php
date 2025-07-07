<?php

namespace App\Courier\Repositories;

use App\Courier\DTO\ReportPDFAllCourierDTO;
use App\Courier\DTO\ReportPDFCourierDTO;
use Illuminate\Http\Request;

interface IPDFCourierRepository
{
    public function getAllReportCourier(Request $request): ReportPDFAllCourierDTO;

    public function getReportByCourier(string $id, Request $request): ReportPDFCourierDTO;
}
