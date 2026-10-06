# dev.to Article

## Title
Jalali Dates in Modern PHP: Building ParsiDate — Immutable, Zero-Dependency, with Holidays Built In

## Tags
#php #opensource #datetime #laravel

## Cover image suggestion
Use `art/social-preview.png` from the repo (1280×640, ready-made).

---

## Article Body

If you build software for 85+ million Persian speakers, you need Jalali (Solar Hijri) dates. And if you do it in PHP, you've probably met the usual suspects: libraries built a decade ago, `strftime()` calls that PHP 8.1 deprecated, mutable date objects that cause spooky action-at-a-distance in queue workers, and holiday lists you have to maintain by hand.

I built **ParsiDate** to fix that for my own projects. It's now at v1.1.0, MIT licensed, with 131 passing tests:

```bash
composer require mahdyaralipor/parsidate
```

## Design decisions

**1. Immutable by default.** Every arithmetic method returns a new instance:

```php
$date = ParsiDate::create(1403, 1, 1);
$later = $date->addDays(10);   // $date is untouched
```

If you've ever debugged a Carbon object mutated three call frames away, you know why this matters.

**2. Zero dependencies.** `composer.json` requires exactly one thing: `php: ^8.1`. No Carbon, no framework, no polyfills. It works in Laravel, Symfony, WordPress, or a bare script.

**3. Holidays are data, not your problem.** Official Iranian public holidays (1400–1405+) ship inside the package, with an API for variable holidays:

```php
$date->isHoliday();        // true on Nowruz
$date->holidayName();      // "جشن نوروز"
$date->addWorkdays(5);     // skips Fridays + public holidays
$date->workdaysUntil($other);
```

**4. Periods are iterable.** Date ranges are first-class:

```php
$period = ParsiPeriod::ofMonth(1403, 1);

$period->countWorkdays();
$period->overlap($other);       // ParsiPeriod|null
$period->splitByDays(7);        // weekly chunks

foreach ($period->fridays() as $friday) {
    echo $friday->format('Y/m/d');
}
```

**5. Persian digits everywhere.** Parsing and formatting both understand `۰۱۲۳۴۵۶۷۸۹`, because real user input uses them:

```php
ParsiDate::parse('۱۴۰۳/۰۶/۱۵');
$date->formatPersian('Y/m/d');  // ۱۴۰۳/۰۶/۱۵
```

**6. Hijri conversion included.** `$date->toHijri()` returns a value object with Arabic + Latin month names and array/JSON output. One honest caveat, documented in the README: it uses the tabular (arithmetic) calendar, so it can differ ±1–2 days from moon-sighting calendars.

## What's next

- Recurring events / RRULE-style helpers
- More calendar systems on request
- Framework bridges (a Laravel service provider is the most requested)

Repo: https://github.com/mahdyaralipor/parsidate

If you work with Jalali dates in production, I'd love to hear what your current setup is and where it hurts — that's what shapes the roadmap. And if it's useful, a ⭐ helps others find it.
