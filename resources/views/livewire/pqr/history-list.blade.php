<div class="max-w-6xl mx-auto">

    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="kairo-stat-card">
            <div class="kairo-stat-value">{{ $total }}</div>
            <div class="kairo-stat-label">Analisis realizados</div>
        </div>
        <div class="kairo-stat-card">
            <div class="kairo-stat-value" style="color:#fca5a5">{{ $totalJuridica }}</div>
            <div class="kairo-stat-label">Requirieron revision juridica</div>
        </div>
        <div class="kairo-stat-card">
            <div class="kairo-stat-value">{{ $analyses->total() }}</div>
            <div class="kairo-stat-label">Total en historial</div>
        </div>
    </div>

    @if (session('status'))
        <div class="kairo-panel p-4 mb-4 text-sm" style="color:#6ee7b7;border:1px solid #064e3b;">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-3" x-data="{ abierto: {{ ($busqueda || $fechaDesde || $fechaHasta || $clasificacionFiltro || $juridicaFiltro) ? 'true' : 'false' }} }">
        <button type="button" @click="abierto = ! abierto"
            class="kairo-btn-copy text-xs flex items-center gap-1">
            <span>Filtros</span>
            @if ($busqueda || $fechaDesde || $fechaHasta || $clasificacionFiltro || $juridicaFiltro)
                <span class="rounded-full px-1.5" style="background: rgba(59,130,246,.35); color:#93c5fd; font-size:10px;">activos</span>
            @endif
            <span x-text="abierto ? '▲' : '▼'" style="font-size:9px;"></span>
        </button>

        <div x-show="abierto" x-cloak x-transition class="p-3 mt-2 rounded-lg" style="background: var(--kairo-bg-2); border: 1px solid var(--kairo-border);">
            <div class="text-xs font-semibold mb-2" style="color: var(--kairo-blue-dim);">Filtrar análisis</div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
                <div>
                    <label class="block kairo-label mb-1 text-xs">Queja contiene</label>
                    <input type="text" wire:model.live.debounce.400ms="busqueda"
                           placeholder="Buscar..." class="kairo-textarea w-full text-xs p-1.5">
                </div>
                <div>
                    <label class="block kairo-label mb-1 text-xs">Desde</label>
                    <input type="date" wire:model.live="fechaDesde" class="kairo-textarea w-full text-xs p-1.5">
                </div>
                <div>
                    <label class="block kairo-label mb-1 text-xs">Hasta</label>
                    <input type="date" wire:model.live="fechaHasta" class="kairo-textarea w-full text-xs p-1.5">
                </div>
                <div>
                    <label class="block kairo-label mb-1 text-xs">Clasificación</label>
                    <select wire:model.live="clasificacionFiltro" class="kairo-textarea w-full text-xs p-1.5">
                        <option value="">Todas</option>
                        <option value="URGENCIAS">Urgencias</option>
                        <option value="OTRO SERVICIO">Otro servicio</option>
                        <option value="MIXTA">Mixta</option>
                    </select>
                </div>
                <div>
                    <label class="block kairo-label mb-1 text-xs">Alerta jurídica</label>
                    <select wire:model.live="juridicaFiltro" class="kairo-textarea w-full text-xs p-1.5">
                        <option value="">Todas</option>
                        <option value="si">Sí</option>
                        <option value="no">No</option>
                    </select>
                </div>
            </div>
            @if ($busqueda || $fechaDesde || $fechaHasta || $clasificacionFiltro || $juridicaFiltro)
                <button type="button" wire:click="limpiarFiltros" class="kairo-btn-copy mt-2 text-xs">Limpiar filtros</button>
            @endif
        </div>
    </div>

    <hr style="border-color: var(--kairo-border); margin: 22px 0;">

    <div class="text-xs font-semibold mb-2" style="color: var(--kairo-text-dim);">
        {{ $analyses->total() }} {{ $analyses->total() === 1 ? 'análisis' : 'análisis' }} encontrados
    </div>

    <div class="kairo-panel overflow-hidden" style="border: 1px solid var(--kairo-border);">
        <table class="kairo-table">
            <thead>
                <tr>
                    <th style="width:140px;">Fecha</th>
                    <th style="width:140px;">Usuario</th>
                    <th>Queja (extracto)</th>
                    <th style="width:120px;">Clasificacion</th>
                    <th style="width:110px;">Alerta juridica</th>
                    <th style="width:70px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($analyses as $a)
                    <tr>
                        <td style="color: var(--kairo-text-dim)" class="whitespace-nowrap">{{ $a->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $a->user?->name ?? '—' }}</td>
                        <td style="color: var(--kairo-text); white-space: normal; word-wrap: break-word;">{{ \Illuminate\Support\Str::limit($a->queja, 90) }}</td>
                        <td>
                            @if ($a->clasificacion)
                                <span class="text-xs font-semibold px-2 py-1 rounded-md" style="background: rgba(59,130,246,0.15); color: var(--kairo-blue-dim)">{{ $a->clasificacion }}</span>
                            @else
                                <span class="text-xs" style="color: var(--kairo-text-dim)">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($a->requiere_revision_juridica)
                                <span class="text-xs font-bold px-2 py-1 rounded-md" style="background:#991b1b; color:#fff">SI</span>
                            @else
                                <span class="text-xs" style="color: var(--kairo-text-dim)">No</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('historial.detalle', $a) }}" wire:navigate class="kairo-btn-copy">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center" style="color: var(--kairo-text-dim)">No hay análisis que coincidan con los filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $analyses->onEachSide(1)->links('vendor.pagination.kairo') }}
    </div>
</div>
