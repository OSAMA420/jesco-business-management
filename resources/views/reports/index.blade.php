<x-app-layout>
    <x-slot name="title">Reports</x-slot>

    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Reports</h2>
            <p class="text-sm text-gray-500">Business, inventory &amp; financial reports with PDF export.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($reports as $report)
            <x-card class="p-5 flex flex-col gap-3">
                <div class="w-10 h-10 rounded-lg bg-jesco-50 text-jesco-600 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664" /></svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">{{ $report['title'] }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $report['description'] }}</p>
                </div>
                <div class="mt-auto pt-2 flex gap-2">
                    <x-btn variant="secondary">View</x-btn>
                    <x-btn variant="secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 12m0 0l4.5-4.5M12 12V3" /></svg>
                        Export PDF
                    </x-btn>
                </div>
            </x-card>
            @endforeach
        </div>
    </div>
</x-app-layout>
