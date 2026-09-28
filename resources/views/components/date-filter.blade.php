{{-- Shared date filter for list pages. Picking a preset submits the form; a custom range waits for Apply. --}}
<div x-data="{ period: @js(request('period', '')) }" class="flex flex-wrap items-center gap-2">
    <select name="period" x-model="period" @change="if (period !== 'custom') $el.form.requestSubmit()"
            class="rounded-lg text-sm focus:border-jesco-500 focus:ring-jesco-500 {{ request()->filled('period') ? 'border-jesco-400 bg-jesco-50 text-jesco-700' : 'border-gray-300' }}">
        <option value="">Any date</option>
        @foreach(\App\Support\DateFilter::PERIODS as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
    <template x-if="period === 'custom'">
        <div class="flex items-center gap-2">
            <input type="date" name="from" value="{{ request('from') }}" aria-label="From date"
                   class="rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
            <span class="text-sm text-gray-400">to</span>
            <input type="date" name="to" value="{{ request('to') }}" aria-label="To date"
                   class="rounded-lg border-gray-300 text-sm focus:border-jesco-500 focus:ring-jesco-500">
        </div>
    </template>
</div>
