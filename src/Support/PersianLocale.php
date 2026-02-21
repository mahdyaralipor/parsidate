<?php

declare(strict_types=1);

namespace ParsiDate\Support;

/**
 * Persian (Farsi)  locale data for month names, day names, and digit conversion.
 * 
 */
final class PersianLocale
{
    public const MONTH_NAMES = [
        1 => "فروردین",
        2 => "اردیبهشت",
        3 => "خرداد",
        4 => "تیر",
        5 => "مرداد",
        6 => "شهریور",
        7 => "مهر",
        8 => "آبان",
        9 => "آذر",
        10 => "دی",
        11 => "بهمن",
        12 => "اسفند"
    ];

   public const MONTH_NAMES_EN = [
        1  => 'Farvardin',
        2  => 'Ordibehesht',
        3  => 'Khordad',
        4  => 'Tir',
        5  => 'Mordad',
        6  => 'Shahrivar',
        7  => 'Mehr',
        8  => 'Aban',
        9  => 'Azar',
        10 => 'Dey',
        11 => 'Bahman',
        12 => 'Esfand',
    ];

    public const DAY_NAMES = [
        0 => 'یکشنبه',   // Sunday
        1 => 'دوشنبه',   // Monday
        2 => 'سه‌شنبه',  // Tuesday
        3 => 'چهارشنبه', // Wednesday
        4 => 'پنجشنبه',  // Thursday
        5 => 'جمعه',     // Friday
        6 => 'شنبه',     // Saturday
    ];

    public const DAY_NAMES_EN = [
        0 => 'Yekshanbeh',
        1 => 'Doshanbeh',
        2 => 'Seshanbeh',
        3 => 'Chaharshanbeh',
        4 => 'Panjshanbeh',
        5 => 'Jomeh',
        6 => 'Shanbeh',
    ];
    private const PERSIAN_DIGITS = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];

    
}

