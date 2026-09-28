<x-app-layout>
    <x-slot name="title">Users</x-slot>

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Users</h2>
                <p class="text-sm text-gray-500">Manage staff accounts and what each role can access.</p>
            </div>
            <a href="{{ route('users.create') }}">
                <x-btn type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Add User
                </x-btn>
            </a>
        </div>

        @if(session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm px-4 py-3">{{ session('error') }}</div>
        @endif

        <x-card>
            <form method="GET" action="{{ route('users.index') }}" class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email..." class="w-full sm:w-64 rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500" />
                <x-filter-select name="role" placeholder="All roles" :options="$roles" />
                <x-btn type="submit" variant="secondary">Search</x-btn>
                @if(request()->anyFilled(['search', 'role']))
                    <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear filters</a>
                @endif
            </form>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 uppercase tracking-wide bg-gray-50">
                            <th class="px-5 py-3 font-medium">Name</th>
                            <th class="px-5 py-3 font-medium">Email</th>
                            <th class="px-5 py-3 font-medium">Role</th>
                            <th class="px-5 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-full bg-jesco-100 text-jesco-700 flex items-center justify-center text-xs font-semibold">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </span>
                                    <span class="font-medium text-gray-800">{{ $user->name }}</span>
                                    @if($user->id === auth()->id())
                                        <span class="text-xs text-gray-400">(you)</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $user->email }}</td>
                            <td class="px-5 py-3">
                                @php
                                    $roleColor = match($user->role) {
                                        'admin' => 'blue',
                                        'manager' => 'green',
                                        'sales' => 'yellow',
                                        'warehouse' => 'gray',
                                        default => 'gray',
                                    };
                                @endphp
                                <x-badge :color="$roleColor">{{ $user->roleLabel() }}</x-badge>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-3">
                                    <a href="{{ route('users.edit', $user) }}" class="text-jesco-600 hover:underline text-sm font-medium">Edit</a>
                                    @if($user->id !== auth()->id())
                                        <button type="button"
                                                @click="$dispatch('confirm-delete', { url: '{{ route('users.destroy', $user) }}', name: '{{ addslashes($user->name) }}' })"
                                                class="text-rose-600 hover:underline text-sm font-medium">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-confirm-delete-modal />
    </div>
</x-app-layout>
