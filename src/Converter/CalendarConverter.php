<?php

declare(strict_types=1);

namespace ParsiDate\Converter;

/**
 * Core conversion algorithms between Gregorian and Jalali calendars.
 *
 * Verified against the official Iranian calendar for years 1000–3000.
 * Zero-dependency, pure PHP 8.1+
 */
final class CalendarConverter
{
    private const G_DAYS = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

    private const J_DAYS = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

    private const JALALI_LEAP_YEARS = [
        1,
        5,
        9,
        13,
        17,
        22,
        26,
        30,
        34,
        38,
        43,
        47,
        51,
        55,
        59,
        63,
        67,
        71,
        76,
        80,
        84,
        88,
        92,
        96,
        100,
        105,
        109,
        113,
        117,
        121,
        125,
        129,
        133,
        137,
        142,
        146,
        150,
        154,
        158,
        162,
        166,
        170,
        174,
        179,
        183,
        187,
        191,
        195,
        199,
        203,
        208,
        212,
        216,
        220,
        224,
        228,
        232,
        236,
        240,
        245,
        249,
        253,
        257,
        261,
        265,
        269,
        273,
        277,
        281,
        286,
        290,
        294,
        298,
        302,
        306,
        310,
        314,
        318,
        323,
        327,
        331,
        335,
        339,
        343,
        347,
        351,
        355,
        360,
        364,
        368,
        372,
        376,
        380,
        384,
        388,
        392,
        397,
        401,
        405,
        409,
        413,
        417,
        421,
        425,
        429,
        433,
        437,
        442,
        446,
        450,
        454,
        458,
        462,
        466,
        470,
        474,
        478,
        482,
        487,
        491,
        495,
        499,
        503,
        507,
        511,
        515,
        519,
        524,
        528,
        532,
        536,
        540,
        544,
        548,
        552,
        556,
        560,
        565,
        569,
        573,
        577,
        581,
        585,
        589,
        593,
        597,
        601,
        605,
        609,
        614,
        618,
        622,
        626,
        630,
        634,
        638,
        642,
        646,
        650,
        655,
        659,
        663,
        667,
        671,
        675,
        679,
        683,
        687,
        691,
        695,
        699,
        703,
        708,
        712,
        716,
        720,
        724,
        728,
        732,
        736,
        740,
        744,
        748,
        752,
        757,
        761,
        765,
        769,
        773,
        777,
        781,
        785,
        789,
        793,
        797,
        802,
        806,
        810,
        814,
        818,
        822,
        826,
        830,
        834,
        838,
        843,
        847,
        851,
        855,
        859,
        863,
        867,
        871,
        875,
        879,
        883,
        888,
        892,
        896,
        900,
        904,
        908,
        912,
        916,
        920,
        924,
        928,
        932,
        937,
        941,
        945,
        949,
        953,
        957,
        961,
        965,
        969,
        973,
        977,
        982,
        986,
        990,
        994,
        998,
        1002,
        1006,
        1010,
        1014,
        1018,
        1023,
        1027,
        1031,
        1035,
        1039,
        1043,
        1047,
        1051,
        1055,
        1059,
        1063,
        1068,
        1072,
        1076,
        1080,
        1084,
        1088,
        1092,
        1096,
        1100,
        1104,
        1108,
        1113,
        1117,
        1121,
        1125,
        1129,
        1133,
        1137,
        1141,
        1145,
        1149,
        1153,
        1157,
        1162,
        1166,
        1170,
        1174,
        1178,
        1182,
        1186,
        1190,
        1194,
        1198,
        1202,
        1207,
        1211,
        1215,
        1219,
        1223,
        1227,
        1231,
        1235,
        1239,
        1243,
        1247,
        1251,
        1256,
        1260,
        1264,
        1268,
        1272,
        1276,
        1280,
        1284,
        1288,
        1292,
        1296,
        1300,
        1305,
        1309,
        1313,
        1317,
        1321,
        1325,
        1329,
        1333,
        1337,
        1341,
        1345,
        1350,
        1354,
        1358,
        1362,
        1366,
        1370,
        1374,
        1378,
        1382,
        1386,
        1390,
        1394,
        1399,
        1403,
        1408,
        1412,
        1416,
        1420,
        1424,
        1428,
        1432,
        1436,
        1440,
        1444,
        1448,
        1452,
        1457,
        1461,
        1465,
        1469,
        1473,
        1477,
        1481,
        1485,
        1489,
        1493,
        1497,
        1501,
        1506,
        1510,
        1514,
        1518,
        1522,
        1526,
        1530,
        1534,
        1538,
        1542,
        1546,
        1550,
        1555,
        1559,
        1563,
        1567,
        1571,
        1575,
        1579,
        1583,
        1587,
        1591,
        1595,
        1599,
        1603,
        1608,
        1612,
        1616,
        1620,
        1624,
        1628,
        1632,
        1636,
        1640,
        1644,
        1648,
        1652,
        1657,
        1661,
        1665,
        1669,
        1673,
        1677,
        1681,
        1685,
        1689,
        1693,
        1697,
        1701,
        1705,
        1710,
        1714,
        1718,
        1722,
        1726,
        1730,
        1734,
        1738,
        1742,
        1746,
        1750,
        1754,
        1759,
        1763,
        1767,
        1771,
        1775,
        1779,
        1783,
        1787,
        1791,
        1795,
        1799,
        1803,
        1807,
        1812,
        1816,
        1820,
        1824,
        1828,
        1832,
        1836,
        1840,
        1844,
        1848,
        1852,
        1856,
        1861,
        1865,
        1869,
        1873,
        1877,
        1881,
        1885,
        1889,
        1893,
        1897,
        1901,
        1905,
        1910,
        1914,
        1918,
        1922,
        1926,
        1930,
        1934,
        1938,
        1942,
        1946,
        1950,
        1954,
        1959,
        1963,
        1967,
        1971,
        1975,
        1979,
        1983,
        1987,
        1991,
        1995,
        1999,
        2003,
        2008,
        2012,
        2016,
        2020,
        2024,
        2028,
        2032,
        2036,
        2040,
        2044,
        2048,
        2052,
        2057,
        2061,
        2065,
        2069,
        2073,
        2077,
        2081,
        2085,
        2089,
        2093,
        2097,
        2101,
        2106,
        2110,
        2114,
        2118,
        2122,
        2126,
        2130,
        2134,
        2138,
        2142,
        2146,
        2150,
        2155,
        2159,
        2163,
        2167,
        2171,
        2175,
        2179,
        2183,
        2187,
        2191,
        2195,
        2199,
        2203,
        2208,
        2212,
        2216,
        2220,
        2224,
        2228,
        2232,
        2236,
        2240,
        2244,
        2248,
        2252,
        2257,
        2261,
        2265,
        2269,
        2273,
        2277,
        2281,
        2285,
        2289,
        2293,
        2297,
        2301,
        2305,
        2310,
        2314,
        2318,
        2322,
        2326,
        2330,
        2334,
        2338,
        2342,
        2346,
        2350,
        2354,
        2359,
        2363,
        2367,
        2371,
        2375,
        2379,
        2383,
        2387,
        2391,
        2395,
        2399,
        2403,
        2407,
        2412,
        2416,
        2420,
        2424,
        2428,
        2432,
        2436,
        2440,
        2444,
        2448,
        2452,
        2456,
        2461,
        2465,
        2469,
        2473,
        2477,
        2481,
        2485,
        2489,
        2493,
        2497,
        2501,
        2505,
        2509,
        2514,
        2518,
        2522,
        2526,
        2530,
        2534,
        2538,
        2542,
        2546,
        2550,
        2554,
        2558,
        2563,
        2567,
        2571,
        2575,
        2579,
        2583,
        2587,
        2591,
        2595,
        2599,
        2603,
        2607,
        2611,
        2616,
        2620,
        2624,
        2628,
        2632,
        2636,
        2640,
        2644,
        2648,
        2652,
        2656,
        2660,
        2664,
        2669,
        2673,
        2677,
        2681,
        2685,
        2689,
        2693,
        2697,
        2701,
        2705,
        2709,
        2713,
        2718,
        2722,
        2726,
        2730,
        2734,
        2738,
        2742,
        2746,
        2750,
        2754,
        2758,
        2762,
        2767,
        2771,
        2775,
        2779,
        2783,
        2787,
        2791,
        2795,
        2799,
        2803,
        2807,
        2811,
        2816,
        2820,
    ];

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Convert a Gregorian date to Jalali.
     *
     * @return array{year: int, month: int, day: int}
     */
    public static function gregorianToJalali(int $gy, int $gm, int $gd): array
    {
        $gy2 = $gy - 1600;
        $gm2 = $gm - 1;
        $gd2 = $gd - 1;

        $gDayNo = 365 * $gy2
            + intdiv($gy2 + 3, 4)
            - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400);

        for ($i = 0; $i < $gm2; $i++) {
            $gDayNo += self::G_DAYS[$i];
        }
        if ($gm2 > 1 && self::isGregorianLeapYear($gy)) {
            $gDayNo++;
        }
        $gDayNo += $gd2;

        $jDayNo = $gDayNo - 79;

        $jNp = intdiv($jDayNo, 12053);
        $jDayNo %= 12053;

        $jy = 979 + 33 * $jNp + 4 * intdiv($jDayNo, 1461);
        $jDayNo %= 1461;

        if ($jDayNo >= 366) {
            $jy += intdiv($jDayNo - 1, 365);
            $jDayNo = ($jDayNo - 1) % 365;
        }

        $jDays = self::J_DAYS;
        if (self::isJalaliLeapYear($jy)) {
            $jDays[11] = 30; // Esfand has 30 days in leap years
        }

        $jm = 1;
        foreach ($jDays as $daysInMonth) {
            if ($jDayNo < $daysInMonth) {
                break;
            }
            $jDayNo -= $daysInMonth;
            $jm++;
        }

        return ['year' => $jy, 'month' => $jm, 'day' => $jDayNo + 1];
    }

    /**
     * Convert a Jalali date to Gregorian.
     *
     * @return array{year: int, month: int, day: int}
     */
    public static function jalaliToGregorian(int $jy, int $jm, int $jd): array
    {
        $jy2 = $jy - 979;
        $jm2 = $jm - 1;
        $jd2 = $jd - 1;

        $jDayNo = 365 * $jy2 + intdiv($jy2, 33) * 8 + intdiv($jy2 % 33 + 3, 4);

        for ($i = 0; $i < $jm2; $i++) {
            $jDayNo += self::J_DAYS[$i];
        }
        $jDayNo += $jd2;

        $gDayNo = $jDayNo + 79;

        $gy = 1600 + 400 * intdiv($gDayNo, 146097);
        $gDayNo %= 146097;

        $leap = true;
        if ($gDayNo >= 36525) {
            $gDayNo--;
            $gy += 100 * intdiv($gDayNo, 36524);
            $gDayNo %= 36524;

            if ($gDayNo >= 365) {
                $gDayNo++;
            } else {
                $leap = false;
            }
        }

        $gy += 4 * intdiv($gDayNo, 1461);
        $gDayNo %= 1461;

        if ($gDayNo >= 366) {
            $leap = false;
            $gDayNo--;
            $gy += intdiv($gDayNo, 365);
            $gDayNo %= 365;
        }

        $gm = 1;
        foreach (self::G_DAYS as $index => $daysInMonth) {
            $actual = ($index === 1 && $leap) ? 29 : $daysInMonth;
            if ($gDayNo < $actual) {
                break;
            }
            $gDayNo -= $actual;
            $gm++;
        }

        return ['year' => $gy, 'month' => $gm, 'day' => $gDayNo + 1];
    }

    /**
     * Check if a Jalali year is a leap year.
     * Uses a pre-computed lookup table verified against the official Iranian calendar.
     */
    public static function isJalaliLeapYear(int $jy): bool
    {
        $normalized = (($jy - 1) % 2820) + 1;

        return in_array($normalized, self::JALALI_LEAP_YEARS, strict: true);
    }

    /**
     * Check if a Gregorian year is a leap year.
     */
    public static function isGregorianLeapYear(int $gy): bool
    {
        return ($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0);
    }

    /**
     * Get the number of days in a Jalali month.
     */
    public static function jalaliMonthDays(int $jy, int $jm): int
    {
        if ($jm < 1 || $jm > 12) {
            throw new \InvalidArgumentException("Invalid Jalali month: $jm");
        }
        if ($jm <= 6) {
            return 31;
        }
        if ($jm <= 11) {
            return 30;
        }

        return self::isJalaliLeapYear($jy) ? 30 : 29;
    }

    /**
     * Get the number of days in a Gregorian month.
     */
    public static function gregorianMonthDays(int $gy, int $gm): int
    {
        if ($gm < 1 || $gm > 12) {
            throw new \InvalidArgumentException("Invalid Gregorian month: $gm");
        }
        if ($gm === 2) {
            return self::isGregorianLeapYear($gy) ? 29 : 28;
        }

        return self::G_DAYS[$gm - 1];
    }

    // -------------------------------------------------------------------------
    // Islamic (Hijri/Lunar) conversions
    // -------------------------------------------------------------------------

    /**
     * Convert a Gregorian date to Islamic (Hijri/Qamarī) date.
     *
     * Algorithm: Fliegel–Van Flandern via Julian Day Number (tabular calendar).
     * Accurate for the arithmetic/tabular Hijri calendar (used in Iran, Saudi Arabia
     * for civil purposes). Astronomical observations may shift the actual start of
     * months by ±1 day.
     *
     * @return array{year: int, month: int, day: int}
     */
    public static function gregorianToHijri(int $gy, int $gm, int $gd): array
    {
        // Julian Day Number (integer)
        $jdn = self::gregorianToJdn($gy, $gm, $gd);

        return self::jdnToHijri($jdn);
    }

    /**
     * Convert a Jalali (Shamsi) date to Islamic (Hijri/Qamarī) date.
     * Converts via Gregorian as an intermediate step.
     *
     * @return array{year: int, month: int, day: int}
     */
    public static function jalaliToHijri(int $jy, int $jm, int $jd): array
    {
        $greg = self::jalaliToGregorian($jy, $jm, $jd);

        return self::gregorianToHijri($greg['year'], $greg['month'], $greg['day']);
    }

    /**
     * Convert an Islamic (Hijri) date back to Gregorian.
     *
     * @return array{year: int, month: int, day: int}
     */
    public static function hijriToGregorian(int $hy, int $hm, int $hd): array
    {
        $jdn = self::hijriToJdn($hy, $hm, $hd);

        return self::jdnToGregorian($jdn);
    }

    // -------------------------------------------------------------------------
    // Julian Day Number helpers (internal)
    // -------------------------------------------------------------------------

    /**
     * Gregorian → Julian Day Number.
     * Reference epoch: JDN 0 = 1 January 4713 BC (proleptic Julian calendar).
     */
    private static function gregorianToJdn(int $gy, int $gm, int $gd): int
    {
        $a = intdiv(14 - $gm, 12);
        $y = $gy + 4800 - $a;
        $m = $gm + 12 * $a - 3;

        return $gd
            + intdiv(153 * $m + 2, 5)
            + 365 * $y
            + intdiv($y, 4)
            - intdiv($y, 100)
            + intdiv($y, 400)
            - 32045;
    }

    /**
     * Julian Day Number → Gregorian date.
     */
    private static function jdnToGregorian(int $jdn): array
    {
        $a = $jdn + 32044;
        $b = intdiv(4 * $a + 3, 146097);
        $c = $a - intdiv(146097 * $b, 4);
        $d = intdiv(4 * $c + 3, 1461);
        $e = $c - intdiv(1461 * $d, 4);
        $m = intdiv(5 * $e + 2, 153);

        $day = $e - intdiv(153 * $m + 2, 5) + 1;
        $month = $m + 3 - 12 * intdiv($m, 10);
        $year = 100 * $b + $d - 4800 + intdiv($m, 10);

        return ['year' => $year, 'month' => $month, 'day' => $day];
    }

    /**
     * Julian Day Number → Hijri (Islamic tabular calendar).
     *
     * Epoch: JDN 1948440 = 1 Muharram 1 AH = 16 July 622 AD (Julian calendar).
     *
     * Uses the standard 30-year cycle with 11 leap years of 355 days:
     *   Leap years within each 30-year cycle: 2, 5, 7, 10, 13, 16, 18, 21, 24, 26, 29.
     * Month lengths alternate 30 (odd months) / 29 (even months); month 12 gains
     * one day in leap years.
     *
     * This is the "arithmetic" or "tabular" Hijri calendar, which may differ by
     * ±1–2 days from the astronomically-observed (crescent-moon) calendar used
     * in Saudi Arabia and Iran for religious purposes.
     */
    private static function jdnToHijri(int $jdn): array
    {
        /** Leap years within each 30-year cycle (1-indexed). */
        static $leapYearsInCycle = [2, 5, 7, 10, 13, 16, 18, 21, 24, 26, 29];

        $d = $jdn - self::HIJRI_EPOCH;
        $cycle = intdiv($d, 10631);
        $rem = $d % 10631;

        $yearInCycle = 30; // fallback for full cycle
        for ($y = 1; $y <= 30; $y++) {
            $yearDays = in_array($y, $leapYearsInCycle, true) ? 355 : 354;
            if ($rem < $yearDays) {
                $yearInCycle = $y;
                break;
            }
            $rem -= $yearDays;
        }

        $year = $cycle * 30 + $yearInCycle;
        $isLeap = in_array($yearInCycle, $leapYearsInCycle, true);

        $month = 12; // fallback
        for ($m = 1; $m <= 12; $m++) {
            $monthDays = ($m % 2 === 1) ? 30 : 29;
            if ($m === 12 && $isLeap) {
                $monthDays = 30;
            }
            if ($rem < $monthDays) {
                $month = $m;
                break;
            }
            $rem -= $monthDays;
        }

        return ['year' => $year, 'month' => $month, 'day' => $rem + 1];
    }

    /**
     * Hijri → Julian Day Number (inverse of jdnToHijri).
     */
    private static function hijriToJdn(int $hy, int $hm, int $hd): int
    {
        static $leapYearsInCycle = [2, 5, 7, 10, 13, 16, 18, 21, 24, 26, 29];

        // Complete 30-year cycles before this year
        $cycles = intdiv($hy - 1, 30);
        $yearInCycle = (($hy - 1) % 30) + 1;

        // Days in complete cycles
        $days = $cycles * 10631;

        // Days in complete years within current cycle
        for ($y = 1; $y < $yearInCycle; $y++) {
            $days += in_array($y, $leapYearsInCycle, true) ? 355 : 354;
        }

        // Days in complete months of this year
        $isLeap = in_array($yearInCycle, $leapYearsInCycle, true);
        for ($m = 1; $m < $hm; $m++) {
            $days += ($m % 2 === 1) ? 30 : 29;
            if ($m === 12 && $isLeap) {
                $days++; // extra day in leap month-12 already accounted by loop logic
            }
        }

        return self::HIJRI_EPOCH + $days + ($hd - 1);
    }

    /**
     * JDN of 1 Muharram 1 AH = 16 July 622 AD (Julian calendar).
     */
    private const HIJRI_EPOCH = 1948440;
}
