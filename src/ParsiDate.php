<?php

declare(strict_types=1);

namespace ParsiDate;

use ParsiDate\Converter\CalendarConverter;
use ParsiDate\Exceptions\InvalidDateException;
use ParsiDate\Holidays\HolidayChecker;
use ParsiDate\Support\PersianLocale;

/**
 * Immutable Jalali (Shamsi) date.
 *
 * @example
 *   ParsiDate::now()
 *   ParsiDate::create(1403, 1, 1)
 *   ParsiDate::fromGregorian(2024, 3, 20)
 *   ParsiDate::parse('1403/01/01')
 */
final class ParsiDate implements \Stringable
{
    // -------------------------------------------------------------------------
    // Constructor & factories
    // -------------------------------------------------------------------------

    private function __construct(
        private readonly int $year,
        private readonly int $month,
        private readonly int $day,
    ) {}

    /**
     * Create from explicit Jalali year, month, day.
     *
     * @throws InvalidDateException
     */
    public static function create(int $year, int $month, int $day): self
    {
        self::assertValid($year, $month, $day);
        return new self($year, $month, $day);
    }

    /**
     * Create from today's date in the given timezone.
     */
    public static function now(\DateTimeZone|string $timezone = 'Asia/Tehran'): self
    {
        $tz  = is_string($timezone) ? new \DateTimeZone($timezone) : $timezone;
        $dt  = new \DateTimeImmutable('now', $tz);
        $j   = CalendarConverter::gregorianToJalali(
            (int) $dt->format('Y'),
            (int) $dt->format('n'),
            (int) $dt->format('j'),
        );
        return new self($j['year'], $j['month'], $j['day']);
    }

    /**
     * Create from a Gregorian date.
     */
    public static function fromGregorian(int $year, int $month, int $day): self
    {
        $j = CalendarConverter::gregorianToJalali($year, $month, $day);
        return new self($j['year'], $j['month'], $j['day']);
    }

    /**
     * Create from a PHP DateTime/DateTimeImmutable object.
     */
    public static function fromDateTime(\DateTimeInterface $dt): self
    {
        $j = CalendarConverter::gregorianToJalali(
            (int) $dt->format('Y'),
            (int) $dt->format('n'),
            (int) $dt->format('j'),
        );
        return new self($j['year'], $j['month'], $j['day']);
    }

    /**
     * Parse a Jalali date string.
     * Supported formats: Y/m/d  Y-m-d  Ymd
     *
     * @throws InvalidDateException
     */
    public static function parse(string $date): self
    {
        // Normalize Persian digits
        $date = PersianLocale::toLatinDigits($date);

        if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $date, $m)) {
            return self::create((int) $m[1], (int) $m[2], (int) $m[3]);
        }

        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $date, $m)) {
            return self::create((int) $m[1], (int) $m[2], (int) $m[3]);
        }

        throw new InvalidDateException("Cannot parse date string: \"$date\"");
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    public function year(): int  { return $this->year; }
    public function month(): int { return $this->month; }
    public function day(): int   { return $this->day; }

    public function isLeapYear(): bool
    {
        return CalendarConverter::isJalaliLeapYear($this->year);
    }

    /** Day of week: 0=Sunday … 6=Saturday */
    public function dayOfWeek(): int
    {
        return (int) $this->toDateTime()->format('w');
    }

    /** Day name in Persian (default) or English. */
    public function dayName(bool $persian = true): string
    {
        return PersianLocale::dayName($this->dayOfWeek(), $persian);
    }

    /** Month name in Persian (default) or English. */
    public function monthName(bool $persian = true): string
    {
        return PersianLocale::monthName($this->month, $persian);
    }

    /** 1-based day of year (1–365/366). */
    public function dayOfYear(): int
    {
        $days = 0;
        for ($m = 1; $m < $this->month; $m++) {
            $days += CalendarConverter::jalaliMonthDays($this->year, $m);
        }
        return $days + $this->day;
    }

    /** Number of days in this month. */
    public function daysInMonth(): int
    {
        return CalendarConverter::jalaliMonthDays($this->year, $this->month);
    }

    /** Number of days in this year. */
    public function daysInYear(): int
    {
        return $this->isLeapYear() ? 366 : 365;
    }

    // -------------------------------------------------------------------------
    // Arithmetic (immutable — each method returns a new instance)
    // -------------------------------------------------------------------------

    public function addDays(int $days): self
    {
        return self::fromDateTime(
            $this->toDateTime()->modify("+{$days} days")
        );
    }

    public function subDays(int $days): self
    {
        return $this->addDays(-$days);
    }

    public function addMonths(int $months): self
    {
        $y = $this->year;
        $m = $this->month + $months;
        $d = $this->day;

        while ($m > 12) { $m -= 12; $y++; }
        while ($m < 1)  { $m += 12; $y--; }

        // Clamp day to valid range (e.g. 31st Shahrivar → 30th)
        $d = min($d, CalendarConverter::jalaliMonthDays($y, $m));

        return new self($y, $m, $d);
    }

    public function subMonths(int $months): self
    {
        return $this->addMonths(-$months);
    }

    public function addYears(int $years): self
    {
        $y = $this->year + $years;
        $d = min($this->day, CalendarConverter::jalaliMonthDays($y, $this->month));
        return new self($y, $this->month, $d);
    }

    public function subYears(int $years): self
    {
        return $this->addYears(-$years);
    }

    /** Go to the first day of this month. */
    public function startOfMonth(): self
    {
        return new self($this->year, $this->month, 1);
    }

    /** Go to the last day of this month. */
    public function endOfMonth(): self
    {
        return new self($this->year, $this->month, $this->daysInMonth());
    }

    /** Go to the first day of this year (1 Farvardin). */
    public function startOfYear(): self
    {
        return new self($this->year, 1, 1);
    }

    /** Go to the last day of this year (29 or 30 Esfand). */
    public function endOfYear(): self
    {
        return new self($this->year, 12, $this->isLeapYear() ? 30 : 29);
    }

    // -------------------------------------------------------------------------
    // Comparison
    // -------------------------------------------------------------------------

    public function equalTo(self $other): bool
    {
        return $this->year  === $other->year
            && $this->month === $other->month
            && $this->day   === $other->day;
    }

    public function before(self $other): bool
    {
        return $this->toDateTime() < $other->toDateTime();
    }

    public function after(self $other): bool
    {
        return $this->toDateTime() > $other->toDateTime();
    }

    public function between(self $start, self $end): bool
    {
        return !$this->before($start) && !$this->after($end);
    }

    /** Difference in days (positive if $other is after $this). */
    public function diffInDays(self $other): int
    {
        $a = $this->toDateTime()->setTime(0,0);
        $b = $other->toDateTime()->setTime(0,0);
        return (int) $a->diff($b)->days * ($a <= $b ? 1 : -1);
    }

    /** Difference in months (approximate). */
    public function diffInMonths(self $other): int
    {
        return ($other->year - $this->year) * 12 + ($other->month - $this->month);
    }

    // -------------------------------------------------------------------------
    // Formatting
    // -------------------------------------------------------------------------

    /**
     * Format the date using familiar tokens:
     *
     *   Y  - 4-digit year          (1403)
     *   y  - 2-digit year          (03)
     *   m  - 2-digit month         (01)
     *   n  - month without padding (1)
     *   M  - Persian month name    (فروردین)
     *   F  - English month name    (Farvardin)
     *   d  - 2-digit day           (05)
     *   j  - day without padding   (5)
     *   D  - Persian day name      (شنبه)
     *   l  - English day name      (Shanbeh)
     *   N  - day of week (1=Sat…7=Fri, Persian week)
     *   w  - day of week 0=Sun…6=Sat (PHP standard)
     *   z  - day of year           (1–366)
     *   t  - days in month
     *   L  - 1 if leap year, 0 otherwise
     */
    public function format(string $fmt): string
    {
        $dow = $this->dayOfWeek();
        $map = [
            'Y' => (string) $this->year,
            'y' => substr((string) $this->year, -2),
            'm' => sprintf('%02d', $this->month),
            'n' => (string) $this->month,
            'M' => PersianLocale::monthName($this->month, persian: true),
            'F' => PersianLocale::monthName($this->month, persian: false),
            'd' => sprintf('%02d', $this->day),
            'j' => (string) $this->day,
            'D' => PersianLocale::dayName($dow, persian: true),
            'l' => PersianLocale::dayName($dow, persian: false),
            'N' => (string) (($dow + 1) % 7 + 1),
            'w' => (string) $dow,
            'z' => (string) $this->dayOfYear(),
            't' => (string) $this->daysInMonth(),
            'L' => $this->isLeapYear() ? '1' : '0',
        ];

        $result = '';
        $len    = strlen($fmt);
        for ($i = 0; $i < $len; $i++) {
            $char    = $fmt[$i];
            $result .= $map[$char] ?? $char;
        }
        return $result;
    }

    /**
     * Format with Persian (Eastern Arabic) digits.
     */
    public function formatPersian(string $fmt): string
    {
        return PersianLocale::toPersianDigits($this->format($fmt));
    }

    // -------------------------------------------------------------------------
    // Conversion
    // -------------------------------------------------------------------------

    /**
     * Convert to a PHP DateTimeImmutable (midnight, Asia/Tehran).
     */
    public function toDateTime(\DateTimeZone|string $timezone = 'Asia/Tehran'): \DateTimeImmutable
    {
        $tz = is_string($timezone) ? new \DateTimeZone($timezone) : $timezone;
        $g  = CalendarConverter::jalaliToGregorian($this->year, $this->month, $this->day);
        return new \DateTimeImmutable(
            sprintf('%04d-%02d-%02d', $g['year'], $g['month'], $g['day']),
            $tz
        );
    }

    /**
     * Return as a Gregorian date array.
     *
     * @return array{year: int, month: int, day: int}
     */
    public function toGregorian(): array
    {
        return CalendarConverter::jalaliToGregorian($this->year, $this->month, $this->day);
    }

    /**
     * Return as array.
     *
     * @return array{year: int, month: int, day: int}
     */
    public function toArray(): array
    {
        return ['year' => $this->year, 'month' => $this->month, 'day' => $this->day];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function __toString(): string
    {
        return $this->format('Y/m/d');
    }

    // -------------------------------------------------------------------------
    // Holidays & workdays
    // -------------------------------------------------------------------------

    /**
     * Check if this date is Friday (Iran's official weekend).
     */
    public function isWeekend(): bool
    {
        return HolidayChecker::isWeekend($this->dayOfWeek());
    }

    /**
     * Check if this date is an official public holiday (not counting weekends).
     */
    public function isHoliday(): bool
    {
        return HolidayChecker::isHoliday($this->year, $this->month, $this->day);
    }

    /**
     * Get the holiday name if this date is a holiday, null otherwise.
     */
    public function holidayName(): ?string
    {
        return HolidayChecker::holidayName($this->year, $this->month, $this->day);
    }

    /**
     * Check if this date is a working day (not a weekend and not a holiday).
     */
    public function isWorkday(): bool
    {
        return !$this->isWeekend() && !$this->isHoliday();
    }

    /**
     * Get the next working day after this date.
     */
    public function nextWorkday(): self
    {
        $date = $this->addDays(1);
        while (!$date->isWorkday()) {
            $date = $date->addDays(1);
        }
        return $date;
    }

    /**
     * Get the previous working day before this date.
     */
    public function previousWorkday(): self
    {
        $date = $this->subDays(1);
        while (!$date->isWorkday()) {
            $date = $date->subDays(1);
        }
        return $date;
    }

    /**
     * Add N working days (skips weekends and holidays).
     */
    public function addWorkdays(int $days): self
    {
        $date    = $this;
        $counted = 0;
        $step    = $days >= 0 ? 1 : -1;
        $target  = abs($days);

        while ($counted < $target) {
            $date = $date->addDays($step);
            if ($date->isWorkday()) {
                $counted++;
            }
        }
        return $date;
    }

    /**
     * Count working days between this date and another (exclusive of start, inclusive of end).
     */
    public function workdaysUntil(self $other): int
    {
        $count  = 0;
        $cursor = $this->before($other) ? $this : $other;
        $end    = $this->before($other) ? $other : $this;

        while ($cursor->before($end)) {
            $cursor = $cursor->addDays(1);
            if ($cursor->isWorkday()) {
                $count++;
            }
        }
        return $count;
    }

    // -------------------------------------------------------------------------
    // Validation helper
    // -------------------------------------------------------------------------

    private static function assertValid(int $y, int $m, int $d): void
    {
        if ($m < 1 || $m > 12) {
            throw new InvalidDateException("Invalid Jalali month: $m (must be 1–12)");
        }

        $maxDay = CalendarConverter::jalaliMonthDays($y, $m);
        if ($d < 1 || $d > $maxDay) {
            throw new InvalidDateException(
                "Invalid Jalali day: $d for month $m/$y (must be 1–$maxDay)"
            );
        }
    }
}