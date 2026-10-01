<?php

declare(strict_types=1);

namespace App\Services\Reports;

/**
 * Single source of truth for the tracked RM portfolio (rm_code => name/segment), shared by
 * all four RM movers reports (daily/weekly, deposits/loans). Backed by
 * config('reports.balances.rm_portfolio'); the constant here is only a fallback in case
 * that config key is ever emptied by mistake.
 */
class RmPortfolioService
{
    /** Job Unit segments, in display order. Any code with no known segment groups as 'Unassigned'. */
    public const SEGMENTS = ['Premier', 'Advantage', 'Direct'];

    private const DEFAULT_PORTFOLIO = [
        'KE0827' => ['name' => 'Veronica Nasieku Lalarari',      'segment' => 'Premier'],
        'KE1228' => ['name' => 'James Chisakane Odera',          'segment' => 'Premier'],
        'KE0539' => ['name' => 'Lucy Kamede Lidahuli',           'segment' => 'Premier'],
        'KE1189' => ['name' => 'Edward Mwenda',                  'segment' => 'Premier'],
        'KE1330' => ['name' => 'Jenipher Dola',                  'segment' => 'Premier'],
        'KE1301' => ['name' => 'Susan Odhiambo',                 'segment' => 'Premier'],
        'KE1285' => ['name' => "Jackson Nyakang'o",              'segment' => 'Advantage'],
        'KE1343' => ['name' => 'Joan Sang',                      'segment' => 'Advantage'],
        'KE0887' => ['name' => 'John Njogu Waithaka',            'segment' => 'Direct'],
        'KE1187' => ['name' => 'Betty Chelagat Keter',           'segment' => 'Direct'],
        'KE1318' => ['name' => 'Edwin Araka',                    'segment' => 'Direct'],
        'KE0445' => ['name' => 'Jennifer Waithera Macharia',     'segment' => 'Direct'],
        'KE0949' => ['name' => 'Monica Nyambura Gikonyo',        'segment' => 'Direct'],
        'KE1262' => ['name' => 'Glory Kendi',                    'segment' => 'Direct'],
        'KE0343' => ['name' => 'Nancy Akoth Oywer',              'segment' => 'Direct'],
        'KE1286' => ['name' => 'Viginia Wangui Waweru',          'segment' => 'Direct'],
        'KE1229' => ['name' => 'Erick Ochieng Ouma',             'segment' => 'Direct'],
    ];

    /** @return array<string, array{name: string, segment: string}> */
    public static function all(): array
    {
        $configured = config('reports.balances.rm_portfolio', []);
        return !empty($configured) ? $configured : self::DEFAULT_PORTFOLIO;
    }

    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function name(string $rmCode): string
    {
        return self::all()[$rmCode]['name'] ?? $rmCode;
    }

    public static function segment(string $rmCode): string
    {
        return self::all()[$rmCode]['segment'] ?? 'Unassigned';
    }

    /** rm_code => name, for everywhere that only needs a display-name lookup. */
    public static function names(): array
    {
        return collect(self::all())->map(fn ($rm) => is_array($rm) ? ($rm['name'] ?? '') : (string) $rm)->all();
    }

    /**
     * Group the given RM codes by segment, in SEGMENTS order (then any unknown segment last).
     * Only segments actually present in $rmCodes are returned.
     *
     * @return array<string, array<int, string>> segment => [rm_code, ...]
     */
    public static function groupBySegment(array $rmCodes): array
    {
        $groups = [];

        foreach ($rmCodes as $code) {
            $groups[self::segment($code)][] = $code;
        }

        $order = array_flip(self::SEGMENTS);
        uksort($groups, fn ($a, $b) => ($order[$a] ?? 99) <=> ($order[$b] ?? 99) ?: strcmp($a, $b));

        return $groups;
    }
}
