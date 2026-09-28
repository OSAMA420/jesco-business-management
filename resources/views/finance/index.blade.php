<x-app-layout>
    <x-slot name="title">Finance &amp; Accounts</x-slot>

    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Finance &amp; Accounts</h2>
            <p class="text-sm text-gray-500">Sales, purchases, income, expenses, payments &amp; ledgers.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card label="Total Sales" value="Rs. {{ number_format($summary['total_sales']) }}" color="jesco">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 015.814-5.518l2.74-1.22" /></svg>
                </x-slot>
            </x-stat-card>
            <x-stat-card label="Total Purchases" value="Rs. {{ number_format($summary['total_purchases']) }}" color="amber">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                </x-slot>
            </x-stat-card>
            <x-stat-card label="Total Expenses" value="Rs. {{ number_format($summary['total_expenses']) }}" :positive="false" color="rose">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l1.858 1.858a2.25 2.25 0 003.182 0l5.159-5.159M18 4.5h2.25V6.75" /></svg>
                </x-slot>
            </x-stat-card>
            <x-stat-card label="Outstanding Balance" value="Rs. {{ number_format($summary['outstanding']) }}" :positive="false" color="rose">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </x-slot>
            </x-stat-card>
        </div>

        <x-card>
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Recent Transactions</h2>
                <div class="flex gap-2">
                    <x-btn variant="secondary">Add Payment</x-btn>
                    <x-btn variant="secondary">Add Expense</x-btn>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                            <th class="px-5 py-3 font-medium">Date</th>
                            <th class="px-5 py-3 font-medium">Type</th>
                            <th class="px-5 py-3 font-medium">Reference</th>
                            <th class="px-5 py-3 font-medium">Party</th>
                            <th class="px-5 py-3 font-medium">Amount</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($transactions as $t)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-gray-500">{{ $t['date'] }}</td>
                            <td class="px-5 py-3">
                                @php
                                    $typeColor = match($t['type']) {
                                        'sale' => 'green',
                                        'purchase' => 'blue',
                                        'expense' => 'red',
                                        default => 'gray',
                                    };
                                @endphp
                                <x-badge :color="$typeColor">{{ ucfirst($t['type']) }}</x-badge>
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $t['ref'] }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $t['party'] }}</td>
                            <td class="px-5 py-3 font-medium {{ $t['amount'] < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ $t['amount'] < 0 ? '-' : '+' }}Rs. {{ number_format(abs($t['amount'])) }}
                            </td>
                            <td class="px-5 py-3">
                                @php
                                    $statusColor = match($t['status']) {
                                        'paid' => 'green',
                                        'partial' => 'yellow',
                                        'unpaid' => 'red',
                                        default => 'gray',
                                    };
                                @endphp
                                <x-badge :color="$statusColor">{{ ucfirst($t['status']) }}</x-badge>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-app-layout>
