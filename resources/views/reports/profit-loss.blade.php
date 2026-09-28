<x-app-layout>
    <x-slot name="title">{{ $meta['title'] }}</x-slot>

    @php
        $s = $statement;
        $margin = fn ($v) => $s['revenue'] > 0 ? round($v / $s['revenue'] * 100, 1).'%' : '—';
        $money = fn ($v) => ($v < 0 ? '-' : '').'Rs. '.number_format(abs($v));
    @endphp

    <div class="space-y-6">
        @include('reports._header')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <x-card class="lg:col-span-2 break-inside-avoid">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Profit &amp; Loss Statement</h3>
                </div>
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        <tr>
                            <td class="px-5 py-3 text-gray-800">Sales revenue</td>
                            <td class="px-5 py-3 text-right text-gray-900">{{ $money($s['revenue']) }}</td>
                            <td class="px-5 py-3 text-right text-xs text-gray-400 w-20">100%</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 text-gray-800">Less: cost of goods sold</td>
                            <td class="px-5 py-3 text-right text-gray-900">{{ $money(-$s['cogs']) }}</td>
                            <td class="px-5 py-3 text-right text-xs text-gray-400">{{ $margin($s['cogs']) }}</td>
                        </tr>
                        <tr class="bg-gray-50">
                            <td class="px-5 py-3 font-semibold text-gray-900">Gross profit</td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-900">{{ $money($s['gross']) }}</td>
                            <td class="px-5 py-3 text-right text-xs font-medium text-gray-600">{{ $margin($s['gross']) }}</td>
                        </tr>
                        @foreach($s['expenses'] as [$label, $amount])
                            <tr>
                                <td class="px-5 py-2.5 pl-9 text-gray-600">{{ $label }}</td>
                                <td class="px-5 py-2.5 text-right text-gray-700">{{ $money(-$amount) }}</td>
                                <td class="px-5 py-2.5 text-right text-xs text-gray-400">{{ $margin($amount) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td class="px-5 py-3 text-gray-800">Less: total expenses</td>
                            <td class="px-5 py-3 text-right text-gray-900">{{ $money(-$s['expenseTotal']) }}</td>
                            <td class="px-5 py-3 text-right text-xs text-gray-400">{{ $margin($s['expenseTotal']) }}</td>
                        </tr>
                        <tr class="border-t-2 border-gray-300">
                            <td class="px-5 py-4 text-base font-semibold text-gray-900">Net {{ $s['net'] >= 0 ? 'profit' : 'loss' }}</td>
                            <td class="px-5 py-4 text-right text-base font-semibold {{ $s['net'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $money($s['net']) }}</td>
                            <td class="px-5 py-4 text-right text-xs font-medium text-gray-600">{{ $margin($s['net']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </x-card>

            <div class="space-y-4">
                <x-stat-card label="Gross Profit" :value="$money($s['gross'])" :change="$margin($s['gross']).' of sales'" />
                <x-stat-card label="Net {{ $s['net'] >= 0 ? 'Profit' : 'Loss' }}" :value="$money($s['net'])" :change="$margin($s['net']).' of sales'" :positive="$s['net'] >= 0" />
                <p class="text-xs text-gray-500 leading-relaxed">
                    Cost of goods sold uses the purchase cost of each item at the time it was sold.
                    Only orders that aren't cancelled are counted.
                </p>
            </div>
        </div>

        @foreach(collect($tables)->reject(fn ($t) => $t['csvOnly'] ?? false) as $title => $table)
            <x-report-table :title="$title" :columns="$table['columns']" :rows="$table['rows']" />
        @endforeach
    </div>
</x-app-layout>
