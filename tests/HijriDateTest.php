<?php

declare(strict_types=1);

namespace ParsiDate\Tests;

use ParsiDate\HijriDate;
use ParsiDate\ParsiDate;
use ParsiDate\Converter\CalendarConverter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Jalali → Hijri conversion and HijriDate value object.
 *
 * All reference dates use the tabular (arithmetic) Hijri calendar
 * with epoch JDN 1948440 = 1 Muharram 1 AH = 16 July 622 AD (Julian).
 *
 * Note: observational (crescent-moon) calendars may differ by ±1–2 days.
 */
class HijriDateTest extends TestCase
{
    // -------------------------------------------------------------------------
    // CalendarConverter::gregorianToHijri — spot checks
    // -------------------------------------------------------------------------

    /** @dataProvider gregorianToHijriProvider */
    public function test_gregorian_to_hijri(
        int $gy,
        int $gm,
        int $gd,
        int $hy,
        int $hm,
        int $hd,
        string $label,
    ): void {
        $result = CalendarConverter::gregorianToHijri($gy, $gm, $gd);
        $this->assertSame(
            ['year' => $hy, 'month' => $hm, 'day' => $hd],
            $result,
            "Failed for: $label",
        );
    }

    public static function gregorianToHijriProvider(): array
    {
        return [
            // [Gregorian Y, M, D] => [Hijri Y, M, D]  (tabular calendar)
            [2024,  3, 20, 1445,  9, 10, 'Nowruz 1403 = 10 Ramadan 1445'],
            [2024,  6, 17, 1445, 12, 10, 'Eid al-Adha 2024 = 10 Dhul-Hijjah 1445'],
            [2024,  7,  7, 1445, 12, 30, '7 Jul 2024 = 30 Dhul-Hijjah 1445'],
            [2000,  1,  1, 1420,  9, 24, '1 Jan 2000 = 24 Ramadan 1420'],
            [1970,  1,  1, 1389, 10, 22, 'Unix epoch = 22 Shawwal 1389'],
        ];
    }

    // -------------------------------------------------------------------------
    // CalendarConverter::jalaliToHijri — spot checks
    // -------------------------------------------------------------------------

    /** @dataProvider jalaliToHijriProvider */
    public function test_jalali_to_hijri(
        int $jy,
        int $jm,
        int $jd,
        int $hy,
        int $hm,
        int $hd,
        string $label,
    ): void {
        $result = CalendarConverter::jalaliToHijri($jy, $jm, $jd);
        $this->assertSame(
            ['year' => $hy, 'month' => $hm, 'day' => $hd],
            $result,
            "Failed for: $label",
        );
    }

    public static function jalaliToHijriProvider(): array
    {
        return [
            [1403,  1,  1, 1445,  9, 10, 'Nowruz 1403 = 10 Ramadan 1445'],
            [1400,  1,  1, 1442,  8,  7, 'Nowruz 1400 = 7 Sha\'ban 1442'],
            [1402, 11, 30, 1445,  8,  9, '30 Bahman 1402 = 9 Sha\'ban 1445'],
            [1403,  6, 31, 1446,  3, 17, '31 Shahrivar 1403 = 17 Rabi\' al-Awwal 1446'],
        ];
    }

    // -------------------------------------------------------------------------
    // Round-trip: Hijri → Gregorian → Hijri
    // -------------------------------------------------------------------------

    public function test_round_trip_hijri_gregorian(): void
    {
        $cases = [
            [1445,  9, 10],
            [1445, 12, 10],
            [1446,  1,  1],
            [1400,  1,  1],
            [1389, 10, 22],
        ];

        foreach ($cases as [$hy, $hm, $hd]) {
            $greg = CalendarConverter::hijriToGregorian($hy, $hm, $hd);
            $back = CalendarConverter::gregorianToHijri($greg['year'], $greg['month'], $greg['day']);
            $this->assertSame(
                ['year' => $hy, 'month' => $hm, 'day' => $hd],
                $back,
                "Round-trip failed for Hijri $hy/$hm/$hd",
            );
        }
    }

    // -------------------------------------------------------------------------
    // ParsiDate::toHijri() — integration
    // -------------------------------------------------------------------------

    public function test_parsidate_to_hijri_returns_hijri_date(): void
    {
        $h = ParsiDate::create(1403, 1, 1)->toHijri();
        $this->assertInstanceOf(HijriDate::class, $h);
    }

    public function test_parsidate_to_hijri_nowruz_1403(): void
    {
        $h = ParsiDate::create(1403, 1, 1)->toHijri();
        $this->assertSame(1445, $h->year());
        $this->assertSame(9,    $h->month());
        $this->assertSame(10,   $h->day());
    }

    public function test_parsidate_to_hijri_is_immutable(): void
    {
        $jalali = ParsiDate::create(1403, 1, 1);
        $h1 = $jalali->toHijri();
        $h2 = $jalali->toHijri();
        $this->assertNotSame($h1, $h2);
        $this->assertSame($h1->year(), $h2->year());
    }

    // -------------------------------------------------------------------------
    // HijriDate — formatting
    // -------------------------------------------------------------------------

    public function test_format_basic_tokens(): void
    {
        $h = new HijriDate(1445, 9, 10);

        $this->assertSame('1445',    $h->format('Y'));
        $this->assertSame('45',      $h->format('y'));
        $this->assertSame('09',      $h->format('m'));
        $this->assertSame('9',       $h->format('n'));
        $this->assertSame('10',      $h->format('d'));
        $this->assertSame('10',      $h->format('j'));
        $this->assertSame('رمضان',   $h->format('F'));
        $this->assertSame('Ramadan', $h->format('M'));
    }

    public function test_format_combined(): void
    {
        $h = new HijriDate(1445, 9, 10);
        $this->assertSame('1445/09/10',    $h->format('Y/m/d'));
        $this->assertSame('10 رمضان 1445', $h->format('j F Y'));
    }

    public function test_format_persian_digits(): void
    {
        $h = new HijriDate(1445, 9, 10);
        $this->assertSame('۱۴۴۵/۰۹/۱۰',    $h->formatPersian('Y/m/d'));
        $this->assertSame('۱۰ رمضان ۱۴۴۵', $h->formatPersian('j F Y'));
    }

    public function test_to_string(): void
    {
        $h = new HijriDate(1445, 9, 10);
        $this->assertSame('1445/09/10', (string) $h);
    }

    public function test_to_array(): void
    {
        $h = new HijriDate(1445, 9, 10);
        $this->assertSame(['year' => 1445, 'month' => 9, 'day' => 10], $h->toArray());
    }

    public function test_to_json(): void
    {
        $h = new HijriDate(1445, 9, 10);
        $this->assertSame('{"year":1445,"month":9,"day":10}', $h->toJson());
    }

    public function test_all_month_names_arabic(): void
    {
        $expected = [
            1 => 'محرم',
            2 => 'صفر',
            3 => 'ربیع الاول',
            4 => 'ربیع الثانی',
            5 => 'جمادی الاول',
            6 => 'جمادی الثانی',
            7 => 'رجب',
            8 => 'شعبان',
            9 => 'رمضان',
            10 => 'شوال',
            11 => 'ذوالقعده',
            12 => 'ذوالحجه',
        ];

        foreach ($expected as $m => $name) {
            $h = new HijriDate(1445, $m, 1);
            $this->assertSame($name, $h->monthName(), "Month $m Arabic name");
            $this->assertSame($name, $h->format('F'),  "Month $m via format('F')");
        }
    }

    public function test_all_month_names_latin(): void
    {
        $expected = [
            1 => 'Muharram',
            2 => 'Safar',
            3 => "Rabi' al-Awwal",
            4 => "Rabi' al-Thani",
            5 => "Jumada al-Awwal",
            6 => "Jumada al-Thani",
            7 => 'Rajab',
            8 => "Sha'ban",
            9 => 'Ramadan',
            10 => 'Shawwal',
            11 => "Dhu al-Qi'dah",
            12 => 'Dhu al-Hijjah',
        ];

        foreach ($expected as $m => $name) {
            $h = new HijriDate(1445, $m, 1);
            $this->assertSame($name, $h->monthNameLatin(), "Month $m Latin name");
            $this->assertSame($name, $h->format('M'),      "Month $m via format('M')");
        }
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function test_accessors(): void
    {
        $h = new HijriDate(1445, 9, 10);
        $this->assertSame(1445, $h->year());
        $this->assertSame(9,    $h->month());
        $this->assertSame(10,   $h->day());
    }
}
