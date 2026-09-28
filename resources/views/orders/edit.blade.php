<x-app-layout>
    <x-slot name="title">Edit {{ $order->order_number }}</x-slot>

    <div class="space-y-6 max-w-2xl">
        <div>
            <x-back-link :href="route('orders.show', $order)">Back to {{ $order->order_number }}</x-back-link>
            <h2 class="text-xl font-semibold text-gray-800 mt-1">Edit Order</h2>
            <p class="text-sm text-gray-500">{{ $order->customer?->name }} &middot; Items can't be changed after saving. To fix items, cancel this order and create a new one.</p>
        </div>

        <x-card class="p-6">
            <form method="POST" action="{{ route('orders.update', $order) }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="order_date" class="block text-sm font-medium text-gray-700 mb-1.5">Order Date</label>
                        <input type="date" id="order_date" name="order_date" value="{{ old('order_date', $order->order_date->format('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                        @error('order_date') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                        <select id="status" name="status" required class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                            @foreach(\App\Http\Controllers\OrderController::STATUSES as $status)
                                <option value="{{ $status }}" @selected(old('status', $order->status) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        @error('status') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                        <p class="mt-1.5 text-xs text-gray-500">Setting to Cancelled puts the stock back. An order with payments recorded can't be cancelled until those payments are removed.</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
                        <input type="text" id="notes" name="notes" value="{{ old('notes', $order->notes) }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                        @error('notes') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <a href="{{ route('orders.show', $order) }}">
                        <x-btn type="button" variant="secondary">Cancel</x-btn>
                    </a>
                    <x-btn type="submit">Update Order</x-btn>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
