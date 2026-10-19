{{-- Finance overview: where the money is, where it came from and went this period, and the profit. --}}
@php
    $money = fn ($v) => ($v < 0 ? '-' : '').'Rs. '.number_format(abs($v));
    $accountIcons = [
        'cash' => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z',
        'bank' => 'M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z',
        'wallet' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
    ];
    $flowRows = fn ($byAccount) => collect(\App\Models\Transaction::ACCOUNTS)->map(fn ($label, $key) => [$label, $byAccount[$key]])
        ->when($byAccount['none'] > 0, fn ($c) => $c->put('none', ['Method not recorded', $byAccount['none']]));
    $moneyIn = (float) $flows['in']->sum();
    $moneyOut = (float) $flows['suppliers']->sum() + (float) $flows['expenses']->sum();
@endphp

<!-- Where the money is -->
<section class="space-y-3">
    <div class="flex items-center gap-2">
        <h3 class="text-base font-semibold text-gray-800">Money You Have Now</h3>
        <x-info-tip text="Each account starts from its opening balance, then adds payments received and takes away supplier payments and expenses paid from it. Cheques count as Bank. These are today's balances, whatever the date filter says." />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach($accounts['list'] as $key => $account)
            <x-card class="p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500">{{ $account['label'] }}</p>
                        <p class="mt-1 text-2xl font-semibold {{ $account['balance'] < 0 ? 'text-rose-600' : 'text-gray-900' }}">{{ $money($account['balance']) }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $accountIcons[$key] }}" /></svg>
                    </div>
                </div>
                <dl class="mt-3 space-y-1 text-xs">
                    <div class="flex justify-between"><dt class="text-gray-500">Opening balance</dt><dd class="text-gray-700">{{ $money($account['opening']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">In ({{ strtolower($periodLabel) }})</dt><dd class="text-emerald-600">+{{ $money($account['in']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Out ({{ strtolower($periodLabel) }})</dt><dd class="text-rose-600">-{{ $money($account['out']) }}</dd></div>
                </dl>
                <a href="{{ route('finance.index', ['tab' => 'cashbook', 'account' => $key] + request()->only('period', 'from', 'to')) }}"
                   class="mt-3 inline-block text-xs font-medium text-jesco-600 hover:underline">Open {{ $account['label'] }} book</a>
            </x-card>
        @endforeach

        {{-- Not <x-card>: its white background would win over the dark one here. --}}
        <div class="rounded-xl bg-slate-900 text-white shadow-sm p-5">
            <p class="text-sm text-slate-300">Total</p>
            <p class="mt-1 text-2xl font-semibold {{ $accounts['total'] < 0 ? 'text-rose-300' : 'text-white' }}">{{ $money($accounts['total']) }}</p>
            <p class="mt-3 text-xs text-slate-300 leading-relaxed">Cash, bank and wallet added together.</p>
            <x-btn type="button" variant="secondary" class="mt-4" @click="openingOpen = true">Set Opening Balances</x-btn>
        </div>
    </div>

    @if($accounts['unassignedCount'] > 0)
        <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm px-4 py-3">
            {{ $accounts['unassignedCount'] }} older {{ Str::plural('entry', $accounts['unassignedCount']) }} ({{ $money($accounts['unassigned']) }} net) {{ $accounts['unassignedCount'] === 1 ? 'was' : 'were' }} recorded before "Paid Via" existed, so {{ $accounts['unassignedCount'] === 1 ? "it isn't" : "they aren't" }} in any account above.
        </div>
    @endif
</section>

<!-- This period -->
<section class="space-y-3">
    <div class="flex items-center gap-2">
        <h3 class="text-base font-semibold text-gray-800">{{ $periodLabel }}: Money In and Out</h3>
        <x-info-tip text="Only real money moving counts here: payments from customers, payments to suppliers and expenses. A new order or purchase is a bill, not money, until it is paid." />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Money in -->
        <x-card class="p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-700">Money In</p>
                <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" /></svg>
                </span>
            </div>
            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $money($moneyIn) }}</p>
            <p class="text-xs text-gray-500">Payments received from customers</p>
            <dl class="mt-4 pt-3 border-t border-gray-100 space-y-1.5 text-sm">
                @foreach($flowRows($flows['in']) as [$label, $amount])
                    <div class="flex justify-between"><dt class="text-gray-600">{{ $label }}</dt><dd class="font-medium text-gray-900">{{ $money($amount) }}</dd></div>
                @endforeach
            </dl>
        </x-card>

        <!-- Money out -->
        <x-card class="p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-700">Money Out</p>
                <span class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" /></svg>
                </span>
            </div>
            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $money($moneyOut) }}</p>
            <p class="text-xs text-gray-500">Paid to suppliers and spent on expenses</p>
            <dl class="mt-4 pt-3 border-t border-gray-100 space-y-1.5 text-sm">
                <div class="flex justify-between"><dt class="font-medium text-gray-700">Paid to suppliers</dt><dd class="font-medium text-gray-900">{{ $money($flows['suppliers']->sum()) }}</dd></div>
                @foreach($flowRows($flows['suppliers']) as [$label, $amount])
                    @if($amount > 0)<div class="flex justify-between pl-3"><dt class="text-gray-500">{{ $label }}</dt><dd class="text-gray-700">{{ $money($amount) }}</dd></div>@endif
                @endforeach
                <div class="flex justify-between pt-1"><dt class="font-medium text-gray-700">Expenses</dt><dd class="font-medium text-gray-900">{{ $money($flows['expenses']->sum()) }}</dd></div>
                @foreach($flowRows($flows['expenses']) as [$label, $amount])
                    @if($amount > 0)<div class="flex justify-between pl-3"><dt class="text-gray-500">{{ $label }}</dt><dd class="text-gray-700">{{ $money($amount) }}</dd></div>@endif
                @endforeach
            </dl>
        </x-card>

        <!-- Net -->
        <x-card class="p-5">
            <div class="flex items-center gap-2">
                <p class="text-sm font-medium text-gray-700">Net Cash Flow</p>
                <x-info-tip align="right" text="Money In minus Money Out. A minus figure is not a loss: it means more was paid out than collected in this period, for example while paying for stock that hasn't sold yet." />
            </div>
            <p class="mt-1 text-2xl font-semibold {{ $moneyIn - $moneyOut < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $money($moneyIn - $moneyOut) }}</p>
            <p class="mt-3 text-sm text-gray-600 leading-relaxed">
                {{ $money($moneyIn) }} came in and {{ $money($moneyOut) }} went out,
                so your accounts hold <span class="font-medium text-gray-900">{{ $money(abs($moneyIn - $moneyOut)) }} {{ $moneyIn - $moneyOut < 0 ? 'less' : 'more' }}</span>
                than at the start of this period.
            </p>
        </x-card>
    </div>
</section>

<!-- Profit -->
<section class="space-y-3">
    <div class="flex items-center gap-2">
        <h3 class="text-base font-semibold text-gray-800">{{ $periodLabel }}: Profit</h3>
        <x-info-tip text="Profit counts what was sold (billed), not what was collected. It takes away what those goods cost when they were bought, then the expenses. Cancelled orders are left out." />
    </div>
    <x-card>
        <dl class="divide-y divide-gray-100 text-sm">
            <div class="px-5 py-3 flex justify-between"><dt class="text-gray-700">Sales (billed)</dt><dd class="text-gray-900">{{ $money($profit['revenue']) }}</dd></div>
            <div class="px-5 py-3 flex justify-between"><dt class="text-gray-700">Less: cost of the goods sold</dt><dd class="text-gray-900">-{{ $money($profit['cogs']) }}</dd></div>
            <div class="px-5 py-3 flex justify-between bg-gray-50"><dt class="font-semibold text-gray-900">Gross profit</dt><dd class="font-semibold text-gray-900">{{ $money($profit['gross']) }}</dd></div>
            <div class="px-5 py-3 flex justify-between"><dt class="text-gray-700">Less: expenses</dt><dd class="text-gray-900">-{{ $money($profit['expenses']) }}</dd></div>
            <div class="px-5 py-3 flex flex-wrap items-center justify-between gap-2 border-t-2 border-gray-200">
                <dt class="text-base font-semibold text-gray-900">Net {{ $profit['net'] >= 0 ? 'profit' : 'loss' }}</dt>
                <dd class="flex items-center gap-4">
                    <a href="{{ route('reports.show', ['report' => 'profit-loss'] + request()->only('period', 'from', 'to')) }}" class="text-xs font-medium text-jesco-600 hover:underline">Full Profit &amp; Loss report</a>
                    <span class="text-base font-semibold {{ $profit['net'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $money($profit['net']) }}</span>
                </dd>
            </div>
        </dl>
    </x-card>
</section>
