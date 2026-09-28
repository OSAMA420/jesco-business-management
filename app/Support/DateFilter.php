<?php

namespace App\Support;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Reads the shared date filter (?period=...&from=...&to=...) used on list
 * pages and applies it to a query.
 */
class DateFilter
{
    public const PERIODS = [
        'today' => 'Today',
        'this_week' => 'This Week',
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'this_year' => 'This Year',
        'custom' => 'Custom Range',
    ];

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}|null
     */
    public static function range(Request $request): ?array
    {
        $now = now();

        return match ($request->string('period')->toString()) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'custom' => self::customRange($request),
            default => null,
        };
    }

    public static function apply(Builder $query, Request $request, string $column): Builder
    {
        [$from, $to] = self::range($request) ?? [null, null];

        return $query
            ->when($from, fn ($q) => $q->whereDate($column, '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->whereDate($column, '<=', $to->toDateString()));
    }

    private static function customRange(Request $request): ?array
    {
        $parse = fn (string $key) => rescue(fn () => $request->filled($key) ? Carbon::parse($request->string($key)) : null, null, false);
        $from = $parse('from');
        $to = $parse('to');

        return $from || $to ? [$from, $to] : null;
    }
}
