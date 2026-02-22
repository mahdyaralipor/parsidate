<?php
declare(strict_types=1);
namespace ParsiDate;

use ParsiDate\Holidays\HolidayChecker;

/**
 * Represents an immutable range between two Jalali dates (inclusive).
 *
 * @example
 *   $period = ParsiPeriod::create(ParsiDate::create(1403, 1, 1), ParsiDate::create(1403, 1, 31));
 *   $period->days();
 *   $period->workdays();
 *   $period->holidays();
 *   foreach ($period as $date) { ... }
 */
final class ParsiPeriod implements \Countable, \IteratorAggregate
{
    private function __construct(
        private readonly ParsiDate $start,
        private readonly ParsiDate $end,
    ) {}

    // -------------------------------------------------------------------------
    // Factories
    // -------------------------------------------------------------------------

    /**
     * Create a period between two dates (start must be <= end).
     */
    public static function create(ParsiDate $start, ParsiDate $end): self
    {
        if ($start->after($end)) {
            [$start, $end] = [$end, $start];
        }
        return new self($start, $end);
    }

    /**
     * Create a period for an entire Jalali month.
     */
    public static function ofMonth(int $year, int $month): self
    {
        $start = ParsiDate::create($year, $month, 1);
        $end   = $start->endOfMonth();
        return new self($start, $end);
    }

    /**
     * Create a period for an entire Jalali year.
     */
    public static function ofYear(int $year): self
    {
        $start = ParsiDate::create($year, 1, 1);
        $end   = $start->endOfYear();
        return new self($start, $end);
    }

    /**
     * Create a period of N days starting from a date.
     */
    public static function fromDate(ParsiDate $start, int $days): self
    {
        return new self($start, $start->addDays($days - 1));
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    public function start(): ParsiDate { return $this->start; }
    public function end(): ParsiDate   { return $this->end; }

    /**
     * Total number of days in the period (inclusive).
     */
    public function days(): int
    {
        return abs($this->start->diffInDays($this->end)) + 1;
    }

    // -------------------------------------------------------------------------
    // Contains & overlap
    // -------------------------------------------------------------------------

    public function contains(ParsiDate $date): bool
    {
        return $date->between($this->start, $this->end);
    }

    public function overlaps(self $other): bool
    {
        return $this->start->before($other->end) || $this->start->equalTo($other->end)
            && $this->end->after($other->start) || $this->end->equalTo($other->start);
    }

    /**
     * Get the overlapping period between this and another, or null if no overlap.
     */
    public function overlap(self $other): ?self
    {
        $start = $this->start->after($other->start) ? $this->start : $other->start;
        $end   = $this->end->before($other->end)    ? $this->end   : $other->end;

        if ($start->after($end)) {
            return null;
        }
        return new self($start, $end);
    }

    // -------------------------------------------------------------------------
    // Filtering
    // -------------------------------------------------------------------------

    /**
     * All dates in the period.
     *
     * @return ParsiDate[]
     */
    public function toArray(): array
    {
        $dates  = [];
        $cursor = $this->start;
        while (!$cursor->after($this->end)) {
            $dates[] = $cursor;
            $cursor  = $cursor->addDays(1);
        }
        return $dates;
    }

    /**
     * All Fridays (weekends) in the period.
     *
     * @return ParsiDate[]
     */
    public function fridays(): array
    {
        return array_values(array_filter(
            $this->toArray(),
            fn(ParsiDate $d) => $d->isWeekend()
        ));
    }

    /**
     * All official holidays (excluding Fridays) in the period.
     *
     * @return ParsiDate[]
     */
    public function holidays(): array
    {
        return array_values(array_filter(
            $this->toArray(),
            fn(ParsiDate $d) => $d->isHoliday()
        ));
    }

    /**
     * All non-working days (Fridays + official holidays) in the period.
     *
     * @return ParsiDate[]
     */
    public function offDays(): array
    {
        return array_values(array_filter(
            $this->toArray(),
            fn(ParsiDate $d) => !$d->isWorkday()
        ));
    }

    /**
     * All working days in the period.
     *
     * @return ParsiDate[]
     */
    public function workdays(): array
    {
        return array_values(array_filter(
            $this->toArray(),
            fn(ParsiDate $d) => $d->isWorkday()
        ));
    }

    /**
     * Filter dates by a custom callback.
     *
     * @return ParsiDate[]
     */
    public function filter(callable $callback): array
    {
        return array_values(array_filter($this->toArray(), $callback));
    }

    /**
     * Get all dates matching a specific day of week (0=Sun … 6=Sat).
     *
     * @return ParsiDate[]
     */
    public function byDayOfWeek(int $dayOfWeek): array
    {
        return $this->filter(fn(ParsiDate $d) => $d->dayOfWeek() === $dayOfWeek);
    }

    // -------------------------------------------------------------------------
    // Counts
    // -------------------------------------------------------------------------

    public function countWorkdays(): int  { return count($this->workdays()); }
    public function countHolidays(): int  { return count($this->holidays()); }
    public function countFridays(): int   { return count($this->fridays()); }
    public function countOffDays(): int   { return count($this->offDays()); }

    // -------------------------------------------------------------------------
    // Splitting
    // -------------------------------------------------------------------------

    /**
     * Split the period into chunks of N days.
     *
     * @return self[]
     */
    public function splitByDays(int $days): array
    {
        $chunks = [];
        $cursor = $this->start;

        while (!$cursor->after($this->end)) {
            $chunkEnd = $cursor->addDays($days - 1);
            if ($chunkEnd->after($this->end)) {
                $chunkEnd = $this->end;
            }
            $chunks[] = new self($cursor, $chunkEnd);
            $cursor   = $chunkEnd->addDays(1);
        }

        return $chunks;
    }

    /**
     * Split into monthly sub-periods.
     *
     * @return self[]
     */
    public function splitByMonth(): array
    {
        $chunks  = [];
        $cursor  = $this->start;

        while (!$cursor->after($this->end)) {
            $monthEnd = $cursor->endOfMonth();
            $chunkEnd = $monthEnd->after($this->end) ? $this->end : $monthEnd;
            $chunks[] = new self($cursor, $chunkEnd);
            $cursor   = $monthEnd->addDays(1);
        }

        return $chunks;
    }

    // -------------------------------------------------------------------------
    // Interfaces
    // -------------------------------------------------------------------------

    /** Count = total days (for Countable). */
    public function count(): int
    {
        return $this->days();
    }

    /** Iterate over each day (for IteratorAggregate / foreach). */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->toArray());
    }

    public function __toString(): string
    {
        return $this->start . ' → ' . $this->end;
    }
}