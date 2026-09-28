<x-app-layout>
    <x-slot name="title">{{ $purchase->purchase_number }}</x-slot>

    <div class="space-y-6 max-w-4xl" x-data="{ confirmingDestroy: false }">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <x-back-link :href="route('purchases.index')">Back to Purchases</x-back-link>
                <div class="flex items-center gap-3 mt-1">
                    <h2 class="text-xl font-semibold text-gray-800">{{ $purchase->purchase_number }}</h2>
                    @php
                        $statusColor = match($purchase->status) {
                            'received' => 'green',
                            'ordered' => 'yellow',
                            'cancelled' => 'red',
                            default => 'gray',
                        };
                    @endphp
                    <x-badge :color="$statusColor">{{ ucfirst($purchase->status) }}</x-badge>
                </div>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $purchase->purchase_date->format('d M Y') }} &middot;
                    <a href="{{ route('suppliers.show', $purchase->supplier_id) }}" class="text-jesco-600 hover:underline">{{ $purchase->supplier?->name }}</a>
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('purchases.edit', $purchase->id) }}">
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

        @if($purchase->status === 'ordered')
            <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-sm px-4 py-3">
                This stock has not arrived yet. Mark the purchase as Received once it does, and inventory will be updated.
            </div>
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
                            <th class="px-5 py-3 font-medium">Unit Cost</th>
                            <th class="px-5 py-3 font-medium">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($purchase->items as $item)
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $item->product?->name ?? 'Deleted product' }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $item->quantity }}</td>
                            <td class="px-5 py-3 text-gray-600">Rs. {{ number_format($item->unit_cost) }}</td>
                            <td class="px-5 py-3 text-gray-800">Rs. {{ number_format($item->subtotal()) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-gray-100 flex justify-end">
                <div class="text-right">
                    <p class="text-sm text-gray-500">Purchase Total</p>
                    <p class="text-xl font-semibold text-gray-900">Rs. {{ number_format($purchase->total()) }}</p>
                </div>
            </div>
        </x-card>

        @php
            $paymentNotice = match($purchase->status) {
                'ordered' => 'Payments can be recorded once this stock is received.',
                'cancelled' => 'This purchase is cancelled, so no payment is due.',
                default => null,
            };
        @endphp
        <x-payment-panel
            :total="$purchase->total()"
            :paid="$purchase->amountPaid()"
            :payments="$purchase->payments()"
            :remove-url="fn ($payment) => route('purchases.payments.destroy', [$purchase, $payment])"
            :action="route('purchases.payments.store', $purchase)"
            :can-pay="$purchase->status === 'received'"
            :notice="$paymentNotice"
            paid-label="Paid" />

        <x-card class="p-5">
            <p class="text-sm text-gray-500">Notes</p>
            <p class="mt-1 text-sm text-gray-800">{{ $purchase->notes ?? 'No notes' }}</p>
        </x-card>

        <div x-show="confirmingDestroy" x-cloak class="fixed inset-0 z-50">
            <div x-show="confirmingDestroy" x-transition.opacity @click="confirmingDestroy = false" class="fixed inset-0 bg-gray-900/50"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div x-show="confirmingDestroy" x-transition class="relative w-full max-w-sm bg-white rounded-xl shadow-xl p-6">
                    <h3 class="text-base font-semibold text-gray-900">Delete {{ $purchase->purchase_number }}?</h3>
                    <p class="mt-1.5 text-sm text-gray-500">
                        This will remove the purchase{{ $purchase->status === 'received' ? ', take its stock back out of inventory' : '' }} and delete its payment record. This cannot be undone.
                    </p>
                    <form method="POST" action="{{ route('purchases.destroy', $purchase->id) }}" class="mt-6 flex justify-end gap-2">
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
