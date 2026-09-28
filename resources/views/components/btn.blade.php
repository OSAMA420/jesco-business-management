@props(['variant' => 'primary'])

@php
$variants = [
    'primary' => 'bg-jesco-600 text-white hover:bg-jesco-700 focus:ring-jesco-500',
    'secondary' => 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-jesco-500',
    'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
];
$classes = 'inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors ' . ($variants[$variant] ?? $variants['primary']);
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>
    {{ $slot }}
</button>
