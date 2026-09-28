<x-app-layout>
    <x-slot name="title">Add Customer</x-slot>

    <div class="space-y-6 max-w-3xl">
        <div>
            <x-back-link :href="route('customers.index')">Back to Customers</x-back-link>
            <h2 class="text-xl font-semibold text-gray-800 mt-1">Add Customer</h2>
        </div>

        <x-card class="p-6">
            <form method="POST" action="{{ route('customers.store') }}">
                @csrf
                @include('customers._form')

                <div class="mt-6 flex justify-end gap-2">
                    <a href="{{ route('customers.index') }}">
                        <x-btn type="button" variant="secondary">Cancel</x-btn>
                    </a>
                    <x-btn type="submit">Save Customer</x-btn>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
