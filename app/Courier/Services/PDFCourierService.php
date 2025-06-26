<?php

namespace App\Courier\Services;

use App\Courier\DTO\ReportPDFAllCourierDTO;
use App\Courier\DTO\ReportPDFCourierDTO;
use App\Courier\Repositories\IPDFCourierRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PDFCourierService implements IPDFCourierRepository
{
    public function getAllReportCourier(Request $request): ReportPDFAllCourierDTO
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        [$startDate, $endDate] = $this->validatedDates($startDate, $endDate);

        $couriers = Auth::user()->couriers()->with([
            'deliveries' => function ($query) use ($startDate, $endDate) {
                $query->byPeriod($startDate, $endDate)
                    ->with(['service', 'client', 'receipt']);
            }
        ])->get();

        return new ReportPDFAllCourierDTO($couriers, $startDate, $endDate);
    }

    public function getReportByCourier(string $id, Request $request): ReportPDFCourierDTO
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        [$startDate, $endDate] = $this->validatedDates($startDate, $endDate);

        $courier = Auth::user()->couriers()
            ->with([
                'deliveries' => function ($query) use ($startDate, $endDate) {
                    $query->byPeriod($startDate, $endDate)
                        ->with(['service', 'client', 'receipt']);
                }
            ])
            ->findOrFail($id);

        $normalizedData = [
            'id' => $courier->id,
            'first_name' => $courier->first_name,
            'last_name' => $courier->last_name,
            'phone' => $courier->phone,
            'deliveries' => $courier->deliveries->map(function ($delivery) {
                return [
                    'id' => $delivery->id,
                    'created_at' => $delivery->created_at->format('d/m/Y'),
                    'client' => $delivery->client?->name ?? '-',
                    'service' => $delivery->service?->name ?? '-',
                    'receipt' => $delivery->receipt?->number ?? '-',
                    'amount' => (float)$delivery->amount,
                    'status' => $delivery->status ?? 'pending',
                ];
            })->toArray(),
        ];

        return new ReportPDFCourierDTO($normalizedData, $startDate, $endDate);
    }

    private function validatedDates(?string $startDate, ?string $endDate): array
    {
        if (!$startDate || !$endDate) {
            $startDate = now()->toDateString();
            $endDate = now()->toDateString();
        }

        return [$startDate, $endDate];
    }
}
