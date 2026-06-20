<div class="max-w-5xl mx-auto">

    <div class="flex items-start gap-4 bg-gradient-to-r from-blue-50 to-white border border-blue-100 rounded-xl p-5 mb-6">
        <img src="{{ asset('kairo.png') }}" alt="Kairo" class="w-12 h-12 rounded-full border-2 border-blue-400 object-cover flex-shrink-0">
        <div class="text-sm text-blue-800 leading-relaxed">
            <strong>Hola, soy Kairo.</strong> Analizo quejas, PQR y derechos de peticion del servicio de urgencias. Pegue la queja del usuario y los registros clinicos disponibles; construire una respuesta institucional formal lista para revision.
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-2">Queja o solicitud del usuario</label>
            <textarea wire:model="queja" rows="9" placeholder="Pegue aqui el texto de la queja, PQR o solicitud..." class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <label class="block text-xs font-bold uppercase tracking-wide text-gray-500 mb-2">Historia clinica o registros disponibles</label>
            <textarea wire:model="historia" rows="9" placeholder="Pegue aqui historia clinica, evoluciones, notas, ordenes, epicrisis, triage..." class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
        </div>
    </div>

    <button wire:click="analizar" wire:loading.attr="disabled" class="mt-5 w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-semibold py-3 rounded-lg transition">
        <span wire:loading wire:target="analizar" class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
        <span wire:loading.remove wire:target="analizar">Analizar caso con Kairo</span>
        <span wire:loading wire:target="analizar">Kairo esta analizando...</span>
    </button>

    @if ($error)
        <div class="mt-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-4">{{ $error }}</div>
    @endif

    @if ($secciones)
        <div class="mt-8 space-y-4">

            <div class="flex items-center gap-2 text-sm font-semibold text-blue-700">
                <img src="{{ asset('kairo.png') }}" class="w-7 h-7 rounded-full border border-blue-400">
                Analisis de Kairo
                <button wire:click="nuevoAnalisis" class="ml-auto text-xs font-medium text-gray-500 hover:text-gray-700 underline">Nuevo analisis</button>
            </div>

            @php
                $alertas = $secciones['ALERTAS INTERNAS'] ?? '';
                $esNoQueja = str_contains($alertas, 'NO_ES_QUEJA');
                $requiereJuridica = str_contains(strtoupper($alertas), 'REVISION JURIDICA');
            @endphp

            @if ($esNoQueja)
                <div class="bg-amber-50 border border-amber-300 text-amber-800 rounded-xl p-5 text-sm">
                    No se identifico una queja, PQR o solicitud valida en el texto ingresado. Por favor verifique el contenido y vuelva a intentarlo.
                </div>
            @else
                <div class="rounded-xl p-5 border {{ $requiereJuridica ? 'bg-red-50 border-red-300' : 'bg-red-50/60 border-red-200' }}">
                    <div class="text-xs font-bold uppercase tracking-wide text-red-700 mb-3 pb-2 border-b border-red-200">Alertas internas</div>
                    @if ($requiereJuridica)
                        <span class="inline-block bg-red-700 text-white text-xs font-bold px-3 py-1 rounded-md mb-2">REQUIERE REVISION JURIDICA ANTES DE ENVIO</span>
                    @endif
                    <div class="text-sm text-gray-700 whitespace-pre-line">{{ str_replace('REQUIERE REVISION JURIDICA ANTES DE ENVIO', '', $alertas) }}</div>
                </div>

                <div class="rounded-xl p-5 border bg-amber-50/60 border-amber-200">
                    <div class="text-xs font-bold uppercase tracking-wide text-amber-800 mb-3 pb-2 border-b border-amber-200">Profesionales o areas para revision</div>
                    <div class="text-sm text-gray-700 whitespace-pre-line">{{ $secciones['PROFESIONALES O AREAS PARA REVISION'] ?? '' }}</div>
                </div>

                <div class="rounded-xl p-5 border bg-blue-50/60 border-blue-200">
                    <div class="text-xs font-bold uppercase tracking-wide text-blue-800 mb-3 pb-2 border-b border-blue-200">Resumen del caso</div>
                    <div class="text-sm text-gray-700 whitespace-pre-line">{{ $secciones['RESUMEN DEL CASO'] ?? '' }}</div>
                </div>

                <div class="rounded-xl p-5 border bg-green-50/60 border-green-200">
                    <div class="text-xs font-bold uppercase tracking-wide text-green-800 mb-3 pb-2 border-b border-green-200">Analisis de registros clinicos</div>
                    <div class="text-sm text-gray-700 whitespace-pre-line">{{ $secciones['ANALISIS DE REGISTROS CLINICOS'] ?? '' }}</div>
                </div>

                <div class="rounded-xl p-5 border-2 border-blue-600 bg-white" x-data>
                    <div class="text-xs font-bold uppercase tracking-wide text-blue-700 mb-3 pb-2 border-b border-blue-100 flex items-center justify-between">
                        Respuesta sugerida al usuario
                        <button type="button" x-on:click="navigator.clipboard.writeText($refs.respuesta.innerText)" class="text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded-md border border-blue-200">Copiar</button>
                    </div>
                    <div x-ref="respuesta" class="text-sm text-gray-800 whitespace-pre-line leading-relaxed">{{ $secciones['RESPUESTA SUGERIDA AL USUARIO'] ?? '' }}</div>
                </div>

                <div class="rounded-xl p-5 border bg-purple-50/60 border-purple-200">
                    <div class="text-xs font-bold uppercase tracking-wide text-purple-800 mb-3 pb-2 border-b border-purple-200">Acciones internas recomendadas</div>
                    <div class="text-sm text-gray-700 whitespace-pre-line">{{ $secciones['ACCIONES INTERNAS RECOMENDADAS'] ?? '' }}</div>
                </div>
            @endif
        </div>
    @endif
</div>
