<x-app-layout>
    <x-slot name="title">{{ $meta['title'] }}</x-slot>

    <div class="space-y-6">
        @include('reports._header')

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card label="Orders" :value="number_format($kpis['orders'])" />
            <x-stat-card label="Sales (billed)" :value="'Rs. '.number_format($kpis['billed'])" />
            <x-stat-card label="Payments Received" :value="'Rs. '.number_format($kpis['received'])" />
            <x-stat-card label="Average Order" :value="'Rs. '.number_format($kpis['average'])" />
        </div>

        <x-card class="p-5 break-inside-avoid">
            <h3 class="font-semibold text-gray-800">Sales by Month</h3>
            <p class="text-xs text-gray-500">Last 6 months, billed amount</p>
            <x-bar-chart :rows="$monthly" label="Sales" />
        </x-card>

        @foreach($tables as $title => $table)
            <x-report-table :title="$title" :columns="$table['columns']" :rows="$table['rows']" />
        @endforeach
    </div>
</x-app-layout>
