<div align="center">

<img src="https://raw.githubusercontent.com/Mahdyaralipor/parsidate/main/art/logo.svg" width="120" alt="ParsiDate Logo">

# ParsiDate

**A modern, zero-dependency PHP library for Jalali (Shamsi) date and time.**

[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Tests](https://img.shields.io/badge/tests-131%20passing-brightgreen?style=flat-square)](#)
[![License](https://img.shields.io/badge/license-MIT-blue?style=flat-square)](LICENSE)
[![Packagist](https://img.shields.io/packagist/v/mahdyaralipor/parsidate?style=flat-square)](https://packagist.org/packages/mahdyaralipor/parsidate)

[English](#english) · [فارسی](#فارسی)

</div>

---

<a name="english"></a>

## 🇬🇧 English

### Why ParsiDate?

Most existing Jalali libraries for PHP are either outdated, depend on deprecated functions like `strftime()`, tied to a specific framework, or have clunky APIs. **ParsiDate** is built from the ground up for modern PHP:

- ✅ **Zero external dependencies** — pure PHP 8.1+
- ✅ **Immutable by default** — no side-effects, no surprises
- ✅ **Fluent, chainable API** — similar feel to Carbon
- ✅ **Full holiday support** — official Iranian public holidays built-in (1400–1405+)
- ✅ **Period & Range** — iterate, filter, split date ranges
- ✅ **Persian digit support** — نمایش اعداد فارسی out of the box
- ✅ **PHP 8.1–8.5** tested and working

---

### Installation

```bash
composer require mahdyaralipor/parsidate
```

---

### Quick Start

```php
use ParsiDate\ParsiDate;
use ParsiDate\ParsiDateTime;
use ParsiDate\ParsiPeriod;

// Today
$today = ParsiDate::now();                        // e.g. 1403/06/15
echo $today->format('l j M Y');                   // Panjshanbeh 15 شهریور 1403

// Create & convert
$nowruz = ParsiDate::create(1403, 1, 1);
$greg   = $nowruz->toGregorian();                 // ['year'=>2024,'month'=>3,'day'=>20]

// Parse
$date = ParsiDate::parse('1403/06/15');
$date = ParsiDate::parse('۱۴۰۳/۰۶/۱۵');          // Persian digits supported

// From Gregorian
$date = ParsiDate::fromGregorian(2024, 3, 20);
```

---

### Fluent Arithmetic

```php
$date = ParsiDate::create(1403, 1, 1);

$date->addDays(10);           // 1403/01/11
$date->subMonths(2);          // 1402/11/01
$date->addYears(1);           // 1404/01/01

$date->startOfMonth();        // 1403/01/01
$date->endOfMonth();          // 1403/01/31
$date->startOfYear();         // 1403/01/01
$date->endOfYear();           // 1403/12/30  (leap year)
```

---

### Formatting

```php
$date = ParsiDate::create(1403, 6, 15);

$date->format('Y/m/d');           // 1403/06/15
$date->format('j M Y');           // 15 شهریور 1403
$date->format('D، j F Y');        // شنبه، 15 Shahrivar 1403
$date->formatPersian('Y/m/d');    // ۱۴۰۳/۰۶/۱۵

// Available tokens
// Y=year, m=month(pad), n=month, d=day(pad), j=day
// M=Persian month name, F=English month name
// D=Persian day name, l=English day name
// L=leap(0/1), t=days in month, z=day of year
```

---

### Date & Time

```php
use ParsiDate\ParsiDateTime;

$now = ParsiDateTime::now();                         // 1403/06/15 14:30:00
$now->format('Y/m/d H:i:s');                        // 1403/06/15 14:30:00
$now->formatPersian('Y/m/d H:i');                   // ۱۴۰۳/۰۶/۱۵ ۱۴:۳۰

$dt = ParsiDateTime::create(1403, 6, 15, 22, 0, 0);
$dt->addHours(3)->format('H:i');                    // 01:00 (next day)
$dt->startOfDay()->format('H:i:s');                 // 00:00:00
$dt->endOfDay()->format('H:i:s');                   // 23:59:59

// Timezone support
$dt->inTimezone('UTC')->format('H:i');
```

---

### Holidays & Workdays

```php
$date = ParsiDate::create(1403, 1, 1);

$date->isHoliday();           // true  — Nowruz
$date->holidayName();         // "جشن نوروز"
$date->isWeekend();           // false (it's Wednesday)
$date->isWorkday();           // false (holiday)

// Working day navigation
$date->nextWorkday();         // first workday after this date
$date->previousWorkday();     // last workday before this date
$date->addWorkdays(5);        // 5 working days from now (skips weekends & holidays)
$date->workdaysUntil($other); // count working days between two dates

// Custom/updated holidays
use ParsiDate\Holidays\HolidayChecker;

HolidayChecker::setVariableHolidays(1406, [
    [3, 15, 'تعطیل فرضی'],
]);

// List holidays
HolidayChecker::ofMonth(1403, 1);  // all holidays in Farvardin 1403
HolidayChecker::ofYear(1403);      // all holidays in 1403
```

---

### Period & Range

```php
use ParsiDate\ParsiPeriod;

// Create a period
$period = ParsiPeriod::create(
    ParsiDate::create(1403, 1, 1),
    ParsiDate::create(1403, 1, 31)
);

// Or use shortcuts
$period = ParsiPeriod::ofMonth(1403, 1);
$period = ParsiPeriod::ofYear(1403);
$period = ParsiPeriod::fromDate(ParsiDate::create(1403, 1, 1), 30); // 30 days

// Info
$period->days();              // 31
$period->countWorkdays();     // working days count
$period->countHolidays();     // holidays count

// Filter
$period->toArray();           // all ParsiDate objects
$period->workdays();          // only working days
$period->holidays();          // only official holidays
$period->fridays();           // only Fridays
$period->byDayOfWeek(6);      // all Saturdays (6=Sat)
$period->filter(fn($d) => $d->day() % 2 === 0); // custom filter

// Contains & overlap
$period->contains($date);     // bool
$period->overlap($other);     // ParsiPeriod|null

// Split
$period->splitByDays(7);      // weekly chunks
$period->splitByMonth();      // monthly chunks

// Iterable — use in foreach!
foreach ($period as $date) {
    echo $date->format('Y/m/d') . PHP_EOL;
}
```

---

### Comparison

```php
$a = ParsiDate::create(1403, 1, 1);
$b = ParsiDate::create(1403, 6, 1);

$a->before($b);               // true
$a->after($b);                // false
$a->equalTo($b);              // false
$a->between($start, $end);    // true/false

$a->diffInDays($b);           // 155
$a->diffInMonths($b);         // 5
```

---

### Hijri (Islamic) Date Conversion

```php
$date = ParsiDate::create(1403, 1, 1);  // Nowruz 1403

$hijri = $date->toHijri();

// Accessors
$hijri->year();    // 1445
$hijri->month();   // 9
$hijri->day();     // 10

// Month names
$hijri->monthName();       // رمضان
$hijri->monthNameLatin();  // Ramadan

// Formatting
$hijri->format('j F Y');          // 10 رمضان 1445
$hijri->format('Y/m/d');          // 1445/09/10
$hijri->formatPersian('j F Y');   // ۱۰ رمضان ۱۴۴۵
$hijri->formatPersian('Y/m/d');   // ۱۴۴۵/۰۹/۱۰

// Available format tokens
// Y=4-digit year, y=2-digit year
// m=month(padded), n=month(no pad)
// d=day(padded),   j=day(no pad)
// F=Arabic month name, M=Latin month name

// Convert to array / JSON
$hijri->toArray();  // ['year' => 1445, 'month' => 9, 'day' => 10]
$hijri->toJson();   // {"year":1445,"month":9,"day":10}
(string) $hijri;    // "1445/09/10"
```

> **Note:** Uses the arithmetic (tabular) Hijri calendar — the standard civil calendar
> used across the Islamic world. Astronomical/observational calendars may differ by ±1–2 days.

---

### Contributing

Contributions, bug reports, and pull requests are welcome!

```bash
git clone https://github.com/Mahdyaralipor/parsidate.git
cd parsidate
composer install
./vendor/bin/phpunit tests/
```

---

<a name="فارسی"></a>

## 🇮🇷 فارسی

### چرا ParsiDate؟

اکثر کتابخانه‌های موجود برای تاریخ جلالی در PHP یا قدیمی هستن، یا به توابع deprecated مثل `strftime()` وابسته‌ان، یا به یه فریم‌ورک خاص نیاز دارن. **ParsiDate** از صفر برای PHP مدرن نوشته شده:

- ✅ **بدون وابستگی خارجی** — PHP خالص 8.1+
- ✅ **Immutable** — بدون side-effect
- ✅ **API روان و chainable** — شبیه Carbon
- ✅ **تعطیلات رسمی** — تعطیلات ایران از 1400 تا 1405+ به صورت built-in
- ✅ **Period و Range** — پیمایش، فیلتر و تقسیم بازه زمانی
- ✅ **اعداد فارسی** — پشتیبانی کامل از ارقام فارسی
- ✅ **PHP 8.1 تا 8.5** — تست‌شده

---

### نصب

```bash
composer require mahdyaralipor/parsidate
```

---

### شروع سریع

```php
use ParsiDate\ParsiDate;

// امروز
$today = ParsiDate::now();
echo $today->format('l j M Y');   // Panjshanbeh 15 شهریور 1403

// ساخت و تبدیل
$nowruz = ParsiDate::create(1403, 1, 1);
$greg   = $nowruz->toGregorian(); // ['year'=>2024,'month'=>3,'day'=>20]

// Parse با اعداد فارسی
$date = ParsiDate::parse('۱۴۰۳/۰۶/۱۵');
```

---

### تعطیلات و روزهای کاری

```php
$date = ParsiDate::create(1403, 1, 1);

$date->isHoliday();           // true  — نوروز
$date->holidayName();         // "جشن نوروز"
$date->isWeekend();           // false
$date->isWorkday();           // false

$date->nextWorkday();         // اولین روز کاری بعد
$date->addWorkdays(5);        // ۵ روز کاری بعد (جمعه‌ها و تعطیلات skip)
$date->workdaysUntil($other); // تعداد روزهای کاری بین دو تاریخ
```

---

### بازه زمانی (Period)

```php
use ParsiDate\ParsiPeriod;

$period = ParsiPeriod::ofMonth(1403, 1); // کل فروردین ۱۴۰۳

$period->days();            // ۳۱
$period->countWorkdays();   // روزهای کاری
$period->workdays();        // لیست ParsiDate های کاری
$period->fridays();         // همه جمعه‌ها
$period->holidays();        // همه تعطیلات رسمی

// قابل استفاده در foreach
foreach ($period as $date) {
    echo $date->format('Y/m/d');
}
```

---

### تبدیل به تاریخ هجری قمری

```php
$date = ParsiDate::create(1403, 1, 1);  // نوروز ۱۴۰۳

$hijri = $date->toHijri();

// دسترسی به اجزا
$hijri->year();    // 1445
$hijri->month();   // 9
$hijri->day();     // 10

// نام ماه
$hijri->monthName();       // رمضان
$hijri->monthNameLatin();  // Ramadan

// فرمت‌بندی
$hijri->format('j F Y');          // 10 رمضان 1445
$hijri->format('Y/m/d');          // 1445/09/10
$hijri->formatPersian('j F Y');   // ۱۰ رمضان ۱۴۴۵
$hijri->formatPersian('Y/m/d');   // ۱۴۴۵/۰۹/۱۰

// خروجی آرایه / JSON
$hijri->toArray();  // ['year' => 1445, 'month' => 9, 'day' => 10]
$hijri->toJson();   // {"year":1445,"month":9,"day":10}
(string) $hijri;    // "1445/09/10"
```

> **توجه:** این تبدیل از تقویم هجری جدولی (حسابی) استفاده می‌کند — همان تقویمی که در ایران و
> اکثر کشورهای اسلامی برای مقاصد مدنی به‌کار می‌رود. تقویم هجری نجومی (رؤیت هلال)
> ممکن است ۱ تا ۲ روز متفاوت باشد.

---

### لایسنس

این پروژه تحت **لایسنس MIT** منتشر شده است.

---

<div align="center">
  Made with ❤️ by <a href="https://github.com/Mahdyaralipor">Mahdyaralipor</a>
</div>
