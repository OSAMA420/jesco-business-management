<section>
    <header>
        <h2 class="text-base font-semibold text-gray-900">Profile Information</h2>
        <p class="mt-1 text-sm text-gray-500">Update your account's name and email address.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Name</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name"
                   class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
            @error('name') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                   class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
            @error('email') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="text-sm text-gray-600">
                        Your email address is unverified.
                        <button form="send-verification" class="underline text-jesco-600 hover:text-jesco-700">
                            Click here to re-send the verification email.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-medium text-emerald-600">A new verification link has been sent to your email address.</p>
                    @endif
                </div>
            @endif
        </div>

        <x-btn type="submit">Save</x-btn>
    </form>
</section>
