@php
    /** @var \App\Models\Product|null $product */
    $product ??= null;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div class="sm:col-span-2">
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Product Name</label>
        <input type="text" id="name" name="name" value="{{ old('name', $product?->name) }}" required
               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
        @error('name') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="sku" class="block text-sm font-medium text-gray-700 mb-1.5">SKU</label>
        <input type="text" id="sku" name="sku" value="{{ old('sku', $product?->sku) }}" required
               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
        @error('sku') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1.5">Category</label>
        <select id="category_id" name="category_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
            <option value="">No Category</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $product?->category_id) == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('category_id') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="cost_price" class="block text-sm font-medium text-gray-700 mb-1.5">Cost Price (Rs.)</label>
        <input type="number" step="0.01" min="0" id="cost_price" name="cost_price" value="{{ old('cost_price', $product?->cost_price) }}" required
               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
        @error('cost_price') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="selling_price" class="block text-sm font-medium text-gray-700 mb-1.5">Selling Price (Rs.)</label>
        <input type="number" step="0.01" min="0" id="selling_price" name="selling_price" value="{{ old('selling_price', $product?->selling_price) }}" required
               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
        @error('selling_price') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="stock_quantity" class="block text-sm font-medium text-gray-700 mb-1.5">Opening Stock</label>
        <input type="number" min="0" id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity', $product?->stock_quantity ?? 0) }}" required
               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
        @error('stock_quantity') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="reorder_level" class="block text-sm font-medium text-gray-700 mb-1.5">Reorder Level</label>
        <input type="number" min="0" id="reorder_level" name="reorder_level" value="{{ old('reorder_level', $product?->reorder_level ?? 0) }}" required
               class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
        <p class="mt-1.5 text-xs text-gray-500">Alert when stock falls at or below this number.</p>
        @error('reorder_level') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
        <textarea id="description" name="description" rows="3"
                  class="w-full rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">{{ old('description', $product?->description) }}</textarea>
        @error('description') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="flex items-center gap-2.5 cursor-pointer select-none">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product?->is_active ?? true))
                   class="rounded border-gray-300 text-jesco-600 focus:ring-jesco-500">
            <span class="text-sm text-gray-700">Active (visible for sale)</span>
        </label>
    </div>
</div>
