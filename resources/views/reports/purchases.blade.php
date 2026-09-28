<x-app-layout>
    <x-slot name="title">{{ $meta['title'] }}</x-slot>

    <div class="space-y-6">
        @include('reports._header')

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card label="Purchases Received" :value="number_format($kpis['count'])" />
            <x-stat-card label="Total Bought" :value="'Rs. '.number_format($kpis['total'])" />
            <x-stat-card label="Paid to Suppliers" :value="'Rs. '.number_format($kpis['paid'])" />
            <x-stat-card label="Still Owed" :value="'Rs. '.number_format($kpis['due'])" :change="$kpis['due'] > 0 ? 'Payable' : 'All paid'" :positive="$kpis['due'] <= 0" />
        </div>

        @foreach($tables as $title => $table)
            <x-report-table :title="$title" :columns="$table['columns']" :rows="$table['rows']" empty="No purchases were received in this period." />
        @endforeach
    </div>
</x-app-layout>
