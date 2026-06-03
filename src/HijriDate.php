<?php

declare(strict_types=1);

namespace ParsiDate;

/**
 * Represents an Islamic (Hijri/Lunar) date.
 *
 * Returned by ParsiDate::toHijri() — immutable, read-only value object.
 *
 * @example
 *   $h = ParsiDate::create(1403, 1, 1)->toHijri();
 *   echo $h->format('j F Y');        // e.g.  20 رمضان 1445
 *   echo $h->formatPersian('j F Y'); // ۲۰ رمضان ۱۴۴۵
 */
final class HijriDate implements \Stringable
{
    // Arabic month names
    private const MONTH_NAMES_AR = [
        1  => 'محرم',
        2  => 'صفر',
        3  => 'ربیع الاول',
        4  => 'ربیع الثانی',
        5  => 'جمادی الاول',
        6  => 'جمادی الثانی',
        7  => 'رجب',
        8  => 'شعبان',
        9  => 'رمضان',
        10 => 'شوال',
        11 => 'ذوالقعده',
        12 => 'ذوالحجه',
    ];

    // Transliterated month names (Latin)
    private const MONTH_NAMES_LATIN = [
        1  => 'Muharram',
        2  => 'Safar',
        3  => "Rabi' al-Awwal",
        4  => "Rabi' al-Thani",
        5  => "Jumada al-Awwal",
        6  => "Jumada al-Thani",
        7  => 'Rajab',
        8  => "Sha'ban",
        9  => 'Ramadan',
        10 => 'Shawwal',
        11 => "Dhu al-Qi'dah",
        12 => 'Dhu al-Hijjah',
    ];

    private const PERSIAN_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    public function __construct(
        private readonly int $year,
        private readonly int $month,
        private readonly int $day,
    ) {}

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function year(): int
    {
        return $this->year;
    }
    public function month(): int
    {
        return $this->month;
    }
    public function day(): int
    {
        return $this->day;
    }

    /** Month name in Arabic. */
    public function monthName(): string
    {
        return self::MONTH_NAMES_AR[$this->month];
    }

    /** Month name transliterated in Latin script. */
    public function monthNameLatin(): string
    {
        return self::MONTH_NAMES_LATIN[$this->month];
    }

    // -------------------------------------------------------------------------
    // Formatting
    // -------------------------------------------------------------------------

    /**
     * Format the date using tokens.
     *
     * Tokens:
     *   Y = 4-digit year          (e.g. 1445)
     *   y = 2-digit year          (e.g. 45)
     *   m = zero-padded month     (e.g. 09)
     *   n = month without padding (e.g. 9)
     *   d = zero-padded day       (e.g. 03)
     *   j = day without padding   (e.g. 3)
     *   F = Arabic month name     (e.g. رمضان)
     *   M = Latin month name      (e.g. Ramadan)
     */
    public function format(string $fmt): string
    {
        return strtr($fmt, $this->tokens());
    }

    /**
     * Same as format() but digits are replaced with Eastern Arabic (Persian) digits.
     */
    public function formatPersian(string $fmt): string
    {
        return $this->toPersianDigits($this->format($fmt));
    }

    // -------------------------------------------------------------------------
    // Conversion helpers
    // -------------------------------------------------------------------------

    /** @return array{year: int, month: int, day: int} */
    public function toArray(): array
    {
        return ['year' => $this->year, 'month' => $this->month, 'day' => $this->day];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    public function __toString(): string
    {
        return $this->format('Y/m/d');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /** @return array<string,string> */
    private function tokens(): array
    {
        return [
            'Y' => (string) $this->year,
            'y' => substr((string) $this->year, -2),
            'm' => str_pad((string) $this->month, 2, '0', STR_PAD_LEFT),
            'n' => (string) $this->month,
            'd' => str_pad((string) $this->day, 2, '0', STR_PAD_LEFT),
            'j' => (string) $this->day,
            'F' => self::MONTH_NAMES_AR[$this->month],
            'M' => self::MONTH_NAMES_LATIN[$this->month],
        ];
    }

    private function toPersianDigits(string $str): string
    {
        return str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            self::PERSIAN_DIGITS,
            $str,
        );
    }
}
