<div class="max-w-2xl mx-auto space-y-6" x-data="{
    abrirPopup(url) {
        const popup = window.open(url, 'google_oauth', 'width=520,height=640');
        const poll = setInterval(() => {
            if (!popup || popup.closed) {
                clearInterval(poll);
                $wire.$refresh();
            }
        }, 800);
    }
}">

    <div class="kairo-intro kairo-panel flex items-start gap-4 p-5">
        <img src="{{ asset('kairo.png') }}" alt="Kairo" class="w-12 h-12 kairo-avatar-ring flex-shrink-0">
        <div class="text-sm leading-relaxed" style="color: var(--kairo-blue-dim)">
            <strong style="color: var(--kairo-blue-light)">Calendarios de Google</strong><br>
            Kairo necesita acceso a estos calendarios para detectar reuniones.
            Si una cuenta aparece en rojo, haz clic en "Autorizar" — se abrirá Google y al aceptar queda activo automáticamente.
        </div>
    </div>

    <div class="kairo-panel p-4 space-y-2">
        <div class="px-2 pb-2" style="border-bottom: 1px solid var(--kairo-border)">
            <span class="text-sm font-semibold" style="color: var(--kairo-blue-light)">Estado de las cuentas</span>
        </div>

        <div class="space-y-1 pt-1">
            @foreach ($cuentas as $cuenta)
            <div class="flex items-center gap-3 rounded-lg px-4 py-3"
                 style="background: rgba(255,255,255,0.03); border: 1px solid var(--kairo-border)">
                <div class="flex-shrink-0 w-2 h-2 rounded-full"
                     style="background: {{ $cuenta['estado'] === 'ok' ? '#10b981' : ($cuenta['estado'] === 'error' ? '#ef4444' : '#f59e0b') }}">
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-sm" style="color: var(--kairo-text)">{{ $cuenta['nombre'] }}</div>
                    <div class="text-xs mt-0.5"
                         style="color: {{ $cuenta['estado'] === 'ok' ? 'var(--kairo-text-dim)' : ($cuenta['estado'] === 'error' ? '#fca5a5' : '#fcd34d') }}">
                        {{ $cuenta['mensaje'] }}
                    </div>
                </div>
                <button type="button"
                    @click="abrirPopup('{{ $cuenta['url'] }}')"
                    class="flex-shrink-0 text-xs font-medium px-3 py-1.5 rounded-md whitespace-nowrap"
                    style="@if($cuenta['estado'] === 'ok') color:var(--kairo-text-dim);background:rgba(148,163,184,0.08);border:1px solid var(--kairo-border) @else color:#fff;background:#1d4ed8;border:1px solid rgba(99,179,255,0.4) @endif">
                    @if($cuenta['estado'] === 'ok') Re-autorizar @else Autorizar → @endif
                </button>
            </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between px-2 pt-2" style="border-top: 1px solid var(--kairo-border)">
            <span class="text-xs" style="color: var(--kairo-text-dim)">
                {{ collect($cuentas)->where('estado', 'ok')->count() }} / {{ count($cuentas) }} activas
            </span>
            <button wire:click="$refresh" wire:loading.attr="disabled"
                class="text-xs px-3 py-1.5 rounded-md flex items-center gap-1.5"
                style="color:var(--kairo-blue-dim);background:rgba(59,130,246,0.08);border:1px solid rgba(99,179,255,0.2)">
                <span wire:loading wire:target="$refresh"
                    class="inline-block w-3 h-3 border border-current border-t-transparent rounded-full animate-spin"></span>
                <span wire:loading.remove wire:target="$refresh">↻</span>
                Verificar
            </button>
        </div>
    </div>

    <div class="kairo-panel p-5 space-y-2 text-sm" style="color: var(--kairo-text-dim)">
        <p class="text-xs font-semibold uppercase tracking-wide mb-3">Cómo autorizar</p>
        <p>1. Clic en <strong style="color:var(--kairo-text)">"Autorizar"</strong> → se abre Google en ventana nueva.</p>
        <p>2. Selecciona la cuenta correcta y acepta los permisos de calendario.</p>
        <p>3. La ventana se cierra sola y el estado se actualiza automáticamente.</p>
        <p class="text-xs pt-2" style="border-top:1px solid var(--kairo-border);color:var(--kairo-text-dim)">
            Los tokens se renuevan automáticamente. Solo debes re-autorizar si revocas el acceso desde tu cuenta de Google.
        </p>
    </div>

</div>
