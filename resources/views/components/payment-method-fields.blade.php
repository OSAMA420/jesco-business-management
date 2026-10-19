@props([
    'methodName' => 'payment_method',
    'referenceName' => 'payment_reference',
    'idPrefix' => 'payment',
])

{{-- "How was it paid" fields shared by every payment form. --}}
@php
    $placeholders = [
        'cash' => 'Optional, e.g. receipt no. 117',
        'bank' => 'e.g. IBFT ref 20481',
        'wallet' => 'e.g. TID 0123456789',
        'cheque' => 'e.g. Cheque no. 004512',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'grid grid-cols-2 gap-4']) }}
     x-data="{ method: @js(old($methodName, 'cash')), placeholders: @js($placeholders) }">
    <div>
        <label for="{{ $idPrefix }}_method" class="block text-sm font-medium text-gray-700 mb-1.5">Paid Via</label>
        <select id="{{ $idPrefix }}_method" name="{{ $methodName }}" x-model="method" required
                class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
            @foreach(\App\Models\Transaction::PAYMENT_METHODS as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        @error($methodName) <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="{{ $idPrefix }}_reference" class="block text-sm font-medium text-gray-700 mb-1.5">Reference No.</label>
        <input type="text" id="{{ $idPrefix }}_reference" name="{{ $referenceName }}" value="{{ old($referenceName) }}" maxlength="100"
               :placeholder="placeholders[method]"
               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
        @error($referenceName) <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
</div>
