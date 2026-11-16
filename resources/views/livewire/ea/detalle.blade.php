<div class="max-w-5xl">

    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('ea.historial') }}" wire:navigate class="text-xs font-medium underline" style="color: var(--c-text-dim)">&larr; Volver al historial</a>
            <h1 class="text-xl font-bold mt-1" style="color: var(--c-text)">Análisis EA #{{ $analysis->id }}</h1>
            <p class="text-sm" style="color: var(--c-text-dim)">
                {{ $analysis->created_at->translatedFormat('d \d\e F \d\e Y, h:i A') }}
                @if ($analysis->user) &middot; {{ $analysis->user->name }} @endif
                @if ($analysis->clasificacion) &middot; {{ $analysis->clasificacion }} @endif
            </p>
        </div>

        <x-dropdown align="right" width="48" contentClasses="py-1 kairo-dropdown-panel">
            <x-slot name="trigger">
                <button type="button" class="kairo-btn-copy">Opciones &darr;</button>
            </x-slot>
            <x-slot name="content">
                <button type="button"
                    wire:click="eliminar"
                    wire:confirm="¿Seguro que quieres eliminar este análisis del historial? Esta acción no se puede deshacer."
                    class="block w-full text-left px-4 py-2 text-sm" style="color:var(--c-red-soft);">
                    Eliminar análisis
                </button>
            </x-slot>
        </x-dropdown>
    </div>

    @if ($analysis->tokens_totales !== null || $analysis->duracion_segundos)
        <div class="kairo-panel p-3 mb-5 text-xs flex flex-wrap gap-x-4 gap-y-1" style="color: var(--c-text-dim)">
            @if ($analysis->tokens_totales !== null)<span><strong>Tokens procesados:</strong> {{ number_format($analysis->tokens_totales, 0, ',', '.') }}</span>@endif
            @if ($analysis->tokens_entrada !== null)<span>Entrada: {{ number_format($analysis->tokens_entrada, 0, ',', '.') }}</span>@endif
            @if ($analysis->tokens_salida !== null)<span>Salida: {{ number_format($analysis->tokens_salida, 0, ',', '.') }}</span>@endif
            @if ($analysis->tokens_cache !== null)<span>Caché: {{ number_format($analysis->tokens_cache, 0, ',', '.') }}</span>@endif
            @if ($analysis->modelo)<span>Modelo: {{ $analysis->modelo }}</span>@endif
            @if ($analysis->llamadas_modelo)<span>Llamadas: {{ $analysis->llamadas_modelo }}</span>@endif
            @if ($analysis->fragmentos && $analysis->fragmentos > 1)<span>Fragmentos: {{ $analysis->fragmentos }}</span>@endif
            @if ($analysis->duracion_segundos)<span>Tiempo: {{ number_format($analysis->duracion_segundos, 1, ',', '.') }} s</span>@endif
        </div>
    @endif

    @if (session('status'))
        <div class="kairo-panel p-4 mb-4 text-sm" style="color:var(--c-green);border:1px solid #cfe9db;">
            {{ session('status') }}
        </div>
    @endif

    <div class="kairo-panel p-5 mb-4">
        <div class="kairo-label mb-2">Descripción del caso</div>
        <div class="text-sm whitespace-pre-line" style="color: var(--c-text-dim)">{{ $analysis->caso }}</div>
    </div>

    @if ($analysis->historia)
        <div class="kairo-panel p-5 mb-6" x-data="{ abierto: false }">
            <div class="flex items-center justify-between cursor-pointer" @click="abierto = ! abierto">
                <div class="kairo-label">Historia clínica / registros aportados</div>
                <span x-text="abierto ? '▲' : '▼'" style="font-size:9px; color: var(--c-text-dim)"></span>
            </div>
            <div x-show="abierto" x-cloak x-transition class="text-sm whitespace-pre-line mt-3" style="color: var(--c-text-dim)">{{ $analysis->historia }}</div>
        </div>
    @endif

    @php $secciones = $analysis->secciones ?? []; @endphp

    <div class="space-y-4">

        <div class="kairo-section kairo-sec-verificacion">
            <div class="kairo-section-title">Análisis de causalidad</div>
            <div class="kairo-content whitespace-pre-line">{{ $secciones['ANÁLISIS DE CAUSALIDAD'] ?? '' }}</div>
        </div>

        <div class="kairo-section kairo-sec-alertas">
            <div class="kairo-section-title">Acciones inseguras identificadas</div>
            <div class="kairo-content whitespace-pre-line">{{ $secciones['ACCIONES INSEGURAS IDENTIFICADAS'] ?? '' }}</div>
        </div>

        <div class="kairo-section kairo-sec-profesionales">
            <div class="kairo-section-title">Factores contributivos</div>
            <div class="kairo-content prose prose-invert prose-sm max-w-none">
                {!! \Illuminate\Support\Str::markdown($secciones['FACTORES CONTRIBUTIVOS'] ?? '') !!}
            </div>
        </div>

        <div class="kairo-section" style="border-left: 3px solid var(--c-text-soft)">
            <div class="kairo-section-title">Factores organizacionales y culturales</div>
            <div class="kairo-content prose prose-invert prose-sm max-w-none">
                {!! \Illuminate\Support\Str::markdown($secciones['FACTORES RELACIONADOS CON LA ADMINISTRACIÓN DE LA CULTURA ORGANIZACIONAL'] ?? '') !!}
            </div>
        </div>

        <div class="kairo-section kairo-sec-acciones">
            <div class="kairo-section-title">Lecciones aprendidas</div>
            <div class="kairo-content whitespace-pre-line">{{ $secciones['LECCIONES APRENDIDAS'] ?? '' }}</div>
        </div>

        <div class="kairo-section kairo-sec-respuesta" x-data>
            <div class="kairo-section-title flex items-center justify-between">
                Planes de acción
                <button type="button" x-on:click="navigator.clipboard.writeText($refs.planes.innerText)" class="kairo-btn-copy">Copiar</button>
            </div>
            <div x-ref="planes" class="kairo-content whitespace-pre-line" style="color: var(--c-text)">{{ $secciones['PLANES DE ACCIÓN'] ?? '' }}</div>
        </div>

        <div class="kairo-section" style="border-left: 3px solid var(--c-green)">
            <div class="kairo-section-title" style="color: var(--c-green)">Conclusiones</div>
            <div class="kairo-content whitespace-pre-line">{{ $secciones['CONCLUSIONES'] ?? '' }}</div>
        </div>

    </div>
</div>
