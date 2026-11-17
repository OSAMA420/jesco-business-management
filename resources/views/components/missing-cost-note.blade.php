@props(['missing'])

{{-- Shown wherever profit is worked out: sold products that had no cost price count as zero cost. --}}
@if(($missing['products'] ?? 0) > 0)
    <div {{ $attributes->merge(['class' => 'rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm px-4 py-3']) }}>
        {{ $missing['products'] }} {{ Str::plural('product', $missing['products']) }} sold without a cost price (Rs. {{ number_format($missing['sales']) }} of sales).
        Their cost is counted as zero, so the profit shown is higher than the real figure.
        Once the cost is added on the product's Edit page, or a purchase of it is received, these sales use that cost.
    </div>
@endif
