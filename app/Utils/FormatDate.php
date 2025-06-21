<?php

namespace App\Utils;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FormatDate
{
    public function getPeriodDates(string $period, string $date): array
    {
        try {
            $date = trim($date);

            if (strpos($date, '/') !== false) {
                $parts = explode('/', $date);
                if (count($parts) === 3) {
                    $date = "{$parts[2]}-{$parts[1]}-{$parts[0]}";
                }
            }

            $parsedDate = Carbon::parse($date);
        } catch (\Exception $e) {
            Log::warning("Error parsing date in getPeriodDates: {$date} - {$e->getMessage()}");
            $parsedDate = Carbon::now();
        }

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

    public function formatDateLabel(Carbon $date, string $period, Carbon $referenceDate): string
    {
        try {
            return match ($period) {
                'day' => $date->format('Y-m-d'),
                'week' => 'Week ' . $date->weekOfYear . ' - ' . $date->year,
                'month' => $date->format('Y-m'),
                'year' => (string)$date->year,
                default => $date->format('Y-m-d'),
            };
        } catch (\Exception $e) {
            Log::error("Error formateando etiqueta de fecha: {$e->getMessage()}");
            return $date->format('Y-m-d');
        }
    }

    public function formatDateLabelDisplay(Carbon $deliveryDate, string $period, Carbon $requestDate): string
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

    private function getSpanishDayName(string $dayCode): string
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

    private function getSpanishMonthName(string $monthCode): string
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
