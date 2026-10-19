{{-- Cash Book: one account's (or all accounts') money, day by day, with running balances. --}}
@php
    $money = fn ($v) => ($v < 0 ? '-' : '').'Rs. '.number_format(abs($v));
    $accountName = $account === 'all' ? 'All accounts' : \App\Models\Transaction::ACCOUNTS[$account];
    $typeLabels = ['payment_in' => 'Payment received', 'payment_out' => 'Paid to supplier', 'expense' => 'Expense', 'opening' => 'Opening balance'];
@endphp

<x-card>
    <form method="GET" action="{{ route('finance.index') }}" class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center gap-2">
        <input type="hidden" name="tab" value="cashbook">
        <x-filter-select name="account" placeholder="All accounts" :options="\App\Models\Transaction::ACCOUNTS" />
        <x-date-filter />
        <x-btn type="submit" variant="secondary">Apply</x-btn>
        @if(request()->anyFilled(['account', 'period']))
            <a href="{{ route('finance.index', ['tab' => 'cashbook']) }}" class="text-sm text-gray-500 hover:text-gray-700">Clear filters</a>
        @endif
        <span class="ml-auto inline-flex items-center gap-1.5 text-xs text-gray-500">
            How to read this
            <x-info-tip align="right" text="Start from the opening balance, add what came in, take away what went out, and you get the closing balance. Each day shows what the account held that morning and that evening." />
        </span>
    </form>

    <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-gray-100 border-b border-gray-100">
        @foreach([
            ['Opening Balance', $book['opening'], 'text-gray-900', 'At the start of the period'],
            ['Money In', $book['in'], 'text-emerald-600', 'Received'],
            ['Money Out', $book['out'], 'text-rose-600', 'Paid and spent'],
            ['Closing Balance', $book['closing'], $book['closing'] < 0 ? 'text-rose-600' : 'text-gray-900', 'At the end of the period'],
        ] as [$label, $value, $color, $hint])
            <div class="px-5 py-4">
                <p class="text-xs text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-xl font-semibold {{ $color }}">{{ $money($value) }}</p>
                <p class="text-xs text-gray-400">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                    <th class="px-5 py-3 font-medium">Details</th>
                    <th class="px-5 py-3 font-medium">Account</th>
                    <th class="px-5 py-3 font-medium text-right">In</th>
                    <th class="px-5 py-3 font-medium text-right">Out</th>
                    <th class="px-5 py-3 font-medium text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-gray-50/60 border-b border-gray-100">
                    <td class="px-5 py-3 font-medium text-gray-700" colspan="4">Opening balance &middot; {{ $accountName }}</td>
                    <td class="px-5 py-3 text-right font-medium text-gray-900 whitespace-nowrap">{{ $money($book['opening']) }}</td>
                </tr>
                @forelse($book['days'] as $day)
                    <tr class="bg-slate-50 border-y border-gray-200">
                        <td class="px-5 py-2 font-semibold text-gray-800" colspan="2">{{ $day->date->format('l, d M Y') }}</td>
                        <td class="px-5 py-2 text-right text-xs text-gray-500" colspan="3">Morning {{ $money($day->opening) }}</td>
                    </tr>
                    @foreach($day->lines as $line)
                        @php
                            $t = $line->t;
                            $party = $t->customer?->name ?? $t->supplier?->name;
                            $bill = $t->reference?->order_number ?? $t->reference?->purchase_number;
                            $sub = collect([$party, $bill, $t->type === 'expense' ? (\App\Http\Controllers\FinanceController::EXPENSE_CATEGORIES[$t->category] ?? null) : null])->filter()->implode(' · ');
                            $note = in_array($t->description, ['Payment received', 'Payment made', 'Opening balance'], true) || Str::startsWith((string) $t->description, 'Paid in full (recorded before') ? null : $t->description;
                        @endphp
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <p class="text-gray-800">{{ $typeLabels[$t->type] ?? ucfirst($t->type) }}</p>
                                @if($sub || $note)
                                    <p class="text-xs text-gray-500">{{ collect([$sub, $note])->filter()->implode(' · ') }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                @if($t->methodLabel())
                                    <p class="text-gray-700">{{ $t->methodLabel() }}</p>
                                    @if($t->reference_no)<p class="text-xs text-gray-500">Ref: {{ $t->reference_no }}</p>@endif
                                @else
                                    <span class="text-xs text-gray-400">Not recorded</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right text-emerald-600 whitespace-nowrap">{{ $line->in ? $money($line->in) : '' }}</td>
                            <td class="px-5 py-3 text-right text-rose-600 whitespace-nowrap">{{ $line->out ? $money($line->out) : '' }}</td>
                            <td class="px-5 py-3 text-right font-medium text-gray-900 whitespace-nowrap">{{ $money($line->balance) }}</td>
                        </tr>
                    @endforeach
                    <tr class="border-b border-gray-200">
                        <td class="px-5 py-2 text-xs text-gray-500" colspan="2">End of day</td>
                        <td class="px-5 py-2 text-right text-xs text-emerald-600 whitespace-nowrap">{{ $money($day->in) }}</td>
                        <td class="px-5 py-2 text-right text-xs text-rose-600 whitespace-nowrap">{{ $money($day->out) }}</td>
                        <td class="px-5 py-2 text-right text-xs font-semibold text-gray-900 whitespace-nowrap">{{ $money($day->closing) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-gray-500">No money moved in this period.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 border-t-2 border-gray-200">
                    <td class="px-5 py-3 font-semibold text-gray-900" colspan="2">Closing balance &middot; {{ $accountName }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-emerald-700 whitespace-nowrap">{{ $money($book['in']) }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-rose-700 whitespace-nowrap">{{ $money($book['out']) }}</td>
                    <td class="px-5 py-3 text-right font-semibold whitespace-nowrap {{ $book['closing'] < 0 ? 'text-rose-600' : 'text-gray-900' }}">{{ $money($book['closing']) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-card>
