@props(['rows', 'label' => 'Value'])

{{-- One series of monthly bars (categorical slot 1), with a hover tooltip and a table view. --}}
@php
    $peak = max(1000, $rows->max('value'));
    $raw = $peak / 4;
    $magnitude = 10 ** floor(log10($raw));
    $step = collect([1, 2, 2.5, 5, 10])->map(fn ($f) => $f * $magnitude)->first(fn ($s) => $s >= $raw);
    $scaleMax = $step * 4;
    $ticks = collect(range(0, 4))->map(fn ($i) => $step * $i)->reverse()->values();
    $short = fn ($v) => $v >= 1000 ? rtrim(rtrim(number_format($v / 1000, 1), '0'), '.').'k' : (string) $v;
@endphp

<div x-data="{ hover: null, asTable: false }">
    <div class="flex justify-end print:hidden">
        <button type="button" @click="asTable = !asTable" class="text-xs font-medium text-jesco-600 hover:underline"
                x-text="asTable ? 'Show chart' : 'Show as table'"></button>
    </div>

    <div x-show="!asTable" class="mt-3 flex gap-3" role="img" aria-label="{{ $label }} by month">
        <div class="flex flex-col justify-between h-48 text-[11px] text-gray-400 text-right w-10 shrink-0 -mt-1.5">
            @foreach($ticks as $tick)
                <span>{{ $short($tick) }}</span>
            @endforeach
        </div>
        <div class="relative flex-1">
            <div class="absolute inset-x-0 top-0 h-48 flex flex-col justify-between pointer-events-none">
                @foreach($ticks as $tick)
                    <div class="border-t {{ $loop->last ? 'border-gray-300' : 'border-gray-100' }}"></div>
                @endforeach
            </div>
            <div class="relative h-48 flex items-end">
                @foreach($rows as $i => $row)
                    <div class="relative flex-1 h-full flex items-end justify-center" @mouseenter="hover = {{ $i }}" @mouseleave="hover = null">
                        <div class="absolute inset-y-0 inset-x-1 rounded-md transition-colors" :class="hover === {{ $i }} ? 'bg-gray-100/70' : ''"></div>
                        <div class="relative w-6 sm:w-8 rounded-t-[4px]" style="height: {{ $row['value'] / $scaleMax * 100 }}%; background: #2a78d6"></div>
                        <div x-show="hover === {{ $i }}" x-cloak x-transition.opacity
                             class="absolute bottom-full mb-2 z-10 whitespace-nowrap rounded-lg bg-white border border-gray-200 shadow-lg px-3 py-2 text-xs {{ $loop->last ? 'right-0' : ($loop->first ? 'left-0' : 'left-1/2 -translate-x-1/2') }}">
                            <p class="font-semibold text-gray-900">{{ $row['label'] }}</p>
                            <p class="text-gray-600">{{ $label }}: <span class="font-medium text-gray-900">Rs. {{ number_format($row['value']) }}</span></p>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex mt-2 text-[11px] text-gray-500">
                @foreach($rows as $row)
                    <span class="flex-1 text-center">{{ $row['label'] }}</span>
                @endforeach
            </div>
        </div>
    </div>

    <div x-show="asTable" x-cloak class="mt-3 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                    <th class="px-4 py-2 font-medium">Month</th>
                    <th class="px-4 py-2 font-medium text-right">{{ $label }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($rows as $row)
                    <tr>
                        <td class="px-4 py-2 text-gray-800">{{ $row['label'] }}</td>
                        <td class="px-4 py-2 text-right text-gray-800">Rs. {{ number_format($row['value']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
