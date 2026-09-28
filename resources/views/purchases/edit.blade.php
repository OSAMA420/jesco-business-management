<x-app-layout>
    <x-slot name="title">Edit {{ $purchase->purchase_number }}</x-slot>

    <div class="space-y-6 max-w-2xl">
        <div>
            <x-back-link :href="route('purchases.show', $purchase->id)">Back to {{ $purchase->purchase_number }}</x-back-link>
            <h2 class="text-xl font-semibold text-gray-800 mt-1">Edit Purchase</h2>
            <p class="text-sm text-gray-500">{{ $purchase->supplier?->name }} &middot; Items can't be changed after saving. To fix items, cancel this purchase and create a new one.</p>
        </div>

        <x-card class="p-6">
            <form method="POST" action="{{ route('purchases.update', $purchase->id) }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="purchase_date" class="block text-sm font-medium text-gray-700 mb-1.5">Purchase Date</label>
                        <input type="date" id="purchase_date" name="purchase_date" value="{{ old('purchase_date', $purchase->purchase_date->format('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                        @error('purchase_date') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                        <select id="status" name="status" required class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                            @foreach(\App\Http\Controllers\PurchaseController::STATUSES as $status)
                                <option value="{{ $status }}" @selected(old('status', $purchase->status) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        @error('status') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <p class="text-xs text-gray-500">
                            Ordered to Received adds the stock to inventory. Received to Ordered or Cancelled takes it back out.
                            Cancelling also removes the purchase from the supplier's balance. If payments are recorded on it, remove them first.
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
                        <input type="text" id="notes" name="notes" value="{{ old('notes', $purchase->notes) }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                        @error('notes') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <a href="{{ route('purchases.show', $purchase->id) }}">
                        <x-btn type="button" variant="secondary">Cancel</x-btn>
                    </a>
                    <x-btn type="submit">Update Purchase</x-btn>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
