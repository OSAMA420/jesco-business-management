<x-app-layout>
    <x-slot name="title">{{ $customer->name }}</x-slot>

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <x-back-link :href="route('customers.index')">Back to Customers</x-back-link>
                <div class="flex items-center gap-3 mt-1">
                    <span class="w-11 h-11 rounded-full bg-jesco-100 text-jesco-700 flex items-center justify-center font-semibold">
                        {{ strtoupper(substr($customer->name, 0, 1)) }}
                    </span>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">{{ $customer->name }}</h2>
                        <p class="text-sm text-gray-500">{{ $customer->company ?? 'No company on file' }}</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('customers.edit', $customer) }}">
                    <x-btn type="button" variant="secondary">Edit Customer</x-btn>
                </a>
                <x-party-payment
                    :action="route('customers.payments.store', $customer)"
                    :balance="$balance"
                    :invoices="$openInvoices"
                    title="Receive Payment" />
            </div>
        </div>

        @if(session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">{{ session('status') }}</div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card label="Total Orders" :counter="$customer->orders_count" color="jesco">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.756 2.505-7.324a.75.75 0 00-.738-.894H5.106M7.5 14.25L5.106 5.11M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
                </x-slot>
            </x-stat-card>
            <x-stat-card label="Total Spent" :counter="(int) $totalSpent" prefix="Rs. " color="emerald">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 015.814-5.518l2.74-1.22" /></svg>
                </x-slot>
            </x-stat-card>
            <x-stat-card label="Outstanding Balance" :counter="(int) $balance" prefix="Rs. " :positive="$balance <= 0" color="rose">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </x-slot>
            </x-stat-card>
            <x-card class="p-5">
                <p class="text-sm text-gray-500">Contact</p>
                <p class="mt-1 text-sm text-gray-800">{{ $customer->phone ?? 'No phone' }}</p>
                <p class="text-sm text-gray-500">{{ $customer->email ?? 'No email' }}</p>
            </x-card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Order history -->
            <x-card>
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800">Order History</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                                <th class="px-5 py-3 font-medium">Order</th>
                                <th class="px-5 py-3 font-medium">Date</th>
                                <th class="px-5 py-3 font-medium">Total</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($orders as $order)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 font-medium">
                                    <a href="{{ route('orders.show', $order) }}" class="text-jesco-600 hover:underline">{{ $order->order_number }}</a>
                                    @if($order->balanceDue() > 0)
                                        <p class="text-xs font-normal text-rose-600">Rs. {{ number_format($order->balanceDue()) }} due</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-gray-500">{{ $order->order_date->format('Y-m-d') }}</td>
                                <td class="px-5 py-3 text-gray-800">Rs. {{ number_format($order->total()) }}</td>
                                <td class="px-5 py-3">
                                    @php
                                        $statusColor = match($order->status) {
                                            'delivered' => 'green',
                                            'processing' => 'blue',
                                            'pending' => 'yellow',
                                            'cancelled' => 'red',
                                            default => 'gray',
                                        };
                                    @endphp
                                    <x-badge :color="$statusColor">{{ ucfirst($order->status) }}</x-badge>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-gray-500">No orders yet.</td>
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
                                <th class="px-5 py-3 font-medium">Type</th>
                                <th class="px-5 py-3 font-medium">Amount</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($transactions as $t)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 text-gray-500">{{ $t->transaction_date->format('Y-m-d') }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ ucfirst(str_replace('_', ' ', $t->type)) }}</td>
                                <td class="px-5 py-3 font-medium {{ $t->amount < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                    {{ $t->amount < 0 ? '-' : '+' }}Rs. {{ number_format(abs($t->amount)) }}
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
