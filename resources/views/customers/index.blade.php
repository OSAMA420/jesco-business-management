<x-app-layout>
    <x-slot name="title">Customers</x-slot>

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Customers</h2>
                <p class="text-sm text-gray-500">Customer profiles &amp; complete order history.</p>
            </div>
            <a href="{{ route('customers.create') }}">
                <x-btn type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Add Customer
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
            <form method="GET" action="{{ route('customers.index') }}" class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customers..." class="w-full sm:w-64 rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500" />
                <x-filter-select name="balance" placeholder="Any balance" :options="['due' => 'Has dues', 'clear' => 'Clear']" />
                <x-btn type="submit" variant="secondary">Search</x-btn>
                @if(request()->anyFilled(['search', 'balance']))
                    <a href="{{ route('customers.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear filters</a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                            <th class="px-5 py-3 font-medium">Customer</th>
                            <th class="px-5 py-3 font-medium">Company</th>
                            <th class="px-5 py-3 font-medium">Phone</th>
                            <th class="px-5 py-3 font-medium">Orders</th>
                            <th class="px-5 py-3 font-medium">Total Spent</th>
                            <th class="px-5 py-3 font-medium">Balance</th>
                            <th class="px-5 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($customers as $customer)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-full bg-jesco-100 text-jesco-700 flex items-center justify-center text-xs font-semibold">
                                        {{ strtoupper(substr($customer->name, 0, 1)) }}
                                    </span>
                                    <span class="font-medium text-gray-800">{{ $customer->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $customer->company ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $customer->phone ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-800">{{ $customer->orders_count }}</td>
                            <td class="px-5 py-3 text-gray-800">Rs. {{ number_format($customer->total_spent) }}</td>
                            <td class="px-5 py-3">
                                @if($customer->balance_amount > 0)
                                    <x-badge color="red">Rs. {{ number_format($customer->balance_amount) }}</x-badge>
                                @else
                                    <x-badge color="green">Clear</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-3">
                                    <a href="{{ route('customers.show', $customer) }}" class="text-jesco-600 hover:underline text-sm font-medium">View</a>
                                    <a href="{{ route('customers.edit', $customer) }}" class="text-jesco-600 hover:underline text-sm font-medium">Edit</a>
                                    <button type="button"
                                            @click="$dispatch('confirm-delete', { url: '{{ route('customers.destroy', $customer) }}', name: '{{ addslashes($customer->name) }}' })"
                                            class="text-rose-600 hover:underline text-sm font-medium">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-gray-500">No customers found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($customers->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">
                    {{ $customers->links() }}
                </div>
            @endif
        </x-card>

        <x-confirm-delete-modal />
    </div>
</x-app-layout>
