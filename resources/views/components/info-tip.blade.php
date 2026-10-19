@props(['text', 'align' => 'left'])

{{-- A small (i) that explains a figure on hover or keyboard focus. --}}
<span class="relative inline-flex group align-middle print:hidden" tabindex="0" aria-label="{{ $text }}">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 group-hover:text-jesco-600 group-focus:text-jesco-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
    <span role="tooltip"
          class="pointer-events-none absolute top-full mt-1.5 z-20 w-64 rounded-lg bg-gray-900 px-3 py-2 text-xs font-normal leading-relaxed text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 group-focus:opacity-100 {{ $align === 'right' ? 'right-0' : 'left-0' }}">
        {{ $text }}
    </span>
</span>
