<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

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

    // Scopes para filtrar por estado
    public function scopeReceived(Builder $query): Builder
    {
        return $query->where("status", "received");
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where("status", "cancelled");
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where("status", "pending");
    }

    public function scopeInTransit(Builder $query): Builder
    {
        return $query->where("status", "in_transit");
    }

    public function scopePaymentPending(Builder $query): Builder
    {
        return $query->where("payment_status", "pending");
    }

    public function scopePartiallyPaid(Builder $query): Builder
    {
        return $query->where("payment_status", "partial_paid");
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where("payment_status", "paid");
    }

    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public static function getReceived(): Collection
    {
        return static::received()->get();
    }

    public static function getCancelled(): Collection
    {
        return static::cancelled()->get();
    }

    public static function getPending(): Collection
    {
        return static::pending()->get();
    }

    public static function getInTransit(): Collection
    {
        return static::inTransit()->get();
    }

    public static function getPaymentPending(): Collection
    {
        return static::paymentPending()->get();
    }

    public static function getPartiallyPaid(): Collection
    {
        return static::partiallyPaid()->get();
    }

    public static function getPaid(): Collection
    {
        return static::paid()->get();
    }

    public static function getTotalDelivered($userId, $startDate, $endDate): int
    {
        return self::where('user_id', $userId)
            ->byPeriod($startDate, $endDate)
            ->count();
    }

    public static function getTotalInvoiced($userId, $startDate, $endDate): float
    {
        return (float) self::where('user_id', $userId)
            ->byPeriod($startDate, $endDate)
            ->sum('amount');
    }

    public static function getTotalCollected($userId, $startDate, $endDate): float
    {
        return (float) self::where('user_id', $userId)
            ->byPeriod($startDate, $endDate)
            ->with(['debt', 'debt.payments'])
            ->get()
            ->sum(function ($delivery) {
                if ($delivery->payment_status === 'paid') {
                    return $delivery->amount;
                } elseif ($delivery->payment_status === 'partial_paid' && $delivery->debt) {
                    return $delivery->debt->payments->sum('amount');
                }
                return 0;
            });
    }

    public static function getStatsByPeriod($userId, $startDate, $endDate): array
    {
        return [
            'total_delivered' => self::getTotalDelivered($userId, $startDate, $endDate),
            'total_invoiced' => self::getTotalInvoiced($userId, $startDate, $endDate),
            'total_collected' => self::getTotalCollected($userId, $startDate, $endDate)
        ];
    }

    public static function getHistoricalDelivered($userId, string $period, string $date): array
    {
        $date = Carbon::parse($date);
        $format = match ($period) {
            'day' => 'H:i',
            'week' => 'D',
            'month' => 'W',
            'year' => 'M',
            default => 'H:i',
        };

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
            ->groupBy(function ($delivery) use ($format, $period) {
                $deliveryDate = Carbon::parse($delivery->date);
                return match ($period) {
                    'day' => $deliveryDate->format('H:i'),
                    'week' => $deliveryDate->format('D'),
                    'month' => 'Week ' . $deliveryDate->weekOfMonth,
                    'year' => $deliveryDate->format('M'),
                    default => $deliveryDate->format('H:i'),
                };
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
        $format = match ($period) {
            'day' => 'H:i',
            'week' => 'D',
            'month' => 'W',
            'year' => 'M',
            default => 'H:i',
        };

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
            ->groupBy(function ($delivery) use ($format, $period) {
                $deliveryDate = Carbon::parse($delivery->date);
                return match ($period) {
                    'day' => $deliveryDate->format('H:i'),
                    'week' => $deliveryDate->format('D'),
                    'month' => 'Week ' . $deliveryDate->weekOfMonth,
                    'year' => $deliveryDate->format('M'),
                    default => $deliveryDate->format('H:i'),
                };
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
        $format = match ($period) {
            'day' => 'H:i',
            'week' => 'D',
            'month' => 'W',
            'year' => 'M',
            default => 'H:i',
        };

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
            ->groupBy(function ($delivery) use ($format, $period) {
                $deliveryDate = Carbon::parse($delivery->date);
                return match ($period) {
                    'day' => $deliveryDate->format('H:i'),
                    'week' => $deliveryDate->format('D'),
                    'month' => 'Week ' . $deliveryDate->weekOfMonth,
                    'year' => $deliveryDate->format('M'),
                    default => $deliveryDate->format('H:i'),
                };
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
        $format = match ($period) {
            'day' => 'H:i',
            'week' => 'D',
            'month' => 'W',
            'year' => 'M',
            default => 'H:i',
        };

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

        $groupedData = $deliveries->groupBy(function ($delivery) use ($format, $period) {
            $deliveryDate = Carbon::parse($delivery->date);
            return match ($period) {
                'day' => $deliveryDate->format('H:i'),
                'week' => $deliveryDate->format('D'),
                'month' => 'Week ' . $deliveryDate->weekOfMonth,
                'year' => $deliveryDate->format('M'),
                default => $deliveryDate->format('H:i'),
            };
        });

        $billsByDate = $companyBills->groupBy(function ($bill) use ($format, $period) {
            $billDate = Carbon::parse($bill->date);
            return match ($period) {
                'day' => $billDate->format('H:i'),
                'week' => $billDate->format('D'),
                'month' => 'Week ' . $billDate->weekOfMonth,
                'year' => $billDate->format('M'),
                default => $billDate->format('H:i'),
            };
        });

        $result = [];
        $allDates = array_unique(array_merge(
            $groupedData->keys()->toArray(),
            $billsByDate->keys()->toArray()
        ));

        foreach ($allDates as $dateKey) {
            $deliveryGroup = $groupedData->get($dateKey, collect());
            $billsGroup = $billsByDate->get($dateKey, collect());

            $totalCollected = (float) $deliveryGroup->sum(function ($delivery) {
                if ($delivery->payment_status === 'paid') {
                    return $delivery->amount;
                } elseif ($delivery->payment_status === 'partial_paid' && $delivery->debt) {
                    return $delivery->debt->payments->sum('amount');
                }
                return 0;
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
