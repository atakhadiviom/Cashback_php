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

    public static function jalaliToGregorian(int $jy, int $jm, int $jd): array
    {
        $jalaliMonthDays = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
        $gregorianMonthDays = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        $jalaliYear = $jy - 979;
        $jalaliMonth = $jm - 1;
        $jalaliDay = $jd - 1;

        $jalaliDayNumber = 365 * $jalaliYear + intdiv($jalaliYear, 33) * 8 + intdiv(($jalaliYear % 33) + 3, 4);
        for ($i = 0; $i < $jalaliMonth; $i++) {
            $jalaliDayNumber += $jalaliMonthDays[$i];
        }
        $jalaliDayNumber += $jalaliDay;

        $gregorianDayNumber = $jalaliDayNumber + 79;
        $gy = 1600 + 400 * intdiv($gregorianDayNumber, 146097);
        $gregorianDayNumber %= 146097;

        $leap = 1;
        if ($gregorianDayNumber >= 36525) {
            $gregorianDayNumber--;
            $gy += 100 * intdiv($gregorianDayNumber, 36524);
            $gregorianDayNumber %= 36524;
            if ($gregorianDayNumber >= 365) {
                $gregorianDayNumber++;
            } else {
                $leap = 0;
            }
        }

        $gy += 4 * intdiv($gregorianDayNumber, 1461);
        $gregorianDayNumber %= 1461;

        if ($gregorianDayNumber >= 366) {
            $leap = 0;
            $gregorianDayNumber--;
            $gy += intdiv($gregorianDayNumber, 365);
            $gregorianDayNumber %= 365;
        }

        for ($i = 0; $gregorianDayNumber >= $gregorianMonthDays[$i] + ($i === 1 ? $leap : 0); $i++) {
            $gregorianDayNumber -= $gregorianMonthDays[$i] + ($i === 1 ? $leap : 0);
        }

        return [$gy, $i + 1, $gregorianDayNumber + 1];
    }

    /** Value for Jalali datepicker input (YYYY/MM/DD). */
    public static function toInputValue(?string $gregorianDate): string
    {
        if (!$gregorianDate) {
            return '';
        }

        return self::formatDate($gregorianDate);
    }

    /**
     * Parse Jalali (1403/01/15) or Gregorian (2024-01-15) input to Y-m-d for MySQL.
     * Returns empty string when input is empty, null when invalid.
     */
    public static function parseInputToGregorian(string $input): ?string
    {
        $input = trim(\normalize_digits($input));
        if ($input === '') {
            return '';
        }

        if (!preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $input, $matches)) {
            return null;
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        if ($year >= 1900 && $year <= 2100) {
            if (!checkdate($month, $day, $year)) {
                return null;
            }

            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        if ($year < 1200 || $year > 1600 || $month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return null;
        }

        [$gy, $gm, $gd] = self::jalaliToGregorian($year, $month, $day);
        if (!checkdate($gm, $gd, $gy)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }

    /** @return array{month: int, day: int}|null */
    public static function jalaliMonthDay(?string $gregorianDate): ?array
    {
        if (!$gregorianDate) {
            return null;
        }
        [$gy, $gm, $gd] = array_map('intval', explode('-', substr($gregorianDate, 0, 10)));
        [$jy, $jm, $jd] = self::gregorianToJalali($gy, $gm, $gd);
        return ['month' => $jm, 'day' => $jd];
    }

    /** @return array{month: int, day: int} */
    public static function todayJalaliMonthDay(): array
    {
        [$jy, $jm, $jd] = self::gregorianToJalali((int) date('Y'), (int) date('n'), (int) date('j'));
        return ['month' => $jm, 'day' => $jd];
    }

    public static function isJalaliBirthdayToday(?string $gregorianBirthDate): bool
    {
        $birth = self::jalaliMonthDay($gregorianBirthDate);
        if ($birth === null) {
            return false;
        }
        $today = self::todayJalaliMonthDay();
        return $birth['month'] === $today['month'] && $birth['day'] === $today['day'];
    }
}
