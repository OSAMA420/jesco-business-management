<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    @php
        $user = auth()->user();
        $can = fn (string $area) => $user->canAccess($area);
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        $statusColors = ['pending' => 'yellow', 'processing' => 'blue', 'delivered' => 'green', 'cancelled' => 'red'];
        $payColors = ['paid' => 'green', 'partial' => 'yellow', 'unpaid' => 'red'];
        $toneClasses = [
            'amber' => 'bg-amber-50 text-amber-600',
            'sky' => 'bg-sky-50 text-sky-600',
            'rose' => 'bg-rose-50 text-rose-600',
            'gray' => 'bg-gray-100 text-gray-600',
        ];
        $openItems = collect($attention)->filter(fn ($a) => $a['count'] > 0 && $can($a['area']));
        $statusTotal = max(1, array_sum($statuses));
        $salesChange = $kpis['sales_change'] === null
            ? 'No sales in the same days last month'
            : ($kpis['sales_change'] >= 0 ? '+' : '').$kpis['sales_change'].'% vs same days last month';
        $periodButtons = ['7d' => '7 Days', '30d' => '5 Weeks', '12m' => '12 Months'];
    @endphp

    <div class="space-y-6">
        <!-- Greeting + quick actions -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">{{ $greeting }}, {{ Str::before($user->name.' ', ' ') }}</h2>
                <p class="text-sm text-gray-500">{{ now()->format('l, d F Y') }} &middot; Here is how JESCO is doing this month.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($can('orders'))
                    <a href="{{ route('orders.create') }}"><x-btn type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        New Order
                    </x-btn></a>
                @endif
                @if($can('purchases'))
                    <a href="{{ route('purchases.create') }}"><x-btn type="button" variant="secondary">New Purchase</x-btn></a>
                @endif
                @if($can('inventory'))
                    <a href="{{ route('inventory.index') }}"><x-btn type="button" :variant="$can('orders') ? 'secondary' : 'primary'">Stock In / Out</x-btn></a>
                @endif
                @if($can('customers'))
                    <a href="{{ route('customers.create') }}"><x-btn type="button" variant="secondary">Add Customer</x-btn></a>
                @endif
                @if($can('finance'))
                    <a href="{{ route('finance.index', ['tab' => 'expenses']) }}"><x-btn type="button" variant="secondary">Add Expense</x-btn></a>
                @endif
            </div>
        </div>

        <!-- This month -->
        @if($can('orders') || $can('finance') || $can('reports') || $can('customers'))
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                @if($can('orders'))
                    <x-stat-card label="Sales This Month" :counter="(int) $kpis['sales']" prefix="Rs. " :change="$salesChange" :positive="($kpis['sales_change'] ?? 0) >= 0" color="jesco">
                        <x-slot name="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 015.814-5.518l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.94" /></svg>
                        </x-slot>
                    </x-stat-card>
                @endif
                @if($can('finance'))
                    <x-stat-card label="Cash Collected" :counter="(int) $kpis['collected']" prefix="Rs. " change="Payments received this month" color="emerald">
                        <x-slot name="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" /></svg>
                        </x-slot>
                    </x-stat-card>
                @endif
                @if($can('reports'))
                    <a href="{{ route('reports.show', ['report' => 'profit-loss', 'period' => 'this_month']) }}" class="block">
                        <x-stat-card label="Net Profit This Month" :counter="(int) $kpis['profit']" prefix="Rs. " change="After cost of goods and expenses" :positive="$kpis['profit'] >= 0" color="amber">
                            <x-slot name="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                            </x-slot>
                        </x-stat-card>
                    </a>
                @endif
                @if($can('customers'))
                    <a href="{{ route('customers.index', ['balance' => 'due']) }}" class="block">
                        <x-stat-card label="Customers Owe You" :counter="(int) $kpis['receivable']" prefix="Rs. " :change="$kpis['receivable_customers'].' '.Str::plural('customer', $kpis['receivable_customers']).' with dues'" :positive="$kpis['receivable'] <= 0" color="rose">
                            <x-slot name="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                            </x-slot>
                        </x-stat-card>
                    </a>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            @if($can('orders'))
                <!-- Sales trend -->
                <x-card class="lg:col-span-2 p-5" x-data="trendChart({{ Js::from($salesTrend) }}, [{ key: 'sales', label: 'Sales', color: '#2a78d6', money: true }])">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <div>
                            <h3 class="font-semibold text-gray-800">Sales</h3>
                            <p class="text-xs text-gray-500">Billed amount, cancelled orders left out</p>
                        </div>
                        <div class="flex gap-1 bg-gray-100 rounded-lg p-1">
                            @foreach($periodButtons as $key => $label)
                                <button type="button" @click="setPeriod('{{ $key }}')" :class="period === '{{ $key }}' ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500'"
                                        class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="h-64"><canvas x-ref="canvas" role="img" aria-label="Sales over time"></canvas></div>
                </x-card>
            @endif

            <!-- Needs attention -->
            <x-card class="{{ $can('orders') ? '' : 'lg:col-span-3' }}">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Needs Attention</h3>
                </div>
                <ul class="divide-y divide-gray-100">
                    @forelse($openItems as $item)
                        <li>
                            <a href="{{ $item['url'] }}" class="px-5 py-3.5 flex items-start gap-3 hover:bg-gray-50 group">
                                <span class="w-9 h-9 shrink-0 rounded-lg flex items-center justify-center text-sm font-semibold {{ $toneClasses[$item['tone']] }}">{{ $item['count'] }}</span>
                                <span class="flex-1 min-w-0">
                                    <span class="block text-sm font-medium text-gray-800">{{ $item['label'][$item['count'] === 1 ? 0 : 1] }}</span>
                                    <span class="block text-xs text-gray-500 truncate" title="{{ $item['detail'] }}">{{ $item['detail'] }}</span>
                                </span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-2.5 text-gray-300 group-hover:text-jesco-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-10 text-center">
                            <p class="text-sm font-medium text-gray-800">All clear</p>
                            <p class="text-xs text-gray-500 mt-1">Nothing needs your attention right now.</p>
                        </li>
                    @endforelse
                </ul>
            </x-card>
        </div>

        @if($can('orders'))
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Recent orders -->
                <x-card class="lg:col-span-2">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">Recent Orders</h3>
                        <a href="{{ route('orders.index') }}" class="text-sm text-jesco-600 hover:underline">View all</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                                    <th class="px-5 py-3 font-medium">Order</th>
                                    <th class="px-5 py-3 font-medium">Customer</th>
                                    <th class="px-5 py-3 font-medium">Date</th>
                                    <th class="px-5 py-3 font-medium text-right">Total</th>
                                    <th class="px-5 py-3 font-medium">Payment</th>
                                    <th class="px-5 py-3 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($recentOrders as $order)
                                    <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('orders.show', $order) }}'">
                                        <td class="px-5 py-3 font-medium">
                                            <a href="{{ route('orders.show', $order) }}" class="text-jesco-600 hover:underline">{{ $order->order_number }}</a>
                                        </td>
                                        <td class="px-5 py-3 text-gray-600">{{ $order->customer?->name ?? '—' }}</td>
                                        <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $order->order_date->format('d M') }}</td>
                                        <td class="px-5 py-3 text-right text-gray-800 whitespace-nowrap">Rs. {{ number_format($order->total()) }}</td>
                                        <td class="px-5 py-3">
                                            @if($order->status !== 'cancelled')
                                                <x-badge :color="$payColors[$order->paymentStatus()]">{{ ucfirst($order->paymentStatus()) }}</x-badge>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3"><x-badge :color="$statusColors[$order->status] ?? 'gray'">{{ ucfirst($order->status) }}</x-badge></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">No orders yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <div class="space-y-6">
                    <!-- Order status -->
                    <x-card class="p-5">
                        <h3 class="font-semibold text-gray-800">Order Status</h3>
                        <p class="text-xs text-gray-500 mb-4">All orders</p>
                        <div class="space-y-3">
                            @foreach($statuses as $status => $count)
                                <a href="{{ route('orders.index', ['status' => $status]) }}" class="block group">
                                    <div class="flex items-center justify-between text-sm">
                                        <x-badge :color="$statusColors[$status]">{{ ucfirst($status) }}</x-badge>
                                        <span class="font-medium text-gray-900 group-hover:text-jesco-600">{{ $count }}</span>
                                    </div>
                                    <div class="mt-1.5 h-1.5 rounded-full bg-gray-100">
                                        <div class="h-full rounded-full bg-slate-400" style="width: {{ $count / $statusTotal * 100 }}%"></div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </x-card>

                    <!-- Top products -->
                    <x-card>
                        <div class="px-5 py-4 border-b border-gray-100">
                            <h3 class="font-semibold text-gray-800">Top Products This Month</h3>
                        </div>
                        <ol class="divide-y divide-gray-100">
                            @forelse($topProducts as $i => [$name, $qty, $revenue])
                                <li class="px-5 py-3 flex items-center gap-3 text-sm">
                                    <span class="w-5 text-xs font-semibold text-gray-400">{{ $i + 1 }}</span>
                                    <span class="flex-1 min-w-0">
                                        <span class="block text-gray-800 truncate" title="{{ $name }}">{{ $name }}</span>
                                        <span class="block text-xs text-gray-500">{{ $qty }} sold</span>
                                    </span>
                                    <span class="font-medium text-gray-900 whitespace-nowrap">Rs. {{ number_format($revenue) }}</span>
                                </li>
                            @empty
                                <li class="px-5 py-8 text-center text-sm text-gray-500">No sales this month yet.</li>
                            @endforelse
                        </ol>
                    </x-card>
                </div>
            </div>
        @endif

        @if($can('inventory'))
            <!-- Stock -->
            <div class="pt-2">
                <h3 class="text-base font-semibold text-gray-800">Stock</h3>
                <p class="text-xs text-gray-500">Levels are as of now; units in and out are for this month.</p>
            </div>

            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
                <x-stat-card label="Stock Value (at cost)" :counter="(int) $kpis['stock_value']" prefix="Rs. " color="jesco">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                    </x-slot>
                </x-stat-card>
                <a href="{{ route('inventory.index', ['stock' => 'low']) }}" class="block">
                    <x-stat-card label="Low Stock Items" :counter="$kpis['low_stock']" :change="$kpis['out_of_stock'].' out of stock'" :positive="$kpis['out_of_stock'] === 0" color="amber">
                        <x-slot name="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        </x-slot>
                    </x-stat-card>
                </a>
                <a href="{{ route('inventory.index', ['type' => 'in', 'period' => 'this_month']) }}" class="block">
                    <x-stat-card label="Units In This Month" :counter="$kpis['units_in']" color="emerald">
                        <x-slot name="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19.5v-15m0 0l-6.75 6.75M12 4.5l6.75 6.75" /></svg>
                        </x-slot>
                    </x-stat-card>
                </a>
                <a href="{{ route('inventory.index', ['type' => 'out', 'period' => 'this_month']) }}" class="block">
                    <x-stat-card label="Units Out This Month" :counter="$kpis['units_out']" color="rose">
                        <x-slot name="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m0 0l6.75-6.75M12 19.5l-6.75-6.75" /></svg>
                        </x-slot>
                    </x-stat-card>
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Stock movement trend -->
                <x-card class="lg:col-span-2 p-5"
                        x-data="trendChart({{ Js::from($stockTrend) }}, [{ key: 'in', label: 'Stock In', color: '#2a78d6' }, { key: 'out', label: 'Stock Out', color: '#eb6834' }])">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <div>
                            <h3 class="font-semibold text-gray-800">Stock Movement</h3>
                            <p class="text-xs text-gray-500">Units in and out, all products</p>
                        </div>
                        <div class="flex gap-1 bg-gray-100 rounded-lg p-1">
                            @foreach($periodButtons as $key => $label)
                                <button type="button" @click="setPeriod('{{ $key }}')" :class="period === '{{ $key }}' ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500'"
                                        class="px-2.5 py-1 rounded-md text-xs font-medium transition-colors">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="h-64"><canvas x-ref="canvas" role="img" aria-label="Stock in and out over time"></canvas></div>
                </x-card>

                <!-- Stock levels -->
                <x-card>
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-800">Stock Levels</h3>
                            <p class="text-xs text-gray-500">Closest to reorder level first</p>
                        </div>
                        <a href="{{ route('inventory.index') }}" class="text-sm text-jesco-600 hover:underline">View all</a>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        @forelse($stockLevels as $product)
                            @php
                                $scale = max($product->reorder_level * 3, $product->stock_quantity, 1);
                                $badge = $product->isOutOfStock() ? ['red', 'Out'] : ($product->isLowStock() ? ['yellow', 'Low'] : null);
                            @endphp
                            <li class="px-5 py-3">
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <span class="text-gray-800 truncate" title="{{ $product->name }}">{{ $product->name }}</span>
                                    <span class="flex items-center gap-2 shrink-0">
                                        @if($badge)<x-badge :color="$badge[0]">{{ $badge[1] }}</x-badge>@endif
                                        <span class="font-medium text-gray-900">{{ $product->stock_quantity }}</span>
                                    </span>
                                </div>
                                <div class="relative mt-1.5 h-1.5 rounded-full bg-gray-100">
                                    <div class="h-full rounded-full bg-slate-400" style="width: {{ min(100, max(0, $product->stock_quantity) / $scale * 100) }}%"></div>
                                    <span class="absolute -top-0.5 h-2.5 w-0.5 bg-gray-700" style="left: {{ $product->reorder_level / $scale * 100 }}%" title="Reorder level {{ $product->reorder_level }}"></span>
                                </div>
                                <p class="mt-1 text-[11px] text-gray-400">Reorder at {{ $product->reorder_level }}</p>
                            </li>
                        @empty
                            <li class="px-5 py-8 text-center text-sm text-gray-500">No products yet.</li>
                        @endforelse
                    </ul>
                </x-card>
            </div>

            <!-- Recent movements -->
            <x-card>
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">Recent Stock Movements</h3>
                    <a href="{{ route('inventory.index') }}" class="text-sm text-jesco-600 hover:underline">View all</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                                <th class="px-5 py-3 font-medium">When</th>
                                <th class="px-5 py-3 font-medium">Product</th>
                                <th class="px-5 py-3 font-medium">Type</th>
                                <th class="px-5 py-3 font-medium text-right">Qty</th>
                                <th class="px-5 py-3 font-medium">Source</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($recentMovements as $m)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $m->created_at->diffForHumans() }}</td>
                                    <td class="px-5 py-3 text-gray-800">{{ $m->product?->name ?? 'Deleted product' }}</td>
                                    <td class="px-5 py-3"><x-badge :color="$m->type === 'in' ? 'green' : 'red'">{{ $m->type === 'in' ? 'Stock In' : 'Stock Out' }}</x-badge></td>
                                    <td class="px-5 py-3 text-right font-medium text-gray-900">{{ $m->quantity }}</td>
                                    <td class="px-5 py-3 text-gray-500">{{ $m->reference?->order_number ?? $m->reference?->purchase_number ?? $m->note ?? 'Manual entry' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-10 text-center text-gray-500">No stock movements yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endif
    </div>

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.1/chart.umd.min.js"></script>
    @endpush
</x-app-layout>
