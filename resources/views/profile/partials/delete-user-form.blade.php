<section class="space-y-4">
    <header>
        <h2 class="text-base font-semibold text-gray-900">Delete Account</h2>
        <p class="mt-1 text-sm text-gray-500">
            Once your account is deleted, all of its resources and data will be permanently deleted.
            Before deleting your account, please download any data or information that you wish to retain.
        </p>
    </header>

    @if($user->isLastRemainingAdmin())
        <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-sm px-4 py-3">
            You are the only admin account. Add another admin from <a href="{{ route('users.index') }}" class="underline font-medium">Users</a> before you can delete this one.
        </div>
    @else
        <div x-data="{ show: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
            <x-btn type="button" variant="danger" @click="show = true">Delete Account</x-btn>

            <div x-show="show" x-cloak class="fixed inset-0 z-50">
                <div x-show="show" x-transition.opacity @click="show = false" class="fixed inset-0 bg-gray-900/50"></div>

                <div class="fixed inset-0 flex items-center justify-center p-4">
                    <div x-show="show" x-transition class="relative w-full max-w-md bg-white rounded-xl shadow-xl p-6">
                        <form method="post" action="{{ route('profile.destroy') }}">
                            @csrf
                            @method('delete')

                            <h3 class="text-base font-semibold text-gray-900">Are you sure you want to delete your account?</h3>
                            <p class="mt-1.5 text-sm text-gray-500">
                                This cannot be undone. Enter your password to confirm.
                            </p>

                            <div class="mt-5">
                                <label for="password" class="sr-only">Password</label>
                                <input id="password" name="password" type="password" placeholder="Password"
                                       class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
                                @error('password', 'userDeletion') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="mt-6 flex justify-end gap-2">
                                <x-btn type="button" variant="secondary" @click="show = false">Cancel</x-btn>
                                <x-btn type="submit" variant="danger">Delete Account</x-btn>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</section>
