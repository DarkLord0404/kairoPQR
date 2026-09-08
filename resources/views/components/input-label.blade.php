@props(['value'])

<label {{ $attributes->merge(['class' => 'kairo-label block mb-1']) }}>
    {{ $value ?? $slot }}
</label>
