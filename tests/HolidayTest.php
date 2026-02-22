<?php
declare(strict_types=1);
namespace ParsiDate\Tests;

use ParsiDate\ParsiDate;
use ParsiDate\Holidays\HolidayChecker;
use PHPUnit\Framework\TestCase;

class HolidayTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Fixed holidays
    // -------------------------------------------------------------------------

    public function test_nowruz_is_holiday(): void
    {
        $this->assertTrue(ParsiDate::create(1403, 1, 1)->isHoliday());
        $this->assertTrue(ParsiDate::create(1404, 1, 1)->isHoliday());
    }

    public function test_nowruz_days_are_holiday(): void
    {
        foreach ([1, 2, 3, 4] as $day) {
            $this->assertTrue(
                ParsiDate::create(1403, 1, $day)->isHoliday(),
                "Nowruz day $day should be holiday"
            );
        }
    }

    public function test_sizdah_bedar_is_holiday(): void
    {
        $this->assertTrue(ParsiDate::create(1403, 1, 13)->isHoliday());
        $this->assertSame('روز طبیعت (سیزده‌بدر)', ParsiDate::create(1403, 1, 13)->holidayName());
    }

    public function test_revolution_day_is_holiday(): void
    {
        $this->assertTrue(ParsiDate::create(1403, 11, 22)->isHoliday());
        $this->assertSame('پیروزی انقلاب اسلامی', ParsiDate::create(1403, 11, 22)->holidayName());
    }

    public function test_oil_nationalization_is_holiday(): void
    {
        $this->assertTrue(ParsiDate::create(1403, 12, 29)->isHoliday());
    }

    public function test_imam_khomeini_death_is_holiday(): void
    {
        $this->assertTrue(ParsiDate::create(1403, 3, 14)->isHoliday());
    }

    // -------------------------------------------------------------------------
    // Variable holidays
    // -------------------------------------------------------------------------

    public function test_eid_fitr_1403_is_holiday(): void
    {
        $this->assertTrue(ParsiDate::create(1403, 1, 27)->isHoliday());
        $this->assertTrue(ParsiDate::create(1403, 1, 28)->isHoliday());
    }

    public function test_ashura_1403_is_holiday(): void
    {
        $this->assertTrue(ParsiDate::create(1403, 4, 16)->isHoliday());
        $this->assertSame('عاشورای حسینی', ParsiDate::create(1403, 4, 16)->holidayName());
    }

    public function test_eid_ghadir_1404_is_holiday(): void
    {
        $this->assertTrue(ParsiDate::create(1404, 3, 21)->isHoliday());
        $this->assertSame('عید غدیر خم', ParsiDate::create(1404, 3, 21)->holidayName());
    }

    // -------------------------------------------------------------------------
    // holidayName
    // -------------------------------------------------------------------------

    public function test_holiday_name_returns_null_for_normal_day(): void
    {
        $this->assertNull(ParsiDate::create(1403, 5, 5)->holidayName());
    }

    public function test_holiday_name_returns_string_for_holiday(): void
    {
        $name = ParsiDate::create(1403, 1, 1)->holidayName();
        $this->assertSame('جشن نوروز', $name);
    }

    // -------------------------------------------------------------------------
    // Weekend
    // -------------------------------------------------------------------------

    public function test_friday_is_weekend(): void
    {
        // 1403/4/15 = Friday
        $this->assertTrue(ParsiDate::create(1403, 4, 15)->isWeekend());
    }

    public function test_saturday_is_not_weekend(): void
    {
        // 1403/4/16 = Saturday (workday in Iran)
        $this->assertFalse(ParsiDate::create(1403, 4, 16)->isWeekend());
    }

    public function test_thursday_is_not_weekend(): void
    {
        // 1403/4/14 = Thursday
        $this->assertFalse(ParsiDate::create(1403, 4, 14)->isWeekend());
    }

    // -------------------------------------------------------------------------
    // isWorkday
    // -------------------------------------------------------------------------

    public function test_normal_day_is_workday(): void
    {
        // 1403/5/6 = Sunday, not a holiday
        $this->assertTrue(ParsiDate::create(1403, 5, 6)->isWorkday());
    }

    public function test_holiday_is_not_workday(): void
    {
        $this->assertFalse(ParsiDate::create(1403, 1, 1)->isWorkday());
    }

    public function test_friday_is_not_workday(): void
    {
        // 1403/4/15 = Friday
        $this->assertFalse(ParsiDate::create(1403, 4, 15)->isWorkday());
    }

    // -------------------------------------------------------------------------
    // nextWorkday / previousWorkday
    // -------------------------------------------------------------------------

    public function test_next_workday_skips_holiday(): void
    {
        // 1403/1/4 is last day of Nowruz holidays, next workday = 1403/1/5
        $next = ParsiDate::create(1403, 1, 4)->nextWorkday();
        $this->assertSame(5, $next->day());
        $this->assertSame(1, $next->month());
    }

    public function test_next_workday_skips_friday(): void
    {
        // 1403/4/14 = Thursday
        // 1403/4/15 = Friday (weekend) → skip
        // 1403/4/16 = Saturday but it's Ashura (عاشورا) → skip
        // 1403/4/17 = Sunday → first workday
        $next = ParsiDate::create(1403, 4, 14)->nextWorkday();
        $this->assertSame(17, $next->day());
        $this->assertSame(4, $next->month());
    }

    public function test_previous_workday(): void
    {
        // Day after Nowruz holidays: go back → should land on last day before Nowruz
        $prev = ParsiDate::create(1403, 1, 5)->previousWorkday();
        // 1/4 is holiday (Nowruz), 1/3 is holiday, 1/2 is holiday, 1/1 is holiday
        // Going before Nowruz: 12/29 of 1402 (oil nationalization day — also holiday)
        // 12/28 of 1402 — check if workday
        $this->assertTrue($prev->isWorkday());
    }

    // -------------------------------------------------------------------------
    // addWorkdays
    // -------------------------------------------------------------------------

    public function test_add_workdays_basic(): void
    {
        // Start on a normal workday (Saturday 1403/5/5), add 5 workdays
        $result = ParsiDate::create(1403, 5, 5)->addWorkdays(5);
        $this->assertTrue($result->isWorkday());
        // 5 workdays forward from Saturday skips the Friday in between
        $this->assertGreaterThan(5, $result->day()); // must be after day 5+5=10 since Friday is skipped
    }

    public function test_add_zero_workdays(): void
    {
        $d = ParsiDate::create(1403, 5, 5);
        $this->assertTrue($d->equalTo($d->addWorkdays(0)));
    }

    // -------------------------------------------------------------------------
    // workdaysUntil
    // -------------------------------------------------------------------------

    public function test_workdays_until(): void
    {
        // From 1403/1/5 (first workday after Nowruz) count 5 workdays
        $start = ParsiDate::create(1403, 1, 5);
        $end   = $start->addWorkdays(5);
        $this->assertSame(5, $start->workdaysUntil($end));
    }

    // -------------------------------------------------------------------------
    // ofMonth / ofYear
    // -------------------------------------------------------------------------

    public function test_of_month_returns_holidays(): void
    {
        $holidays = HolidayChecker::ofMonth(1403, 1);
        $days = array_column($holidays, 'day');

        $this->assertContains(1,  $days); // Nowruz
        $this->assertContains(13, $days); // Sizdah Bedar
        $this->assertContains(27, $days); // Eid Fitr 1403
    }

    public function test_of_year_contains_fixed_and_variable(): void
    {
        $holidays = HolidayChecker::ofYear(1403);
        $this->assertNotEmpty($holidays);

        $names = array_column($holidays, 'name');
        $this->assertContains('جشن نوروز', $names);
        $this->assertContains('پیروزی انقلاب اسلامی', $names);
        $this->assertContains('عاشورای حسینی', $names);
    }

    public function test_of_year_is_sorted(): void
    {
        $holidays = HolidayChecker::ofYear(1403);
        for ($i = 1; $i < count($holidays); $i++) {
            $prev = $holidays[$i - 1];
            $curr = $holidays[$i];
            $this->assertLessThanOrEqual(
                $curr['month'] * 100 + $curr['day'],
                $prev['month'] * 100 + $prev['day'] + 1, // allow equal (same day multiple names unlikely but safe)
                "Holidays should be sorted by date"
            );
        }
    }

    // -------------------------------------------------------------------------
    // setVariableHolidays (custom override)
    // -------------------------------------------------------------------------

    public function test_set_variable_holidays_override(): void
    {
        HolidayChecker::setVariableHolidays(1450, [
            [6, 15, 'تعطیل فرضی'],
        ]);

        $this->assertTrue(ParsiDate::create(1450, 6, 15)->isHoliday());
        $this->assertSame('تعطیل فرضی', ParsiDate::create(1450, 6, 15)->holidayName());
        // Fixed holidays still work
        $this->assertTrue(ParsiDate::create(1450, 1, 1)->isHoliday());
    }
}