@props(['title' => 'Delete this item?', 'body' => 'This action cannot be undone.'])

<div
    x-data="{ open: false, url: '', name: '' }"
    x-on:confirm-delete.window="open = true; url = $event.detail.url; name = $event.detail.name ?? ''"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50"
>
    <div
        x-show="open"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-on:click="open = false"
        class="fixed inset-0 bg-gray-900/50"
    ></div>

    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-sm bg-white rounded-xl shadow-xl p-6"
        >
            <div class="flex items-start gap-4">
                <div class="w-11 h-11 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                </div>
                <div class="pt-1.5">
                    <h3 class="text-base font-semibold text-gray-900" x-text="name ? ('Delete ' + name + '?') : @js($title)"></h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $body }}</p>
                </div>
            </div>

            <form method="POST" :action="url" class="mt-6 flex justify-end gap-2">
                @csrf
                @method('DELETE')
                <x-btn type="button" variant="secondary" @click="open = false">Cancel</x-btn>
                <x-btn type="submit" variant="danger">Delete</x-btn>
            </form>
        </div>
    </div>
</div>
