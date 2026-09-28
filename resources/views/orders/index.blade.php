<x-app-layout>
    <x-slot name="title">Orders</x-slot>

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Orders</h2>
                <p class="text-sm text-gray-500">Orders, status, quantities &amp; invoices.</p>
            </div>
            <a href="{{ route('orders.create') }}">
                <x-btn type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    New Order
                </x-btn>
            </a>
        </div>

        @if(session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm px-4 py-3">{{ session('error') }}</div>
        @endif

        <x-card>
            <div class="px-5 pt-4 flex flex-wrap gap-2">
                <a href="{{ route('orders.index', request()->except('status', 'page')) }}" class="px-3 py-1.5 rounded-lg text-sm font-medium {{ ! request('status') ? 'bg-jesco-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">All</a>
                @foreach(['pending', 'processing', 'delivered', 'cancelled'] as $status)
                    <a href="{{ route('orders.index', array_merge(request()->except('status', 'page'), ['status' => $status])) }}" class="px-3 py-1.5 rounded-lg text-sm font-medium {{ request('status') === $status ? 'bg-jesco-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">{{ ucfirst($status) }}</a>
                @endforeach
            </div>
            <form method="GET" action="{{ route('orders.index') }}" class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center gap-2">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search order # or customer..." class="w-full sm:w-56 rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500" />
                <x-filter-select name="payment" placeholder="Any payment" :options="['paid' => 'Paid', 'partial' => 'Partial', 'unpaid' => 'Unpaid']" />
                <x-filter-select name="customer" placeholder="All customers" :options="$customers" class="max-w-[12rem]" />
                <x-date-filter />
                <x-btn type="submit" variant="secondary">Apply</x-btn>
                @if(request()->anyFilled(['search', 'payment', 'customer', 'period']))
                    <a href="{{ route('orders.index', request()->only('status')) }}" class="text-sm text-gray-500 hover:text-gray-700">Clear filters</a>
                @endif
            </form>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                            <th class="px-5 py-3 font-medium">Order #</th>
                            <th class="px-5 py-3 font-medium">Customer</th>
                            <th class="px-5 py-3 font-medium">Date</th>
                            <th class="px-5 py-3 font-medium">Items</th>
                            <th class="px-5 py-3 font-medium">Total</th>
                            <th class="px-5 py-3 font-medium">Payment</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($orders as $order)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $order->order_number }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $order->customer?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $order->order_date->format('Y-m-d') }}</td>
                            <td class="px-5 py-3 text-gray-800">{{ $order->items_count }}</td>
                            <td class="px-5 py-3 text-gray-800 whitespace-nowrap">Rs. {{ number_format($order->total()) }}</td>
                            <td class="px-5 py-3">
                                @if($order->status === 'cancelled')
                                    <span class="text-gray-400">—</span>
                                @else
                                    @php
                                        $due = $order->balanceDue();
                                        [$payLabel, $payColor] = match (true) {
                                            $due <= 0 => ['Paid', 'green'],
                                            $order->amountPaid() > 0 => ['Partial', 'yellow'],
                                            default => ['Unpaid', 'red'],
                                        };
                                    @endphp
                                    <x-badge :color="$payColor">{{ $payLabel }}</x-badge>
                                    @if($due > 0)
                                        <p class="mt-1 text-xs text-gray-500 whitespace-nowrap">Rs. {{ number_format($due) }} due</p>
                                    @endif
                                @endif
                            </td>
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
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('orders.show', $order) }}" class="text-jesco-600 hover:underline text-sm font-medium">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-gray-500">No orders found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">
                    {{ $orders->links() }}
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
