<div class="max-w-xl mx-auto space-y-5" wire:poll.3000ms="polling">

    {{-- Estado actual --}}
    <div class="kairo-panel p-5">
        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-bold" style="color: var(--kairo-blue-light)">Estado del bot</span>
        </div>

        @if ($reunionesActivas)
            <div class="space-y-3">
            @foreach ($reunionesActivas as $reunion)
            <div class="rounded-xl p-4 flex items-start gap-4"
                 style="background: rgba(16,185,129,0.07); border: 1px solid rgba(16,185,129,0.25);">
                <div class="flex-shrink-0 mt-1">
                    <span class="w-3 h-3 rounded-full bg-emerald-400 inline-block animate-pulse"
                          style="box-shadow: 0 0 8px #10b981;"></span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-semibold mb-1" style="color: #34d399">Kairo está {{ $reunion['estado'] }}</div>
                    <div class="text-xs mb-0.5 font-medium" style="color: var(--kairo-text)">
                        {{ $reunion['titulo'] }}
                    </div>
                    <a href="{{ $reunion['url'] }}" target="_blank"
                       class="text-xs break-all"
                       style="color: var(--kairo-blue-dim)">
                        {{ $reunion['url'] }}
                    </a>
                </div>
                <button wire:click="desconectar('{{ $reunion['session_id'] }}')" wire:loading.attr="disabled" wire:target="desconectar"
                    class="flex-shrink-0 text-xs font-medium px-3 py-1.5 rounded-md whitespace-nowrap flex items-center gap-1.5"
                    style="color:#fca5a5; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3)">
                    <span wire:loading wire:target="desconectar"
                          class="inline-block w-3 h-3 border border-red-400 border-t-transparent rounded-full animate-spin"></span>
                    <span wire:loading.remove wire:target="desconectar">✕</span>
                    Desconectar
                </button>
            </div>
            @endforeach
            </div>
        @else
            {{-- Libre --}}
            <div class="rounded-xl p-4 flex items-center gap-3"
                 style="background: rgba(255,255,255,0.03); border: 1px solid var(--kairo-border);">
                <span class="w-3 h-3 rounded-full bg-gray-500 inline-block flex-shrink-0"></span>
                <span class="text-sm" style="color: var(--kairo-text-dim)">Kairo no está en ninguna reunión</span>
            </div>
        @endif
    </div>

    @if ($colasProcesamiento)
        <div class="rounded-xl p-4 space-y-3"
             style="background:rgba(245,158,11,0.07);border:1px solid rgba(245,158,11,0.25)">
            <div class="text-sm font-bold" style="color:#fbbf24">Procesamiento pendiente</div>
            @foreach ($colasProcesamiento as $trabajo)
                @php
                    $etiqueta = match ($trabajo['fase']) {
                        'pending_groq' => 'En cola para transcripción',
                        'transcribing_groq' => 'Transcribiendo con Groq',
                        'waiting_groq' => 'Groq no disponible · reintentará automáticamente',
                        'pending_openai' => 'En cola para preparar el acta',
                        'processing_openai' => 'Preparando el acta',
                        'waiting_openai' => 'OpenAI no disponible · reintentará automáticamente',
                        default => 'Procesamiento pendiente',
                    };
                @endphp
                <div class="flex items-center justify-between gap-3 text-xs">
                    <span class="truncate" style="color:var(--kairo-text)">{{ $trabajo['titulo'] }}</span>
                    <span class="text-right" style="color:#fbbf24">{{ $etiqueta }}</span>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Formulario de conexión --}}
    <div class="kairo-panel p-5 space-y-4">
        <div class="flex items-center gap-2 pb-3" style="border-bottom: 1px solid var(--kairo-border)">
            <span class="text-lg">🎙️</span>
            <span class="text-sm font-bold" style="color: var(--kairo-blue-light)">Conectar a reunión</span>
        </div>

        {{-- URL --}}
        <div class="space-y-1.5">
            <label class="text-xs font-semibold uppercase tracking-wider" style="color: var(--kairo-text-dim)">
                Link de Google Meet
            </label>
            <input
                wire:model="url"
                type="url"
                placeholder="https://meet.google.com/xxx-xxxx-xxx"
                class="w-full text-sm rounded-lg px-3 py-2.5 outline-none transition-all"
                style="background: rgba(255,255,255,0.05); border: 1px solid var(--kairo-border); color: var(--kairo-text);"
                @focus="$el.style.borderColor='rgba(99,179,255,0.5)'"
                @blur="$el.style.borderColor='var(--kairo-border)'"
                >
        </div>

        {{-- Título --}}
        <div class="space-y-1.5">
            <label class="text-xs font-semibold uppercase tracking-wider" style="color: var(--kairo-text-dim)">
                Título <span class="font-normal normal-case" style="color: var(--kairo-text-dim)">(opcional)</span>
            </label>
            <input
                wire:model="titulo"
                type="text"
                placeholder="Nombre de la reunión"
                class="w-full text-sm rounded-lg px-3 py-2.5 outline-none transition-all"
                style="background: rgba(255,255,255,0.05); border: 1px solid var(--kairo-border); color: var(--kairo-text);"
                @focus="$el.style.borderColor='rgba(99,179,255,0.5)'"
                @blur="$el.style.borderColor='var(--kairo-border)'"
                >
        </div>

        {{-- Error --}}
        @if ($error)
            <div class="rounded-lg px-3 py-2.5 text-xs"
                 style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5;">
                {{ $error }}
            </div>
        @endif

        {{-- Botón --}}
        <button
            wire:click="conectar"
            wire:loading.attr="disabled"
            wire:target="conectar"
            class="w-full text-sm font-semibold py-2.5 rounded-lg flex items-center justify-center gap-2 transition-all"
            style="background:#1d4ed8;color:#fff;border:1px solid rgba(99,179,255,0.4);cursor:pointer">
            <span wire:loading wire:target="conectar"
                  class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
            <span wire:loading.remove wire:target="conectar">
                {{ $reunionesActivas ? 'Conectar a otra reunión' : 'Conectar Kairo' }}
            </span>
            <span wire:loading wire:target="conectar">Conectando...</span>
        </button>
    </div>

    {{-- Nota informativa --}}
    <div class="text-xs px-1 space-y-1" style="color: var(--kairo-text-dim)">
        <p>Kairo se unirá directamente sin necesitar invitación formal. Si la reunión tiene sala de espera, esperará hasta que alguien lo admita.</p>
        <p>La grabación y el acta quedarán en el historial de reuniones al terminar.</p>
    </div>

</div>
