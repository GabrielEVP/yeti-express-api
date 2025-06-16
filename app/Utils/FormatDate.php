<?php

namespace App\Utils;

use Carbon\Carbon;

class FormatDate
{
    public function getPeriodDates(string $period, string $date): array
    {
        $parsedDate = Carbon::parse($date);

        $startDate = match ($period) {
            'day' => $parsedDate->copy()->startOfDay(),
            'week' => $parsedDate->copy()->startOfWeek(),
            'month' => $parsedDate->copy()->startOfMonth(),
            'year' => $parsedDate->copy()->startOfYear(),
            default => $parsedDate->copy()->startOfDay(),
        };

        $endDate = match ($period) {
            'day' => $parsedDate->copy()->endOfDay(),
            'week' => $parsedDate->copy()->endOfWeek(),
            'month' => $parsedDate->copy()->endOfMonth(),
            'year' => $parsedDate->copy()->endOfYear(),
            default => $parsedDate->copy()->endOfDay(),
        };

        return [
            $startDate->toDateTimeString(),
            $endDate->toDateTimeString()
        ];
    }

    public function formatDateLabel($deliveryDate, $period, $requestDate): string
    {
        $today = Carbon::today();
        $deliveryDay = Carbon::parse($deliveryDate->toDateString());

        return match ($period) {
            'day' => $deliveryDay->isSameDay($today) ? 'Hoy' : $deliveryDay->format('d/m'),
            'week' => $this->getSpanishDayName($deliveryDate->format('D')),
            'month' => 'Semana ' . $deliveryDate->weekOfMonth,
            'year' => $this->getSpanishMonthName($deliveryDate->format('M')),
            default => $deliveryDay->isSameDay($today) ? 'Hoy' : $deliveryDay->format('d/m'),
        };
    }

    private function getSpanishDayName($dayCode): string
    {
        return match ($dayCode) {
            'Mon' => 'Lunes',
            'Tue' => 'Martes',
            'Wed' => 'Miércoles',
            'Thu' => 'Jueves',
            'Fri' => 'Viernes',
            'Sat' => 'Sábado',
            'Sun' => 'Domingo',
            default => $dayCode,
        };
    }

    private function getSpanishMonthName($monthCode): string
    {
        return match ($monthCode) {
            'Jan' => 'Enero',
            'Feb' => 'Febrero',
            'Mar' => 'Marzo',
            'Apr' => 'Abril',
            'May' => 'Mayo',
            'Jun' => 'Junio',
            'Jul' => 'Julio',
            'Aug' => 'Agosto',
            'Sep' => 'Septiembre',
            'Oct' => 'Octubre',
            'Nov' => 'Noviembre',
            'Dec' => 'Diciembre',
            default => $monthCode,
        };
    }
}
