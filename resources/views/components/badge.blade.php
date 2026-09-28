@props(['color' => 'gray'])

@php
$colors = [
    'gray' => 'bg-gray-100 text-gray-700',
    'green' => 'bg-emerald-100 text-emerald-700',
    'yellow' => 'bg-amber-100 text-amber-700',
    'red' => 'bg-rose-100 text-rose-700',
    'blue' => 'bg-sky-100 text-sky-700',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . ($colors[$color] ?? $colors['gray'])]) }}>
    {{ $slot }}
</span>
