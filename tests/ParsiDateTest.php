<?php

declare(strict_types=1);

namespace ParsiDate\Tests;

use ParsiDate\Exceptions\InvalidDateException;
use ParsiDate\ParsiDate;
use PHPUnit\Framework\TestCase;

class ParsiDateTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Factories
    // -------------------------------------------------------------------------

    public function test_create_valid_date(): void
    {
        $d = ParsiDate::create(1403, 1, 1);
        $this->assertSame(1403, $d->year());
        $this->assertSame(1, $d->month());
        $this->assertSame(1, $d->day());
    }

    public function test_create_invalid_month_throws(): void
    {
        $this->expectException(InvalidDateException::class);
        ParsiDate::create(1403, 13, 1);
    }

    public function test_create_invalid_day_throws(): void
    {
        $this->expectException(InvalidDateException::class);
        ParsiDate::create(1403, 7, 31); // months 7-11 have 30 days
    }

    public function test_create_day_29_esfand_non_leap_throws(): void
    {
        $this->expectException(InvalidDateException::class);
        ParsiDate::create(1402, 12, 30); // 1402 is not a leap year
    }

    public function test_create_day_30_esfand_leap_is_valid(): void
    {
        $d = ParsiDate::create(1403, 12, 30); // 1403 is a leap year
        $this->assertSame(30, $d->day());
    }

    public function test_from_gregorian(): void
    {
        $d = ParsiDate::fromGregorian(2024, 3, 20);
        $this->assertSame(1403, $d->year());
        $this->assertSame(1, $d->month());
        $this->assertSame(1, $d->day());
    }

    public function test_from_gregorian_leap_esfand_30th(): void
    {
        // Regression: leap-year Esfand 30th used to decode as month 13
        $d = ParsiDate::fromGregorian(2025, 3, 20);
        $this->assertSame('1403/12/30', $d->format('Y/m/d'));
        $d = ParsiDate::fromGregorian(2030, 3, 20);
        $this->assertSame('1408/12/30', $d->format('Y/m/d'));
    }

    public function test_parse_slash_format(): void
    {
        $d = ParsiDate::parse('1403/06/15');
        $this->assertSame(1403, $d->year());
        $this->assertSame(6, $d->month());
        $this->assertSame(15, $d->day());
    }

    public function test_parse_dash_format(): void
    {
        $d = ParsiDate::parse('1403-06-15');
        $this->assertSame(15, $d->day());
    }

    public function test_parse_compact_format(): void
    {
        $d = ParsiDate::parse('14030615');
        $this->assertSame(1403, $d->year());
        $this->assertSame(6, $d->month());
    }

    public function test_parse_persian_digits(): void
    {
        $d = ParsiDate::parse('۱۴۰۳/۰۱/۰۱');
        $this->assertSame(1403, $d->year());
    }

    public function test_parse_invalid_throws(): void
    {
        $this->expectException(InvalidDateException::class);
        ParsiDate::parse('not-a-date');
    }

    // -------------------------------------------------------------------------
    // Getters & Info
    // -------------------------------------------------------------------------

    public function test_is_leap_year(): void
    {
        $this->assertTrue(ParsiDate::create(1403, 1, 1)->isLeapYear());
        $this->assertFalse(ParsiDate::create(1402, 1, 1)->isLeapYear());
    }

    public function test_days_in_month(): void
    {
        $this->assertSame(31, ParsiDate::create(1403, 1, 1)->daysInMonth());
        $this->assertSame(30, ParsiDate::create(1403, 7, 1)->daysInMonth());
        $this->assertSame(30, ParsiDate::create(1403, 12, 1)->daysInMonth()); // leap
        $this->assertSame(29, ParsiDate::create(1402, 12, 1)->daysInMonth()); // non-leap
    }

    public function test_day_of_year(): void
    {
        $this->assertSame(1, ParsiDate::create(1403, 1, 1)->dayOfYear());
        $this->assertSame(32, ParsiDate::create(1403, 2, 1)->dayOfYear());
        $this->assertSame(366, ParsiDate::create(1403, 12, 30)->dayOfYear()); // leap year
    }

    public function test_month_name_persian(): void
    {
        $this->assertSame('فروردین', ParsiDate::create(1403, 1, 1)->monthName());
        $this->assertSame('اسفند', ParsiDate::create(1403, 12, 1)->monthName());
    }

    public function test_month_name_english(): void
    {
        $this->assertSame('Farvardin', ParsiDate::create(1403, 1, 1)->monthName(persian: false));
    }

    // -------------------------------------------------------------------------
    // Arithmetic
    // -------------------------------------------------------------------------

    public function test_add_days(): void
    {
        $d = ParsiDate::create(1403, 1, 30)->addDays(2);
        $this->assertSame(1403, $d->year());
        $this->assertSame(2, $d->month());
        $this->assertSame(1, $d->day());
    }

    public function test_sub_days(): void
    {
        $d = ParsiDate::create(1403, 2, 1)->subDays(1);
        $this->assertSame(1, $d->month());
        $this->assertSame(31, $d->day());
    }

    public function test_add_months_simple(): void
    {
        $d = ParsiDate::create(1403, 1, 15)->addMonths(3);
        $this->assertSame(4, $d->month());
        $this->assertSame(15, $d->day());
    }

    public function test_add_months_year_overflow(): void
    {
        $d = ParsiDate::create(1403, 11, 1)->addMonths(3);
        $this->assertSame(1404, $d->year());
        $this->assertSame(2, $d->month());
    }

    public function test_add_months_clamps_day(): void
    {
        // Shahrivar (month 6) has 31 days, Mehr (month 7) has 30
        $d = ParsiDate::create(1403, 6, 31)->addMonths(1);
        $this->assertSame(7, $d->month());
        $this->assertSame(30, $d->day()); // clamped
    }

    public function test_add_years(): void
    {
        $d = ParsiDate::create(1403, 5, 10)->addYears(2);
        $this->assertSame(1405, $d->year());
    }

    public function test_start_of_month(): void
    {
        $d = ParsiDate::create(1403, 6, 25)->startOfMonth();
        $this->assertSame(1, $d->day());
    }

    public function test_end_of_month(): void
    {
        $d = ParsiDate::create(1403, 1, 5)->endOfMonth();
        $this->assertSame(31, $d->day());
    }

    public function test_start_of_year(): void
    {
        $d = ParsiDate::create(1403, 8, 20)->startOfYear();
        $this->assertSame(1, $d->month());
        $this->assertSame(1, $d->day());
    }

    public function test_end_of_year_leap(): void
    {
        $d = ParsiDate::create(1403, 1, 1)->endOfYear();
        $this->assertSame(12, $d->month());
        $this->assertSame(30, $d->day()); // leap year
    }

    public function test_end_of_year_non_leap(): void
    {
        $d = ParsiDate::create(1402, 1, 1)->endOfYear();
        $this->assertSame(29, $d->day());
    }

    // -------------------------------------------------------------------------
    // Comparison
    // -------------------------------------------------------------------------

    public function test_equal_to(): void
    {
        $a = ParsiDate::create(1403, 1, 1);
        $b = ParsiDate::create(1403, 1, 1);
        $c = ParsiDate::create(1403, 1, 2);
        $this->assertTrue($a->equalTo($b));
        $this->assertFalse($a->equalTo($c));
    }

    public function test_before_after(): void
    {
        $a = ParsiDate::create(1403, 1, 1);
        $b = ParsiDate::create(1403, 6, 1);
        $this->assertTrue($a->before($b));
        $this->assertTrue($b->after($a));
        $this->assertFalse($b->before($a));
    }

    public function test_between(): void
    {
        $start = ParsiDate::create(1403, 1, 1);
        $end = ParsiDate::create(1403, 12, 29);
        $mid = ParsiDate::create(1403, 6, 15);
        $this->assertTrue($mid->between($start, $end));
        $this->assertTrue($start->between($start, $end)); // inclusive
    }

    public function test_diff_in_days(): void
    {
        $a = ParsiDate::create(1403, 1, 1);
        $b = ParsiDate::create(1403, 1, 11);
        $this->assertSame(10, $a->diffInDays($b));
        $this->assertSame(-10, $b->diffInDays($a));
    }

    public function test_diff_in_months(): void
    {
        $a = ParsiDate::create(1403, 1, 1);
        $b = ParsiDate::create(1403, 7, 1);
        $this->assertSame(6, $a->diffInMonths($b));
    }

    // -------------------------------------------------------------------------
    // Formatting
    // -------------------------------------------------------------------------

    public function test_format_basic(): void
    {
        $d = ParsiDate::create(1403, 1, 5);
        $this->assertSame('1403/01/05', $d->format('Y/m/d'));
        $this->assertSame('5 فروردین 1403', $d->format('j M Y'));
    }

    public function test_format_persian_digits(): void
    {
        $d = ParsiDate::create(1403, 1, 5);
        $this->assertSame('۱۴۰۳/۰۱/۰۵', $d->formatPersian('Y/m/d'));
    }

    public function test_to_string(): void
    {
        $d = ParsiDate::create(1403, 6, 15);
        $this->assertSame('1403/06/15', (string) $d);
    }

    // -------------------------------------------------------------------------
    // Immutability
    // -------------------------------------------------------------------------

    public function test_immutability(): void
    {
        $original = ParsiDate::create(1403, 1, 1);
        $modified = $original->addDays(10);
        $this->assertNotSame($original, $modified);
        $this->assertSame(1, $original->day()); // unchanged
    }

    // -------------------------------------------------------------------------
    // Conversion
    // -------------------------------------------------------------------------

    public function test_to_gregorian(): void
    {
        $g = ParsiDate::create(1403, 1, 1)->toGregorian();
        $this->assertSame(['year' => 2024, 'month' => 3, 'day' => 20], $g);
    }

    public function test_to_array(): void
    {
        $a = ParsiDate::create(1403, 6, 15)->toArray();
        $this->assertSame(['year' => 1403, 'month' => 6, 'day' => 15], $a);
    }
}
