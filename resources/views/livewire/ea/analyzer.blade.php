<div class="max-w-5xl mx-auto">

    <div class="kairo-intro kairo-panel flex items-start gap-4 p-5 mb-6">
        <img src="{{ asset('kairo.png') }}" alt="Kairo" class="w-12 h-12 kairo-avatar-ring flex-shrink-0">
        <div class="text-sm leading-relaxed" style="color: var(--kairo-blue-dim)">
            <strong style="color: var(--kairo-blue-light)">Soy Kairo, asistente virtual de Alexander Torres.</strong>
            En este módulo analizo casos clínicos con enfoque en seguridad del paciente, eventos adversos e incidentes.
            Describa el caso y pegue o adjunte la historia clínica disponible; generaré un análisis institucional listo para informe interno.
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="kairo-panel p-5">
            <label class="block kairo-label mb-2">Descripción del caso</label>
            <textarea wire:model="caso" rows="9"
                placeholder="Describa el caso: motivo de consulta, atención recibida, reingreso, hallazgos relevantes..."
                class="kairo-textarea w-full text-sm p-3"></textarea>
        </div>
        @include('livewire.partials.historia-field')
    </div>

    <button wire:click="analizar" wire:loading.attr="disabled"
        class="kairo-btn-primary mt-5 w-full flex items-center justify-center gap-2 py-3">
        <span wire:loading wire:target="analizar"
            class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
        <span wire:loading.remove wire:target="analizar">Analizar evento adverso con Kairo</span>
        <span wire:loading wire:target="analizar">Kairo está analizando...</span>
    </button>

    @if ($error)
        <div class="kairo-error mt-4 text-sm rounded-lg p-4">{{ $error }}</div>
    @endif

    @if ($secciones)
        <div class="mt-8 space-y-4">
            <div class="flex items-center gap-2 text-sm font-semibold" style="color: var(--kairo-blue-light)">
                <img src="{{ asset('kairo.png') }}" class="w-7 h-7 kairo-avatar-ring">
                Análisis de Kairo
                @if ($duracionSegundos)
                    <span class="text-xs font-normal ml-2" style="color: var(--kairo-text-dim)">{{ number_format($duracionSegundos, 1, ',', '.') }} s</span>
                @endif
                <button wire:click="nuevoAnalisis" class="ml-auto text-xs font-medium underline" style="color: var(--kairo-text-dim)">Nuevo análisis</button>
            </div>

            @if ($tokensTotales !== null || $duracionSegundos)
                <div class="kairo-panel p-3 text-xs flex flex-wrap gap-x-4 gap-y-1" style="color: var(--kairo-text-dim)">
                    @if ($tokensTotales)<span><strong>Tokens procesados:</strong> {{ number_format($tokensTotales, 0, ',', '.') }}</span>@endif
                    @if ($tokensEntrada !== null)<span>Entrada: {{ number_format($tokensEntrada, 0, ',', '.') }}</span>@endif
                    @if ($tokensSalida !== null)<span>Salida: {{ number_format($tokensSalida, 0, ',', '.') }}</span>@endif
                    @if ($tokensCache !== null)<span>Caché: {{ number_format($tokensCache, 0, ',', '.') }}</span>@endif
                    @if ($modelo)<span>Modelo: {{ $modelo }}</span>@endif
                    @if ($llamadasModelo)<span>Llamadas: {{ $llamadasModelo }}</span>@endif
                    @if ($fragmentos && $fragmentos > 1)<span>Fragmentos: {{ $fragmentos }}</span>@endif
                    @if ($duracionSegundos)<span>Tiempo: {{ number_format($duracionSegundos, 1, ',', '.') }} s</span>@endif
                </div>
                <div class="text-[11px] mt-1" style="color: var(--kairo-text-dim)">Métrica informativa de OpenClaw; no representa un cobro de API.</div>
            @endif

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
            <div class="kairo-section" style="border-left: 3px solid var(--kairo-blue-dim)">
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
                <div x-ref="planes" class="kairo-content whitespace-pre-line" style="color: #e2e8f0">{{ $secciones['PLANES DE ACCIÓN'] ?? '' }}</div>
            </div>
            <div class="kairo-section" style="border-left: 3px solid #10b981">
                <div class="kairo-section-title" style="color: #10b981">Conclusiones</div>
                <div class="kairo-content whitespace-pre-line">{{ $secciones['CONCLUSIONES'] ?? '' }}</div>
            </div>
        </div>
    @endif
</div>
