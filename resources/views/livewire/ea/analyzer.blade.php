@php
    $esMaster = auth()->user()->isMaster();
    if ($esMaster) {
        $kpiHoy = \App\Models\EaAnalysis::where('created_at', '>=', now()->startOfDay())->count();
        $kpiSemana = \App\Models\EaAnalysis::where('created_at', '>=', now()->subDays(6)->startOfDay())->count();
        $kpiMes = \App\Models\EaAnalysis::where('created_at', '>=', now()->startOfMonth())->count();
    }
    $paso = $secciones ? 3 : 1;
@endphp

<div x-data="{ escrito: false }">
    {{-- Encabezado --}}
    <div class="flex flex-wrap items-end justify-between gap-4 mb-4">
        <div>
            <div class="c-eyebrow">Seguridad del paciente</div>
            <h1 class="c-title mt-0.5">Análisis de eventos adversos</h1>
            <p class="text-sm mt-1 max-w-2xl" style="color:var(--c-text-dim)">Casos clínicos con enfoque en seguridad del paciente, eventos adversos e incidentes. Clara genera un análisis institucional listo para informe interno.</p>
        </div>
        @if ($esMaster)
            <div class="c-kpis">
                <div><b>{{ $kpiHoy }}</b><span>Analizados hoy</span></div>
                <div><b>{{ $kpiSemana }}</b><span>Últimos 7 días</span></div>
                <div><b>{{ $kpiMes }}</b><span>Este mes</span></div>
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
            <span class="c-step {{ $paso === 3 ? 'is-active' : '' }}"><i>3</i>Revisión e informe</span>
            <div class="c-assist">
                <div class="c-bubble">
                    <span wire:loading.remove wire:target="analizar">
                        @if ($secciones)
                            Listo. Revisa la respuesta y las alertas antes de usarla.
                        @else
                            <span x-show="!escrito">Pega el caso y la historia clínica; yo me encargo del resto.</span>
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
                <div class="c-fieldhead"><label class="kairo-label">Descripción del caso</label></div>
                <textarea wire:model="caso" x-on:input="escrito = $event.target.value.trim().length > 0"
                    placeholder="Describa el caso: motivo de consulta, atención recibida, reingreso, hallazgos relevantes..."
                    class="kairo-textarea c-fit-ta w-full text-sm p-3"></textarea>
            </div>
            @include('livewire.partials.historia-field')
        </div>

        <div class="c-cardfoot">
            <span wire:loading wire:target="analizar" class="mr-auto text-xs" style="color:var(--c-text-dim)">Clara está analizando el evento; puede tardar unos minutos.</span>
            @if ($secciones || $caso || $historia)
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
        <div class="c-result mt-6">
            <nav class="kairo-panel c-toc" x-data="{ activo: 'ea-planes' }">
                <a href="#ea-planes" @click="activo='ea-planes'" :class="activo==='ea-planes' && 'is-active'">Planes de acción</a>
                <a href="#ea-causalidad" @click="activo='ea-causalidad'" :class="activo==='ea-causalidad' && 'is-active'">Análisis de causalidad</a>
                <a href="#ea-inseguras" @click="activo='ea-inseguras'" :class="activo==='ea-inseguras' && 'is-active'">Acciones inseguras</a>
                <a href="#ea-factores" @click="activo='ea-factores'" :class="activo==='ea-factores' && 'is-active'">Factores contributivos</a>
                <a href="#ea-cultura" @click="activo='ea-cultura'" :class="activo==='ea-cultura' && 'is-active'">Factores organizacionales</a>
                <a href="#ea-lecciones" @click="activo='ea-lecciones'" :class="activo==='ea-lecciones' && 'is-active'">Lecciones aprendidas</a>
                <a href="#ea-conclusiones" @click="activo='ea-conclusiones'" :class="activo==='ea-conclusiones' && 'is-active'">Conclusiones</a>
                @if ($tokensTotales !== null || $duracionSegundos)
                    <div class="px-3 pt-3 mt-2 text-[11px] leading-relaxed" style="border-top:1px solid var(--c-border);color:var(--c-text-faint)">
                        @if ($duracionSegundos){{ number_format($duracionSegundos, 1, ',', '.') }} s @endif
                        @if ($tokensTotales) · {{ number_format($tokensTotales, 0, ',', '.') }} tokens @endif
                        @if ($modelo)<br>{{ $modelo }}@endif
                    </div>
                @endif
            </nav>

            <div class="space-y-4 min-w-0">
                <div id="ea-planes" class="kairo-section kairo-sec-respuesta" x-data>
                    <div class="kairo-section-title flex items-center justify-between">
                        Planes de acción
                        <button type="button" x-on:click="navigator.clipboard.writeText($refs.planes.innerText)" class="kairo-btn-copy">Copiar</button>
                    </div>
                    <div x-ref="planes" class="kairo-content whitespace-pre-line">{{ $secciones['PLANES DE ACCIÓN'] ?? '' }}</div>
                </div>
                <div id="ea-causalidad" class="kairo-section kairo-sec-verificacion">
                    <div class="kairo-section-title">Análisis de causalidad</div>
                    <div class="kairo-content whitespace-pre-line">{{ $secciones['ANÁLISIS DE CAUSALIDAD'] ?? '' }}</div>
                </div>
                <div id="ea-inseguras" class="kairo-section kairo-sec-alertas">
                    <div class="kairo-section-title">Acciones inseguras identificadas</div>
                    <div class="kairo-content whitespace-pre-line">{{ $secciones['ACCIONES INSEGURAS IDENTIFICADAS'] ?? '' }}</div>
                </div>
                <div id="ea-factores" class="kairo-section kairo-sec-profesionales">
                    <div class="kairo-section-title">Factores contributivos</div>
                    <div class="kairo-content">{!! \Illuminate\Support\Str::markdown($secciones['FACTORES CONTRIBUTIVOS'] ?? '') !!}</div>
                </div>
                <div id="ea-cultura" class="kairo-section kairo-sec-acciones">
                    <div class="kairo-section-title">Factores organizacionales y culturales</div>
                    <div class="kairo-content">{!! \Illuminate\Support\Str::markdown($secciones['FACTORES RELACIONADOS CON LA ADMINISTRACIÓN DE LA CULTURA ORGANIZACIONAL'] ?? '') !!}</div>
                </div>
                <div id="ea-lecciones" class="kairo-section kairo-sec-acciones">
                    <div class="kairo-section-title">Lecciones aprendidas</div>
                    <div class="kairo-content whitespace-pre-line">{{ $secciones['LECCIONES APRENDIDAS'] ?? '' }}</div>
                </div>
                <div id="ea-conclusiones" class="kairo-section kairo-sec-analisis">
                    <div class="kairo-section-title" style="color:var(--c-green)">Conclusiones</div>
                    <div class="kairo-content whitespace-pre-line">{{ $secciones['CONCLUSIONES'] ?? '' }}</div>
                </div>
                <p class="text-[11px]" style="color:var(--c-text-faint)">Métrica de tokens informativa de OpenClaw; no representa un cobro de API. El resultado es un borrador para revisión humana.</p>
            </div>
        </div>
    @endif
</div>
