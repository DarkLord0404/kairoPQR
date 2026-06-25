<div class="max-w-5xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('reuniones') }}" wire:navigate class="text-xs font-medium underline" style="color: var(--kairo-text-dim)">&larr; Volver a reuniones</a>
            <h1 class="text-xl font-bold mt-1" style="color: var(--kairo-text)">{{ $meeting->titulo }}</h1>
            <p class="text-sm" style="color: var(--kairo-text-dim)">
                {{ $meeting->fecha_inicio?->translatedFormat('d \d\e F \d\e Y, h:i A') }}
                @if($meeting->duracion_legible) &middot; {{ $meeting->duracion_legible }} @endif
            </p>
        </div>

        @if (auth()->user()?->isMaster())
            <x-dropdown align="right" width="48" contentClasses="py-1 kairo-dropdown-panel">
                <x-slot name="trigger">
                    <button type="button" class="kairo-btn-copy">Opciones &darr;</button>
                </x-slot>
                <x-slot name="content">
                    <button type="button"
                        wire:click="eliminar"
                        wire:confirm="¿Seguro que quieres eliminar la reunión '{{ $meeting->titulo }}'? Se borrará el audio, la transcripción y el acta de forma permanente. Esta acción no se puede deshacer."
                        class="block w-full text-left px-4 py-2 text-sm" style="color:#fca5a5;">
                        Eliminar reunión
                    </button>
                </x-slot>
            </x-dropdown>
        @endif
    </div>

    <div class="kairo-panel p-5 mb-6">
        <div class="kairo-label mb-3">Audio de la reunion</div>
        @for ($i = 0; $i < $meeting->num_segmentos; $i++)
            <div class="mb-3">
                @if ($meeting->num_segmentos > 1)
                    <div class="text-xs mb-1" style="color: var(--kairo-text-dim)">Minuto {{ $i * 30 }}-{{ ($i + 1) * 30 }} (segmento {{ $i + 1 }}/{{ $meeting->num_segmentos }})</div>
                @endif
                <audio controls preload="none" class="w-full" style="filter: invert(0.9) hue-rotate(180deg);">
                    <source src="{{ route('reuniones.audio', [$meeting, $i]) }}" type="audio/wav">
                </audio>
            </div>
        @endfor
    </div>

    <div class="flex gap-2 mb-4">
        <button wire:click="cambiarPestana('acta')" class="kairo-btn-copy" style="{{ $pestana === 'acta' ? 'background: rgba(37,99,235,.35);' : '' }}">Acta</button>
        <button wire:click="cambiarPestana('transcripcion')" class="kairo-btn-copy" style="{{ $pestana === 'transcripcion' ? 'background: rgba(37,99,235,.35);' : '' }}">Transcripcion</button>
        <button wire:click="cambiarPestana('participantes')" class="kairo-btn-copy" style="{{ $pestana === 'participantes' ? 'background: rgba(37,99,235,.35);' : '' }}">Participantes</button>
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
