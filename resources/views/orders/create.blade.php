<x-app-layout>
    <x-slot name="title">New Order</x-slot>

    <div class="space-y-6 max-w-4xl"
         x-data="{
            items: [{ product_id: '', quantity: 1, unit_price: 0 }],
            amountPaid: {{ (float) old('amount_paid', 0) }},
            customers: @js($customers->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'company' => $c->company])->values()),
            customerId: @js((string) old('customer_id', '')),
            newCustomerOpen: false,
            newCustomer: { name: '', company: '', phone: '', email: '', address: '' },
            newCustomerErrors: {},
            savingCustomer: false,
            customerLabel(c) { return c.name + (c.company ? ' (' + c.company + ')' : ''); },
            onCustomerChange(event) {
                if (event.target.value !== '__new') {
                    this.customerId = event.target.value;
                    return;
                }
                // Not a real choice: put back whatever was selected before and open the popup.
                event.target.value = this.customerId;
                this.newCustomer = { name: '', company: '', phone: '', email: '', address: '' };
                this.newCustomerErrors = {};
                this.newCustomerOpen = true;
                this.$nextTick(() => this.$refs.newCustomerName.focus());
            },
            async saveCustomer() {
                this.savingCustomer = true;
                this.newCustomerErrors = {};
                try {
                    const response = await fetch(@js(route('customers.quick-store')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify(this.newCustomer),
                    });
                    const data = await response.json();
                    if (response.status === 422) {
                        this.newCustomerErrors = data.errors;
                        return;
                    }
                    if (!response.ok) throw new Error();
                    this.customers.push(data);
                    this.customers.sort((a, b) => a.name.localeCompare(b.name));
                    this.customerId = String(data.id);
                    this.$nextTick(() => { this.$refs.customerSelect.value = this.customerId; });
                    this.newCustomerOpen = false;
                } catch (e) {
                    this.newCustomerErrors = { name: ['Could not save the customer. Please try again.'] };
                } finally {
                    this.savingCustomer = false;
                }
            },
            products: @js($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'price' => (float) $p->selling_price, 'stock' => $p->stock_quantity])),
            addItem() { this.items.push({ product_id: '', quantity: 1, unit_price: 0 }); },
            removeItem(i) { if (this.items.length > 1) this.items.splice(i, 1); },
            productInfo(id) { return this.products.find(p => p.id == id); },
            onProductChange(item) {
                const p = this.productInfo(item.product_id);
                item.unit_price = p ? p.price : 0;
            },
            subtotal(item) { return (Number(item.quantity) || 0) * (Number(item.unit_price) || 0); },
            total() { return this.items.reduce((sum, item) => sum + this.subtotal(item), 0); },
            paymentStatus() {
                const total = this.total(), paid = Number(this.amountPaid) || 0;
                if (total > 0 && paid >= total) return 'paid';
                return paid > 0 ? 'partial' : 'unpaid';
            },
         }">
        <div>
            <x-back-link :href="route('orders.index')">Back to Orders</x-back-link>
            <h2 class="text-xl font-semibold text-gray-800 mt-1">New Order</h2>
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

        <form method="POST" action="{{ route('orders.store') }}">
            @csrf

            <x-card class="p-6 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-1.5">Customer</label>
                        <select id="customer_id" name="customer_id" required x-ref="customerSelect" @change="onCustomerChange($event)"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                            <option value="">Select a customer</option>
                            <option value="__new" class="font-medium text-jesco-600">+ Add new customer</option>
                            <template x-for="c in customers" :key="c.id">
                                <option :value="String(c.id)" :selected="String(c.id) === customerId" x-text="customerLabel(c)"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label for="order_date" class="block text-sm font-medium text-gray-700 mb-1.5">Order Date</label>
                        <input type="date" id="order_date" name="order_date" value="{{ old('order_date', now()->format('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                        <select id="status" name="status" required class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                            @foreach(\App\Http\Controllers\OrderController::STATUSES as $status)
                                <option value="{{ $status }}" @selected(old('status', 'pending') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="amount_paid" class="block text-sm font-medium text-gray-700">Amount Paid Now (Rs.)</label>
                            <button type="button" @click="amountPaid = total()" class="text-xs font-medium text-jesco-600 hover:underline">Full</button>
                        </div>
                        <input type="number" id="amount_paid" name="amount_paid" x-model.number="amountPaid" min="0" step="0.01" :max="total()"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                        @error('amount_paid') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                        <div class="mt-3" x-cloak :class="Number(amountPaid) > 0 ? '' : 'hidden'">
                            <x-payment-method-fields method-name="paid_method" reference-name="paid_reference" id-prefix="paid" />
                        </div>
                        <p class="mt-1.5 text-xs text-gray-500">
                            Payment status:
                            <span class="font-medium"
                                  :class="{ 'text-emerald-600': paymentStatus() === 'paid', 'text-amber-600': paymentStatus() === 'partial', 'text-rose-600': paymentStatus() === 'unpaid' }"
                                  x-text="paymentStatus().charAt(0).toUpperCase() + paymentStatus().slice(1)"></span>
                            <span x-show="paymentStatus() === 'partial'" x-text="'(Rs. ' + Math.max(0, total() - amountPaid).toLocaleString('en-US') + ' due)'"></span>
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1.5">Notes (optional)</label>
                        <input type="text" id="notes" name="notes" value="{{ old('notes') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                    </div>
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
                                <th class="px-5 py-3 font-medium">Unit Price</th>
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
                                                <option :value="p.id" x-text="p.name + ' (' + p.sku + ') — ' + p.stock + ' in stock'"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td class="px-5 py-3">
                                        <input type="number" :name="'items[' + index + '][quantity]'" x-model.number="item.quantity" min="1" required
                                               class="w-24 rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                                    </td>
                                    <td class="px-5 py-3">
                                        <input type="number" step="0.01" :name="'items[' + index + '][unit_price]'" x-model.number="item.unit_price" min="0" required
                                               class="w-28 rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                                    </td>
                                    <td class="px-5 py-3 font-medium text-gray-800" x-text="'Rs. ' + subtotal(item).toLocaleString('en-US')"></td>
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
                        <p class="text-sm text-gray-500">Order Total</p>
                        <p class="text-xl font-semibold text-gray-900" x-text="'Rs. ' + total().toLocaleString('en-US')"></p>
                    </div>
                </div>
            </x-card>

            <div class="mt-6 flex justify-end gap-2">
                <a href="{{ route('orders.index') }}">
                    <x-btn type="button" variant="secondary">Cancel</x-btn>
                </a>
                <x-btn type="submit">Create Order</x-btn>
            </div>
        </form>
        <!-- Add new customer popup -->
        <div x-show="newCustomerOpen" x-cloak class="fixed inset-0 z-50" @keydown.escape.window="newCustomerOpen = false">
            <div x-show="newCustomerOpen" x-transition.opacity @click="newCustomerOpen = false" class="fixed inset-0 bg-gray-900/50"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div x-show="newCustomerOpen" x-transition class="relative w-full max-w-lg bg-white rounded-xl shadow-xl p-6">
                    <h3 class="text-base font-semibold text-gray-900">Add New Customer</h3>
                    <p class="mt-1 text-sm text-gray-500">They'll be selected for this order. Items you've already added stay as they are.</p>

                    <form @submit.prevent="saveCustomer()" class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="new_customer_name" class="block text-sm font-medium text-gray-700 mb-1.5">Customer Name</label>
                            <input type="text" id="new_customer_name" x-ref="newCustomerName" x-model="newCustomer.name" required
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                            <template x-if="newCustomerErrors.name"><p class="mt-1.5 text-sm text-rose-600" x-text="newCustomerErrors.name[0]"></p></template>
                        </div>
                        <div>
                            <label for="new_customer_company" class="block text-sm font-medium text-gray-700 mb-1.5">Company / Workshop</label>
                            <input type="text" id="new_customer_company" x-model="newCustomer.company"
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                            <template x-if="newCustomerErrors.company"><p class="mt-1.5 text-sm text-rose-600" x-text="newCustomerErrors.company[0]"></p></template>
                        </div>
                        <div>
                            <label for="new_customer_phone" class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
                            <input type="text" id="new_customer_phone" x-model="newCustomer.phone" placeholder="0300-1234567"
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                            <template x-if="newCustomerErrors.phone"><p class="mt-1.5 text-sm text-rose-600" x-text="newCustomerErrors.phone[0]"></p></template>
                        </div>
                        <div>
                            <label for="new_customer_email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                            <input type="email" id="new_customer_email" x-model="newCustomer.email"
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                            <template x-if="newCustomerErrors.email"><p class="mt-1.5 text-sm text-rose-600" x-text="newCustomerErrors.email[0]"></p></template>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="new_customer_address" class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
                            <textarea id="new_customer_address" x-model="newCustomer.address" rows="2"
                                      class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500"></textarea>
                            <template x-if="newCustomerErrors.address"><p class="mt-1.5 text-sm text-rose-600" x-text="newCustomerErrors.address[0]"></p></template>
                        </div>
                        <div class="sm:col-span-2 pt-1 flex justify-end gap-2">
                            <x-btn type="button" variant="secondary" @click="newCustomerOpen = false">Cancel</x-btn>
                            <x-btn type="submit" ::disabled="savingCustomer" class="disabled:opacity-60">
                                <span x-text="savingCustomer ? 'Saving...' : 'Save Customer'"></span>
                            </x-btn>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
