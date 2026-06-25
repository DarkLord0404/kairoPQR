@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm']) }} style="color:#6ee7b7;">
        {{ $status }}
    </div>
@endif
