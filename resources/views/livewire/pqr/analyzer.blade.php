@php
    $esMaster = auth()->user()->isMaster();
    if ($esMaster) {
        $hoy = now()->startOfDay();
        $kpiHoy = \App\Models\PqrAnalysis::where('created_at', '>=', $hoy)->count();
        $kpiSemana = \App\Models\PqrAnalysis::where('created_at', '>=', now()->subDays(6)->startOfDay())->count();
        $kpiJuridica = \App\Models\PqrAnalysis::where('created_at', '>=', $hoy)->where('requiere_revision_juridica', true)->count();
    }
    $paso = $secciones ? 3 : 1;

    $alertas = $secciones['ALERTAS INTERNAS'] ?? '';
    $esNoQueja = str_contains($alertas, 'NO_ES_QUEJA');
    $requiereJuridica = str_contains(strtoupper($alertas), 'REVISION JURIDICA');
@endphp

<div x-data="{ escrito: false }">
    {{-- Encabezado --}}
    <div class="flex flex-wrap items-end justify-between gap-4 mb-4">
        <div>
            <div class="c-eyebrow">Calidad · PQR</div>
            <h1 class="c-title mt-0.5">Análisis de PQR</h1>
            <p class="text-sm mt-1 max-w-2xl" style="color:var(--c-text-dim)">Quejas, PQR y derechos de petición del servicio de urgencias. Clara prepara una respuesta institucional formal lista para revisión.</p>
        </div>
        @if ($esMaster)
            <div class="c-kpis">
                <div><b>{{ $kpiHoy }}</b><span>Analizadas hoy</span></div>
                <div><b>{{ $kpiSemana }}</b><span>Últimos 7 días</span></div>
                <div><b style="{{ $kpiJuridica ? 'color:var(--c-red-soft)' : '' }}">{{ $kpiJuridica }}</b><span>Revisión jurídica hoy</span></div>
            </div>
        @endif
    </div>

    {{-- Tarjeta de trabajo por pasos --}}
    <div class="kairo-panel">
        <div class="c-steps">
            <span class="c-step {{ $paso === 1 ? 'is-active' : 'is-done' }}" wire:loading.class.remove="is-active" wire:target="analizar"><i>{{ $paso > 1 ? '✓' : '1' }}</i>Datos del caso</span>
            <span class="c-step-line"></span>
            <span class="c-step {{ $paso > 2 ? 'is-done' : '' }}" wire:loading.class="is-active" wire:target="analizar"><i>{{ $paso > 2 ? '✓' : '2' }}</i>Análisis de Clara</span>
            <span class="c-step-line"></span>
            <span class="c-step {{ $paso === 3 ? 'is-active' : '' }}"><i>3</i>Revisión y respuesta</span>
            <div class="c-assist">
                <div class="c-bubble">
                    <span wire:loading.remove wire:target="analizar">
                        @if ($secciones)
                            Listo. Revisa la respuesta y las alertas antes de usarla.
                        @else
                            <span x-show="!escrito">Pega la queja y la historia clínica; yo me encargo del resto.</span>
                            <span x-show="escrito" x-cloak>Perfecto. Cuando estés listo, presiona <b>Analizar con Clara</b>.</span>
                        @endif
                    </span>
                    <span wire:loading wire:target="analizar">Estoy revisando el caso<span class="c-dots"><i></i><i></i><i></i></span></span>
                </div>
                <span class="c-clara c-clara-md {{ $secciones ? 'is-done' : '' }}" wire:loading.class="is-thinking" wire:target="analizar"
                      x-data="{ rx: 0, ry: 0 }"
                      @mousemove.window="const r = $el.getBoundingClientRect(); ry = Math.max(-1, Math.min(1, (event.clientX - r.left - r.width / 2) / 600)) * 18; rx = -Math.max(-1, Math.min(1, (event.clientY - r.top - r.height / 2) / 400)) * 14">
                    <img src="{{ asset('clara.webp') }}" alt="Clara" :style="`transform: perspective(240px) rotateX(${rx}deg) rotateY(${ry}deg); transition: transform .2s ease-out`">
                </span>
            </div>
        </div>

        <div class="c-split">
            <div>
                <div class="c-fieldhead"><label class="kairo-label">Queja o solicitud del usuario</label></div>
                <textarea wire:model="queja" x-on:input="escrito = $event.target.value.trim().length > 0"
                    placeholder="Pegue aquí el texto de la queja, PQR o solicitud..."
                    class="kairo-textarea c-fit-ta w-full text-sm p-3"></textarea>
            </div>
            @include('livewire.partials.historia-field')
        </div>

        <div class="c-cardfoot">
            <span wire:loading wire:target="analizar" class="mr-auto text-xs" style="color:var(--c-text-dim)">Clara está analizando el caso; puede tardar unos minutos.</span>
            @if ($secciones || $queja || $historia)
                <button type="button" wire:click="nuevoAnalisis" wire:loading.attr="disabled" class="c-btn c-btn-ghost">Limpiar</button>
            @endif
            <button wire:click="analizar" wire:loading.attr="disabled" class="kairo-btn-primary c-btn">
                <span wire:loading wire:target="analizar" class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                <span wire:loading.remove wire:target="analizar">Analizar con Clara</span>
                <span wire:loading wire:target="analizar">Analizando...</span>
            </button>
        </div>
    </div>

    @if ($error)
        <div class="kairo-error mt-4 text-sm rounded-xl p-4">{{ $error }}</div>
    @endif

    @if ($secciones)
        @if ($esNoQueja)
            <div class="kairo-error rounded-xl p-5 text-sm mt-6">
                No se identificó una queja, PQR o solicitud válida en el texto ingresado. Verifique el contenido y vuelva a intentarlo.
            </div>
        @else
            <div class="c-result mt-6">
                <nav class="kairo-panel c-toc" x-data="{ activo: 'sec-respuesta' }">
                    <a href="#sec-respuesta" @click="activo='sec-respuesta'" :class="activo==='sec-respuesta' && 'is-active'">Respuesta sugerida</a>
                    <a href="#sec-alertas" @click="activo='sec-alertas'" :class="activo==='sec-alertas' && 'is-active'">Alertas internas @if ($requiereJuridica)<span class="c-pill c-pill-red">Jurídica</span>@endif</a>
                    <a href="#sec-resumen" @click="activo='sec-resumen'" :class="activo==='sec-resumen' && 'is-active'">Resumen para verificación</a>
                    <a href="#sec-profesionales" @click="activo='sec-profesionales'" :class="activo==='sec-profesionales' && 'is-active'">Profesionales o áreas</a>
                    <a href="#sec-acciones" @click="activo='sec-acciones'" :class="activo==='sec-acciones' && 'is-active'">Acciones internas</a>
                    @if ($tokensTotales !== null || $duracionSegundos)
                        <div class="px-3 pt-3 mt-2 text-[11px] leading-relaxed" style="border-top:1px solid var(--c-border);color:var(--c-text-faint)">
                            @if ($duracionSegundos){{ number_format($duracionSegundos, 1, ',', '.') }} s @endif
                            @if ($tokensTotales) · {{ number_format($tokensTotales, 0, ',', '.') }} tokens @endif
                            @if ($modelo)<br>{{ $modelo }}@endif
                        </div>
                    @endif
                </nav>

                <div class="space-y-4 min-w-0">
                    <div id="sec-respuesta" class="kairo-section kairo-sec-respuesta" x-data>
                        <div class="kairo-section-title flex items-center justify-between">
                            Respuesta sugerida al usuario
                            <button type="button" x-on:click="navigator.clipboard.writeText($refs.respuesta.innerText)" class="kairo-btn-copy">Copiar</button>
                        </div>
                        <div x-ref="respuesta" class="kairo-content whitespace-pre-line">{{ $secciones['RESPUESTA SUGERIDA AL USUARIO'] ?? '' }}</div>
                    </div>
                    <div id="sec-alertas" class="kairo-section kairo-sec-alertas">
                        <div class="kairo-section-title">Alertas internas</div>
                        @if ($requiereJuridica)
                            <span class="kairo-alert-juridica">Requiere revisión jurídica antes de envío</span>
                        @endif
                        <div class="kairo-content whitespace-pre-line">{{ str_replace('REQUIERE REVISION JURIDICA ANTES DE ENVIO', '', $alertas) }}</div>
                    </div>
                    <div id="sec-resumen" class="kairo-section kairo-sec-verificacion">
                        <div class="kairo-section-title">Resumen para verificación interna</div>
                        <div class="kairo-content whitespace-pre-line">{{ $secciones['RESUMEN PARA VERIFICACION INTERNA'] ?? '' }}</div>
                    </div>
                    <div id="sec-profesionales" class="kairo-section kairo-sec-profesionales">
                        <div class="kairo-section-title">Profesionales o áreas para revisión</div>
                        <div class="kairo-content whitespace-pre-line">{{ $secciones['PROFESIONALES O AREAS PARA REVISION'] ?? '' }}</div>
                    </div>
                    <div id="sec-acciones" class="kairo-section kairo-sec-acciones">
                        <div class="kairo-section-title">Acciones internas recomendadas</div>
                        <div class="kairo-content whitespace-pre-line">{{ $secciones['ACCIONES INTERNAS RECOMENDADAS'] ?? '' }}</div>
                    </div>
                    <p class="text-[11px]" style="color:var(--c-text-faint)">Métrica de tokens informativa de OpenClaw; no representa un cobro de API. El resultado es un borrador para revisión humana.</p>
                </div>
            </div>
        @endif
    @endif
</div>
