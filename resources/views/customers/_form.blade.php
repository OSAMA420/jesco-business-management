@php
    /** @var \App\Models\Customer|null $customer */
    $customer ??= null;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Customer Name</label>
        <input type="text" id="name" name="name" value="{{ old('name', $customer?->name) }}" required
               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
        @error('name') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="company" class="block text-sm font-medium text-gray-700 mb-1.5">Company / Workshop</label>
        <input type="text" id="company" name="company" value="{{ old('company', $customer?->company) }}"
               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
        @error('company') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
        <input type="text" id="phone" name="phone" value="{{ old('phone', $customer?->phone) }}" placeholder="0300-1234567"
               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
        @error('phone') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email', $customer?->email) }}"
               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
        @error('email') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="address" class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
        <textarea id="address" name="address" rows="2"
                  class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">{{ old('address', $customer?->address) }}</textarea>
        @error('address') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
</div>
