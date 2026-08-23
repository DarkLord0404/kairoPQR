<div class="max-w-5xl mx-auto" x-data="{ modal: false }" x-on:acta-enviada.window="setTimeout(() => { modal = false }, 1800)">

    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('reuniones') }}" wire:navigate class="text-xs font-medium underline" style="color: var(--kairo-text-dim)">&larr; Volver a reuniones</a>
            <h1 class="text-xl font-bold mt-1" style="color: var(--kairo-text)">{{ $meeting->titulo }}</h1>
            <p class="text-sm" style="color: var(--kairo-text-dim)">
                {{ $meeting->fecha_inicio?->translatedFormat('d \d\e F \d\e Y, h:i A') }}
                @if($meeting->duracion_legible) &middot; {{ $meeting->duracion_legible }} @endif
            </p>
        </div>

        <x-dropdown align="right" width="48" contentClasses="py-1 kairo-dropdown-panel">
            <x-slot name="trigger">
                <button type="button" class="kairo-btn-copy">Opciones &darr;</button>
            </x-slot>
            <x-slot name="content">
                @if ($this->actaHtml)
                    <button type="button"
                        @click="modal = true; $wire.mensajeEnvio = null"
                        class="block w-full text-left px-4 py-2 text-sm" style="color: var(--kairo-text);">
                        Enviar acta por correo
                    </button>
                @endif
                @if (auth()->user()?->isMaster())
                    <button type="button"
                        wire:click="enviarACumple"
                        wire:loading.attr="disabled"
                        wire:target="enviarACumple"
                        class="block w-full text-left px-4 py-2 text-sm" style="color:var(--kairo-text);">
                        Enviar borrador a CUMPLE
                    </button>
                    <button type="button"
                        wire:click="eliminar"
                        wire:confirm="¿Seguro que quieres eliminar la reunión '{{ $meeting->titulo }}'? Se borrará el audio, la transcripción y el acta de forma permanente. Esta acción no se puede deshacer."
                        class="block w-full text-left px-4 py-2 text-sm" style="color:#fca5a5;">
                        Eliminar reunión
                    </button>
                @endif
            </x-slot>
        </x-dropdown>
    </div>

    {{-- Modal: enviar acta por correo --}}
    <div x-show="modal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,.8); backdrop-filter: blur(3px);"
         @keydown.escape.window="modal = false"
         @click="modal = false">
        <div class="w-full p-5 rounded-xl"
             style="max-width:360px; background:#0d1424; border:1px solid var(--kairo-border); box-shadow:0 24px 64px rgba(0,0,0,.9);"
             @click.stop>
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-sm" style="color: var(--kairo-text)">Enviar acta por correo</h3>
                <button @click="modal = false" class="w-6 h-6 flex items-center justify-center rounded text-lg leading-none" style="color: var(--kairo-text-dim);">&times;</button>
            </div>
            <p class="text-xs mb-3" style="color: var(--kairo-text-dim)">
                Acta de <strong style="color:var(--kairo-text)">{{ $meeting->titulo }}</strong>
            </p>
            <input
                type="email"
                wire:model="emailDestino"
                placeholder="correo@ejemplo.com"
                class="w-full rounded px-3 py-2 text-sm mb-2"
                style="background:#131f35; color:var(--kairo-text); border:1px solid var(--kairo-border); outline:none;"
                wire:keydown.enter="enviarActa"
                x-ref="emailInput"
                x-init="$watch('modal', v => v && $nextTick(() => $refs.emailInput.focus()))"
            >
            @if ($mensajeEnvio)
                <p class="text-xs mb-2" style="color: {{ str_starts_with($mensajeEnvio, '✅') ? '#6ee7b7' : '#fca5a5' }};">
                    {{ $mensajeEnvio }}
                </p>
            @endif
            <div class="flex gap-2 justify-end mt-3">
                <button @click="modal = false" class="kairo-btn-copy text-sm">Cancelar</button>
                <button
                    wire:click="enviarActa"
                    wire:loading.attr="disabled"
                    wire:target="enviarActa"
                    class="kairo-btn-primary text-sm px-4 py-1.5"
                >
                    <span wire:loading.remove wire:target="enviarActa">Enviar</span>
                    <span wire:loading wire:target="enviarActa">Enviando...</span>
                </button>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="kairo-panel p-4 mb-4 text-sm" style="color:#6ee7b7;border:1px solid #064e3b;">
            {{ session('status') }}
        </div>
    @endif

    @if ($mensajeCumple)
        <div class="kairo-panel p-4 mb-4 text-sm" style="color:{{ str_starts_with($mensajeCumple, '✅') ? '#6ee7b7' : '#fca5a5' }};border:1px solid {{ str_starts_with($mensajeCumple, '✅') ? '#064e3b' : '#7f1d1d' }};">
            {{ $mensajeCumple }}
        </div>
    @endif

    <div class="flex gap-2 mb-4">
        <button wire:click="cambiarPestana('acta')" class="kairo-btn-copy" style="{{ $pestana === 'acta' ? 'background: rgba(37,99,235,.35);' : '' }}">Acta</button>
        <button wire:click="cambiarPestana('transcripcion')" class="kairo-btn-copy" style="{{ $pestana === 'transcripcion' ? 'background: rgba(37,99,235,.35);' : '' }}">Transcripcion</button>
        <button wire:click="cambiarPestana('participantes')" class="kairo-btn-copy" style="{{ $pestana === 'participantes' ? 'background: rgba(37,99,235,.35);' : '' }}">Participantes</button>
        <button wire:click="cambiarPestana('audio')" class="kairo-btn-copy" style="{{ $pestana === 'audio' ? 'background: rgba(37,99,235,.35);' : '' }}">
            Audio
            @if ($meeting->tieneAudio())
                <span class="text-xs ml-1" style="color: var(--kairo-text-dim)">({{ $meeting->audioPesaLegible() }})</span>
            @endif
        </button>
    </div>

    @if ($pestana === 'acta')
        <div class="kairo-section kairo-sec-respuesta">
            @if ($this->actaHtml)
                <div class="kairo-content prose-acta">{!! $this->actaHtml !!}</div>
            @else
                <div class="text-sm" style="color: var(--kairo-text-dim)">El acta de esta reunion aun no esta lista.</div>
            @endif
        </div>
    @elseif ($pestana === 'transcripcion')
        <div class="kairo-section kairo-sec-resumen">
            @if ($this->transcripcion)
                <div class="kairo-content whitespace-pre-line">{{ $this->transcripcion }}</div>
            @else
                <div class="text-sm" style="color: var(--kairo-text-dim)">La transcripcion de esta reunion aun no esta lista.</div>
            @endif
        </div>
    @elseif ($pestana === 'participantes')
        <div class="kairo-section kairo-sec-profesionales">
            @if ($meeting->participants->isEmpty())
                <div class="text-sm" style="color: var(--kairo-text-dim)">
                    Aun no hay deteccion de participantes ni porcentaje de interaccion para esta reunion.
                    Esta funcion llega en una proxima fase (diarizacion de voz).
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach ($meeting->participants as $p)
                        <div class="kairo-stat-card">
                            <div class="kairo-stat-value">{{ $p->porcentaje }}%</div>
                            <div class="kairo-stat-label">{{ $p->nombreVisible() }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @elseif ($pestana === 'audio')
        <div class="kairo-section">
            @if (! $meeting->tieneAudio())
                <div class="text-sm" style="color: var(--kairo-text-dim)">
                    El audio de esta reunión ya fue eliminado
                    @if ($meeting->audio_eliminado_en) el {{ $meeting->audio_eliminado_en->translatedFormat('d \d\e F \d\e Y') }} @endif.
                    La transcripción y el acta siguen disponibles.
                </div>
            @else
                <div class="text-xs mb-3" style="color: var(--kairo-text-dim)">{{ $meeting->audioPesaLegible() }} en disco, en {{ $meeting->num_segmentos }} {{ $meeting->num_segmentos === 1 ? 'archivo' : 'archivos' }} de 30 min.</div>

                <div class="space-y-2 mb-3">
                    @for ($i = 0; $i < $meeting->num_segmentos; $i++)
                        <details class="kairo-stat-card" style="padding: .5rem .75rem;">
                            <summary class="text-xs cursor-pointer" style="color: var(--kairo-text-dim)">
                                @if ($meeting->num_segmentos > 1)
                                    Minuto {{ $i * 30 }}-{{ ($i + 1) * 30 }} (segmento {{ $i + 1 }}/{{ $meeting->num_segmentos }})
                                @else
                                    Audio completo
                                @endif
                            </summary>
                            <audio controls preload="none" class="w-full mt-2" style="filter: invert(0.9) hue-rotate(180deg); height:32px;">
                                <source src="{{ route('reuniones.audio', [$meeting, $i]) }}" type="audio/wav">
                            </audio>
                        </details>
                    @endfor
                </div>

                @if (auth()->user()?->isMaster())
                    <button type="button"
                        wire:click="eliminarAudio"
                        wire:confirm="¿Eliminar solo el audio de esta reunión ({{ $meeting->audioPesaLegible() }})? El acta y la transcripción se conservan. Esta acción no se puede deshacer."
                        class="kairo-btn-copy text-xs" style="color:#fca5a5;border-color:#7f1d1d;">
                        Eliminar audio (liberar {{ $meeting->audioPesaLegible() }})
                    </button>
                @endif
            @endif
        </div>
    @endif
</div>

<style>
    .prose-acta h1 { font-size: 1.4rem; font-weight: 800; color: var(--kairo-text); margin-bottom: .5rem; }
    .prose-acta h2 { font-size: 1.05rem; font-weight: 700; color: var(--kairo-blue-light); margin-top: 1.25rem; margin-bottom: .5rem; border-bottom: 1px solid var(--kairo-border); padding-bottom: .3rem; }
    .prose-acta ul { list-style: disc; padding-left: 1.4rem; margin-bottom: .75rem; }
    .prose-acta li { margin-bottom: .3rem; }
    .prose-acta table { width: 100%; font-size: .85rem; margin: .75rem 0; }
    .prose-acta th, .prose-acta td { border: 1px solid var(--kairo-border); padding: .4rem .6rem; text-align: left; }
    .prose-acta th { background: rgba(13,20,36,.9); color: var(--kairo-blue-dim); }
    .prose-acta p { margin-bottom: .75rem; }
    .prose-acta strong { color: var(--kairo-text); }
</style>
