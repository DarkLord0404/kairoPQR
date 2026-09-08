<button {{ $attributes->merge(['type' => 'submit', 'class' => 'kairo-btn-primary inline-flex items-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest']) }}>
    {{ $slot }}
</button>
