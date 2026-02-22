<?php

declare(strict_types=1);

namespace ParsiDate\Holidays;

/**
 * Checks whether a Jalali date is a public holiday or weekend.
 *
 * Weekend in Iran: Friday only (day-of-week = 5 in PHP's 0=Sun…6=Sat)
 * Thursday is a half-day in some organizations but NOT an official holiday.
 */
final class HolidayChecker
{
    /** PHP day-of-week index for Friday (Iran's official weekend). */
    private const FRIDAY = 5;

    // Compiled cache: "year-month-day" => name
    private static array $cache = [];
    private static array $compiledYears = [];

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Check if the date is a Friday (official weekend).
     */
    public static function isWeekend(int $dayOfWeek): bool
    {
        return $dayOfWeek === self::FRIDAY;
    }

    /**
     * Check if the date is an official public holiday (excluding weekends).
     */
    public static function isHoliday(int $year, int $month, int $day): bool
    {
        self::compileYear($year);
        return isset(self::$cache[self::key($year, $month, $day)]);
    }

    /**
     * Get the holiday name, or null if not a holiday.
     */
    public static function holidayName(int $year, int $month, int $day): ?string
    {
        self::compileYear($year);
        return self::$cache[self::key($year, $month, $day)] ?? null;
    }

    /**
     * Get all holidays for a given year as array of ['month','day','name'].
     *
     * @return list<array{month: int, day: int, name: string}>
     */
    public static function ofYear(int $year): array
    {
        self::compileYear($year);

        $result = [];
        foreach (self::$cache as $key => $name) {
            [$y, $m, $d] = explode('-', $key);
            if ((int)$y === $year) {
                $result[] = ['month' => (int)$m, 'day' => (int)$d, 'name' => $name];
            }
        }

        usort($result, fn($a, $b) => $a['month'] <=> $b['month'] ?: $a['day'] <=> $b['day']);
        return $result;
    }

    /**
     * Get all holidays for a given month.
     *
     * @return list<array{month: int, day: int, name: string}>
     */
    public static function ofMonth(int $year, int $month): array
    {
        return array_values(
            array_filter(self::ofYear($year), fn($h) => $h['month'] === $month)
        );
    }

    /**
     * Allow injecting custom/corrected variable holidays for a year.
     * Useful when the library's built-in data needs updating.
     *
     * @param list<array{0: int, 1: int, 2: string}> $holidays  [[month, day, name], ...]
     */
    public static function setVariableHolidays(int $year, array $holidays): void
    {
        // Clear cache for this year so it gets recompiled
        foreach (array_keys(self::$cache) as $key) {
            if (str_starts_with($key, $year . '-')) {
                unset(self::$cache[$key]);
            }
        }
        unset(self::$compiledYears[$year]);

        // Register override and recompile
        self::$overrides[$year] = $holidays;
        self::compileYear($year);
    }

    // -------------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------------

    /** Overrides registered via setVariableHolidays(). */
    private static array $overrides = [];

    private static function compileYear(int $year): void
    {
        if (isset(self::$compiledYears[$year])) {
            return;
        }

        // Fixed holidays — key format: "month-day"
        foreach (HolidayData::FIXED as $md => $name) {
            [$m, $d] = explode('-', (string) $md);
            self::$cache[self::key($year, (int) $m, (int) $d)] = $name;
        }

        // Variable holidays — overrides take precedence over built-in data
        $variable = self::$overrides[$year] ?? HolidayData::VARIABLE[$year] ?? [];

        foreach ($variable as [$m, $d, $name]) {
            self::$cache[self::key($year, $m, $d)] = $name;
        }

        self::$compiledYears[$year] = true;
    }

    private static function key(int $y, int $m, int $d): string
    {
        return "$y-$m-$d";
    }
}