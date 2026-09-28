@props([
    'action',
    'balance',
    'invoices' => [],
    'title' => 'Record Payment',
])

@php
    $reopen = $errors->has('payment_amount') || $errors->has('payment_date') || $errors->has('apply_to');
@endphp

<div x-data="{
        open: {{ $reopen ? 'true' : 'false' }},
        applyTo: @js(old('apply_to', 'auto')),
        amount: {{ (float) old('payment_amount', max($balance, 0)) }},
        balance: {{ max($balance, 0) }},
        invoices: @js($invoices),
        maxAmount() {
            if (this.applyTo === 'auto') return this.balance;
            const inv = this.invoices.find(i => String(i.id) === String(this.applyTo));
            return inv ? inv.due : 0;
        },
     }">
    <x-btn type="button" @click="open = true; applyTo = 'auto'; amount = balance" :disabled="$balance <= 0" class="disabled:opacity-50 disabled:cursor-not-allowed">
        {{ $title }}
    </x-btn>

    <div x-show="open" x-cloak class="fixed inset-0 z-50" @keydown.escape.window="open = false">
        <div x-show="open" x-transition.opacity @click="open = false" class="fixed inset-0 bg-gray-900/50"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div x-show="open" x-transition class="relative w-full max-w-md bg-white rounded-xl shadow-xl p-6">
                <h3 class="text-base font-semibold text-gray-900">{{ $title }}</h3>
                <p class="mt-1 text-sm text-gray-500">Total balance due: <span class="font-medium text-gray-800">Rs. {{ number_format(max($balance, 0)) }}</span></p>

                <form method="POST" action="{{ $action }}" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label for="apply_to" class="block text-sm font-medium text-gray-700 mb-1.5">Apply to</label>
                        <select id="apply_to" name="apply_to" x-model="applyTo" @change="amount = maxAmount()"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                            <option value="auto">Oldest unpaid bills first (automatic)</option>
                            <template x-for="inv in invoices" :key="inv.id">
                                <option :value="inv.id" x-text="inv.label + ', Rs. ' + inv.due.toLocaleString('en-US') + ' due'"></option>
                            </template>
                        </select>
                        <p class="mt-1.5 text-xs text-gray-500" x-show="applyTo === 'auto'">The amount clears the oldest bills first and moves on to the next one.</p>
                        @error('apply_to') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="party_payment_amount" class="block text-sm font-medium text-gray-700">Amount (Rs.)</label>
                                <button type="button" @click="amount = maxAmount()" class="text-xs font-medium text-jesco-600 hover:underline">Full</button>
                            </div>
                            <input type="number" id="party_payment_amount" name="payment_amount" x-model.number="amount" min="1" :max="maxAmount()" step="0.01" required
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                            @error('payment_amount') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="party_payment_date" class="block text-sm font-medium text-gray-700 mb-1.5">Date</label>
                            <input type="date" id="party_payment_date" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                            @error('payment_date') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="party_payment_note" class="block text-sm font-medium text-gray-700 mb-1.5">Note (optional)</label>
                        <input type="text" id="party_payment_note" name="payment_note" value="{{ old('payment_note') }}" placeholder="e.g. Cash, or cheque no. 004512"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                    </div>

                    <div class="pt-2 flex justify-end gap-2">
                        <x-btn type="button" variant="secondary" @click="open = false">Cancel</x-btn>
                        <x-btn type="submit">Save Payment</x-btn>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
