@props(['label', 'value' => null, 'counter' => null, 'prefix' => '', 'suffix' => '', 'change' => null, 'positive' => true, 'color' => 'jesco'])

@php
$colors = [
    'jesco' => 'bg-jesco-50 text-jesco-600',
    'emerald' => 'bg-emerald-50 text-emerald-600',
    'amber' => 'bg-amber-50 text-amber-600',
    'rose' => 'bg-rose-50 text-rose-600',
];
@endphp

<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 transition-shadow hover:shadow-md">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-sm text-gray-500">{{ $label }}</p>
            @if($counter !== null)
                <p class="mt-1 text-2xl font-semibold text-gray-900"
                   x-data="counter({{ $counter }}, '{{ $prefix }}', '{{ $suffix }}')"
                   x-text="display">{{ $prefix }}0{{ $suffix }}</p>
            @else
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $value }}</p>
            @endif
        </div>
        @isset($icon)
            <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $colors[$color] ?? $colors['jesco'] }}">
                {{ $icon }}
            </div>
        @endisset
    </div>
    @if($change)
        <p class="mt-3 text-xs font-medium {{ $positive ? 'text-emerald-600' : 'text-rose-600' }}">
            {{ $change }}
        </p>
    @endif
</div>
