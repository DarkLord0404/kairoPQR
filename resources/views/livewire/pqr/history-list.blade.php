<div class="max-w-6xl mx-auto">

    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-2xl font-bold text-gray-800">{{ $total }}</div>
            <div class="text-xs text-gray-500 uppercase tracking-wide">Analisis realizados</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-2xl font-bold text-red-600">{{ $totalJuridica }}</div>
            <div class="text-xs text-gray-500 uppercase tracking-wide">Requirieron revision juridica</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-2xl font-bold text-gray-800">{{ $analyses->total() }}</div>
            <div class="text-xs text-gray-500 uppercase tracking-wide">Total en historial</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-left">Fecha</th>
                    <th class="px-4 py-3 text-left">Usuario</th>
                    <th class="px-4 py-3 text-left">Queja (extracto)</th>
                    <th class="px-4 py-3 text-left">Clasificacion</th>
                    <th class="px-4 py-3 text-left">Alerta juridica</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($analyses as $a)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $a->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $a->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ \Illuminate\Support\Str::limit($a->queja, 80) }}</td>
                        <td class="px-4 py-3">
                            @if ($a->clasificacion)
                                <span class="text-xs font-semibold px-2 py-1 rounded-md bg-blue-50 text-blue-700">{{ $a->clasificacion }}</span>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($a->requiere_revision_juridica)
                                <span class="text-xs font-bold px-2 py-1 rounded-md bg-red-700 text-white">SI</span>
                            @else
                                <span class="text-xs text-gray-400">No</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Aun no se han registrado analisis.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $analyses->links() }}</div>
</div>
