@props(['months'])

@php
    // Four gridline steps, each a "nice" number (1, 2, 2.5 or 5 x 10^n), so every tick is round.
    $peak = max(1000, $months->max(fn ($m) => max($m['in'], $m['out'])));
    $raw = $peak / 4;
    $magnitude = 10 ** floor(log10($raw));
    $step = collect([1, 2, 2.5, 5, 10])->map(fn ($f) => $f * $magnitude)->first(fn ($s) => $s >= $raw);
    $scaleMax = $step * 4;
    $ticks = collect(range(0, 4))->map(fn ($i) => $step * $i)->reverse()->values();
    $short = fn ($v) => $v >= 1000 ? rtrim(rtrim(number_format($v / 1000, 1), '0'), '.').'k' : (string) $v;
@endphp

<div class="viz-root" x-data="{ hover: null, asTable: false }">
    <style>
        .viz-root {
            --series-1: #2a78d6; /* Cash In: categorical slot 1 */
            --series-2: #eb6834; /* Cash Out: categorical slot 2 */
        }
    </style>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-4 text-xs text-gray-600" aria-label="Legend">
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background: var(--series-1)"></span>Cash In</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background: var(--series-2)"></span>Cash Out</span>
        </div>
        <button type="button" @click="asTable = !asTable" class="text-xs font-medium text-jesco-600 hover:underline"
                x-text="asTable ? 'Show chart' : 'Show as table'"></button>
    </div>

    <!-- Chart -->
    <div x-show="!asTable" class="mt-4 flex gap-3" role="img"
         aria-label="Cash in and cash out for the last {{ $months->count() }} months">
        <!-- Y axis -->
        <div class="flex flex-col justify-between h-56 text-[11px] text-gray-400 text-right w-10 shrink-0 -mt-1.5">
            @foreach($ticks as $tick)
                <span>{{ $short($tick) }}</span>
            @endforeach
        </div>

        <div class="relative flex-1">
            <!-- Gridlines -->
            <div class="absolute inset-x-0 top-0 h-56 flex flex-col justify-between pointer-events-none">
                @foreach($ticks as $i => $tick)
                    <div class="border-t {{ $loop->last ? 'border-gray-300' : 'border-gray-100' }}"></div>
                @endforeach
            </div>

            <!-- Bars -->
            <div class="relative h-56 flex items-end">
                @foreach($months as $i => $m)
                    <div class="relative flex-1 h-full flex items-end justify-center gap-[2px] cursor-default"
                         @mouseenter="hover = {{ $i }}" @mouseleave="hover = null">
                        <div class="absolute inset-y-0 inset-x-1 rounded-md transition-colors" :class="hover === {{ $i }} ? 'bg-gray-100/70' : ''"></div>
                        <div class="relative w-3 sm:w-4 rounded-t-[4px]" style="height: {{ $m['in'] / $scaleMax * 100 }}%; background: var(--series-1)"></div>
                        <div class="relative w-3 sm:w-4 rounded-t-[4px]" style="height: {{ $m['out'] / $scaleMax * 100 }}%; background: var(--series-2)"></div>

                        <!-- Tooltip -->
                        <div x-show="hover === {{ $i }}" x-cloak x-transition.opacity
                             class="absolute bottom-full mb-2 z-10 w-44 rounded-lg bg-white border border-gray-200 shadow-lg p-3 text-xs {{ $loop->last ? 'right-0' : ($loop->first ? 'left-0' : 'left-1/2 -translate-x-1/2') }}">
                            <p class="font-semibold text-gray-900">{{ $m['month'] }}</p>
                            <div class="mt-1.5 space-y-1">
                                <p class="flex justify-between gap-2"><span class="inline-flex items-center gap-1.5 text-gray-600"><span class="w-2 h-2 rounded-sm" style="background: var(--series-1)"></span>Cash In</span><span class="font-medium text-gray-900">Rs. {{ number_format($m['in']) }}</span></p>
                                <p class="flex justify-between gap-2"><span class="inline-flex items-center gap-1.5 text-gray-600"><span class="w-2 h-2 rounded-sm" style="background: var(--series-2)"></span>Cash Out</span><span class="font-medium text-gray-900">Rs. {{ number_format($m['out']) }}</span></p>
                                <p class="flex justify-between gap-2 pt-1 border-t border-gray-100"><span class="text-gray-600">Net</span><span class="font-medium text-gray-900">{{ $m['in'] - $m['out'] < 0 ? '-' : '' }}Rs. {{ number_format(abs($m['in'] - $m['out'])) }}</span></p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- X axis -->
            <div class="flex mt-2 text-[11px] text-gray-500">
                @foreach($months as $m)
                    <span class="flex-1 text-center">{{ $m['month'] }}</span>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Table view -->
    <div x-show="asTable" x-cloak class="mt-4 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                    <th class="px-4 py-2 font-medium">Month</th>
                    <th class="px-4 py-2 font-medium text-right">Cash In</th>
                    <th class="px-4 py-2 font-medium text-right">Cash Out</th>
                    <th class="px-4 py-2 font-medium text-right">Net</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($months as $m)
                    <tr>
                        <td class="px-4 py-2 text-gray-800">{{ $m['month'] }}</td>
                        <td class="px-4 py-2 text-right text-gray-800">Rs. {{ number_format($m['in']) }}</td>
                        <td class="px-4 py-2 text-right text-gray-800">Rs. {{ number_format($m['out']) }}</td>
                        <td class="px-4 py-2 text-right font-medium text-gray-900">{{ $m['in'] - $m['out'] < 0 ? '-' : '' }}Rs. {{ number_format(abs($m['in'] - $m['out'])) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
