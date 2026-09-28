<x-app-layout>
    <x-slot name="title">New Purchase</x-slot>

    <div class="space-y-6 max-w-4xl"
         x-data="{
            status: @js(old('status', 'received')),
            items: [{ product_id: '', quantity: 1, unit_cost: 0 }],
            amountPaid: {{ (float) old('amount_paid', 0) }},
            products: @js($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'cost' => (float) $p->cost_price, 'stock' => $p->stock_quantity])),
            addItem() { this.items.push({ product_id: '', quantity: 1, unit_cost: 0 }); },
            removeItem(i) { if (this.items.length > 1) this.items.splice(i, 1); },
            productInfo(id) { return this.products.find(p => p.id == id); },
            onProductChange(item) {
                const p = this.productInfo(item.product_id);
                item.unit_cost = p ? p.cost : 0;
            },
            subtotal(item) { return (Number(item.quantity) || 0) * (Number(item.unit_cost) || 0); },
            total() { return this.items.reduce((sum, item) => sum + this.subtotal(item), 0); },
            paymentStatus() {
                if (this.status !== 'received') return 'unpaid';
                const total = this.total(), paid = Number(this.amountPaid) || 0;
                if (total > 0 && paid >= total) return 'paid';
                return paid > 0 ? 'partial' : 'unpaid';
            },
         }">
        <div>
            <x-back-link :href="route('purchases.index')">Back to Purchases</x-back-link>
            <h2 class="text-xl font-semibold text-gray-800 mt-1">New Purchase</h2>
        </div>

        @if($errors->any())
            <div class="rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm px-4 py-3">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('purchases.store') }}">
            @csrf

            <x-card class="p-6 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-1.5">Supplier</label>
                        <select id="supplier_id" name="supplier_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                            <option value="">Select a supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id', request('supplier')) == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="purchase_date" class="block text-sm font-medium text-gray-700 mb-1.5">Purchase Date</label>
                        <input type="date" id="purchase_date" name="purchase_date" value="{{ old('purchase_date', now()->format('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                        <select id="status" name="status" x-model="status" required class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                            @foreach(\App\Http\Controllers\PurchaseController::STATUSES as $status)
                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="status === 'received'">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="amount_paid" class="block text-sm font-medium text-gray-700">Amount Paid Now (Rs.)</label>
                            <button type="button" @click="amountPaid = total()" class="text-xs font-medium text-jesco-600 hover:underline">Full</button>
                        </div>
                        <input type="number" id="amount_paid" name="amount_paid" x-model.number="amountPaid" min="0" step="0.01" :max="total()"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                        @error('amount_paid') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                        <p class="mt-1.5 text-xs text-gray-500">
                            Payment status:
                            <span class="font-medium"
                                  :class="{ 'text-emerald-600': paymentStatus() === 'paid', 'text-amber-600': paymentStatus() === 'partial', 'text-rose-600': paymentStatus() === 'unpaid' }"
                                  x-text="paymentStatus().charAt(0).toUpperCase() + paymentStatus().slice(1)"></span>
                            <span x-show="paymentStatus() === 'partial'" x-text="'(Rs. ' + (total() - amountPaid).toLocaleString('en-US') + ' due)'"></span>
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1.5">Notes (optional)</label>
                        <input type="text" id="notes" name="notes" value="{{ old('notes') }}" placeholder="e.g. Invoice no. or delivery details"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                    </div>
                </div>

                <div class="rounded-lg px-4 py-3 text-sm"
                     :class="{
                        'bg-emerald-50 text-emerald-700': status === 'received',
                        'bg-amber-50 text-amber-700': status === 'ordered',
                        'bg-gray-100 text-gray-600': status === 'cancelled',
                     }">
                    <span x-show="status === 'received'">Stock for these items will be added to inventory as soon as you save.</span>
                    <span x-show="status === 'ordered'" x-cloak>Stock will be added later, when you mark this purchase as Received.</span>
                    <span x-show="status === 'cancelled'" x-cloak>A cancelled purchase does not change stock or balances.</span>
                </div>
            </x-card>

            <x-card class="mt-6">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">Items</h3>
                    <x-btn type="button" variant="secondary" @click="addItem()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Add Item
                    </x-btn>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                                <th class="px-5 py-3 font-medium w-1/2">Product</th>
                                <th class="px-5 py-3 font-medium">Quantity</th>
                                <th class="px-5 py-3 font-medium">Unit Cost</th>
                                <th class="px-5 py-3 font-medium">Subtotal</th>
                                <th class="px-5 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(item, index) in items" :key="index">
                                <tr>
                                    <td class="px-5 py-3">
                                        <select :name="'items[' + index + '][product_id]'" x-model="item.product_id" @change="onProductChange(item)" required
                                                class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                                            <option value="">Select a product</option>
                                            <template x-for="p in products" :key="p.id">
                                                <option :value="p.id" x-text="p.name + ' (' + p.sku + '), ' + p.stock + ' in stock'"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td class="px-5 py-3">
                                        <input type="number" :name="'items[' + index + '][quantity]'" x-model.number="item.quantity" min="1" required
                                               class="w-24 rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                                    </td>
                                    <td class="px-5 py-3">
                                        <input type="number" step="0.01" :name="'items[' + index + '][unit_cost]'" x-model.number="item.unit_cost" min="0" required
                                               class="w-28 rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                                    </td>
                                    <td class="px-5 py-3 font-medium text-gray-800 whitespace-nowrap" x-text="'Rs. ' + subtotal(item).toLocaleString('en-US')"></td>
                                    <td class="px-5 py-3">
                                        <button type="button" @click="removeItem(index)" class="text-rose-600 hover:underline text-sm" x-show="items.length > 1">Remove</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-4 border-t border-gray-100 flex justify-end">
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Purchase Total</p>
                        <p class="text-xl font-semibold text-gray-900" x-text="'Rs. ' + total().toLocaleString('en-US')"></p>
                    </div>
                </div>
            </x-card>

            <div class="mt-6 flex justify-end gap-2">
                <a href="{{ route('purchases.index') }}">
                    <x-btn type="button" variant="secondary">Cancel</x-btn>
                </a>
                <x-btn type="submit">Save Purchase</x-btn>
            </div>
        </form>
    </div>
</x-app-layout>
