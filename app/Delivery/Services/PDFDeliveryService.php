<?php

namespace App\Delivery\Services;

use App\Delivery\DTO\ReportPDFDeliveryDTO;
use App\Delivery\Models\Delivery;
use App\Delivery\Repositories\IPDFDeliveryRepository;

class PDFDeliveryService implements IPDFDeliveryRepository
{
    public function getTicketReportDelivery(string $id): ReportPDFDeliveryDTO
    {
        $delivery = Delivery::query()
            ->select([
                'id',
                'number',
                'date',
                'status',
                'payment_type',
                'payment_status',
                'amount',
                'pickup_address',
                'created_at',
                'notes',
                'service_id',
                'client_id',
                'courier_id',
            ])
            ->with([
                'service:id,name',
                'client:id,legal_name',
                'client.phones:id,client_id,phone',
                'courier:id,first_name,last_name',
                'receipt:id,delivery_id,full_name,phone,address',
            ])
            ->findOrFail($id);


        return new ReportPDFDeliveryDTO([
            'id' => $delivery->id,
            'number' => $delivery->number,
            'date' => optional($delivery->date)->format('Y-m-d'),
            'status' => $delivery->status->value,
            'payment_type' => $delivery->payment_type->value,
            'payment_status' => $delivery->payment_status->value,
            'amount' => (float)$delivery->amount,
            'pickup_address' => $delivery->pickup_address,
            'created_at' => $delivery->created_at->toDateTimeString(),
            'notes' => $delivery->notes,
            'service' => [
                'name' => $delivery->service->name,
            ],
            'client' => $delivery->client_id ? [
                'legal_name' => $delivery->client->legal_name,
            ] : null,
            'courier_full_name' => trim($delivery->courier->first_name . ' ' . $delivery->courier->last_name),
            'receipt' => [
                'full_name' => $delivery->receipt->full_name,
                'phone' => $delivery->receipt->phone,
                'address' => $delivery->receipt->address,
            ],
        ]);
    }
}
