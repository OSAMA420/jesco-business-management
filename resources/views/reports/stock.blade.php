<x-app-layout>
    <x-slot name="title">{{ $meta['title'] }}</x-slot>

    <div class="space-y-6">
        @include('reports._header')

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card label="Stock Value (at cost)" :value="'Rs. '.number_format($kpis['cost_value'])" />
            <x-stat-card label="Stock Value (at selling price)" :value="'Rs. '.number_format($kpis['sale_value'])" />
            <x-stat-card label="Low Stock Items" :value="number_format($kpis['low'])" :change="$kpis['low'] ? 'Reorder soon' : null" :positive="false" />
            <x-stat-card label="Out of Stock" :value="number_format($kpis['out'])" :change="$kpis['out'] ? 'Cannot be sold right now' : null" :positive="false" />
        </div>

        <p class="text-xs text-gray-500 print:hidden">Stock levels and value are as of today. "Sold (period)" follows the date filter above.</p>

        @foreach($tables as $title => $table)
            <x-report-table :title="$title" :columns="$table['columns']" :rows="$table['rows']" />
        @endforeach
    </div>
</x-app-layout>
