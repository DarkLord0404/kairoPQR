@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm']) }} style="color:var(--c-green);">
        {{ $status }}
    </div>
@endif
