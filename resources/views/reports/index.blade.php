<x-app-layout>
    <x-slot name="title">Reports</x-slot>

    @php
        $icons = [
            'sales' => 'M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 015.814-5.518l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941',
            'finance' => 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'inventory' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
            'purchase' => 'M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z',
            'ledger' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        ];
    @endphp

    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Reports</h2>
            <p class="text-sm text-gray-500">Business, stock and financial reports. Each one downloads as CSV or prints to PDF.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($reports as $key => $report)
                <a href="{{ route('reports.show', $key) }}" class="group">
                    <x-card class="p-5 h-full flex flex-col gap-3 transition-shadow group-hover:shadow-md group-hover:border-jesco-200">
                        <div class="w-10 h-10 rounded-lg bg-jesco-50 text-jesco-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$report['icon']] }}" /></svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800">{{ $report['title'] }}</h3>
                            <p class="text-sm text-gray-500 mt-1">{{ $report['description'] }}</p>
                        </div>
                        <span class="mt-auto pt-2 inline-flex items-center gap-1 text-sm font-medium text-jesco-600">
                            Open report
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        </span>
                    </x-card>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
