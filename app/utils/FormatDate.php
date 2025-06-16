<?php
namespace App\Utils;

class FormatDate
{
    private const DAYS = [
        'Mon' => 'Lun',
        'Tue' => 'Mar',
        'Wed' => 'Mié',
        'Thu' => 'Jue',
        'Fri' => 'Vie',
        'Sat' => 'Sáb',
        'Sun' => 'Dom'
    ];

    private const MONTHS = [
        'Jan' => 'Ene',
        'Feb' => 'Feb',
        'Mar' => 'Mar',
        'Apr' => 'Abr',
        'May' => 'May',
        'Jun' => 'Jun',
        'Jul' => 'Jul',
        'Aug' => 'Ago',
        'Sep' => 'Sep',
        'Oct' => 'Oct',
        'Nov' => 'Nov',
        'Dec' => 'Dic'
    ];

    public static function getSpanishDayName(string $dayAbbr): string
    {
        return self::DAYS[$dayAbbr] ?? $dayAbbr;
    }

    public static function getSpanishMonthName(string $monthAbbr): string
    {
        return self::MONTHS[$monthAbbr] ?? $monthAbbr;
    }
}
