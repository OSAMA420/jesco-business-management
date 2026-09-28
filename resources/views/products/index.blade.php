<x-app-layout>
    <x-slot name="title">Products</x-slot>

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Products</h2>
                <p class="text-sm text-gray-500">Manage your products, categories, pricing &amp; availability.</p>
            </div>
            <a href="{{ route('products.create') }}">
                <x-btn type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Add Product
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
            <form method="GET" action="{{ route('products.index') }}" class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..." class="w-full sm:w-64 rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500" />
                <x-filter-select name="category" placeholder="All categories" :options="$categories->pluck('name', 'id')" />
                <x-filter-select name="stock" placeholder="Any stock level" :options="['in' => 'In Stock', 'low' => 'Low Stock', 'out' => 'Out of Stock']" />
                <x-filter-select name="state" placeholder="Active and inactive" :options="['active' => 'Active only', 'inactive' => 'Inactive only']" />
                <x-btn type="submit" variant="secondary">Search</x-btn>
                @if(request()->anyFilled(['search', 'category', 'stock', 'state']))
                    <a href="{{ route('products.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear filters</a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                            <th class="px-5 py-3 font-medium">Product</th>
                            <th class="px-5 py-3 font-medium">SKU</th>
                            <th class="px-5 py-3 font-medium">Category</th>
                            <th class="px-5 py-3 font-medium">Price</th>
                            <th class="px-5 py-3 font-medium">Stock</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($products as $product)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $product->name }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $product->sku }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $product->category?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-800">Rs. {{ number_format($product->selling_price) }}</td>
                            <td class="px-5 py-3 text-gray-800">{{ $product->stock_quantity }}</td>
                            <td class="px-5 py-3">
                                @if($product->is_active)
                                    <x-badge color="green">Active</x-badge>
                                @else
                                    <x-badge color="gray">Inactive</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-3">
                                    <a href="{{ route('products.edit', $product) }}" class="text-jesco-600 hover:underline text-sm font-medium">Edit</a>
                                    <button type="button"
                                            @click="$dispatch('confirm-delete', { url: '{{ route('products.destroy', $product) }}', name: '{{ addslashes($product->name) }}' })"
                                            class="text-rose-600 hover:underline text-sm font-medium">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-gray-500">No products found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($products->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">
                    {{ $products->links() }}
                </div>
            @endif
        </x-card>

        <x-confirm-delete-modal />
    </div>
</x-app-layout>
