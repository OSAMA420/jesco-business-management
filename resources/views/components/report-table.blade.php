@props(['title', 'columns', 'rows', 'totals' => true, 'empty' => 'Nothing to show for this period.'])

@php
    // Columns labelled "(Rs.)" are money: formatted, right-aligned and totalled (averages aren't summed).
    $isMoney = fn ($i) => str_contains($columns[$i], '(Rs.)');
    $isNumeric = fn ($i) => collect($rows)->every(fn ($r) => is_numeric($r[$i] ?? null) || str_ends_with((string) ($r[$i] ?? ''), '%'));
    $summable = fn ($i) => $isMoney($i) && ! str_starts_with($columns[$i], 'Avg');
    $badges = ['In Stock' => 'green', 'Low Stock' => 'yellow', 'Out of Stock' => 'red'];
@endphp

<x-card class="break-inside-avoid">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-800">{{ $title }}</h3>
        <span class="text-xs text-gray-500">{{ count($rows) }} {{ Str::plural('row', count($rows)) }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                    @foreach($columns as $i => $column)
                        <th class="px-5 py-3 font-medium whitespace-nowrap {{ $i > 0 && $isNumeric($i) ? 'text-right' : '' }}">{{ str_replace(' (Rs.)', '', $column) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $row)
                    <tr class="hover:bg-gray-50">
                        @foreach($row as $i => $cell)
                            <td class="px-5 py-3 {{ $i === 0 ? 'font-medium text-gray-800' : 'text-gray-600 whitespace-nowrap' }} {{ $i > 0 && $isNumeric($i) ? 'text-right' : '' }}">
                                @if(isset($badges[$cell]))
                                    <x-badge :color="$badges[$cell]">{{ $cell }}</x-badge>
                                @elseif($isMoney($i) && is_numeric($cell))
                                    {{ $cell == 0 ? '—' : 'Rs. '.number_format($cell) }}
                                @elseif(is_numeric($cell))
                                    {{ number_format($cell) }}
                                @else
                                    {{ $cell }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="px-5 py-8 text-center text-gray-500">{{ $empty }}</td>
                    </tr>
                @endforelse
            </tbody>
            @if($totals && count($rows) > 1 && collect(array_keys($columns))->contains(fn ($i) => $summable($i)))
                <tfoot>
                    <tr class="bg-gray-50 border-t border-gray-200">
                        @foreach($columns as $i => $column)
                            <td class="px-5 py-3 font-semibold text-gray-900 {{ $i > 0 ? 'text-right whitespace-nowrap' : '' }}">
                                @if($i === 0)
                                    Total
                                @elseif($summable($i))
                                    @php $sum = collect($rows)->sum($i); @endphp
                                    {{ $sum == 0 ? '—' : 'Rs. '.number_format($sum) }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</x-card>
