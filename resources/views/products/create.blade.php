<x-app-layout>
    <x-slot name="title">Add Product</x-slot>

    <div class="space-y-6 max-w-3xl">
        <div>
            <x-back-link :href="route('products.index')">Back to Products</x-back-link>
            <h2 class="text-xl font-semibold text-gray-800 mt-1">Add Product</h2>
        </div>

        <x-card class="p-6">
            <form method="POST" action="{{ route('products.store') }}">
                @csrf
                @include('products._form')

                <div class="mt-6 flex justify-end gap-2">
                    <a href="{{ route('products.index') }}">
                        <x-btn type="button" variant="secondary">Cancel</x-btn>
                    </a>
                    <x-btn type="submit">Save Product</x-btn>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
