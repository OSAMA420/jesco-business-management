@props([
    'total',
    'paid',
    'action',
    'payments' => collect(),
    'canPay' => true,
    'notice' => null,
    'paidLabel' => 'Paid',
    'removeUrl' => null,
])

@php
    $due = max(0, $total - $paid);
    $percent = $total > 0 ? min(100, round($paid / $total * 100)) : 0;
    [$statusLabel, $statusColor] = match (true) {
        $total > 0 && $due <= 0 => ['Paid', 'green'],
        $paid > 0 => ['Partial', 'yellow'],
        default => ['Unpaid', 'red'],
    };
    $reopen = $errors->has('payment_amount') || $errors->has('payment_date');
@endphp

<x-card x-data="{ recording: {{ $reopen ? 'true' : 'false' }}, amount: {{ old('payment_amount', $due) ?: 0 }} }">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <h3 class="font-semibold text-gray-800">Payment</h3>
            @if($canPay || $paid > 0)
                <x-badge :color="$statusColor">{{ $statusLabel }}</x-badge>
            @endif
        </div>
        @if($canPay && $due > 0)
            <x-btn type="button" @click="recording = true; amount = {{ $due }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Record Payment
            </x-btn>
        @endif
    </div>

    @if($notice)
        <div class="px-5 py-4 text-sm text-gray-600 border-b border-gray-100">{{ $notice }}</div>
    @endif

    @if($canPay || $paid > 0)
        <div class="px-5 py-5">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <p class="text-xs text-gray-500">Total</p>
                    <p class="mt-0.5 font-semibold text-gray-900">Rs. {{ number_format($total) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">{{ $paidLabel }}</p>
                    <p class="mt-0.5 font-semibold text-emerald-600">Rs. {{ number_format($paid) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Balance Due</p>
                    <p class="mt-0.5 font-semibold {{ $due > 0 ? 'text-rose-600' : 'text-gray-900' }}">Rs. {{ number_format($due) }}</p>
                </div>
            </div>
            <div class="mt-4 h-2 rounded-full bg-gray-100 overflow-hidden">
                <div class="h-full rounded-full bg-emerald-500 transition-all duration-700" style="width: {{ $percent }}%"></div>
            </div>
            <p class="mt-1.5 text-xs text-gray-500">{{ $percent }}% paid</p>
        </div>

        <div class="overflow-x-auto border-t border-gray-100">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                        <th class="px-5 py-3 font-medium">Date</th>
                        <th class="px-5 py-3 font-medium">Amount</th>
                        <th class="px-5 py-3 font-medium">Note</th>
                        @if($removeUrl)
                            <th class="px-5 py-3 font-medium"></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payments as $payment)
                    <tr>
                        <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $payment->transaction_date->format('Y-m-d') }}</td>
                        <td class="px-5 py-3 font-medium text-emerald-600 whitespace-nowrap">Rs. {{ number_format(abs($payment->amount)) }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $payment->description }}</td>
                        @if($removeUrl)
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ $removeUrl($payment) }}"
                                      onsubmit="return confirm('Remove this payment of Rs. {{ number_format(abs($payment->amount)) }}? The balance due will go back up.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:underline text-sm font-medium">Remove</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $removeUrl ? 4 : 3 }}" class="px-5 py-6 text-center text-gray-500">No payments recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <!-- Record payment modal -->
    <div x-show="recording" x-cloak class="fixed inset-0 z-50" @keydown.escape.window="recording = false">
        <div x-show="recording" x-transition.opacity @click="recording = false" class="fixed inset-0 bg-gray-900/50"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div x-show="recording" x-transition class="relative w-full max-w-md bg-white rounded-xl shadow-xl p-6">
                <h3 class="text-base font-semibold text-gray-900">Record Payment</h3>
                <p class="mt-1 text-sm text-gray-500">Balance due: <span class="font-medium text-gray-800">Rs. {{ number_format($due) }}</span></p>

                <form method="POST" action="{{ $action }}" class="mt-5 space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="payment_amount" class="block text-sm font-medium text-gray-700">Amount (Rs.)</label>
                                <button type="button" @click="amount = {{ $due }}" class="text-xs font-medium text-jesco-600 hover:underline">Full</button>
                            </div>
                            <input type="number" id="payment_amount" name="payment_amount" x-model.number="amount" min="1" max="{{ $due }}" step="0.01" required
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                            @error('payment_amount') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="payment_date" class="block text-sm font-medium text-gray-700 mb-1.5">Date</label>
                            <input type="date" id="payment_date" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                            @error('payment_date') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">
                        After this payment:
                        <span class="font-medium" :class="amount >= {{ $due }} ? 'text-emerald-600' : 'text-amber-600'"
                              x-text="amount >= {{ $due }} ? 'Fully paid' : 'Rs. ' + Math.max(0, {{ $due }} - (Number(amount) || 0)).toLocaleString('en-US') + ' still due'"></span>
                    </p>
                    <div>
                        <label for="payment_note" class="block text-sm font-medium text-gray-700 mb-1.5">Note (optional)</label>
                        <input type="text" id="payment_note" name="payment_note" value="{{ old('payment_note') }}" placeholder="e.g. Cash, or cheque no. 004512"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                    </div>
                    <div class="pt-2 flex justify-end gap-2">
                        <x-btn type="button" variant="secondary" @click="recording = false">Cancel</x-btn>
                        <x-btn type="submit">Save Payment</x-btn>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-card>
