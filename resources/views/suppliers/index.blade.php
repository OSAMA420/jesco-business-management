<x-app-layout>
    <x-slot name="title">Suppliers</x-slot>

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Suppliers</h2>
                <p class="text-sm text-gray-500">Who you buy stock from, and how much you owe them.</p>
            </div>
            <a href="{{ route('suppliers.create') }}">
                <x-btn type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Add Supplier
                </x-btn>
            </a>
        </div>

        @if(session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm px-4 py-3">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-stat-card label="Total Suppliers" :counter="$summary['count']" color="jesco">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>
                </x-slot>
            </x-stat-card>
            <x-stat-card label="Total Purchased" :counter="(int) $summary['purchased']" prefix="Rs. " color="emerald">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                </x-slot>
            </x-stat-card>
            <x-stat-card label="Payable to Suppliers" :counter="(int) $summary['payable']" prefix="Rs. " color="rose">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </x-slot>
            </x-stat-card>
        </div>

        <x-card>
            <form method="GET" action="{{ route('suppliers.index') }}" class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search suppliers..." class="w-full sm:w-64 rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500" />
                <x-filter-select name="balance" placeholder="Any balance" :options="['due' => 'Payable', 'clear' => 'Clear']" />
                <x-btn type="submit" variant="secondary">Search</x-btn>
                @if(request()->anyFilled(['search', 'balance']))
                    <a href="{{ route('suppliers.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear filters</a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                            <th class="px-5 py-3 font-medium">Supplier</th>
                            <th class="px-5 py-3 font-medium">Phone</th>
                            <th class="px-5 py-3 font-medium">Purchases</th>
                            <th class="px-5 py-3 font-medium">Total Purchased</th>
                            <th class="px-5 py-3 font-medium">Payable</th>
                            <th class="px-5 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($suppliers as $supplier)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3 min-w-[16rem]">
                                    <span class="w-8 h-8 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center text-xs font-semibold shrink-0">
                                        {{ strtoupper(substr($supplier->name, 0, 1)) }}
                                    </span>
                                    <div>
                                        <p class="font-medium text-gray-800">{{ $supplier->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $supplier->address ?? 'No address on file' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $supplier->phone ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-800">{{ $supplier->purchases_count }}</td>
                            <td class="px-5 py-3 text-gray-800 whitespace-nowrap">Rs. {{ number_format($supplier->total_purchased) }}</td>
                            <td class="px-5 py-3">
                                @if($supplier->balance_amount > 0)
                                    <x-badge color="red">Rs. {{ number_format($supplier->balance_amount) }}</x-badge>
                                @else
                                    <x-badge color="green">Clear</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-3">
                                    <a href="{{ route('suppliers.show', $supplier->id) }}" class="text-jesco-600 hover:underline text-sm font-medium">View</a>
                                    <a href="{{ route('suppliers.edit', $supplier->id) }}" class="text-jesco-600 hover:underline text-sm font-medium">Edit</a>
                                    <button type="button"
                                            @click="$dispatch('confirm-delete', { url: '{{ route('suppliers.destroy', $supplier->id) }}', name: '{{ addslashes($supplier->name) }}' })"
                                            class="text-rose-600 hover:underline text-sm font-medium">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-gray-500">No suppliers found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($suppliers->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">
                    {{ $suppliers->links() }}
                </div>
            @endif
        </x-card>

        <x-confirm-delete-modal />
    </div>
</x-app-layout>
