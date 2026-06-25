@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'kairo-textarea border-0']) }} style="padding: .55rem .75rem;">
