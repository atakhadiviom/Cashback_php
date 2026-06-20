<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Jalali;
use PHPUnit\Framework\TestCase;

final class JalaliTest extends TestCase
{
    public function testFormatDateConvertsGregorianToJalali(): void
    {
        $this->assertSame('1403/01/01', Jalali::formatDate('2024-03-20'));
        $this->assertSame('-', Jalali::formatDate(null));
    }

    public function testParseInputToGregorianAcceptsJalaliGregorianAndPersianDigits(): void
    {
        $this->assertSame('2024-03-20', Jalali::parseInputToGregorian('1403/01/01'));
        $this->assertSame('2024-02-29', Jalali::parseInputToGregorian('2024-02-29'));
        $this->assertSame('2024-03-20', Jalali::parseInputToGregorian('۱۴۰۳/۰۱/۰۱'));
    }

    public function testParseInputToGregorianRejectsInvalidDates(): void
    {
        $this->assertNull(Jalali::parseInputToGregorian('not-a-date'));
        $this->assertNull(Jalali::parseInputToGregorian('2024-02-31'));
        $this->assertNull(Jalali::parseInputToGregorian('1100/01/01'));
    }

    public function testJalaliBirthdayUsesJalaliMonthDayNotGregorian(): void
    {
        $birth = Jalali::jalaliMonthDay('2024-03-20');
        $this->assertNotNull($birth);
        $this->assertSame(1, $birth['month']);
        $this->assertSame(1, $birth['day']);
        $this->assertFalse(Jalali::isJalaliBirthdayToday('2024-03-20'));
    }
}
