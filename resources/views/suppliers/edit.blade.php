<x-app-layout>
    <x-slot name="title">Edit {{ $supplier->name }}</x-slot>

    <div class="space-y-6 max-w-3xl">
        <div>
            <x-back-link :href="route('suppliers.show', $supplier->id)">Back to {{ $supplier->name }}</x-back-link>
            <h2 class="text-xl font-semibold text-gray-800 mt-1">Edit Supplier</h2>
        </div>

        <x-card class="p-6">
            <form method="POST" action="{{ route('suppliers.update', $supplier->id) }}">
                @csrf
                @method('PUT')
                @include('suppliers._form')

                <div class="mt-6 flex justify-end gap-2">
                    <a href="{{ route('suppliers.show', $supplier->id) }}">
                        <x-btn type="button" variant="secondary">Cancel</x-btn>
                    </a>
                    <x-btn type="submit">Update Supplier</x-btn>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
