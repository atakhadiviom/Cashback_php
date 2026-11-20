<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Pure due-date arithmetic for club reminder rules.
 */
final class ReminderSchedule
{
    public const KINDS = [
        'birthday' => 'تبریک تولد',
        'winback' => 'بازگشت مشتری',
        'points_expiry' => 'هشدار انقضای امتیاز',
        'service_due' => 'یادآوری سرویس',
        'contract_renewal' => 'تمدید قرارداد',
        'custom' => 'یادآوری دستی',
    ];

    public static function dueAt(string $anchorDate, string $direction, int $offsetDays, string $timeOfDay = '10:00:00'): string
    {
        $anchor = substr(trim($anchorDate), 0, 10);
        $timestamp = strtotime($anchor . ' 00:00:00');
        if ($timestamp === false) {
            return '';
        }
        $days = abs($offsetDays);
        $delta = $direction === 'before' ? '-' . $days . ' days' : '+' . $days . ' days';
        $shifted = strtotime($delta, $timestamp);
        if ($shifted === false) {
            return '';
        }

        return date('Y-m-d', $shifted) . ' ' . self::normalizeTime($timeOfDay);
    }

    /**
     * Due date of the next birthday reminder (a birthday is a recurring date and
     * must be projected onto the current year, rolling into the next one).
     */
    public static function birthdayDueAt(string $birthday, int $offsetDays, string $timeOfDay, ?string $today = null): string
    {
        $birthday = substr(trim($birthday), 0, 10);
        $stamp = strtotime($birthday);
        if ($stamp === false) {
            return '';
        }
        $today = $today ?? date('Y-m-d');
        $monthDay = date('m-d', $stamp);
        $year = (int) date('Y', strtotime($today));

        $candidate = self::dueAt($year . '-' . $monthDay, 'before', $offsetDays, $timeOfDay);
        if ($candidate !== '' && substr($candidate, 0, 10) < $today) {
            $candidate = self::dueAt(($year + 1) . '-' . $monthDay, 'before', $offsetDays, $timeOfDay);
        }

        return $candidate;
    }

    public static function normalizeTime(string $time): string
    {
        if (!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', trim($time), $matches)) {
            return '10:00:00';
        }

        return sprintf(
            '%02d:%02d:%02d',
            min(23, max(0, (int) $matches[1])),
            min(59, max(0, (int) $matches[2])),
            isset($matches[3]) ? min(59, max(0, (int) $matches[3])) : 0
        );
    }

    public static function kindLabel(string $kind): string
    {
        return self::KINDS[$kind] ?? $kind;
    }
}
