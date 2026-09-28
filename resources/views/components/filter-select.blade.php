@props(['name', 'options', 'placeholder'])

{{-- A dropdown filter for list pages; submits its form as soon as it changes. --}}
<select name="{{ $name }}" onchange="this.form.requestSubmit()" aria-label="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'rounded-lg text-sm focus:border-jesco-500 focus:ring-jesco-500 '.(request()->filled($name) ? 'border-jesco-400 bg-jesco-50 text-jesco-700' : 'border-gray-300')]) }}>
    <option value="">{{ $placeholder }}</option>
    @foreach($options as $value => $label)
        <option value="{{ $value }}" @selected((string) request($name) === (string) $value)>{{ $label }}</option>
    @endforeach
</select>
