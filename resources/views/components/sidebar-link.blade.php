@props(['active' => false])

@php
$classes = $active
    ? 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium bg-jesco-600 text-white'
    : 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition-colors';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $icon }}
    <span>{{ $slot }}</span>
</a>
