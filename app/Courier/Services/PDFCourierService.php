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
        [$startDate, $endDate] = $this->validatedDates(
            $request->get('start_date'),
            $request->get('end_date')
        );

        $couriers = Auth::user()->couriers()
            ->select('id', 'first_name', 'last_name', 'phone')
            ->with(['deliveries' => function ($query) use ($startDate, $endDate) {
                $query->select('id', 'number', 'date', 'amount', 'status', 'cancellation_notes', 'courier_id', 'client_id')
                    ->whereBetween('date', [$startDate, $endDate])
                    ->with(['client:id,legal_name', 'anonymousClient:id,legal_name,delivery_id']);
            }])
            ->get()
            ->map(function ($courier) {
                return [
                    'id' => $courier->id,
                    'full_name' => trim($courier->first_name . ' ' . $courier->last_name),
                    'phone' => $courier->phone,
                    'deliveries' => $courier->deliveries->map(function ($delivery) {
                        $clientName = '-';
                        $isAnonymous = false;

                        if ($delivery->client && $delivery->client->legal_name) {
                            $clientName = $delivery->client->legal_name;
                            $isAnonymous = false;
                        } elseif ($delivery->anonymousClient && $delivery->anonymousClient->legal_name) {
                            $clientName = $delivery->anonymousClient->legal_name;
                            $isAnonymous = true;
                        }

                        return [
                            'number' => $delivery->number,
                            'date' => $delivery->date->format('d/m/Y'),
                            'client_name' => $clientName,
                            'is_anonymous_client' => $isAnonymous,
                            'amount' => (float)$delivery->amount,
                            'status' => $delivery->status,
                            'cancellation_notes' => $delivery->cancellation_notes,
                        ];
                    })->toArray(),
                ];
            })
            ->toArray();

        return new ReportPDFAllCourierDTO($couriers, $startDate, $endDate);
    }


    public function getReportByCourier(string $id, Request $request): ReportPDFCourierDTO
    {
        [$startDate, $endDate] = $this->validatedDates(
            $request->get('start_date'),
            $request->get('end_date')
        );

        $courier = Auth::user()->couriers()
            ->select('id', 'first_name', 'last_name', 'phone')
            ->findOrFail($id);

        $courier->load(['deliveries' => function ($query) use ($startDate, $endDate) {
            $query->select('id', 'number', 'date', 'amount', 'status', 'cancellation_notes', 'courier_id', 'client_id')
                ->whereBetween('date', [$startDate, $endDate])
                ->with(['client:id,legal_name', 'anonymousClient:id,legal_name,delivery_id']);
        }]);

        $deliveries = $courier->deliveries->map(function ($delivery) {
            $clientName = '-';
            $isAnonymous = false;

            if ($delivery->client && $delivery->client->legal_name) {
                $clientName = $delivery->client->legal_name;
                $isAnonymous = false;
            } elseif ($delivery->anonymousClient && $delivery->anonymousClient->legal_name) {
                $clientName = $delivery->anonymousClient->legal_name;
                $isAnonymous = true;
            }

            return [
                'number' => $delivery->number,
                'date' => $delivery->date->format('d/m/Y'),
                'client_name' => $clientName,
                'is_anonymous_client' => $isAnonymous,
                'amount' => (float)$delivery->amount,
                'status' => $delivery->status,
                'cancellation_notes' => $delivery->cancellation_notes,
            ];
        })->toArray();

        return new ReportPDFCourierDTO([
            'id' => $courier->id,
            'full_name' => trim($courier->first_name . ' ' . $courier->last_name),
            'phone' => $courier->phone,
            'deliveries' => $deliveries,
        ], $startDate, $endDate);
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
