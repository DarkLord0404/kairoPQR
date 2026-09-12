<div class="max-w-5xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('historial') }}" wire:navigate class="text-xs font-medium underline" style="color: var(--kairo-text-dim)">&larr; Volver al historial</a>
            <h1 class="text-xl font-bold mt-1" style="color: var(--kairo-text)">Análisis #{{ $analysis->id }}</h1>
            <p class="text-sm" style="color: var(--kairo-text-dim)">
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
                    class="block w-full text-left px-4 py-2 text-sm" style="color:#fca5a5;">
                    Eliminar análisis
                </button>
            </x-slot>
        </x-dropdown>
    </div>

    @if ($analysis->tokens_totales !== null || $analysis->duracion_segundos)
        <div class="kairo-panel p-3 mb-5 text-xs flex flex-wrap gap-x-4 gap-y-1" style="color: var(--kairo-text-dim)">
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
        <div class="kairo-panel p-4 mb-4 text-sm" style="color:#6ee7b7;border:1px solid #064e3b;">
            {{ session('status') }}
        </div>
    @endif

    <div class="kairo-panel p-5 mb-4">
        <div class="kairo-label mb-2">Queja original</div>
        <div class="text-sm whitespace-pre-line" style="color: var(--kairo-text-dim)">{{ $analysis->queja }}</div>
    </div>

    @if ($analysis->historia)
        <div class="kairo-panel p-5 mb-6" x-data="{ abierto: false }">
            <div class="flex items-center justify-between cursor-pointer" @click="abierto = ! abierto">
                <div class="kairo-label">Historia clínica / registros aportados</div>
                <span x-text="abierto ? '▲' : '▼'" style="font-size:9px; color: var(--kairo-text-dim)"></span>
            </div>
            <div x-show="abierto" x-cloak x-transition class="text-sm whitespace-pre-line mt-3" style="color: var(--kairo-text-dim)">{{ $analysis->historia }}</div>
        </div>
    @endif

    @php
        $secciones = $analysis->secciones ?? [];
        $alertas = $secciones['ALERTAS INTERNAS'] ?? '';
        $requiereJuridica = $analysis->requiere_revision_juridica;
        $sinSecciones = empty($secciones) && !empty($analysis->respuesta_completa);
    @endphp

    <div class="space-y-4">
        @if ($sinSecciones)
            {{-- Fallback: model returned free-form text without section headers --}}
            <div class="kairo-section" style="border:1px solid var(--kairo-blue-dim);border-radius:0.75rem;padding:1rem;">
                <div class="kairo-section-title flex items-center justify-between">
                    Análisis completo
                    <span class="text-xs font-normal" style="color:var(--kairo-text-dim)">Respuesta sin secciones estructuradas</span>
                </div>
                <div x-data class="kairo-content whitespace-pre-line leading-relaxed" style="color:#e2e8f0" x-ref="respuesta">
                    {{ $analysis->respuesta_completa }}
                    <button type="button" x-on:click="navigator.clipboard.writeText($refs.respuesta.innerText)" class="kairo-btn-copy mt-3 block">Copiar</button>
                </div>
            </div>
        @else
            <div class="kairo-section kairo-sec-alertas">
                <div class="kairo-section-title">Alertas internas</div>
                @if ($requiereJuridica)
                    <span class="kairo-alert-juridica">REQUIERE REVISION JURIDICA ANTES DE ENVIO</span>
                @endif
                <div class="kairo-content whitespace-pre-line">{{ str_replace('REQUIERE REVISION JURIDICA ANTES DE ENVIO', '', $alertas) }}</div>
            </div>

            @if (! empty($secciones['RESUMEN PARA VERIFICACION INTERNA']))
                <div class="kairo-section kairo-sec-verificacion" style="border: 1px dashed var(--kairo-blue-dim); border-radius: 0.75rem; padding: 1rem;">
                    <div class="kairo-section-title">Resumen para verificación interna</div>
                    <div class="kairo-content whitespace-pre-line">{{ $secciones['RESUMEN PARA VERIFICACION INTERNA'] }}</div>
                </div>
            @endif

            <div class="kairo-section kairo-sec-profesionales">
                <div class="kairo-section-title">Profesionales o áreas para revisión</div>
                <div class="kairo-content whitespace-pre-line">{{ $secciones['PROFESIONALES O AREAS PARA REVISION'] ?? '' }}</div>
            </div>

            <div class="kairo-section kairo-sec-respuesta" x-data>
                <div class="kairo-section-title flex items-center justify-between">
                    Respuesta sugerida al usuario
                    <button type="button" x-on:click="navigator.clipboard.writeText($refs.respuesta.innerText)" class="kairo-btn-copy">Copiar</button>
                </div>
                <div x-ref="respuesta" class="kairo-content whitespace-pre-line leading-relaxed" style="color: #e2e8f0">{{ $secciones['RESPUESTA SUGERIDA AL USUARIO'] ?? $analysis->respuesta_completa }}</div>
            </div>

            <div class="kairo-section kairo-sec-acciones">
                <div class="kairo-section-title">Acciones internas recomendadas</div>
                <div class="kairo-content whitespace-pre-line">{{ $secciones['ACCIONES INTERNAS RECOMENDADAS'] ?? '' }}</div>
            </div>
        @endif
    </div>
</div>
