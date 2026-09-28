<x-app-layout>
    <x-slot name="title">{{ $supplier->name }}</x-slot>

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <x-back-link :href="route('suppliers.index')">Back to Suppliers</x-back-link>
                <div class="flex items-center gap-3 mt-1">
                    <span class="w-11 h-11 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-semibold">
                        {{ strtoupper(substr($supplier->name, 0, 1)) }}
                    </span>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">{{ $supplier->name }}</h2>
                        <p class="text-sm text-gray-500">{{ $supplier->address ?? 'No address on file' }}</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('purchases.create', ['supplier' => $supplier->id]) }}">
                    <x-btn type="button" variant="secondary">New Purchase</x-btn>
                </a>
                <a href="{{ route('suppliers.edit', $supplier->id) }}">
                    <x-btn type="button" variant="secondary">Edit Supplier</x-btn>
                </a>
                <x-party-payment
                    :action="route('suppliers.payments.store', $supplier)"
                    :balance="$balance"
                    :invoices="$openInvoices"
                    title="Record Payment" />
            </div>
        </div>

        @if(session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">{{ session('status') }}</div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card label="Total Purchases" :counter="$purchases->count()" color="jesco">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" /></svg>
                </x-slot>
            </x-stat-card>
            <x-stat-card label="Total Purchased" :counter="(int) $totalPurchased" prefix="Rs. " color="emerald">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 015.814-5.518l2.74-1.22" /></svg>
                </x-slot>
            </x-stat-card>
            <x-stat-card label="Amount Payable" :counter="(int) $balance" prefix="Rs. " color="rose">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </x-slot>
            </x-stat-card>
            <x-card class="p-5">
                <p class="text-sm text-gray-500">Contact</p>
                <p class="mt-1 text-sm text-gray-800">{{ $supplier->phone ?? 'No phone' }}</p>
            </x-card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Purchase history -->
            <x-card>
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800">Purchase History</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                                <th class="px-5 py-3 font-medium">Purchase</th>
                                <th class="px-5 py-3 font-medium">Date</th>
                                <th class="px-5 py-3 font-medium">Total</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($purchases as $purchase)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 font-medium">
                                    <a href="{{ route('purchases.show', $purchase->id) }}" class="text-jesco-600 hover:underline">{{ $purchase->purchase_number }}</a>
                                </td>
                                <td class="px-5 py-3 text-gray-500">{{ $purchase->purchase_date->format('Y-m-d') }}</td>
                                <td class="px-5 py-3 text-gray-800">Rs. {{ number_format($purchase->total()) }}</td>
                                <td class="px-5 py-3">
                                    @php
                                        $statusColor = match($purchase->status) {
                                            'received' => 'green',
                                            'ordered' => 'yellow',
                                            'cancelled' => 'red',
                                            default => 'gray',
                                        };
                                    @endphp
                                    <x-badge :color="$statusColor">{{ ucfirst($purchase->status) }}</x-badge>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-gray-500">No purchases yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            <!-- Ledger -->
            <x-card>
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800">Transaction Ledger</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                                <th class="px-5 py-3 font-medium">Date</th>
                                <th class="px-5 py-3 font-medium">Details</th>
                                <th class="px-5 py-3 font-medium">Amount</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($transactions as $t)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $t->transaction_date->format('Y-m-d') }}</td>
                                <td class="px-5 py-3">
                                    <p class="text-gray-800">{{ $t->type === 'payment_out' ? 'Payment made' : 'Purchase' }}</p>
                                    <p class="text-xs text-gray-500">{{ $t->description }}</p>
                                </td>
                                <td class="px-5 py-3 font-medium whitespace-nowrap {{ $t->type === 'payment_out' ? 'text-emerald-600' : 'text-rose-600' }}">
                                    Rs. {{ number_format(abs($t->amount)) }}
                                </td>
                                <td class="px-5 py-3">
                                    @php
                                        $statusColor = match($t->status) {
                                            'paid' => 'green',
                                            'partial' => 'yellow',
                                            'unpaid' => 'red',
                                            default => 'gray',
                                        };
                                    @endphp
                                    <x-badge :color="$statusColor">{{ ucfirst($t->status) }}</x-badge>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-gray-500">No transactions yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
