<?php
declare(strict_types=1);
namespace ParsiDate;

use ParsiDate\Converter\CalendarConverter;
use ParsiDate\Exceptions\InvalidDateException;
use ParsiDate\Support\PersianLocale;

/**
 * Immutable Jalali date + time.
 *
 * @example
 *   ParsiDateTime::now()
 *   ParsiDateTime::create(1403, 1, 1, 14, 30, 0)
 *   ParsiDateTime::fromGregorian(2024, 3, 20, 10, 0, 0)
 *   $dt->format('Y/m/d H:i:s')
 */
final class ParsiDateTime implements \Stringable
{
    // -------------------------------------------------------------------------
    // Constructor & factories
    // -------------------------------------------------------------------------

    private function __construct(
        private readonly int           $year,
        private readonly int           $month,
        private readonly int           $day,
        private readonly int           $hour,
        private readonly int           $minute,
        private readonly int           $second,
        private readonly \DateTimeZone $timezone,
    ) {}

    public static function create(
        int $year, int $month, int $day,
        int $hour = 0, int $minute = 0, int $second = 0,
        \DateTimeZone|string $timezone = 'Asia/Tehran',
    ): self {
        self::assertValidDate($year, $month, $day);
        self::assertValidTime($hour, $minute, $second);
        $tz = is_string($timezone) ? new \DateTimeZone($timezone) : $timezone;
        return new self($year, $month, $day, $hour, $minute, $second, $tz);
    }

    public static function now(\DateTimeZone|string $timezone = 'Asia/Tehran'): self
    {
        $tz = is_string($timezone) ? new \DateTimeZone($timezone) : $timezone;
        $dt = new \DateTimeImmutable('now', $tz);
        $j  = CalendarConverter::gregorianToJalali(
            (int) $dt->format('Y'),
            (int) $dt->format('n'),
            (int) $dt->format('j'),
        );
        return new self(
            $j['year'], $j['month'], $j['day'],
            (int) $dt->format('H'),
            (int) $dt->format('i'),
            (int) $dt->format('s'),
            $tz,
        );
    }

    public static function fromGregorian(
        int $year, int $month, int $day,
        int $hour = 0, int $minute = 0, int $second = 0,
        \DateTimeZone|string $timezone = 'Asia/Tehran',
    ): self {
        $tz = is_string($timezone) ? new \DateTimeZone($timezone) : $timezone;
        $j  = CalendarConverter::gregorianToJalali($year, $month, $day);
        self::assertValidTime($hour, $minute, $second);
        return new self($j['year'], $j['month'], $j['day'], $hour, $minute, $second, $tz);
    }

    public static function fromDateTime(\DateTimeInterface $dt): self
    {
        $tz = $dt instanceof \DateTimeImmutable
            ? $dt->getTimezone()
            : \DateTimeImmutable::createFromMutable($dt)->getTimezone(); // @phpstan-ignore-line
        $j  = CalendarConverter::gregorianToJalali(
            (int) $dt->format('Y'),
            (int) $dt->format('n'),
            (int) $dt->format('j'),
        );
        return new self(
            $j['year'], $j['month'], $j['day'],
            (int) $dt->format('H'),
            (int) $dt->format('i'),
            (int) $dt->format('s'),
            $tz,
        );
    }

    /** Strip time part and return a ParsiDate. */
    public function toDate(): ParsiDate
    {
        return ParsiDate::create($this->year, $this->month, $this->day);
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    public function year(): int     { return $this->year; }
    public function month(): int    { return $this->month; }
    public function day(): int      { return $this->day; }
    public function hour(): int     { return $this->hour; }
    public function minute(): int   { return $this->minute; }
    public function second(): int   { return $this->second; }
    public function timezone(): \DateTimeZone { return $this->timezone; }

    public function isLeapYear(): bool
    {
        return CalendarConverter::isJalaliLeapYear($this->year);
    }

    public function dayOfWeek(): int
    {
        return (int) $this->toDateTime()->format('w');
    }

    public function dayName(bool $persian = true): string
    {
        return PersianLocale::dayName($this->dayOfWeek(), $persian);
    }

    public function monthName(bool $persian = true): string
    {
        return PersianLocale::monthName($this->month, $persian);
    }

    public function timestamp(): int
    {
        return $this->toDateTime()->getTimestamp();
    }

    // -------------------------------------------------------------------------
    // Arithmetic
    // -------------------------------------------------------------------------

    public function addSeconds(int $seconds): self
    {
        return self::fromDateTime($this->toDateTime()->modify("+{$seconds} seconds"));
    }

    public function subSeconds(int $seconds): self { return $this->addSeconds(-$seconds); }

    public function addMinutes(int $minutes): self
    {
        return self::fromDateTime($this->toDateTime()->modify("+{$minutes} minutes"));
    }

    public function subMinutes(int $minutes): self { return $this->addMinutes(-$minutes); }

    public function addHours(int $hours): self
    {
        return self::fromDateTime($this->toDateTime()->modify("+{$hours} hours"));
    }

    public function subHours(int $hours): self { return $this->addHours(-$hours); }

    public function addDays(int $days): self
    {
        return self::fromDateTime($this->toDateTime()->modify("+{$days} days"));
    }

    public function subDays(int $days): self { return $this->addDays(-$days); }

    public function addMonths(int $months): self
    {
        $date = $this->toDate()->addMonths($months);
        return new self(
            $date->year(), $date->month(), $date->day(),
            $this->hour, $this->minute, $this->second, $this->timezone,
        );
    }

    public function subMonths(int $months): self { return $this->addMonths(-$months); }

    public function addYears(int $years): self
    {
        $date = $this->toDate()->addYears($years);
        return new self(
            $date->year(), $date->month(), $date->day(),
            $this->hour, $this->minute, $this->second, $this->timezone,
        );
    }

    public function subYears(int $years): self { return $this->addYears(-$years); }

    /** Set specific time components (returns new instance). */
    public function setTime(int $hour, int $minute, int $second = 0): self
    {
        self::assertValidTime($hour, $minute, $second);
        return new self(
            $this->year, $this->month, $this->day,
            $hour, $minute, $second, $this->timezone,
        );
    }

    public function startOfDay(): self { return $this->setTime(0, 0, 0); }
    public function endOfDay(): self   { return $this->setTime(23, 59, 59); }

    // -------------------------------------------------------------------------
    // Comparison
    // -------------------------------------------------------------------------

    public function equalTo(self $other): bool
    {
        return $this->toDateTime() == $other->toDateTime();
    }

    public function before(self $other): bool
    {
        return $this->toDateTime() < $other->toDateTime();
    }

    public function after(self $other): bool
    {
        return $this->toDateTime() > $other->toDateTime();
    }

    public function diffInSeconds(self $other): int
    {
        return abs($this->timestamp() - $other->timestamp());
    }

    public function diffInMinutes(self $other): int
    {
        return intdiv($this->diffInSeconds($other), 60);
    }

    public function diffInHours(self $other): int
    {
        return intdiv($this->diffInSeconds($other), 3600);
    }

    public function diffInDays(self $other): int
    {
        return intdiv($this->diffInSeconds($other), 86400);
    }

    // -------------------------------------------------------------------------
    // Formatting
    // -------------------------------------------------------------------------

    /**
     * Format tokens — extends ParsiDate tokens with:
     *
     *   H  - 24h hour with padding    (09)
     *   G  - 24h hour no padding      (9)
     *   h  - 12h hour with padding    (09)
     *   g  - 12h hour no padding      (9)
     *   i  - minutes with padding     (05)
     *   s  - seconds with padding     (00)
     *   A  - AM/PM
     *   a  - am/pm
     *   u  - Unix timestamp
     *   e  - Timezone name
     */
    public function format(string $fmt): string
    {
        $hour12 = $this->hour % 12 ?: 12;
        $dow    = $this->dayOfWeek();

        $map = [
            // Date tokens
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
            'w' => (string) $dow,
            'L' => $this->isLeapYear() ? '1' : '0',
            // Time tokens
            'H' => sprintf('%02d', $this->hour),
            'G' => (string) $this->hour,
            'h' => sprintf('%02d', $hour12),
            'g' => (string) $hour12,
            'i' => sprintf('%02d', $this->minute),
            's' => sprintf('%02d', $this->second),
            'A' => $this->hour < 12 ? 'AM' : 'PM',
            'a' => $this->hour < 12 ? 'am' : 'pm',
            'u' => (string) $this->timestamp(),
            'e' => $this->timezone->getName(),
        ];

        $result = '';
        $len    = strlen($fmt);
        for ($i = 0; $i < $len; $i++) {
            $char    = $fmt[$i];
            $result .= $map[$char] ?? $char;
        }
        return $result;
    }

    public function formatPersian(string $fmt): string
    {
        return PersianLocale::toPersianDigits($this->format($fmt));
    }

    // -------------------------------------------------------------------------
    // Timezone
    // -------------------------------------------------------------------------

    public function inTimezone(\DateTimeZone|string $timezone): self
    {
        $tz = is_string($timezone) ? new \DateTimeZone($timezone) : $timezone;
        return self::fromDateTime($this->toDateTime()->setTimezone($tz));
    }

    // -------------------------------------------------------------------------
    // Conversion
    // -------------------------------------------------------------------------

    public function toDateTime(): \DateTimeImmutable
    {
        $g = CalendarConverter::jalaliToGregorian($this->year, $this->month, $this->day);
        return new \DateTimeImmutable(
            sprintf(
                '%04d-%02d-%02d %02d:%02d:%02d',
                $g['year'], $g['month'], $g['day'],
                $this->hour, $this->minute, $this->second
            ),
            $this->timezone,
        );
    }

    public function toArray(): array
    {
        return [
            'year'   => $this->year,
            'month'  => $this->month,
            'day'    => $this->day,
            'hour'   => $this->hour,
            'minute' => $this->minute,
            'second' => $this->second,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function __toString(): string
    {
        return $this->format('Y/m/d H:i:s');
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    private static function assertValidDate(int $y, int $m, int $d): void
    {
        if ($m < 1 || $m > 12) {
            throw new InvalidDateException("Invalid month: $m");
        }
        $max = CalendarConverter::jalaliMonthDays($y, $m);
        if ($d < 1 || $d > $max) {
            throw new InvalidDateException("Invalid day: $d for $m/$y (max $max)");
        }
    }

    private static function assertValidTime(int $h, int $i, int $s): void
    {
        if ($h < 0 || $h > 23) throw new InvalidDateException("Invalid hour: $h");
        if ($i < 0 || $i > 59) throw new InvalidDateException("Invalid minute: $i");
        if ($s < 0 || $s > 59) throw new InvalidDateException("Invalid second: $s");
    }
}