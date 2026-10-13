<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

/**
 * Holiday calendar for the site-wide easter egg (resources/views/partials/holiday-easter-egg.blade.php).
 *
 * Each holiday has a date window, a secret trigger word and a theme. Only the holiday whose window
 * contains today is shipped to the browser. HOLIDAY_EGGS_ALL=true activates every holiday at once
 * (any environment), and locally ?holiday=<key> previews a single holiday.
 */
class Holidays
{
    /**
     * Eid dates are lunar and confirmed by moon sighting, so these are the expected Kenyan public
     * holiday dates. Extend this list before 2031.
     */
    private const EID_AL_FITR = ['2026-03-20', '2027-03-10', '2028-02-27', '2029-02-15', '2030-02-05'];
    private const EID_AL_ADHA = ['2026-05-27', '2027-05-16', '2028-05-05', '2029-04-24', '2030-04-13'];

    /**
     * Themes to ship to the browser for this request (usually zero or one).
     */
    public static function active(?Request $request = null): array
    {
        if (config('holidays.all_eggs')) {
            return static::all();
        }

        // URL previews stay local-only so nobody can share links that change other people's pages.
        if ($request && app()->environment('local') && $request->filled('holiday')) {
            return array_filter([static::find((string) $request->query('holiday'))]);
        }

        return array_filter([static::forDate(now())]);
    }

    /**
     * Every holiday, one per secret word (holidays sharing a word keep the later one).
     */
    public static function all(): array
    {
        return collect(static::definitions())
            ->map(fn ($holiday, $key) => static::present($key, $holiday))
            ->keyBy('word')
            ->values()
            ->all();
    }

    public static function forDate(CarbonInterface $date): ?array
    {
        $day = $date->copy()->startOfDay();

        // Windows are generated for the surrounding years so ones that cross 31 Dec still match.
        foreach (static::definitions() as $key => $holiday) {
            foreach ([$day->year - 1, $day->year, $day->year + 1] as $year) {
                foreach ($holiday['windows']($year) as [$start, $end]) {
                    if ($day->betweenIncluded($start, $end)) {
                        return static::present($key, $holiday);
                    }
                }
            }
        }

        return null;
    }

    public static function find(string $key): ?array
    {
        $definitions = static::definitions();

        return isset($definitions[$key]) ? static::present($key, $definitions[$key]) : null;
    }

    /**
     * Earlier entries win when windows overlap (e.g. Eid landing on Valentine's Day).
     */
    private static function definitions(): array
    {
        $kenya = [
            'word' => 'kenya',
            'flyers' => ['🇰🇪', '🦁', '🛡️'],
            'riser' => '🇰🇪',
            'gradient' => ['#0B0B0B', '#BB0000', '#006600'],
            'glow' => 'rgba(0, 102, 0, .45)',
            'accent' => '#FFFFFF',
            'suffix' => '🇰🇪',
        ];

        return [
            'eid-al-fitr' => [
                'name' => 'Eid al-Fitr',
                'windows' => fn (int $year) => static::around(self::EID_AL_FITR, $year),
                'word' => 'eid',
                'flyers' => ['🌙', '⭐', '🏮'],
                'riser' => '🌙',
                'gradient' => ['#0B2545', '#13315C', '#C9A227'],
                'glow' => 'rgba(201, 162, 39, .45)',
                'accent' => '#F4D35E',
                'suffix' => '🌙',
                'message' => 'Eid Mubarak from the Dev team!',
            ],
            'eid-al-adha' => [
                'name' => 'Eid al-Adha',
                'windows' => fn (int $year) => static::around(self::EID_AL_ADHA, $year),
                'word' => 'eid',
                'flyers' => ['🌙', '⭐', '🏮'],
                'riser' => '🌙',
                'gradient' => ['#0B2545', '#13315C', '#C9A227'],
                'glow' => 'rgba(201, 162, 39, .45)',
                'accent' => '#F4D35E',
                'suffix' => '🌙',
                'message' => 'Eid Mubarak from the Dev team!',
            ],
            'easter' => [
                'name' => 'Easter',
                'windows' => function (int $year) {
                    $easter = static::easterSunday($year);

                    return [[$easter->copy()->subDays(2), $easter->copy()->addDay()]];
                },
                'word' => 'bunny',
                'flyers' => ['🐣', '🥚', '🌷'],
                'riser' => '🐰',
                'gradient' => ['#6A4C93', '#B185DB', '#F7C8E0'],
                'glow' => 'rgba(177, 133, 219, .45)',
                'accent' => '#FFF3B0',
                'suffix' => '🐰',
                'message' => 'Happy Easter from the Dev team!',
            ],
            'valentines' => [
                'name' => "Valentine's Day",
                'windows' => fn (int $year) => [static::span($year, 2, 13, 2, 15)],
                'word' => 'love',
                'flyers' => ['💖', '💘', '💕'],
                'riser' => '💝',
                'gradient' => ['#590D22', '#A4133C', '#FF4D6D'],
                'glow' => 'rgba(255, 77, 109, .45)',
                'accent' => '#FFCCD5',
                'suffix' => '💖',
                'message' => "Happy Valentine's from the Dev team!",
            ],
            'labour-day' => [
                'name' => 'Labour Day',
                'windows' => fn (int $year) => [static::span($year, 4, 30, 5, 2)],
                'word' => 'labour',
                'flyers' => ['🛠️', '💪', '⚙️'],
                'riser' => '🏆',
                'gradient' => ['#1B263B', '#415A77', '#E09F3E'],
                'glow' => 'rgba(224, 159, 62, .45)',
                'accent' => '#FFD166',
                'suffix' => '💪',
                'message' => 'Happy Labour Day! Thank you for all the hard work.',
            ],
            'madaraka-day' => $kenya + [
                'name' => 'Madaraka Day',
                'windows' => fn (int $year) => [static::span($year, 5, 31, 6, 2)],
                'message' => 'Happy Madaraka Day from the Dev team!',
            ],
            'mashujaa-day' => $kenya + [
                'name' => 'Mashujaa Day',
                'windows' => fn (int $year) => [static::span($year, 10, 19, 10, 21)],
                'message' => 'Happy Mashujaa Day! Celebrating our heroes.',
            ],
            'halloween' => [
                'name' => 'Halloween',
                'windows' => fn (int $year) => [static::span($year, 10, 24, 11, 2)],
                'word' => 'boo',
                'flyers' => ['🦇'],
                'riser' => '👻',
                'gradient' => ['#1A0B2E', '#4B1D6B', '#E8651A'],
                'glow' => 'rgba(232, 101, 26, .45)',
                'accent' => '#FFB347',
                'suffix' => '🎃',
                'twist' => ['Book', 'Boo-k'],
                'message' => 'Happy Halloween from the Dev team!',
            ],
            'jamhuri-day' => $kenya + [
                'name' => 'Jamhuri Day',
                'windows' => fn (int $year) => [static::span($year, 12, 11, 12, 13)],
                'message' => 'Happy Jamhuri Day from the Dev team!',
            ],
            'christmas' => [
                'name' => 'Christmas',
                'windows' => fn (int $year) => [static::span($year, 12, 20, 12, 26)],
                'word' => 'santa',
                'flyers' => ['❄️', '🎁', '⭐'],
                'riser' => '🎅',
                'gradient' => ['#0B3D2E', '#14532D', '#B91C1C'],
                'glow' => 'rgba(185, 28, 28, .45)',
                'accent' => '#FDE68A',
                'suffix' => '🎄',
                'message' => 'Merry Christmas from the Dev team!',
            ],
            'new-year' => [
                'name' => 'New Year',
                'windows' => fn (int $year) => [[Carbon::create($year, 12, 31), Carbon::create($year + 1, 1, 2)]],
                'word' => 'cheers',
                'flyers' => ['🎆', '🎉', '✨'],
                'riser' => '🥂',
                'gradient' => ['#0D1B2A', '#1B263B', '#C9A227'],
                'glow' => 'rgba(201, 162, 39, .45)',
                'accent' => '#F4D35E',
                'suffix' => '🎉',
                'message' => 'Happy New Year from the Dev team!',
            ],
        ];
    }

    /** The theme sent to the browser (no closures). */
    private static function present(string $key, array $holiday): array
    {
        unset($holiday['windows']);

        return ['key' => $key, 'twist' => null] + $holiday;
    }

    private static function span(int $year, int $fromMonth, int $fromDay, int $toMonth, int $toDay): array
    {
        return [Carbon::create($year, $fromMonth, $fromDay), Carbon::create($year, $toMonth, $toDay)];
    }

    /** One-day-either-side windows for the listed dates that fall in $year. */
    private static function around(array $dates, int $year): array
    {
        return collect($dates)
            ->map(fn ($date) => Carbon::parse($date))
            ->filter(fn ($date) => $date->year === $year)
            ->map(fn ($date) => [$date->copy()->subDay(), $date->copy()->addDay()])
            ->values()
            ->all();
    }

    /** Gregorian Easter Sunday (Anonymous Gregorian algorithm); avoids needing ext-calendar. */
    private static function easterSunday(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day);
    }
}
