<div x-data="{ abierto: false }" class="relative flex items-center" @click.outside="abierto = false"
    wire:poll.2000ms="polling">

    {{-- Botón indicador --}}
    <button type="button" @click="abierto = !abierto"
        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer"
        style="
            border: 1px solid {{ $hayFallos ? 'rgba(239,68,68,0.5)' : ($hayAdvertencias ? 'rgba(245,158,11,0.5)' : 'var(--c-border-strong)') }};
            background: {{ $hayFallos ? 'rgba(239,68,68,0.12)' : ($hayAdvertencias ? 'rgba(245,158,11,0.12)' : 'var(--c-surface-2)') }};
            color: {{ $hayFallos ? 'var(--c-red-soft)' : ($hayAdvertencias ? 'var(--c-amber)' : 'var(--c-text-soft)') }};
        ">
        @if ($hayFallos)
            <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse flex-shrink-0"></span>
            <span>Falla detectada</span>
        @elseif ($hayAdvertencias)
            <span class="w-2 h-2 rounded-full bg-yellow-400 animate-pulse flex-shrink-0"></span>
            <span>Advertencia</span>
        @elseif ($verificando)
            <span class="w-2 h-2 rounded-full bg-zinc-400 animate-pulse flex-shrink-0"></span>
            <span>Verificando...</span>
        @else
            <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:#22c55e; box-shadow:0 0 6px #22c55e;"></span>
            <span class="hidden sm:inline">Servicios activos</span>
        @endif
    </button>

    {{-- Popover --}}
    <div x-show="abierto" x-cloak x-transition
        class="absolute right-0 rounded-xl shadow-2xl z-50"
        style="width:min(440px, calc(100vw - 2rem)); top:calc(100% + 8px); background:var(--c-surface-2); border:1px solid var(--c-border);">

        {{-- Header --}}
        <div class="px-4 py-3 flex items-center justify-between" style="border-bottom:1px solid var(--c-border)">
            <div>
                <div class="text-sm font-bold" style="color:var(--c-text)">Estado de servicios</div>
                <div class="text-xs mt-0.5" style="color:var(--c-text-dim)">
                    @if ($verificando)
                        Verificando en tiempo real... ({{ $totalVerificados }}/12)
                    @elseif ($ultimaVerificacion)
                        Última verificación: {{ $ultimaVerificacion }}
                    @else
                        Sin verificaciones aún
                    @endif
                </div>
            </div>
            @if (auth()->user()->isMaster())
                <button wire:click="ejecutarAhora" wire:loading.attr="disabled" @click="abierto = true"
                    class="kairo-btn-copy text-xs flex items-center gap-1.5"
                    @disabled($verificando)>
                    @if ($verificando)
                        <span class="w-3 h-3 border border-white/40 border-t-white rounded-full animate-spin inline-block"></span>
                        Verificando...
                    @else
                        Verificar ahora
                    @endif
                </button>
            @endif
        </div>

        {{-- Grupos de servicios --}}
        <div style="max-height:480px; overflow-y:auto;">

            @php
                $gruposOrden = ['Infraestructura', 'Inteligencia Artificial', 'Calendarios Google', 'KairoMeet'];
                $iconosGrupo = [
                    'Infraestructura'       => '🗄️',
                    'Inteligencia Artificial' => '🤖',
                    'Calendarios Google'    => '📅',
                    'KairoMeet'             => '🎙️',
                ];
                $serviciosPorGrupo = [
                    'Infraestructura'       => 3,
                    'Inteligencia Artificial' => 2,
                    'Calendarios Google'    => 4,
                    'KairoMeet'             => 3,
                ];
                $verificadosHastaAhora = 0;
            @endphp

            @if (empty($grupos) && !$verificando)
                <div class="px-4 py-8 text-center text-xs" style="color:var(--c-text-dim)">
                    Sin verificaciones. Usa "Verificar ahora" o espera la verificación automática (cada 3 h).
                </div>
            @else
                @foreach ($gruposOrden as $nombreGrupo)
                    @php
                        $items = $grupos[$nombreGrupo] ?? [];
                        $esperadosGrupo = $serviciosPorGrupo[$nombreGrupo] ?? 0;
                        $hayFalloGrupo = collect($items)->contains(fn($i) => $i['estado'] === 'fallo');
                        $hayAdvGrupo   = collect($items)->contains(fn($i) => $i['estado'] === 'advertencia');
                        $icono = $iconosGrupo[$nombreGrupo] ?? '⚙️';
                    @endphp

                    {{-- Header del grupo --}}
                    <div class="px-4 py-2 flex items-center justify-between sticky top-0"
                        style="background:var(--c-surface-2); border-bottom:1px solid rgba(16,24,40,0.06); z-index:1;">
                        <div class="flex items-center gap-2">
                            <span class="text-sm">{{ $icono }}</span>
                            <span class="text-xs font-bold uppercase tracking-wider" style="color:var(--c-text-dim)">
                                {{ $nombreGrupo }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            @if ($hayFalloGrupo)
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse inline-block"></span>
                                <span class="text-[10px]" style="color:var(--c-red-soft)">
                                    {{ collect($items)->where('estado', 'fallo')->count() }} falla(s)
                                </span>
                            @elseif ($hayAdvGrupo)
                                <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 inline-block"></span>
                                <span class="text-[10px]" style="color:var(--c-amber)">advertencia</span>
                            @elseif (!empty($items))
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block"></span>
                                <span class="text-[10px]" style="color:var(--c-green)">OK</span>
                            @elseif ($verificando)
                                <span class="text-[10px] animate-pulse" style="color:var(--c-text-dim)">pendiente</span>
                            @endif
                        </div>
                    </div>

                    {{-- Servicios del grupo --}}
                    @foreach ($items as $item)
                        <div class="flex items-start gap-3 px-4 py-2.5" style="border-bottom:1px solid rgba(16,24,40,0.04)">
                            <div class="mt-1.5 flex-shrink-0">
                                @if ($item['estado'] === 'ok')
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span>
                                @elseif ($item['estado'] === 'advertencia')
                                    <span class="w-2 h-2 rounded-full bg-yellow-400 inline-block"></span>
                                @else
                                    <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse inline-block"></span>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-semibold flex items-center justify-between gap-2"
                                    style="color:{{ $item['estado'] === 'ok' ? 'var(--c-text)' : ($item['estado'] === 'advertencia' ? 'var(--c-amber)' : 'var(--c-red-soft)') }}">
                                    <span class="truncate">{{ $item['servicio'] }}</span>
                                    @if ($item['duracion_ms'])
                                        <span class="font-normal text-[10px] flex-shrink-0" style="color:var(--c-text-dim)">{{ $item['duracion_ms'] }}ms</span>
                                    @endif
                                </div>
                                <div class="text-xs mt-0.5 break-words leading-relaxed" style="color:var(--c-text-dim)">{{ $item['mensaje'] ?? '—' }}</div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Placeholders pendientes de este grupo mientras verifica --}}
                    @if ($verificando)
                        @php $pendientesGrupo = max(0, $esperadosGrupo - count($items)); @endphp
                        @for ($i = 0; $i < $pendientesGrupo; $i++)
                            <div class="flex items-center gap-3 px-4 py-2.5" style="border-bottom:1px solid rgba(16,24,40,0.04)">
                                <span class="w-2 h-2 rounded-full bg-gray-600 inline-block flex-shrink-0 animate-pulse"></span>
                                <span class="text-xs animate-pulse" style="color:var(--c-text-dim)">Verificando...</span>
                            </div>
                        @endfor
                    @endif

                @endforeach
            @endif
        </div>

        {{-- Footer --}}
        @php
            $totalOk = 0;
            $totalItems = 0;
            foreach ($grupos as $items) {
                foreach ($items as $i) {
                    $totalItems++;
                    if ($i['estado'] === 'ok') $totalOk++;
                }
            }
        @endphp
        <div class="px-4 py-2 text-xs flex items-center justify-between" style="color:var(--c-text-dim);border-top:1px solid var(--c-border)">
            <span>Verificación automática: cada 3 h</span>
            @if ($totalItems > 0)
                <span>{{ $totalOk }}/{{ $totalItems }} servicios OK</span>
            @endif
        </div>
    </div>
</div>
