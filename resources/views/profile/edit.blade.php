<x-app-layout>
    <x-slot name="title">Settings</x-slot>

    <div class="space-y-6 max-w-2xl">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Account Settings</h2>
            <p class="text-sm text-gray-500">Manage your profile information and password.</p>
        </div>

        @if(session('status') === 'profile-updated')
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">Profile updated successfully.</div>
        @elseif(session('status') === 'password-updated')
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">Password updated successfully.</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm px-4 py-3">{{ session('error') }}</div>
        @endif

        <x-card class="p-6">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </x-card>

        <x-card class="p-6">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </x-card>

        <x-card class="p-6">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </x-card>
    </div>
</x-app-layout>
