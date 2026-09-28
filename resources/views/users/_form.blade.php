@php
    /** @var \App\Models\User|null $user */
    $user ??= null;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
        <input type="text" id="name" name="name" value="{{ old('name', $user?->name) }}" required
               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
        @error('name') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email', $user?->email) }}" required
               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
        @error('email') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">
            Password {{ $user ? '(leave blank to keep current)' : '' }}
        </label>
        <input type="password" id="password" name="password" {{ $user ? '' : 'required' }}
               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
        @error('password') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="role" class="block text-sm font-medium text-gray-700 mb-1.5">Role</label>
        <select id="role" name="role" required class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
            @foreach(\App\Models\User::ROLES as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $user?->role) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('role') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        <p class="mt-1.5 text-xs text-gray-500">
            Admin: full access &middot; Manager: products, inventory, customers, orders, finance &amp; reports &middot;
            Sales Staff: customers &amp; orders only &middot; Warehouse Staff: inventory only.
        </p>
    </div>
</div>
