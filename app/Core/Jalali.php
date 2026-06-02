<?php

declare(strict_types=1);

namespace App\Core;

final class Jalali
{
    public static function formatDate(?string $date): string
    {
        if (!$date) {
            return '-';
        }
        [$gy, $gm, $gd] = array_map('intval', explode('-', substr($date, 0, 10)));
        [$jy, $jm, $jd] = self::gregorianToJalali($gy, $gm, $gd);
        return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
    }

    public static function gregorianToJalali(int $gy, int $gm, int $gd): array
    {
        $gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $gdm[$gm - 1];
        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;
        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }
        $jm = $days < 186 ? 1 + intdiv($days, 31) : 7 + intdiv($days - 186, 30);
        $jd = 1 + ($days < 186 ? $days % 31 : ($days - 186) % 30);
        return [$jy, $jm, $jd];
    }
}
