@php
    $supplier ??= null;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Supplier Name</label>
        <input type="text" id="name" name="name" value="{{ old('name', $supplier?->name) }}" required
               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
        @error('name') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
        <input type="text" id="phone" name="phone" value="{{ old('phone', $supplier?->phone) }}" placeholder="0300-1234567"
               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
        @error('phone') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="address" class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
        <textarea id="address" name="address" rows="2"
                  class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">{{ old('address', $supplier?->address) }}</textarea>
        @error('address') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
</div>
