<?php

declare(strict_types=1);

namespace ParsiDate\Tests;

use ParsiDate\ParsiDateTime;
use ParsiDate\Exceptions\InvalidDateException;
use PHPUnit\Framework\TestCase;

class ParsiDateTimeTest extends TestCase
{
    public function test_create(): void
    {
        $dt = ParsiDateTime::create(1403, 6, 15, 14, 30, 45);
        $this->assertSame(1403, $dt->year());
        $this->assertSame(6,    $dt->month());
        $this->assertSame(15,   $dt->day());
        $this->assertSame(14,   $dt->hour());
        $this->assertSame(30,   $dt->minute());
        $this->assertSame(45,   $dt->second());
    }

    public function test_invalid_hour_throws(): void
    {
        $this->expectException(InvalidDateException::class);
        ParsiDateTime::create(1403, 1, 1, 25, 0, 0);
    }

    public function test_from_gregorian(): void
    {
        $dt = ParsiDateTime::fromGregorian(2024, 3, 20, 12, 0, 0);
        $this->assertSame(1403, $dt->year());
        $this->assertSame(1, $dt->month());
        $this->assertSame(1, $dt->day());
        $this->assertSame(12, $dt->hour());
    }

    public function test_add_hours(): void
    {
        $dt   = ParsiDateTime::create(1403, 1, 1, 22, 0, 0);
        $next = $dt->addHours(3);
        $this->assertSame(1, $next->hour());
        $this->assertSame(2, $next->day()); // rolled over to next day
    }

    public function test_add_minutes(): void
    {
        $dt   = ParsiDateTime::create(1403, 1, 1, 10, 50, 0);
        $next = $dt->addMinutes(15);
        $this->assertSame(11, $next->hour());
        $this->assertSame(5,  $next->minute());
    }

    public function test_set_time(): void
    {
        $dt = ParsiDateTime::create(1403, 1, 1, 8, 0, 0)->setTime(14, 30, 0);
        $this->assertSame(14, $dt->hour());
        $this->assertSame(30, $dt->minute());
    }

    public function test_start_of_day(): void
    {
        $dt = ParsiDateTime::create(1403, 5, 10, 15, 45, 30)->startOfDay();
        $this->assertSame(0, $dt->hour());
        $this->assertSame(0, $dt->minute());
        $this->assertSame(0, $dt->second());
    }

    public function test_end_of_day(): void
    {
        $dt = ParsiDateTime::create(1403, 5, 10, 0, 0, 0)->endOfDay();
        $this->assertSame(23, $dt->hour());
        $this->assertSame(59, $dt->second());
    }

    public function test_format(): void
    {
        $dt = ParsiDateTime::create(1403, 1, 5, 9, 5, 3);
        $this->assertSame('1403/01/05 09:05:03', $dt->format('Y/m/d H:i:s'));
        $this->assertSame('09:05 AM',            $dt->format('h:i A'));
    }

    public function test_format_persian_digits(): void
    {
        $dt = ParsiDateTime::create(1403, 1, 5, 9, 5, 0);
        $this->assertSame('۱۴۰۳/۰۱/۰۵ ۰۹:۰۵:۰۰', $dt->formatPersian('Y/m/d H:i:s'));
    }

    public function test_to_string(): void
    {
        $dt = ParsiDateTime::create(1403, 1, 5, 9, 5, 3);
        $this->assertSame('1403/01/05 09:05:03', (string) $dt);
    }

    public function test_diff_in_seconds(): void
    {
        $a = ParsiDateTime::create(1403, 1, 1, 10, 0, 0);
        $b = ParsiDateTime::create(1403, 1, 1, 11, 0, 0);
        $this->assertSame(3600, $a->diffInSeconds($b));
    }

    public function test_diff_in_days(): void
    {
        $a = ParsiDateTime::create(1403, 1, 1, 0, 0, 0);
        $b = ParsiDateTime::create(1403, 1, 8, 0, 0, 0);
        $this->assertSame(7, $a->diffInDays($b));
    }

    public function test_to_date(): void
    {
        $dt   = ParsiDateTime::create(1403, 6, 15, 10, 30, 0);
        $date = $dt->toDate();
        $this->assertSame(1403, $date->year());
        $this->assertSame(6,    $date->month());
        $this->assertSame(15,   $date->day());
    }

    public function test_immutability(): void
    {
        $original = ParsiDateTime::create(1403, 1, 1, 10, 0, 0);
        $modified = $original->addHours(5);
        $this->assertSame(10, $original->hour()); // unchanged
        $this->assertSame(15, $modified->hour());
    }
}