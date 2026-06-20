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

    <div class="kairo-panel overflow-hidden">
        <table class="kairo-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Queja (extracto)</th>
                    <th>Clasificacion</th>
                    <th>Alerta juridica</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($analyses as $a)
                    <tr>
                        <td style="color: var(--kairo-text-dim)" class="whitespace-nowrap">{{ $a->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $a->user?->name ?? '—' }}</td>
                        <td style="color: var(--kairo-text)">{{ \Illuminate\Support\Str::limit($a->queja, 80) }}</td>
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
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center" style="color: var(--kairo-text-dim)">Aun no se han registrado analisis.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4" style="color: var(--kairo-text-dim)">{{ $analyses->links() }}</div>
</div>
