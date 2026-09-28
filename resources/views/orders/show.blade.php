<x-app-layout>
    <x-slot name="title">{{ $order->order_number }}</x-slot>

    <div class="space-y-6 max-w-4xl" x-data="{ confirmingDestroy: false }">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <x-back-link :href="route('orders.index')">Back to Orders</x-back-link>
                <div class="flex items-center gap-3 mt-1">
                    <h2 class="text-xl font-semibold text-gray-800">{{ $order->order_number }}</h2>
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
                </div>
                <p class="text-sm text-gray-500 mt-1">{{ $order->order_date->format('d M Y') }} &middot; {{ $order->customer?->name }} @if($order->customer?->company) ({{ $order->customer->company }}) @endif</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('orders.edit', $order) }}">
                    <x-btn type="button" variant="secondary">Edit Status</x-btn>
                </a>
                <x-btn type="button" variant="danger" @click="confirmingDestroy = true">Delete</x-btn>
            </div>
        </div>

        @if(session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm px-4 py-3">{{ session('error') }}</div>
        @endif

        <x-card>
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">Items</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                            <th class="px-5 py-3 font-medium">Product</th>
                            <th class="px-5 py-3 font-medium">Qty</th>
                            <th class="px-5 py-3 font-medium">Unit Price</th>
                            <th class="px-5 py-3 font-medium">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($order->items as $item)
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $item->product?->name ?? 'Deleted product' }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $item->quantity }}</td>
                            <td class="px-5 py-3 text-gray-600">Rs. {{ number_format($item->unit_price) }}</td>
                            <td class="px-5 py-3 text-gray-800">Rs. {{ number_format($item->subtotal()) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-gray-100 flex justify-end">
                <div class="text-right">
                    <p class="text-sm text-gray-500">Order Total</p>
                    <p class="text-xl font-semibold text-gray-900">Rs. {{ number_format($order->total()) }}</p>
                </div>
            </div>
        </x-card>

        @if($order->notes)
            <x-card class="p-5">
                <p class="text-sm text-gray-500">Notes</p>
                <p class="mt-1 text-sm text-gray-800">{{ $order->notes }}</p>
            </x-card>
        @endif

        <x-payment-panel
            :total="$order->total()"
            :paid="$order->amountPaid()"
            :payments="$order->payments()"
            :remove-url="fn ($payment) => route('orders.payments.destroy', [$order, $payment])"
            :action="route('orders.payments.store', $order)"
            :can-pay="$order->status !== 'cancelled'"
            :notice="$order->status === 'cancelled' ? 'This order is cancelled, so no payment is due.' : null"
            paid-label="Received" />

        <div x-show="confirmingDestroy" x-cloak class="fixed inset-0 z-50">
            <div x-show="confirmingDestroy" x-transition.opacity @click="confirmingDestroy = false" class="fixed inset-0 bg-gray-900/50"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div x-show="confirmingDestroy" x-transition class="relative w-full max-w-sm bg-white rounded-xl shadow-xl p-6">
                    <h3 class="text-base font-semibold text-gray-900">Delete {{ $order->order_number }}?</h3>
                    <p class="mt-1.5 text-sm text-gray-500">
                        This will remove the order{{ $order->status !== 'cancelled' ? ', restore stock' : '' }} and delete its payment record. This cannot be undone.
                    </p>
                    <form method="POST" action="{{ route('orders.destroy', $order) }}" class="mt-6 flex justify-end gap-2">
                        @csrf
                        @method('DELETE')
                        <x-btn type="button" variant="secondary" @click="confirmingDestroy = false">Cancel</x-btn>
                        <x-btn type="submit" variant="danger">Delete</x-btn>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
