<?php

namespace App\Delivery\DTO;

final class ReportPDFDeliveryDTO
{
    public int $id;
    public string $number;
    public string $date;
    public string $status;
    public string $payment_type;
    public string $payment_status;
    public float $amount;
    public string $pickup_address;
    public string $created_at;
    public string $notes;
    public string $service_name;
    public string $client_name;
    public string $courier_full_name;
    public string $full_name;
    public string $receipt_phone;
    public string $receipt_address;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->number = $data['number'];
        $this->date = $data['date'];
        $this->status = $data['status'];
        $this->payment_type = $data['payment_type'];
        $this->payment_status = $data['payment_status'];
        $this->amount = (float)$data['amount'];
        $this->pickup_address = $data['pickup_address'];
        $this->created_at = $data['created_at'];
        $this->notes = $data['notes'] ?? '';
        $this->service_name = $data['service']['name'];
        $this->client_name = $data['legal_name'] ?? 'cliente anónimo';
        $this->courier_full_name = $data['courier_full_name'];
        $this->full_name = $data['receipt']['full_name'];
        $this->receipt_phone = $data['receipt']['phone'];
        $this->receipt_address = $data['receipt']['address'];
    }
}
