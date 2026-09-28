<x-app-layout>
    <x-slot name="title">Add Supplier</x-slot>

    <div class="space-y-6 max-w-3xl">
        <div>
            <x-back-link :href="route('suppliers.index')">Back to Suppliers</x-back-link>
            <h2 class="text-xl font-semibold text-gray-800 mt-1">Add Supplier</h2>
        </div>

        <x-card class="p-6">
            <form method="POST" action="{{ route('suppliers.store') }}">
                @csrf
                @include('suppliers._form')

                <div class="mt-6 flex justify-end gap-2">
                    <a href="{{ route('suppliers.index') }}">
                        <x-btn type="button" variant="secondary">Cancel</x-btn>
                    </a>
                    <x-btn type="submit">Save Supplier</x-btn>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
