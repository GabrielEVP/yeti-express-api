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

            if (str_contains($date, '/')) {
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

        // Soportar tanto períodos en inglés como en español
        $startDate = match ($period) {
            'day', 'dia' => $parsedDate->copy()->startOfDay(),
            'week', 'semana' => $parsedDate->copy()->startOfWeek(),
            'month', 'mes' => $parsedDate->copy()->startOfMonth(),
            'year', 'año' => $parsedDate->copy()->startOfYear(),
            default => $parsedDate->copy()->startOfDay(),
        };

        $endDate = match ($period) {
            'day', 'dia' => $parsedDate->copy()->endOfDay(),
            'week', 'semana' => $parsedDate->copy()->endOfWeek(),
            'month', 'mes' => $parsedDate->copy()->endOfMonth(),
            'year', 'año' => $parsedDate->copy()->endOfYear(),
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
            // Traducir etiquetas al español correctamente según el periodo
            return match ($period) {
                'day', 'dia' => $date->isToday() ? 'Hoy' : $date->format('Y-m-d'),
                'week', 'semana' => ucfirst($date->locale('es')->dayName),  // Lunes, Martes, etc.
                'month', 'mes' => 'Semana ' . $date->weekOfMonth,  // 'Semana 1', 'Semana 2', etc.
                'year', 'año' => ucfirst($date->locale('es')->monthName),  // Enero, Febrero, etc.
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

        // Definir etiquetas exactamente como se requiere
        return match ($period) {
            'day', 'dia' => 'Hoy',
            'week', 'semana' => ucfirst($deliveryDate->locale('es')->dayName), // Lunes, Martes, etc.
            'month', 'mes' => 'Semana ' . $deliveryDate->weekOfMonth,
            'year', 'año' => ucfirst($deliveryDate->locale('es')->monthName), // Enero, Febrero, etc.
            default => 'Hoy',
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
