<?php
declare(strict_types=1);
namespace ParsiDate\Tests;

use ParsiDate\ParsiDate;
use ParsiDate\ParsiPeriod;
use PHPUnit\Framework\TestCase;

class ParsiPeriodTest extends TestCase
{
    private function period(int $m1, int $d1, int $m2, int $d2, int $y = 1403): ParsiPeriod
    {
        return ParsiPeriod::create(
            ParsiDate::create($y, $m1, $d1),
            ParsiDate::create($y, $m2, $d2),
        );
    }

    // -------------------------------------------------------------------------
    // Factories
    // -------------------------------------------------------------------------

    public function test_create_basic(): void
    {
        $p = $this->period(1, 1, 1, 31);
        $this->assertSame('1403/01/01', (string) $p->start());
        $this->assertSame('1403/01/31', (string) $p->end());
    }

    public function test_create_swaps_if_reversed(): void
    {
        $p = ParsiPeriod::create(
            ParsiDate::create(1403, 6, 31),
            ParsiDate::create(1403, 1, 1),
        );
        $this->assertSame('1403/01/01', (string) $p->start());
        $this->assertSame('1403/06/31', (string) $p->end());
    }

    public function test_of_month(): void
    {
        $p = ParsiPeriod::ofMonth(1403, 1);
        $this->assertSame(1,  $p->start()->day());
        $this->assertSame(31, $p->end()->day());
        $this->assertSame(31, $p->days());
    }

    public function test_of_month_esfand_non_leap(): void
    {
        $p = ParsiPeriod::ofMonth(1402, 12);
        $this->assertSame(29, $p->end()->day());
        $this->assertSame(29, $p->days());
    }

    public function test_of_month_esfand_leap(): void
    {
        $p = ParsiPeriod::ofMonth(1403, 12);
        $this->assertSame(30, $p->end()->day());
        $this->assertSame(30, $p->days());
    }

    public function test_of_year(): void
    {
        $p = ParsiPeriod::ofYear(1403);
        $this->assertSame(1,  $p->start()->month());
        $this->assertSame(1,  $p->start()->day());
        $this->assertSame(12, $p->end()->month());
        $this->assertSame(30, $p->end()->day()); // leap
        $this->assertSame(366, $p->days());
    }

    public function test_from_date(): void
    {
        $p = ParsiPeriod::fromDate(ParsiDate::create(1403, 1, 1), 10);
        $this->assertSame(10, $p->days());
        $this->assertSame(10, $p->end()->day());
    }

    // -------------------------------------------------------------------------
    // Days count
    // -------------------------------------------------------------------------

    public function test_days_single_day(): void
    {
        $p = $this->period(5, 10, 5, 10);
        $this->assertSame(1, $p->days());
    }

    public function test_days_one_week(): void
    {
        $p = $this->period(5, 1, 5, 7);
        $this->assertSame(7, $p->days());
    }

    // -------------------------------------------------------------------------
    // Contains & overlap
    // -------------------------------------------------------------------------

    public function test_contains_true(): void
    {
        $p = $this->period(1, 1, 1, 31);
        $this->assertTrue($p->contains(ParsiDate::create(1403, 1, 15)));
    }

    public function test_contains_boundary(): void
    {
        $p = $this->period(1, 1, 1, 31);
        $this->assertTrue($p->contains(ParsiDate::create(1403, 1, 1)));
        $this->assertTrue($p->contains(ParsiDate::create(1403, 1, 31)));
    }

    public function test_contains_false(): void
    {
        $p = $this->period(1, 1, 1, 31);
        $this->assertFalse($p->contains(ParsiDate::create(1403, 2, 1)));
    }

    public function test_overlap_returns_common_period(): void
    {
        $a = $this->period(1, 1, 1, 20);
        $b = $this->period(1, 10, 1, 31);
        $overlap = $a->overlap($b);
        $this->assertNotNull($overlap);
        $this->assertSame('1403/01/10', (string) $overlap->start());
        $this->assertSame('1403/01/20', (string) $overlap->end());
    }

    public function test_overlap_returns_null_when_no_overlap(): void
    {
        $a = $this->period(1, 1, 1, 10);
        $b = $this->period(1, 15, 1, 31);
        $this->assertNull($a->overlap($b));
    }

    // -------------------------------------------------------------------------
    // toArray / iteration
    // -------------------------------------------------------------------------

    public function test_to_array_count(): void
    {
        $p = $this->period(1, 1, 1, 7);
        $this->assertCount(7, $p->toArray());
    }

    public function test_to_array_dates_are_parsi_date(): void
    {
        $arr = $this->period(1, 1, 1, 3)->toArray();
        $this->assertInstanceOf(ParsiDate::class, $arr[0]);
        $this->assertSame(1, $arr[0]->day());
        $this->assertSame(3, $arr[2]->day());
    }

    public function test_foreach_iteration(): void
    {
        $p     = $this->period(1, 1, 1, 5);
        $days  = [];
        foreach ($p as $date) {
            $days[] = $date->day();
        }
        $this->assertSame([1, 2, 3, 4, 5], $days);
    }

    public function test_countable(): void
    {
        $p = $this->period(1, 1, 1, 10);
        $this->assertCount(10, $p);
    }

    // -------------------------------------------------------------------------
    // Fridays / holidays / workdays
    // -------------------------------------------------------------------------

    public function test_fridays_in_farvardin_1403(): void
    {
        // Farvardin 1403: check that fridays() returns only Fridays
        $fridays = ParsiPeriod::ofMonth(1403, 1)->fridays();
        $this->assertNotEmpty($fridays);
        foreach ($fridays as $d) {
            $this->assertSame(5, $d->dayOfWeek()); // 5 = Friday
        }
    }

    public function test_holidays_contains_nowruz(): void
    {
        $holidays = ParsiPeriod::ofMonth(1403, 1)->holidays();
        $days = array_map(fn($d) => $d->day(), $holidays);
        $this->assertContains(1, $days);
        $this->assertContains(13, $days);
    }

    public function test_workdays_are_not_friday_or_holiday(): void
    {
        $workdays = ParsiPeriod::ofMonth(1403, 1)->workdays();
        foreach ($workdays as $d) {
            $this->assertFalse($d->isWeekend(), "Day {$d} should not be weekend");
            $this->assertFalse($d->isHoliday(), "Day {$d} should not be holiday");
        }
    }

    public function test_workdays_plus_offdays_equals_total(): void
    {
        $p = ParsiPeriod::ofMonth(1403, 1);
        $this->assertSame($p->days(), $p->countWorkdays() + $p->countOffDays());
    }

    public function test_farvardin_has_few_workdays(): void
    {
        // Farvardin 1403: days 1-4 are Nowruz, day 12 republic, day 13 sizdah, + Fridays
        $workdays = ParsiPeriod::ofMonth(1403, 1)->countWorkdays();
        $this->assertLessThan(25, $workdays); // definitely less than 25 out of 31
    }

    // -------------------------------------------------------------------------
    // Filter & byDayOfWeek
    // -------------------------------------------------------------------------

    public function test_filter_custom(): void
    {
        $p      = ParsiPeriod::ofMonth(1403, 1);
        $result = $p->filter(fn(ParsiDate $d) => $d->day() % 2 === 0);
        foreach ($result as $d) {
            $this->assertSame(0, $d->day() % 2);
        }
    }

    public function test_by_day_of_week_returns_correct_days(): void
    {
        $saturdays = ParsiPeriod::ofMonth(1403, 1)->byDayOfWeek(6); // 6 = Saturday
        foreach ($saturdays as $d) {
            $this->assertSame(6, $d->dayOfWeek());
        }
    }

    // -------------------------------------------------------------------------
    // Splitting
    // -------------------------------------------------------------------------

    public function test_split_by_days(): void
    {
        $p      = ParsiPeriod::fromDate(ParsiDate::create(1403, 1, 1), 10);
        $chunks = $p->splitByDays(3);
        $this->assertCount(4, $chunks); // 3+3+3+1
        $this->assertSame(3, $chunks[0]->days());
        $this->assertSame(1, $chunks[3]->days()); // remainder
    }

    public function test_split_by_month(): void
    {
        $p      = ParsiPeriod::create(
            ParsiDate::create(1403, 1, 1),
            ParsiDate::create(1403, 3, 31),
        );
        $months = $p->splitByMonth();
        $this->assertCount(3, $months);
        $this->assertSame(31, $months[0]->days()); // Farvardin
        $this->assertSame(31, $months[1]->days()); // Ordibehesht
        $this->assertSame(31, $months[2]->days()); // Khordad
    }

    public function test_split_by_month_partial(): void
    {
        $p      = ParsiPeriod::create(
            ParsiDate::create(1403, 1, 15),
            ParsiDate::create(1403, 2, 10),
        );
        $months = $p->splitByMonth();
        $this->assertCount(2, $months);
        $this->assertSame(17, $months[0]->days()); // 15→31
        $this->assertSame(10, $months[1]->days()); // 1→10
    }

    // -------------------------------------------------------------------------
    // toString
    // -------------------------------------------------------------------------

    public function test_to_string(): void
    {
        $p = $this->period(1, 1, 1, 31);
        $this->assertSame('1403/01/01 → 1403/01/31', (string) $p);
    }
}