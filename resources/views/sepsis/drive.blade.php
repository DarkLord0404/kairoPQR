<x-app-layout>
    <div class="w-full space-y-6">
        @if(session('status'))
            <div role="status" class="kairo-panel p-4" style="color:var(--c-green)">{{ session('status') }}</div>
        @endif
        <section class="kairo-panel p-6 space-y-4" aria-labelledby="connection-title">
            <div class="flex items-center gap-3">
                <span aria-hidden="true" style="width:12px;height:12px;border-radius:50%;display:inline-block;background:{{ $connection['state'] === 'ok' ? 'var(--c-green)' : ($connection['state'] === 'expired' ? 'var(--c-red)' : 'var(--c-amber)') }}"></span>
                <h2 id="connection-title" class="font-semibold text-lg" style="color:var(--c-text)">{{ $connection['title'] }}</h2>
            </div>
            <p style="color:var(--c-text-dim)">{{ $connection['message'] }}</p>
            <p class="text-sm" style="color:var(--c-text-dim);overflow-wrap:anywhere">Cuenta: <strong>alexander.torres@clinicadeoccidente.com</strong></p>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('sepsis.drive.connect') }}" class="inline-flex items-center rounded-md px-4 py-2 font-semibold text-sm" style="background:var(--c-accent);color:white">{{ $connection['state'] === 'missing' ? 'Autorizar Google Drive' : 'Reautorizar Google Drive' }}</a>
                <form method="POST" action="{{ route('sepsis.drive.verify') }}">@csrf
                    <button type="submit" class="rounded-md px-4 py-2 text-sm" style="border:1px solid var(--c-border);color:var(--c-text)">Verificar conexión</button>
                </form>
            </div>
            <p class="text-xs" style="color:var(--c-text-dim)">Última comprobación: {{ $connection['checked_at'] }} · Colombia</p>
        </section>
        <section class="kairo-panel p-6 space-y-3 text-sm" style="color:var(--c-text-dim)">
            <h2 class="font-semibold" style="color:var(--c-text)">Cuándo reautorizar</h2>
            <p>Kairo renueva automáticamente el acceso de corta duración. Si Google vence o revoca la autorización, usa este botón y acepta nuevamente los permisos con tu cuenta institucional.</p>
            <p>La reautorización conserva las historias y los registros existentes.</p>
            <p>El estado de esta página corresponde a la conexión con Google. La habilitación del análisis clínico depende también de la validación del modelo y del piloto.</p>
        </section>
        <section class="kairo-panel p-6 space-y-4" aria-labelledby="history-title">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="history-title" class="font-semibold text-lg" style="color:var(--c-text)">Historial de análisis de sepsis</h2>
                <span class="rounded-md px-3 py-2 text-sm" style="background:rgb(var(--c-accent-rgb) / .12);color:var(--c-accent-soft)">Límite: 1 historia al día · {{ $audit['used_today'] }} / 1 intentos hoy</span>
            </div>
            <p class="text-sm" style="color:var(--c-text-dim)">Incluye historias nuevas y actualizaciones por egreso. Un error también ocupa el cupo del día; las demás historias permanecen en espera. El límite se reinicia a medianoche de Colombia y la revisión programada se realiza a las 06:00.</p>
            @if(!($audit['processing_enabled'] ?? false))
                <p role="status" class="text-sm" style="color:var(--c-amber)">Procesamiento clínico detenido hasta completar la validación del modelo y del piloto. El límite diario ya está configurado.</p>
            @elseif($audit['remaining_today'] === 0)
                <p role="status" class="text-sm" style="color:var(--c-amber)">Cupo diario utilizado. No se iniciarán más análisis hoy.</p>
            @endif
            <div style="overflow-x:auto">
                <table class="w-full text-sm" style="color:var(--c-text);border-collapse:collapse;min-width:880px">
                    <thead><tr style="text-align:left;border-bottom:1px solid var(--c-border)">
                        @foreach(['Inicio · Colombia','Ingreso / caso','Resultado','Llamadas al modelo','Duración','Tokens reportados','Detalle'] as $heading)
                            <th class="px-3 py-3">{{ $heading }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                        @forelse(($audit['history'] ?? []) as $item)
                            <tr style="border-bottom:1px solid var(--c-border)">
                                <td class="px-3 py-3 whitespace-nowrap">{{ isset($item['inicio']) ? \Carbon\Carbon::parse($item['inicio'])->timezone('America/Bogota')->format('d/m/Y H:i') : '—' }}</td>
                                <td class="px-3 py-3">{{ $item['ingreso'] ?: ($item['case_id'] ? substr($item['case_id'], 0, 12) : 'Por identificar') }}</td>
                                <td class="px-3 py-3">{{ ['analizado' => 'Analizado y registrado', 'error' => 'Error · pendiente de revisión', 'en_proceso' => 'Iniciado · resultado sin confirmar'][$item['resultado']] ?? $item['resultado'] }}</td>
                                <td class="px-3 py-3">{{ $item['llamadas_modelo'] ?? 'No disponible' }}</td>
                                <td class="px-3 py-3 whitespace-nowrap">{{ isset($item['duracion_s']) ? number_format($item['duracion_s'], 1, ',', '.') . ' s' : '—' }}</td>
                                <td class="px-3 py-3">{{ isset($item['tokens_total']) ? number_format($item['tokens_total'], 0, ',', '.') : 'No disponible' }}</td>
                                <td class="px-3 py-3">{{ $item['codigo_error'] ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-3 py-6" style="color:var(--c-text-dim)">Todavía no se han iniciado análisis clínicos con este registro. Las pruebas técnicas anteriores no se cuentan como pacientes analizados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="text-xs" style="color:var(--c-text-dim)">Los tokens o el costo reportado por el modelo no equivalen a créditos de Work. Cuando el proveedor no informa el uso, se muestra «No disponible». Esta página muestra los últimos 200 intentos; el registro completo se conserva en el servidor y se sincroniza en la pestaña Historial de Sepsis CDO.</p>
        </section>
    </div>
</x-app-layout>
