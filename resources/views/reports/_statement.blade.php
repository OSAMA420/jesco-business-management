{{-- Shared by the customer and supplier statements; $cfg (from ReportController::statement) says which side. --}}
<x-app-layout>
    <x-slot name="title">{{ $party ? $party->name.' Statement' : $meta['title'] }}</x-slot>

    @php
        $money = fn ($v) => ($v < 0 ? '-' : '').'Rs. '.number_format(abs($v));
        $isCustomer = $cfg['kind'] === 'customer';
        $statusColors = ['pending' => 'yellow', 'processing' => 'blue', 'delivered' => 'green', 'ordered' => 'yellow', 'received' => 'green', 'cancelled' => 'red'];
        $partyRoute = $isCustomer ? 'customers.show' : 'suppliers.show';
        $billRoute = $isCustomer ? 'orders.show' : 'purchases.show';
        $words = $isCustomer
            ? ['party' => 'customer', 'bill' => 'order', 'due' => 'Balance Due', 'still' => 'Still owed', 'clear' => 'Nothing owed',
               'billed' => 'Orders in this period', 'paid' => 'Payments in this period',
               'empty' => 'No orders or payments in this period.', 'emptyBills' => 'No orders in this period.']
            : ['party' => 'supplier', 'bill' => 'purchase', 'due' => 'You Owe', 'still' => 'Still to pay', 'clear' => 'Nothing to pay',
               'billed' => 'Purchases received in this period', 'paid' => 'Paid to them in this period',
               'empty' => 'No purchases or payments in this period.', 'emptyBills' => 'No purchases in this period.'];
    @endphp

    <div class="space-y-6">
        @include('reports._header')

        <!-- Customer / supplier picker -->
        <form method="GET" action="{{ route('reports.show', $report) }}" class="print:hidden flex flex-wrap items-center gap-2">
            @foreach(request()->only('period', 'from', 'to') as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <label for="statement_party" class="text-sm font-medium text-gray-700">{{ ucfirst($words['party']) }}</label>
            <select id="statement_party" name="{{ $cfg['kind'] }}" onchange="this.form.requestSubmit()"
                    class="w-full sm:w-80 rounded-lg text-sm focus:border-jesco-500 focus:ring-jesco-500 {{ $party ? 'border-jesco-400 bg-jesco-50 text-jesco-700' : 'border-gray-300' }}">
                <option value="">Choose a {{ $words['party'] }}...</option>
                @foreach($parties as $p)
                    <option value="{{ $p->id }}" @selected($party?->id === $p->id)>{{ $p->name }}{{ $p->company ? ' ('.$p->company.')' : '' }}</option>
                @endforeach
            </select>
            @if($party)
                <a href="{{ route($partyRoute, $party) }}" class="text-sm text-jesco-600 hover:underline">Open {{ $words['party'] }} page</a>
            @endif
        </form>

        @if(! $party)
            <x-card class="p-10 text-center print:hidden">
                <p class="text-sm font-medium text-gray-800">Choose a {{ $words['party'] }} to see their statement.</p>
                <p class="mt-1 text-sm text-gray-500">It lists every {{ $words['bill'] }} and payment for the dates you pick, with the balance after each one.</p>
            </x-card>
        @else
            <!-- Party details (screen and paper) -->
            <x-card class="p-5 break-inside-avoid">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Statement For</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ $party->name }}</p>
                        @if($party->company)<p class="text-sm text-gray-700">{{ $party->company }}</p>@endif
                        @if($party->address)<p class="text-sm text-gray-600 max-w-md">{{ $party->address }}</p>@endif
                        <p class="text-sm text-gray-600">{{ collect([$party->phone, $party->email])->filter()->implode(' · ') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $words['due'] }}</p>
                        <p class="mt-1 text-2xl font-semibold {{ $summary['closing'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $money($summary['closing']) }}</p>
                        <p class="text-xs text-gray-500">at the end of this period</p>
                    </div>
                </div>
            </x-card>

            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
                <x-stat-card label="Opening Balance" :value="$money($summary['opening'])" change="Owed before this period" :positive="$summary['opening'] <= 0" />
                <x-stat-card label="Billed" :value="$money($summary['billed'])" :change="$words['billed']" />
                <x-stat-card label="Paid" :value="$money($summary['paid'])" :change="$words['paid']" />
                <x-stat-card label="Closing Balance" :value="$money($summary['closing'])" :change="$summary['closing'] > 0 ? $words['still'] : $words['clear']" :positive="$summary['closing'] <= 0" />
            </div>

            <!-- Statement -->
            <x-card class="break-inside-avoid">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">Statement</h3>
                    <span class="text-xs text-gray-500">{{ $lines->count() }} {{ Str::plural('entry', $lines->count()) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                                <th class="px-5 py-3 font-medium">Date</th>
                                <th class="px-5 py-3 font-medium">Details</th>
                                <th class="px-5 py-3 font-medium">Paid Via</th>
                                <th class="px-5 py-3 font-medium text-right">Billed</th>
                                <th class="px-5 py-3 font-medium text-right">Paid</th>
                                <th class="px-5 py-3 font-medium text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr class="bg-gray-50/60">
                                <td class="px-5 py-3 text-gray-500"></td>
                                <td class="px-5 py-3 font-medium text-gray-700" colspan="4">Opening balance</td>
                                <td class="px-5 py-3 text-right font-medium text-gray-900 whitespace-nowrap">{{ $money($summary['opening']) }}</td>
                            </tr>
                            @forelse($lines as $line)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $line->date->format('d M Y') }}</td>
                                    <td class="px-5 py-3">
                                        <p class="text-gray-800">
                                            @if($line->bill)
                                                {{ Str::before($line->details, $line->number) }}<a href="{{ route($billRoute, $line->bill) }}" class="text-jesco-600 hover:underline print:text-gray-800 print:no-underline">{{ $line->number }}</a>{{ Str::after($line->details, $line->number) }}
                                            @else
                                                {{ $line->details }}
                                            @endif
                                        </p>
                                        @if($line->note)<p class="text-xs text-gray-500">{{ $line->note }}</p>@endif
                                    </td>
                                    <td class="px-5 py-3 whitespace-nowrap">
                                        @if($line->method)
                                            <p class="text-gray-800">{{ $line->method }}</p>
                                            @if($line->reference)<p class="text-xs text-gray-500">Ref: {{ $line->reference }}</p>@endif
                                        @elseif($line->paid)
                                            <span class="text-xs text-gray-400">Not recorded</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right text-gray-800 whitespace-nowrap">{{ $line->billed ? $money($line->billed) : '' }}</td>
                                    <td class="px-5 py-3 text-right text-emerald-600 whitespace-nowrap">{{ $line->paid ? $money($line->paid) : '' }}</td>
                                    <td class="px-5 py-3 text-right font-medium text-gray-900 whitespace-nowrap">{{ $money($line->balance) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-gray-500">{{ $words['empty'] }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 border-t-2 border-gray-200">
                                <td class="px-5 py-3"></td>
                                <td class="px-5 py-3 font-semibold text-gray-900" colspan="2">Closing balance</td>
                                <td class="px-5 py-3 text-right font-semibold text-gray-900 whitespace-nowrap">{{ $money($summary['billed']) }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-emerald-700 whitespace-nowrap">{{ $money($summary['paid']) }}</td>
                                <td class="px-5 py-3 text-right font-semibold whitespace-nowrap {{ $summary['closing'] > 0 ? 'text-rose-600' : 'text-gray-900' }}">{{ $money($summary['closing']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-card>

            <!-- Bills in the period -->
            <x-card class="break-inside-avoid">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">{{ $cfg['bills_title'] }}</h3>
                    <span class="text-xs text-gray-500">{{ $bills->count() }} {{ Str::plural($words['bill'], $bills->count()) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                                <th class="px-5 py-3 font-medium">{{ $cfg['bill_column'] }}</th>
                                <th class="px-5 py-3 font-medium">Date</th>
                                <th class="px-5 py-3 font-medium">Items</th>
                                <th class="px-5 py-3 font-medium text-right">Total</th>
                                <th class="px-5 py-3 font-medium text-right">Paid</th>
                                <th class="px-5 py-3 font-medium text-right">Due</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($bills as $bill)
                                <tr class="hover:bg-gray-50 align-top">
                                    <td class="px-5 py-3 font-medium whitespace-nowrap">
                                        <a href="{{ route($billRoute, $bill) }}" class="text-jesco-600 hover:underline print:text-gray-800 print:no-underline">{{ $bill->{$cfg['number']} }}</a>
                                    </td>
                                    <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $bill->{$cfg['date']}->format('d M Y') }}</td>
                                    <td class="px-5 py-3 text-gray-700">
                                        @foreach($bill->items as $item)
                                            <p>{{ $item->product?->name ?? 'Deleted product' }} <span class="text-gray-400">x {{ $item->quantity }}</span></p>
                                        @endforeach
                                    </td>
                                    <td class="px-5 py-3 text-right text-gray-800 whitespace-nowrap">{{ $money($bill->total()) }}</td>
                                    <td class="px-5 py-3 text-right text-emerald-600 whitespace-nowrap">{{ $bill->isPayable() ? $money($bill->amountPaid()) : '—' }}</td>
                                    <td class="px-5 py-3 text-right whitespace-nowrap {{ $bill->balanceDue() > 0 ? 'text-rose-600 font-medium' : 'text-gray-500' }}">{{ $bill->isPayable() ? $money($bill->balanceDue()) : '—' }}</td>
                                    <td class="px-5 py-3"><x-badge :color="$statusColors[$bill->status] ?? 'gray'">{{ ucfirst($bill->status) }}</x-badge></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-gray-500">{{ $words['emptyBills'] }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endif
    </div>
</x-app-layout>
