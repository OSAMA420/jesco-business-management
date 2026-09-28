<x-app-layout>
    <x-slot name="title">Edit User</x-slot>

    <div class="space-y-6 max-w-2xl">
        <div>
            <x-back-link :href="route('users.index')">Back to Users</x-back-link>
            <h2 class="text-xl font-semibold text-gray-800 mt-1">Edit User</h2>
        </div>

        <x-card class="p-6">
            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf
                @method('PUT')
                @include('users._form')

                <div class="mt-6 flex justify-end gap-2">
                    <a href="{{ route('users.index') }}">
                        <x-btn type="button" variant="secondary">Cancel</x-btn>
                    </a>
                    <x-btn type="submit">Update User</x-btn>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
