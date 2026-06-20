<div class="max-w-5xl mx-auto">

    <div class="kairo-intro kairo-panel flex items-start gap-4 p-5 mb-6">
        <img src="{{ asset('kairo.png') }}" alt="Kairo" class="w-12 h-12 kairo-avatar-ring flex-shrink-0">
        <div class="text-sm leading-relaxed" style="color: var(--kairo-blue-dim)">
            <strong style="color: var(--kairo-blue-light)">Hola, soy Kairo.</strong> Analizo quejas, PQR y derechos de peticion del servicio de urgencias. Pegue la queja del usuario y los registros clinicos disponibles; construire una respuesta institucional formal lista para revision.
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="kairo-panel p-5">
            <label class="block kairo-label mb-2">Queja o solicitud del usuario</label>
            <textarea wire:model="queja" rows="9" placeholder="Pegue aqui el texto de la queja, PQR o solicitud..." class="kairo-textarea w-full text-sm p-3"></textarea>
        </div>
        <div class="kairo-panel p-5">
            <label class="block kairo-label mb-2">Historia clinica o registros disponibles</label>
            <textarea wire:model="historia" rows="9" placeholder="Pegue aqui historia clinica, evoluciones, notas, ordenes, epicrisis, triage..." class="kairo-textarea w-full text-sm p-3"></textarea>
        </div>
    </div>

    <button wire:click="analizar" wire:loading.attr="disabled" class="kairo-btn-primary mt-5 w-full flex items-center justify-center gap-2 py-3">
        <span wire:loading wire:target="analizar" class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
        <span wire:loading.remove wire:target="analizar">Analizar caso con Kairo</span>
        <span wire:loading wire:target="analizar">Kairo esta analizando...</span>
    </button>

    @if ($error)
        <div class="kairo-error mt-4 text-sm rounded-lg p-4">{{ $error }}</div>
    @endif

    @if ($secciones)
        <div class="mt-8 space-y-4">

            <div class="flex items-center gap-2 text-sm font-semibold" style="color: var(--kairo-blue-light)">
                <img src="{{ asset('kairo.png') }}" class="w-7 h-7 kairo-avatar-ring">
                Analisis de Kairo
                <button wire:click="nuevoAnalisis" class="ml-auto text-xs font-medium underline" style="color: var(--kairo-text-dim)">Nuevo analisis</button>
            </div>

            @php
                $alertas = $secciones['ALERTAS INTERNAS'] ?? '';
                $esNoQueja = str_contains($alertas, 'NO_ES_QUEJA');
                $requiereJuridica = str_contains(strtoupper($alertas), 'REVISION JURIDICA');
            @endphp

            @if ($esNoQueja)
                <div class="kairo-error rounded-xl p-5 text-sm">
                    No se identifico una queja, PQR o solicitud valida en el texto ingresado. Por favor verifique el contenido y vuelva a intentarlo.
                </div>
            @else
                <div class="kairo-section kairo-sec-alertas">
                    <div class="kairo-section-title">Alertas internas</div>
                    @if ($requiereJuridica)
                        <span class="kairo-alert-juridica">REQUIERE REVISION JURIDICA ANTES DE ENVIO</span>
                    @endif
                    <div class="kairo-content whitespace-pre-line">{{ str_replace('REQUIERE REVISION JURIDICA ANTES DE ENVIO', '', $alertas) }}</div>
                </div>

                <div class="kairo-section kairo-sec-profesionales">
                    <div class="kairo-section-title">Profesionales o areas para revision</div>
                    <div class="kairo-content whitespace-pre-line">{{ $secciones['PROFESIONALES O AREAS PARA REVISION'] ?? '' }}</div>
                </div>

                <div class="kairo-section kairo-sec-resumen">
                    <div class="kairo-section-title">Resumen del caso</div>
                    <div class="kairo-content whitespace-pre-line">{{ $secciones['RESUMEN DEL CASO'] ?? '' }}</div>
                </div>

                <div class="kairo-section kairo-sec-analisis">
                    <div class="kairo-section-title">Analisis de registros clinicos</div>
                    <div class="kairo-content whitespace-pre-line">{{ $secciones['ANALISIS DE REGISTROS CLINICOS'] ?? '' }}</div>
                </div>

                <div class="kairo-section kairo-sec-respuesta" x-data>
                    <div class="kairo-section-title flex items-center justify-between">
                        Respuesta sugerida al usuario
                        <button type="button" x-on:click="navigator.clipboard.writeText($refs.respuesta.innerText)" class="kairo-btn-copy">Copiar</button>
                    </div>
                    <div x-ref="respuesta" class="kairo-content whitespace-pre-line leading-relaxed" style="color: #e2e8f0">{{ $secciones['RESPUESTA SUGERIDA AL USUARIO'] ?? '' }}</div>
                </div>

                <div class="kairo-section kairo-sec-acciones">
                    <div class="kairo-section-title">Acciones internas recomendadas</div>
                    <div class="kairo-content whitespace-pre-line">{{ $secciones['ACCIONES INTERNAS RECOMENDADAS'] ?? '' }}</div>
                </div>
            @endif
        </div>
    @endif
</div>
