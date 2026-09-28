<section>
    <header>
        <h2 class="text-base font-semibold text-gray-900">Update Password</h2>
        <p class="mt-1 text-sm text-gray-500">Ensure your account is using a long, random password to stay secure.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="block text-sm font-medium text-gray-700 mb-1.5">Current Password</label>
            <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password"
                   class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
            @error('current_password', 'updatePassword') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="update_password_password" class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
            <input id="update_password_password" name="password" type="password" autocomplete="new-password"
                   class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
            @error('password', 'updatePassword') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                   class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
            @error('password_confirmation', 'updatePassword') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <x-btn type="submit">Save</x-btn>
    </form>
</section>
