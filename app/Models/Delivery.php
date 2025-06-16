<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\utils\FormatDate;
use Illuminate\Support\Facades\DB;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        "number",
        "date",
        "status",
        "payment_type",
        "payment_status",
        "amount",
        "notes",
        "service_id",
        "client_id",
        "client_address_id",
        "courier_id",
        "user_id",
    ];

    protected $casts = [
        "date" => "date",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function clientAddress()
    {
        return $this->belongsTo(ClientAddress::class);
    }

    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }

    public function receipt()
    {
        return $this->hasOne(DeliveryReceipt::class);
    }

    public function events()
    {
        return $this->hasMany(DeliveryEvent::class);
    }

    public function debt()
    {
        return $this->hasOne(Debt::class);
    }

    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public static function getTotalDelivered($userId, $startDate, $endDate): int
    {
        return self::where('user_id', $userId)
            ->whereNot('status', 'cancelled')
            ->whereNot('status', 'pending')
            ->byPeriod($startDate, $endDate)
            ->count();
    }

    public static function getTotalInvoiced($userId, $startDate, $endDate): float
    {
        return (float) self::where('user_id', $userId)
            ->whereNot('status', 'cancelled')
            ->whereNot('status', 'pending')
            ->byPeriod($startDate, $endDate)
            ->sum('amount');
    }

    public static function getTotalCollected($userId, $startDate, $endDate): float
    {
        return (float) DebtPayment::query()
            ->select(DB::raw('SUM(debt_payments.amount) as total'))
            ->join('debts', 'debt_payments.debt_id', '=', 'debts.id')
            ->join('deliveries', 'debts.delivery_id', '=', 'deliveries.id')
            ->where('deliveries.user_id', $userId)
            ->whereBetween('debt_payments.created_at', [$startDate, $endDate])
            ->value('total') ?? 0;
    }

    public static function getStatsByPeriod($userId, $startDate, $endDate): array
    {
        return [
            'total_delivered' => self::getTotalDelivered($userId, $startDate, $endDate),
            'total_invoiced' => self::getTotalInvoiced($userId, $startDate, $endDate),
            'total_collected' => self::getTotalCollected($userId, $startDate, $endDate)
        ];
    }

    private static function formatDateLabel($deliveryDate, $period, $requestDate): string
    {
        $today = Carbon::today();
        $deliveryDay = Carbon::parse($deliveryDate->toDateString());

        return match ($period) {
            'day' => $deliveryDay->isSameDay($today) ? 'Hoy' : $deliveryDay->format('d/m'),
            'week' => FormatDate::getSpanishDayName($deliveryDate->format('D')),
            'month' => 'Semana ' . $deliveryDate->weekOfMonth,
            'year' => FormatDate::getSpanishMonthName($deliveryDate->format('M')),
            default => $deliveryDay->isSameDay($today) ? 'Hoy' : $deliveryDay->format('d/m'),
        };
    }

    public static function getHistoricalDelivered($userId, string $period, string $date): array
    {
        $date = Carbon::parse($date);

        $startDate = match ($period) {
            'day' => Carbon::parse($date)->startOfDay(),
            'week' => Carbon::parse($date)->startOfWeek(),
            'month' => Carbon::parse($date)->startOfMonth(),
            'year' => Carbon::parse($date)->startOfYear(),
            default => Carbon::parse($date)->startOfDay(),
        };

        $endDate = match ($period) {
            'day' => Carbon::parse($date)->endOfDay(),
            'week' => Carbon::parse($date)->endOfWeek(),
            'month' => Carbon::parse($date)->endOfMonth(),
            'year' => Carbon::parse($date)->endOfYear(),
            default => Carbon::parse($date)->endOfDay(),
        };

        return self::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($delivery) use ($period, $date) {
                $deliveryDate = Carbon::parse($delivery->date);
                return self::formatDateLabel($deliveryDate, $period, $date);
            })
            ->map(function ($group) {
                return $group->count();
            })
            ->map(function ($count, $date) {
                return ['date' => $date, 'total' => $count];
            })
            ->values()
            ->toArray();
    }

    public static function getHistoricalInvoiced($userId, string $period, string $date): array
    {
        $date = Carbon::parse($date);

        $startDate = match ($period) {
            'day' => Carbon::parse($date)->startOfDay(),
            'week' => Carbon::parse($date)->startOfWeek(),
            'month' => Carbon::parse($date)->startOfMonth(),
            'year' => Carbon::parse($date)->startOfYear(),
            default => Carbon::parse($date)->startOfDay(),
        };

        $endDate = match ($period) {
            'day' => Carbon::parse($date)->endOfDay(),
            'week' => Carbon::parse($date)->endOfWeek(),
            'month' => Carbon::parse($date)->endOfMonth(),
            'year' => Carbon::parse($date)->endOfYear(),
            default => Carbon::parse($date)->endOfDay(),
        };

        return self::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($delivery) use ($period, $date) {
                $deliveryDate = Carbon::parse($delivery->date);
                return self::formatDateLabel($deliveryDate, $period, $date);
            })
            ->map(function ($group) {
                return $group->sum('amount');
            })
            ->map(function ($total, $date) {
                return ['date' => $date, 'total' => (float) $total];
            })
            ->values()
            ->toArray();
    }

    public static function getHistoricalCollected($userId, string $period, string $date): array
    {
        $date = Carbon::parse($date);

        $startDate = match ($period) {
            'day' => Carbon::parse($date)->startOfDay(),
            'week' => Carbon::parse($date)->startOfWeek(),
            'month' => Carbon::parse($date)->startOfMonth(),
            'year' => Carbon::parse($date)->startOfYear(),
            default => Carbon::parse($date)->startOfDay(),
        };

        $endDate = match ($period) {
            'day' => Carbon::parse($date)->endOfDay(),
            'week' => Carbon::parse($date)->endOfWeek(),
            'month' => Carbon::parse($date)->endOfMonth(),
            'year' => Carbon::parse($date)->endOfYear(),
            default => Carbon::parse($date)->endOfDay(),
        };

        return self::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['debt', 'debt.payments'])
            ->get()
            ->groupBy(function ($delivery) use ($period, $date) {
                $deliveryDate = Carbon::parse($delivery->date);
                return self::formatDateLabel($deliveryDate, $period, $date);
            })
            ->map(function ($group) {
                return $group->sum(function ($delivery) {
                    if ($delivery->payment_status === 'paid') {
                        return $delivery->amount;
                    } elseif ($delivery->payment_status === 'partial_paid' && $delivery->debt) {
                        return $delivery->debt->payments->sum('amount');
                    }
                    return 0;
                });
            })
            ->map(function ($total, $date) {
                return ['date' => $date, 'total' => (float) $total];
            })
            ->values()
            ->toArray();
    }

    public static function getHistoricalBalance($userId, string $period, string $date): array
    {
        $date = Carbon::parse($date);

        $startDate = match ($period) {
            'day' => Carbon::parse($date)->startOfDay(),
            'week' => Carbon::parse($date)->startOfWeek(),
            'month' => Carbon::parse($date)->startOfMonth(),
            'year' => Carbon::parse($date)->startOfYear(),
            default => Carbon::parse($date)->startOfDay(),
        };

        $endDate = match ($period) {
            'day' => Carbon::parse($date)->endOfDay(),
            'week' => Carbon::parse($date)->endOfWeek(),
            'month' => Carbon::parse($date)->endOfMonth(),
            'year' => Carbon::parse($date)->endOfYear(),
            default => Carbon::parse($date)->endOfDay(),
        };

        $deliveries = self::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['debt', 'debt.payments'])
            ->get();

        $companyBills = \App\Models\CompanyBill::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $groupedData = $deliveries->groupBy(function ($delivery) use ($period, $date) {
            $deliveryDate = Carbon::parse($delivery->date);
            return self::formatDateLabel($deliveryDate, $period, $date);
        });

        $billsByDate = $companyBills->groupBy(function ($bill) use ($period, $date) {
            $billDate = Carbon::parse($bill->date);
            return self::formatDateLabel($billDate, $period, $date);
        });

        $result = [];
        $allDates = array_unique(array_merge(
            $groupedData->keys()->toArray(),
            $billsByDate->keys()->toArray()
        ));

        $debtPayments = DebtPayment::query()
            ->select('debt_payments.amount', 'debts.delivery_id')
            ->join('debts', 'debt_payments.debt_id', '=', 'debts.id')
            ->join('deliveries', 'debts.delivery_id', '=', 'deliveries.id')
            ->where('deliveries.user_id', $userId)
            ->whereBetween('debt_payments.created_at', [$startDate, $endDate])
            ->get()
            ->groupBy('delivery_id');

        foreach ($allDates as $dateKey) {
            $deliveryGroup = $groupedData->get($dateKey, collect());
            $billsGroup = $billsByDate->get($dateKey, collect());

            $totalCollected = (float) $deliveryGroup->sum(function ($delivery) use ($debtPayments) {
                return $debtPayments->get($delivery->id, collect())->sum('amount') ?? 0;
            });

            $totalExpenses = (float) $billsGroup->sum('amount');
            $balance = $totalCollected - $totalExpenses;

            $result[] = [
                'date' => $dateKey,
                'total_collected' => $totalCollected,
                'total_expenses' => $totalExpenses,
                'balance' => $balance
            ];
        }

        return $result;
    }
}