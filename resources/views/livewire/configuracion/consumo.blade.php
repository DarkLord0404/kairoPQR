<div class="max-w-7xl mx-auto space-y-6">
    <div>
        <div class="text-xs font-semibold uppercase tracking-wider" style="color:var(--kairo-blue-dim)">Configuración</div>
        <h1 class="text-2xl font-bold mt-1" style="color:var(--kairo-text)">Consumo de OpenClaw</h1>
        <p class="text-sm mt-1" style="color:var(--kairo-text-dim)">Tokens procesados por PQR y eventos adversos. Esta métrica no representa un cobro de API.</p>
    </div>

    <div class="kairo-panel p-4 grid grid-cols-2 md:grid-cols-5 gap-3">
        <div>
            <label class="block kairo-label mb-1 text-xs">Desde</label>
            <input type="date" wire:model.live="fechaDesde" class="kairo-textarea w-full text-sm p-2">
        </div>
        <div>
            <label class="block kairo-label mb-1 text-xs">Hasta</label>
            <input type="date" wire:model.live="fechaHasta" class="kairo-textarea w-full text-sm p-2">
        </div>
        <div>
            <label class="block kairo-label mb-1 text-xs">Módulo</label>
            <select wire:model.live="modulo" class="kairo-textarea w-full text-sm p-2">
                <option value="">PQR y EA</option>
                <option value="pqr">PQR</option>
                <option value="ea">Eventos adversos</option>
            </select>
        </div>
        <div>
            <label class="block kairo-label mb-1 text-xs">Modelo</label>
            <select wire:model.live="modelo" class="kairo-textarea w-full text-sm p-2">
                <option value="">Todos</option>
                @foreach ($modelos as $modeloDisponible)
                    <option value="{{ $modeloDisponible }}">{{ $modeloDisponible }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="button" wire:click="limpiarFiltros" class="kairo-btn-copy w-full justify-center">Últimos 30 días</button>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="kairo-stat-card"><div class="kairo-stat-value">{{ number_format($resumen['tokens'], 0, ',', '.') }}</div><div class="kairo-stat-label">Tokens procesados</div></div>
        <div class="kairo-stat-card"><div class="kairo-stat-value">{{ $resumen['promedio'] === null ? '—' : number_format($resumen['promedio'], 0, ',', '.') }}</div><div class="kairo-stat-label">Promedio por análisis medido</div></div>
        <div class="kairo-stat-card"><div class="kairo-stat-value">{{ $resumen['maximo'] === null ? '—' : number_format($resumen['maximo'], 0, ',', '.') }}</div><div class="kairo-stat-label">Máximo individual</div></div>
        <div class="kairo-stat-card"><div class="kairo-stat-value">{{ $resumen['medidos'] }}/{{ $resumen['analisis'] }}</div><div class="kairo-stat-label">Análisis con medición</div></div>
        <div class="kairo-stat-card"><div class="kairo-stat-value">{{ number_format($resumen['llamadas'], 0, ',', '.') }}</div><div class="kairo-stat-label">Llamadas al modelo</div></div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        @foreach ($porModulo as $nombre => $datos)
            <div class="kairo-panel p-5">
                <div class="text-sm font-bold mb-3" style="color:var(--kairo-blue-light)">{{ $nombre === 'EA' ? 'Eventos adversos' : $nombre }}</div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div><div class="text-xl font-bold" style="color:var(--kairo-text)">{{ number_format($datos['tokens'], 0, ',', '.') }}</div><div class="text-xs" style="color:var(--kairo-text-dim)">Tokens</div></div>
                    <div><div class="text-xl font-bold" style="color:var(--kairo-text)">{{ $datos['promedio'] === null ? '—' : number_format($datos['promedio'], 0, ',', '.') }}</div><div class="text-xs" style="color:var(--kairo-text-dim)">Promedio</div></div>
                    <div><div class="text-xl font-bold" style="color:var(--kairo-text)">{{ $datos['medidos'] }}/{{ $datos['analisis'] }}</div><div class="text-xs" style="color:var(--kairo-text-dim)">Medidos</div></div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="kairo-panel overflow-hidden" style="border:1px solid var(--kairo-border)">
        <div class="px-4 py-3 text-sm font-bold" style="color:var(--kairo-text);border-bottom:1px solid var(--kairo-border)">Últimas actividades del periodo</div>
        <div class="overflow-x-auto">
            <table class="kairo-table">
                <thead><tr><th>Fecha</th><th>Módulo</th><th>Usuario</th><th>Modelo</th><th>Entrada</th><th>Salida</th><th>Caché</th><th>Total</th><th>Llamadas</th><th>Tiempo</th></tr></thead>
                <tbody>
                    @forelse ($recientes as $fila)
                        <tr>
                            <td class="whitespace-nowrap">{{ $fila['created_at']->format('d/m/Y H:i') }}</td>
                            <td>{{ $fila['modulo'] }}</td><td>{{ $fila['usuario'] }}</td><td>{{ $fila['modelo'] ?? 'Sin dato histórico' }}</td>
                            <td>{{ $fila['tokens_entrada'] === null ? '—' : number_format($fila['tokens_entrada'], 0, ',', '.') }}</td>
                            <td>{{ $fila['tokens_salida'] === null ? '—' : number_format($fila['tokens_salida'], 0, ',', '.') }}</td>
                            <td>{{ $fila['tokens_cache'] === null ? '—' : number_format($fila['tokens_cache'], 0, ',', '.') }}</td>
                            <td class="font-semibold">{{ $fila['tokens_totales'] === null ? '—' : number_format($fila['tokens_totales'], 0, ',', '.') }}</td>
                            <td>{{ $fila['llamadas_modelo'] ?? '—' }}</td>
                            <td>{{ $fila['duracion_segundos'] === null ? '—' : number_format($fila['duracion_segundos'], 1, ',', '.').' s' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-8 text-center" style="color:var(--kairo-text-dim)">No hay análisis en el periodo seleccionado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-xs" style="color:var(--kairo-text-dim)">Los análisis anteriores a la instrumentación pueden tener solamente el total o aparecer sin medición. No se consulta ni muestra contenido clínico.</div>
</div>
