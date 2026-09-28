<x-app-layout>
    <x-slot name="title">Inventory</x-slot>

    <div class="space-y-6" x-data="{ stockModal: '{{ old('type', 'in') }}' }">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Inventory</h2>
                <p class="text-sm text-gray-500">Real-time stock levels with Stock In / Out tracking.</p>
            </div>
            <div class="flex gap-2">
                <x-btn type="button" variant="secondary" @click="stockModal = 'in'; $dispatch('open-modal', 'stock-movement')">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19.5v-15m0 0l-6.75 6.75M12 4.5l6.75 6.75" /></svg>
                    Stock In
                </x-btn>
                <x-btn type="button" variant="secondary" @click="stockModal = 'out'; $dispatch('open-modal', 'stock-movement')">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m0 0l6.75-6.75M12 19.5l-6.75-6.75" /></svg>
                    Stock Out
                </x-btn>
            </div>
        </div>

        @if(session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm px-4 py-3">{{ session('error') }}</div>
        @endif

        <!-- Current Stock -->
        <x-card>
            <form method="GET" action="{{ route('inventory.index') }}" class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center gap-2">
                <h2 class="font-semibold text-gray-800 mr-auto">Current Stock</h2>
                @if(request()->filled('type'))
                    <input type="hidden" name="type" value="{{ request('type') }}">
                @endif
                @if(request()->filled('product'))
                    <input type="hidden" name="product" value="{{ request('product') }}">
                @endif
                @if(request()->filled('source'))
                    <input type="hidden" name="source" value="{{ request('source') }}">
                @endif
                @if(request()->filled('period'))
                    <input type="hidden" name="period" value="{{ request('period') }}">
                @endif
                @if(request()->filled('from'))
                    <input type="hidden" name="from" value="{{ request('from') }}">
                @endif
                @if(request()->filled('to'))
                    <input type="hidden" name="to" value="{{ request('to') }}">
                @endif
                <input type="text" name="stock_search" value="{{ request('stock_search') }}" placeholder="Search product or SKU..." class="w-full sm:w-56 rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500" />
                <x-filter-select name="stock" placeholder="Any stock level" :options="['in' => 'In Stock', 'low' => 'Low Stock', 'out' => 'Out of Stock']" />
                <x-btn type="submit" variant="secondary">Search</x-btn>
                @if(request()->anyFilled(['stock_search', 'stock']))
                    <a href="{{ route('inventory.index', request()->except(['stock_search', 'stock'])) }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                            <th class="px-5 py-3 font-medium">Product</th>
                            <th class="px-5 py-3 font-medium">SKU</th>
                            <th class="px-5 py-3 font-medium">Stock In</th>
                            <th class="px-5 py-3 font-medium">Stock Out</th>
                            <th class="px-5 py-3 font-medium">Balance</th>
                            <th class="px-5 py-3 font-medium">Reorder Level</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($stock as $product)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $product->name }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $product->sku }}</td>
                            <td class="px-5 py-3 text-emerald-600">+{{ $product->stock_in ?? 0 }}</td>
                            <td class="px-5 py-3 text-rose-600">-{{ $product->stock_out ?? 0 }}</td>
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $product->stock_quantity }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $product->reorder_level }}</td>
                            <td class="px-5 py-3">
                                @if($product->isOutOfStock())
                                    <x-badge color="red">Out of Stock</x-badge>
                                @elseif($product->isLowStock())
                                    <x-badge color="yellow">Low Stock</x-badge>
                                @else
                                    <x-badge color="green">In Stock</x-badge>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-gray-500">No products match these filters.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <!-- Recent Movements -->
        <x-card>
            <form method="GET" action="{{ route('inventory.index') }}" class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center gap-2">
                <h2 class="font-semibold text-gray-800 mr-auto">Stock Movements</h2>
                @if(request()->filled('stock_search'))
                    <input type="hidden" name="stock_search" value="{{ request('stock_search') }}">
                @endif
                @if(request()->filled('stock'))
                    <input type="hidden" name="stock" value="{{ request('stock') }}">
                @endif
                <x-filter-select name="type" placeholder="In and out" :options="['in' => 'Stock In', 'out' => 'Stock Out']" />
                <x-filter-select name="source" placeholder="Any source" :options="['order' => 'Orders', 'purchase' => 'Purchases', 'manual' => 'Manual entries']" />
                <x-filter-select name="product" placeholder="All products" :options="$stock->pluck('name', 'id')" class="max-w-[14rem]" />
                <x-date-filter />
                <x-btn type="submit" variant="secondary">Apply</x-btn>
                @if(request()->anyFilled(['type', 'source', 'product', 'period']))
                    <a href="{{ route('inventory.index', request()->only(['stock_search', 'stock'])) }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                            <th class="px-5 py-3 font-medium">Date</th>
                            <th class="px-5 py-3 font-medium">Product</th>
                            <th class="px-5 py-3 font-medium">Type</th>
                            <th class="px-5 py-3 font-medium">Qty</th>
                            <th class="px-5 py-3 font-medium">Reference</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($movements as $m)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-gray-500">{{ $m->created_at->format('Y-m-d') }}</td>
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $m->product?->name ?? 'Deleted product' }}</td>
                            <td class="px-5 py-3">
                                @if($m->type === 'in')
                                    <x-badge color="green">Stock In</x-badge>
                                @else
                                    <x-badge color="red">Stock Out</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-gray-800">{{ $m->quantity }}</td>
                            <td class="px-5 py-3 text-gray-500">
                                {{ $m->reference?->order_number ?? $m->reference?->purchase_number ?? $m->note ?? '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-gray-500">No stock movements match these filters.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($movements->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">
                    {{ $movements->links() }}
                </div>
            @endif
        </x-card>

        <!-- Stock In / Out modal -->
        <x-modal name="stock-movement" maxWidth="md" :show="$errors->any()">
            <form method="POST" action="{{ route('inventory.movements.store') }}" class="p-6">
                @csrf
                <input type="hidden" name="type" :value="stockModal">

                <h3 class="text-lg font-semibold text-gray-900" x-text="stockModal === 'in' ? 'Stock In' : 'Stock Out'"></h3>
                <p class="text-sm text-gray-500 mt-1" x-text="stockModal === 'in' ? 'Add received stock to a product.' : 'Remove stock for damage, adjustment or manual sale.'"></p>

                <div class="mt-5 space-y-4">
                    <div>
                        <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1.5">Product</label>
                        <select id="product_id" name="product_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                            <option value="">Select a product</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }} ({{ $product->sku }}) &mdash; {{ $product->stock_quantity }} in stock</option>
                            @endforeach
                        </select>
                        @error('product_id') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1.5">Quantity</label>
                        <input type="number" id="quantity" name="quantity" value="{{ old('quantity') }}" min="1" required class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                        @error('quantity') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="note" class="block text-sm font-medium text-gray-700 mb-1.5">Note (optional)</label>
                        <input type="text" id="note" name="note" value="{{ old('note') }}" placeholder="e.g. Received from supplier, damaged stock, manual adjustment"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                        @error('note') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <x-btn type="button" variant="secondary" @click="$dispatch('close-modal', 'stock-movement')">Cancel</x-btn>
                    <x-btn type="submit">Save</x-btn>
                </div>
            </form>
        </x-modal>
    </div>
</x-app-layout>
