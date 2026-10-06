# r/PHP Post

## Title (pick one)

Option A: "ParsiDate — a modern, immutable, zero-dependency Jalali (Solar Hijri) date library for PHP"

Option B: "I built a Jalali date library for PHP with built-in Iranian holidays, workdays and periods"

## Body

Most Jalali date libraries for PHP are showing their age — tied to deprecated `strftime()`, framework-specific, or mutable-by-default APIs that bite you in long-running workers. So I built **ParsiDate**: modern PHP 8.1+, zero dependencies, immutable by default.

```bash
composer require mahdyaralipor/parsidate
```

```php
use ParsiDate\ParsiDate;

$today = ParsiDate::now();
echo $today->format('l j M Y');     // شنبه 15 شهریور 1403

ParsiDate::parse('۱۴۰۳/۰۶/۱۵');     // Persian digits work too

$today->isHoliday();                // true/false (official Iranian holidays built in)
$today->holidayName();               // "جشن نوروز"
$today->addWorkdays(5);             // skips weekends + public holidays
```

What it covers:

- Jalali ↔ Gregorian conversion, parsing (incl. Persian digits), formatting with Persian/English month & weekday names
- Official Iranian public holidays (1400–1405+) with `isHoliday / holidayName / isWeekend / isWorkday / nextWorkday / addWorkdays / workdaysUntil`
- `ParsiPeriod` — date ranges you can iterate (`foreach`), filter (`workdays()`, `holidays()`, `fridays()`, custom closures), split (`splitByDays()`, `splitByMonth()`), overlap-check
- Hijri (tabular Islamic) conversion: `$date->toHijri()`
- Fluent immutable arithmetic: `addDays / subMonths / startOfMonth / endOfYear ...`
- 131 PHPUnit tests passing, MIT licensed

```php
$period = ParsiPeriod::ofMonth(1403, 1);
$period->countWorkdays();
foreach ($period->fridays() as $friday) { ... }
```

It's v1.1.0 — I'd genuinely appreciate feedback, especially from anyone doing Jalali dates in production: what did I get wrong, what's missing?

👉 https://github.com/mahdyaralipor/parsidate

---

## Posting notes
1. **Timing:** Tue–Thu, 9–11am EST (≈ 17:30–19:30 Iran time)
2. **Flair:** none needed on r/PHP, but keep the tone "show + ask for feedback", not an ad
3. **First comment (yours):** post a follow-up comment right away with one technical detail (e.g. why immutable + how the tabular Hijri conversion can differ ±1–2 days from observational calendars) — drives discussion
4. **Reply fast:** answer every comment in the first 24h, including critical ones
5. Expect the "why not Verta / morilog/jalali?" question — honest answer: Verta is Carbon-based and mutable; morilog relies on old APIs. ParsiDate is zero-dep + immutable + holidays/periods built in
