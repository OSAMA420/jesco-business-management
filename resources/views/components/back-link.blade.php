@props(['href'])

<a href="{{ $href }}" class="group inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-jesco-600 transition-colors">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 transition-transform duration-200 ease-out group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
    </svg>
    <span class="group-hover:underline underline-offset-2">{{ $slot }}</span>
</a>
