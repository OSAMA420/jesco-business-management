@php
    $customer = $order->customer;
    $cancelled = $order->status === 'cancelled';
    $total = $order->total();
    $paid = $order->amountPaid();
    $due = $order->balanceDue();
    $payments = $order->payments();
    [$stampText, $stampClasses] = match (true) {
        $cancelled => ['Cancelled', 'text-gray-400 border-gray-300'],
        $order->paymentStatus() === 'paid' => ['Paid', 'text-emerald-600 border-emerald-500'],
        $order->paymentStatus() === 'partial' => ['Partially Paid', 'text-amber-600 border-amber-500'],
        default => ['Unpaid', 'text-rose-600 border-rose-500'],
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoiceNumber }} - {{ $customer?->name }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&family=dancing-script:600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4; margin: 12mm; }
        @media print { html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body class="font-sans antialiased bg-gray-100 text-gray-900 print:bg-white">
    <!-- Toolbar (screen only) -->
    <div class="print:hidden sticky top-0 z-10 bg-white/90 backdrop-blur border-b border-gray-200">
        <div class="max-w-[210mm] mx-auto px-4 py-3 flex items-center justify-between gap-3">
            <a href="{{ route('orders.show', $order) }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to {{ $order->order_number }}</a>
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-jesco-600 text-white hover:bg-jesco-700 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659" /></svg>
                Print / Save as PDF
            </button>
        </div>
    </div>

    <!-- A4 sheet -->
    <main class="max-w-[210mm] mx-auto my-6 bg-white shadow-sm border border-gray-200 px-10 py-10 print:my-0 print:shadow-none print:border-0 print:px-0 print:py-0">
        <!-- Header -->
        <header class="flex items-start justify-between gap-6 pb-6 border-b-2 border-jesco-600">
            <div>
                <p class="text-5xl leading-none text-jesco-600" style="font-family: 'Dancing Script', cursive;">Jesco</p>
                <p class="mt-2 text-sm font-semibold text-gray-900">{{ $company['legal_name'] }}</p>
                <p class="text-xs text-gray-600 max-w-xs">{{ $company['address'] }}</p>
                <p class="text-xs text-gray-600">
                    Phone: {{ $company['phone'] }}
                    @if($company['email']) &middot; {{ $company['email'] }} @endif
                    &middot; {{ $company['website'] }}
                </p>
                @if($company['ntn'])
                    <p class="text-xs text-gray-600">NTN: {{ $company['ntn'] }}</p>
                @endif
            </div>
            <div class="text-right">
                <h1 class="text-3xl font-bold tracking-wide text-gray-900">INVOICE</h1>
                <dl class="mt-3 text-sm grid grid-cols-[auto_auto] gap-x-4 gap-y-1 justify-end">
                    <dt class="text-gray-500">Invoice No.</dt><dd class="font-semibold text-gray-900">{{ $invoiceNumber }}</dd>
                    <dt class="text-gray-500">Order No.</dt><dd class="text-gray-900">{{ $order->order_number }}</dd>
                    <dt class="text-gray-500">Date</dt><dd class="text-gray-900">{{ $order->order_date->format('d M Y') }}</dd>
                    <dt class="text-gray-500">Status</dt><dd class="text-gray-900">{{ ucfirst($order->status) }}</dd>
                </dl>
            </div>
        </header>

        <!-- Bill to + payment stamp -->
        <section class="mt-6 flex items-start justify-between gap-6">
            <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Bill To</p>
            <p class="mt-1 text-base font-semibold text-gray-900">{{ $customer?->name ?? 'Deleted customer' }}</p>
            @if($customer?->company)<p class="text-sm text-gray-700">{{ $customer->company }}</p>@endif
            @if($customer?->address)<p class="text-sm text-gray-600 max-w-sm">{{ $customer->address }}</p>@endif
            <p class="text-sm text-gray-600">
                {{ $customer?->phone }}
                @if($customer?->phone && $customer?->email) &middot; @endif
                {{ $customer?->email }}
            </p>
            </div>
            <div class="mt-3 mr-2 shrink-0 rotate-[-10deg] border-[3px] rounded-lg px-4 py-1.5 text-xl font-bold uppercase tracking-widest opacity-80 {{ $stampClasses }}">
                {{ $stampText }}
            </div>
        </section>

        <!-- Items -->
        <table class="mt-8 w-full text-sm">
            <thead>
                <tr class="bg-gray-100 text-left text-xs uppercase tracking-wide text-gray-600">
                    <th class="px-3 py-2.5 font-semibold w-10">#</th>
                    <th class="px-3 py-2.5 font-semibold">Product</th>
                    <th class="px-3 py-2.5 font-semibold text-right">Qty</th>
                    <th class="px-3 py-2.5 font-semibold text-right">Unit Price</th>
                    <th class="px-3 py-2.5 font-semibold text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($order->items as $i => $item)
                    <tr class="break-inside-avoid">
                        <td class="px-3 py-3 text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-3 py-3">
                            <p class="font-medium text-gray-900">{{ $item->product?->name ?? 'Deleted product' }}</p>
                            @if($item->product?->sku)<p class="text-xs text-gray-500">SKU: {{ $item->product->sku }}</p>@endif
                        </td>
                        <td class="px-3 py-3 text-right text-gray-800">{{ number_format($item->quantity) }}</td>
                        <td class="px-3 py-3 text-right text-gray-800 whitespace-nowrap">Rs. {{ number_format($item->unit_price) }}</td>
                        <td class="px-3 py-3 text-right font-medium text-gray-900 whitespace-nowrap">Rs. {{ number_format($item->subtotal()) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <section class="mt-4 flex flex-col-reverse sm:flex-row print:flex-row justify-between gap-6 break-inside-avoid">
            <div class="sm:max-w-sm text-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Amount in Words</p>
                <p class="mt-1 font-medium text-gray-900">{{ \App\Support\AmountInWords::rupees($total) }}</p>

                @if($payments->isNotEmpty())
                    <p class="mt-5 text-xs font-semibold uppercase tracking-wider text-gray-500">Payments Received</p>
                    <ul class="mt-1 space-y-0.5 text-gray-700">
                        @foreach($payments as $payment)
                            <li class="flex justify-between gap-6">
                                {{-- The migration's internal note isn't meant for customers. --}}
                                <span>{{ $payment->transaction_date->format('d M Y') }} &middot; {{ Str::startsWith((string) $payment->description, 'Paid in full (recorded before') ? 'Payment received' : $payment->description }}</span>
                                <span class="whitespace-nowrap">Rs. {{ number_format(abs($payment->amount)) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if($order->notes)
                    <p class="mt-5 text-xs font-semibold uppercase tracking-wider text-gray-500">Notes</p>
                    <p class="mt-1 text-gray-700">{{ $order->notes }}</p>
                @endif
            </div>

            <dl class="w-full sm:w-72 print:w-72 text-sm">
                <div class="flex justify-between py-1.5"><dt class="text-gray-600">Subtotal</dt><dd class="text-gray-900">Rs. {{ number_format($total) }}</dd></div>
                <div class="flex justify-between py-2 border-t-2 border-gray-900 mt-1"><dt class="font-bold text-gray-900">Total</dt><dd class="font-bold text-gray-900 text-base">Rs. {{ number_format($total) }}</dd></div>
                @unless($cancelled)
                    <div class="flex justify-between py-1.5"><dt class="text-gray-600">Paid</dt><dd class="text-gray-900">Rs. {{ number_format($paid) }}</dd></div>
                    <div class="flex justify-between py-2 px-3 -mx-3 rounded-md {{ $due > 0 ? 'bg-rose-50' : 'bg-emerald-50' }}">
                        <dt class="font-semibold {{ $due > 0 ? 'text-rose-700' : 'text-emerald-700' }}">Balance Due</dt>
                        <dd class="font-semibold {{ $due > 0 ? 'text-rose-700' : 'text-emerald-700' }}">Rs. {{ number_format($due) }}</dd>
                    </div>
                @endunless
            </dl>
        </section>

        <!-- Footer -->
        <footer class="mt-16 flex items-end justify-between gap-6 break-inside-avoid">
            <div class="text-xs text-gray-500">
                <p class="font-medium text-gray-700">Thank you for your business.</p>
                <p>{{ $company['tagline'] }}.</p>
            </div>
            <div class="text-center">
                <div class="w-48 border-t border-gray-400"></div>
                <p class="mt-1 text-xs text-gray-500">Authorised Signature</p>
            </div>
        </footer>

        <p class="mt-8 text-center text-[10px] text-gray-400">Printed {{ now()->format('d M Y, h:i A') }} &middot; Computer-generated invoice</p>
    </main>
</body>
</html>
