<div class="w-full">

    <p class="text-sm mb-5 max-w-3xl" style="color:var(--c-text-dim)">Aquí aparecen todas las reuniones a las que asistió el bot, con su audio, transcripción y acta generada.</p>

    @if(auth()->user()?->isMaster())
        <div class="kairo-panel mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div><strong class="text-sm" style="color:var(--c-text)">Envío automático a CUMPLE</strong><p class="mt-1 text-xs" style="color:var(--c-text-dim)">Envía como borrador cada acta nueva o actualizada. No crea acciones automáticamente.</p></div>
            <label class="flex shrink-0 cursor-pointer items-center gap-3 text-sm" style="color:var(--c-text)"><input type="checkbox" wire:model.live="envioAutomaticoCumple" class="rounded border-gray-300 text-accent-700"><span>{{ $envioAutomaticoCumple ? 'Activado' : 'Desactivado' }}</span></label>
        </div>
    @endif

    {{-- Resumen de almacenamiento --}}
    <div class="flex flex-wrap gap-3 mb-6">
        <div class="kairo-stat-card flex-1 min-w-[140px]" style="padding:.75rem 1rem;">
            <div class="text-xs mb-1" style="color: var(--c-text-dim)">Audio total en disco</div>
            <div class="text-lg font-bold" style="color: var(--c-text)">{{ $this->pesoTotalAudio }}</div>
        </div>
        <div class="kairo-stat-card flex-1 min-w-[140px]" style="padding:.75rem 1rem;">
            <div class="text-xs mb-1" style="color: var(--c-text-dim)">Reuniones con audio</div>
            <div class="text-lg font-bold" style="color: var(--c-text)">{{ $this->reunionesConAudio }}</div>
        </div>
        <div class="kairo-stat-card flex-1 min-w-[140px]" style="padding:.75rem 1rem;">
            <div class="text-xs mb-1" style="color: var(--c-text-dim)">Total de reuniones</div>
            <div class="text-lg font-bold" style="color: var(--c-text)">{{ \App\Models\Meeting::count() }}</div>
        </div>
    </div>

    @if (session('status'))
        <div class="kairo-panel p-4 mb-4 text-sm" style="color:var(--c-green);border:1px solid #cfe9db;">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-3" x-data="{ abierto: {{ ($busqueda || $fechaDesde || $fechaHasta || $duracionFiltro || $estadoFiltro) ? 'true' : 'false' }} }">
        <button type="button" @click="abierto = ! abierto"
            class="kairo-btn-copy text-xs flex items-center gap-1">
            <span>Filtros</span>
            @if ($busqueda || $fechaDesde || $fechaHasta || $duracionFiltro || $estadoFiltro)
                <span class="rounded-full px-1.5" style="background: rgb(var(--c-accent-rgb) / .35); color:var(--c-text-soft); font-size:10px;">activos</span>
            @endif
            <span x-text="abierto ? '▲' : '▼'" style="font-size:9px;"></span>
        </button>

        <div x-show="abierto" x-cloak x-transition class="rounded-lg mt-2" style="background: var(--c-surface-2); border: 1px solid var(--c-border); padding: 1.25rem 1.5rem;">
            <div class="text-xs font-semibold mb-4" style="color: var(--c-text-soft);">Filtrar reuniones</div>

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

    <hr style="border-color: var(--c-border); margin: 22px 0;">

    <div class="text-xs font-semibold mb-2" style="color: var(--c-text-dim);">
        {{ $reuniones->total() }} {{ $reuniones->total() === 1 ? 'reunión' : 'reuniones' }}
    </div>

    @if ($reuniones->isEmpty())
        <div class="kairo-panel p-8 text-center text-sm" style="color: var(--c-text-dim)">
            No hay reuniones que coincidan con los filtros.
        </div>
    @else
        <div class="kairo-panel overflow-hidden" style="border: 1px solid var(--c-border);">
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
                            <td style="color: var(--c-text); white-space: normal; word-wrap: break-word; line-height: 1.3;" title="{{ $reunion->titulo }}">{{ $reunion->titulo }}</td>
                            <td>{{ $reunion->fecha_inicio?->translatedFormat('d M Y, h:i A') }}</td>
                            <td>{{ $reunion->duracion_legible ?? '—' }}</td>
                            <td>
                                @php
                                    $estilos = [
                                        'con_acta' => 'background: rgb(47 138 95 / .15); color:var(--c-green); border:1px solid #cfe9db;',
                                        'transcrita' => 'background: rgb(var(--c-accent-rgb) / .15); color:var(--c-text-soft); border:1px solid var(--c-border-strong);',
                                        'pendiente' => 'background: rgb(var(--c-steel-rgb) / .15); color:var(--c-text-dim); border:1px solid var(--c-border-strong);',
                                    ];
                                    $etiquetas = ['con_acta' => 'Acta lista', 'transcrita' => 'Transcrita', 'pendiente' => 'Pendiente'];
                                @endphp
                                <span class="text-xs font-semibold px-2 py-1 rounded" style="{{ $estilos[$reunion->estado] ?? '' }}">
                                    {{ $etiquetas[$reunion->estado] ?? $reunion->estado }}
                                </span>
                            </td>
                            <td class="text-xs" style="color: var(--c-text-dim);">
                                @if ($reunion->tieneAudio())
                                    {{ $reunion->audioPesaLegible() }}
                                @elseif ($reunion->audio_eliminado_en)
                                    <span title="Eliminado el {{ $reunion->audio_eliminado_en->format('d/m/Y') }}">eliminado</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($reunion->cumple_synced_at)<span class="mb-1 block text-[10px]" style="color:var(--c-green)" title="{{ $reunion->cumple_synced_at->format('d/m/Y H:i') }}">CUMPLE ✓</span>@elseif($reunion->cumple_sync_error)<span class="mb-1 block text-[10px]" style="color:var(--c-red-soft)" title="{{ $reunion->cumple_sync_error }}">CUMPLE !</span>@endif
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
