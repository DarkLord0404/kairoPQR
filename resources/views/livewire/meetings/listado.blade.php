<div class="max-w-6xl mx-auto">

    <div class="kairo-intro kairo-panel flex items-start gap-4 p-5 mb-4">
        <img src="{{ asset('kairo.png') }}" alt="Kairo" class="w-12 h-12 kairo-avatar-ring flex-shrink-0">
        <div class="text-sm leading-relaxed" style="color: var(--kairo-blue-dim)">
            <strong style="color: var(--kairo-blue-light)">Reuniones grabadas por Kairo.</strong>
            Aqui aparecen todas las reuniones a las que el bot asistio, con su audio, transcripcion y acta generada.
        </div>
    </div>

    @if(auth()->user()?->isMaster())
        <div class="kairo-panel mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div><strong class="text-sm" style="color:var(--kairo-text)">Envío automático a CUMPLE</strong><p class="mt-1 text-xs" style="color:var(--kairo-text-dim)">Envía como borrador cada acta nueva o actualizada. No crea acciones automáticamente.</p></div>
            <label class="flex shrink-0 cursor-pointer items-center gap-3 text-sm" style="color:var(--kairo-text)"><input type="checkbox" wire:model.live="envioAutomaticoCumple" class="rounded border-slate-600 bg-slate-900 text-blue-600"><span>{{ $envioAutomaticoCumple ? 'Activado' : 'Desactivado' }}</span></label>
        </div>
    @endif

    {{-- Resumen de almacenamiento --}}
    <div class="flex flex-wrap gap-3 mb-6">
        <div class="kairo-stat-card flex-1 min-w-[140px]" style="padding:.75rem 1rem;">
            <div class="text-xs mb-1" style="color: var(--kairo-text-dim)">Audio total en disco</div>
            <div class="text-lg font-bold" style="color: var(--kairo-blue-light)">{{ $this->pesoTotalAudio }}</div>
        </div>
        <div class="kairo-stat-card flex-1 min-w-[140px]" style="padding:.75rem 1rem;">
            <div class="text-xs mb-1" style="color: var(--kairo-text-dim)">Grabaciones activas</div>
            <div class="text-lg font-bold" style="color: var(--kairo-blue-light)">{{ $this->reunionesConAudio }}</div>
        </div>
        <div class="kairo-stat-card flex-1 min-w-[140px]" style="padding:.75rem 1rem;">
            <div class="text-xs mb-1" style="color: var(--kairo-text-dim)">Total de reuniones</div>
            <div class="text-lg font-bold" style="color: var(--kairo-blue-light)">{{ \App\Models\Meeting::count() }}</div>
        </div>
    </div>

    @if (session('status'))
        <div class="kairo-panel p-4 mb-4 text-sm" style="color:#6ee7b7;border:1px solid #064e3b;">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-3" x-data="{ abierto: {{ ($busqueda || $fechaDesde || $fechaHasta || $duracionFiltro || $estadoFiltro) ? 'true' : 'false' }} }">
        <button type="button" @click="abierto = ! abierto"
            class="kairo-btn-copy text-xs flex items-center gap-1">
            <span>Filtros</span>
            @if ($busqueda || $fechaDesde || $fechaHasta || $duracionFiltro || $estadoFiltro)
                <span class="rounded-full px-1.5" style="background: rgba(59,130,246,.35); color:#93c5fd; font-size:10px;">activos</span>
            @endif
            <span x-text="abierto ? '▲' : '▼'" style="font-size:9px;"></span>
        </button>

        <div x-show="abierto" x-cloak x-transition class="rounded-lg mt-2" style="background: var(--kairo-bg-2); border: 1px solid var(--kairo-border); padding: 1.25rem 1.5rem;">
            <div class="text-xs font-semibold mb-4" style="color: var(--kairo-blue-dim);">Filtrar reuniones</div>

            <div class="mb-4">
                <label class="block kairo-label mb-1.5 text-xs">Nombre de la reunión</label>
                <input type="text" wire:model.live.debounce.250ms="busqueda"
                       placeholder="Escribe para buscar..." class="kairo-textarea w-full text-sm p-2.5">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block kairo-label mb-1.5 text-xs">Desde</label>
                    <input type="date" wire:model.live="fechaDesde" class="kairo-textarea w-full text-sm p-2.5">
                </div>
                <div>
                    <label class="block kairo-label mb-1.5 text-xs">Hasta</label>
                    <input type="date" wire:model.live="fechaHasta" class="kairo-textarea w-full text-sm p-2.5">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block kairo-label mb-1.5 text-xs">Duración</label>
                    <select wire:model.live="duracionFiltro" class="kairo-textarea w-full text-sm p-2.5">
                        <option value="">Todas</option>
                        <option value="corta">Menos de 30 min</option>
                        <option value="media">30 a 60 min</option>
                        <option value="larga">Más de 60 min</option>
                    </select>
                </div>
                <div>
                    <label class="block kairo-label mb-1.5 text-xs">Estado</label>
                    <select wire:model.live="estadoFiltro" class="kairo-textarea w-full text-sm p-2.5">
                        <option value="">Todos</option>
                        <option value="con_acta">Acta lista</option>
                        <option value="transcrita">Transcrita</option>
                        <option value="pendiente">Pendiente</option>
                    </select>
                </div>
            </div>

            @if ($busqueda || $fechaDesde || $fechaHasta || $duracionFiltro || $estadoFiltro)
                <button type="button" wire:click="limpiarFiltros" class="kairo-btn-copy mt-4 text-xs">Limpiar filtros</button>
            @endif
        </div>
    </div>

    <hr style="border-color: var(--kairo-border); margin: 22px 0;">

    <div class="text-xs font-semibold mb-2" style="color: var(--kairo-text-dim);">
        {{ $reuniones->total() }} {{ $reuniones->total() === 1 ? 'reunión' : 'reuniones' }}
    </div>

    @if ($reuniones->isEmpty())
        <div class="kairo-panel p-8 text-center text-sm" style="color: var(--kairo-text-dim)">
            No hay reuniones que coincidan con los filtros.
        </div>
    @else
        <div class="kairo-panel overflow-hidden" style="border: 1px solid var(--kairo-border);">
            <table class="kairo-table">
                <thead>
                    <tr>
                        <th style="width:240px;">Nombre de la reunión</th>
                        <th style="width:160px;">Fecha</th>
                        <th style="width:80px;">Duracion</th>
                        <th style="width:110px;">Estado</th>
                        <th style="width:80px;">Audio</th>
                        <th style="width:60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reuniones as $reunion)
                        <tr>
                            <td style="color: var(--kairo-text); white-space: normal; word-wrap: break-word; line-height: 1.3;" title="{{ $reunion->titulo }}">{{ $reunion->titulo }}</td>
                            <td>{{ $reunion->fecha_inicio?->translatedFormat('d M Y, h:i A') }}</td>
                            <td>{{ $reunion->duracion_legible ?? '—' }}</td>
                            <td>
                                @php
                                    $estilos = [
                                        'con_acta' => 'background: rgba(16,185,129,.15); color:#6ee7b7; border:1px solid #064e3b;',
                                        'transcrita' => 'background: rgba(59,130,246,.15); color:#93c5fd; border:1px solid #1e3a8a;',
                                        'pendiente' => 'background: rgba(148,163,184,.15); color:#94a3b8; border:1px solid #334155;',
                                    ];
                                    $etiquetas = ['con_acta' => 'Acta lista', 'transcrita' => 'Transcrita', 'pendiente' => 'Pendiente'];
                                @endphp
                                <span class="text-xs font-semibold px-2 py-1 rounded" style="{{ $estilos[$reunion->estado] ?? '' }}">
                                    {{ $etiquetas[$reunion->estado] ?? $reunion->estado }}
                                </span>
                            </td>
                            <td class="text-xs" style="color: var(--kairo-text-dim);">
                                @if ($reunion->tieneAudio())
                                    {{ $reunion->audioPesaLegible() }}
                                @elseif ($reunion->audio_eliminado_en)
                                    <span title="Eliminado el {{ $reunion->audio_eliminado_en->format('d/m/Y') }}">eliminado</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($reunion->cumple_synced_at)<span class="mb-1 block text-[10px]" style="color:#6ee7b7" title="{{ $reunion->cumple_synced_at->format('d/m/Y H:i') }}">CUMPLE ✓</span>@elseif($reunion->cumple_sync_error)<span class="mb-1 block text-[10px]" style="color:#fca5a5" title="{{ $reunion->cumple_sync_error }}">CUMPLE !</span>@endif
                                <a href="{{ route('reuniones.detalle', $reunion) }}" wire:navigate
                                   class="kairo-btn-copy">Ver</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $reuniones->onEachSide(1)->links('vendor.pagination.kairo') }}
        </div>
    @endif
</div>
