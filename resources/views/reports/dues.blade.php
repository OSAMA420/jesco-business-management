<x-app-layout>
    <x-slot name="title">{{ $meta['title'] }}</x-slot>

    <div class="space-y-6">
        @include('reports._header', ['datedReport' => false])

        <p class="text-sm text-gray-600 print:hidden">
            Each bill's unpaid amount is placed in a column by how old the bill is.
            Money stuck in the later columns is the hardest to recover, so follow those up first.
        </p>

        @foreach($tables as $title => $table)
            <x-report-table :title="$title" :columns="$table['columns']" :rows="$table['rows']" empty="Nothing is outstanding." />
        @endforeach
    </div>
</x-app-layout>
