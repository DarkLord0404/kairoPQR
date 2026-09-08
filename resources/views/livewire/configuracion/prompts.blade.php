<div class="max-w-5xl mx-auto space-y-3">

    <div class="kairo-intro kairo-panel flex items-start gap-4 p-5">
        <img src="{{ asset('kairo.png') }}" alt="Kairo" class="w-12 h-12 kairo-avatar-ring flex-shrink-0">
        <div class="text-sm leading-relaxed" style="color: var(--kairo-blue-dim)">
            <strong style="color: var(--kairo-blue-light)">Configuración de prompts del sistema</strong><br>
            Edita las instrucciones que Kairo recibe al analizar PQR, eventos adversos y reuniones. Los cambios se aplican de inmediato.
        </div>
    </div>

    @php
        $tarjetas = [
            ['clave' => 'pqr',           'prop' => 'pqr',       'titulo' => 'Prompt — PQR (Quejas)',                    'rows' => 12, 'placeholder' => 'Instrucciones del sistema para el análisis de PQR...'],
            ['clave' => 'ea',            'prop' => 'ea',        'titulo' => 'Prompt — Eventos Adversos',                'rows' => 12, 'placeholder' => 'Instrucciones del sistema para análisis de eventos adversos...'],
            ['clave' => 'meet_fragmento','prop' => 'fragmento', 'titulo' => 'Prompt — Meet (análisis por fragmento)',   'rows' => 8,  'placeholder' => 'Instrucciones para resumir cada fragmento de transcripción...'],
            ['clave' => 'meet_final',    'prop' => 'final',     'titulo' => 'Prompt — Meet (acta final)',               'rows' => 8,  'placeholder' => 'Instrucciones para redactar el acta final de la reunión...'],
        ];
    @endphp

    @foreach ($tarjetas as $t)
    @php $prop = $t['prop']; $valorActual = $$prop; @endphp
    <div class="rounded-xl overflow-hidden" style="background: rgba(13,20,36,0.75); border: 1px solid var(--kairo-border);"
         x-data="{ abierto: false, guardado: false }"
         x-on:guardado-{{ $t['clave'] }}.window="guardado=true; setTimeout(()=>guardado=false,2500)">

        {{-- Encabezado --}}
        <button type="button" @click="abierto = !abierto"
            class="w-full flex items-center justify-between text-left"
            style="padding: 1rem 1.25rem;">
            <span class="font-semibold text-sm" style="color: var(--kairo-blue-light)">{{ $t['titulo'] }}</span>
            <span class="flex items-center gap-3 flex-shrink-0 ml-4">
                <span class="text-xs font-medium text-green-400 transition-opacity duration-300"
                      :class="guardado ? 'opacity-100' : 'opacity-0'">Guardado ✓</span>
                <span class="text-xs" style="color: var(--kairo-text-dim)" x-text="abierto ? '▲' : '▼'"></span>
            </span>
        </button>

        {{-- Contenido colapsable --}}
        <div x-show="abierto" x-cloak x-transition
             style="border-top: 1px solid var(--kairo-border); padding: 1.25rem;">
            <div class="text-xs mb-2" style="color: var(--kairo-text-dim)">{{ strlen($valorActual) }} caracteres</div>
            <textarea wire:model="{{ $prop }}" rows="{{ $t['rows'] }}"
                placeholder="{{ $t['placeholder'] }}"
                class="kairo-textarea text-sm font-mono"
                style="width: 100%; box-sizing: border-box; display: block; padding: .75rem; font-size: .75rem; resize: vertical;"></textarea>
            <div style="margin-top: .75rem;">
                <button wire:click="guardar('{{ $t['clave'] }}')" wire:loading.attr="disabled"
                    class="kairo-btn-primary"
                    style="padding: .45rem 1.25rem; font-size: .85rem;">
                    <span wire:loading wire:target="guardar('{{ $t['clave'] }}')">Guardando...</span>
                    <span wire:loading.remove wire:target="guardar('{{ $t['clave'] }}')">Guardar</span>
                </button>
            </div>
        </div>
    </div>
    @endforeach

    {{-- Glosario de transcripción --}}
    <div class="rounded-xl overflow-hidden" style="background: rgba(13,20,36,0.75); border: 1px solid var(--kairo-border);"
         x-data="{ abierto: false, guardado: false }"
         x-on:guardado-meet_glosario.window="guardado=true; setTimeout(()=>guardado=false,2500)">

        <button type="button" @click="abierto = !abierto"
            class="w-full flex items-center justify-between text-left"
            style="padding: 1rem 1.25rem;">
            <span class="font-semibold text-sm" style="color: var(--kairo-blue-light)">Glosario de transcripción (Whisper STT)</span>
            <span class="flex items-center gap-3 flex-shrink-0 ml-4">
                <span class="text-xs font-medium text-green-400 transition-opacity duration-300"
                      :class="guardado ? 'opacity-100' : 'opacity-0'">Guardado ✓</span>
                <span class="text-xs" style="color: var(--kairo-text-dim)" x-text="abierto ? '▲' : '▼'"></span>
            </span>
        </button>

        <div x-show="abierto" x-cloak x-transition
             style="border-top: 1px solid var(--kairo-border); padding: 1.25rem;">
            <p class="text-xs mb-3" style="color: var(--kairo-text-dim); line-height: 1.5;">
                Un término por línea. Las líneas que empiezan por
                <code style="background:rgba(255,255,255,.08); padding:0 4px; border-radius:3px; font-size:.7rem;">#</code>
                son comentarios. Whisper usa estos términos como pista al transcribir (límite: 224 chars efectivos).
            </p>
            @php
                $lineasActivas = collect(explode("\n", $glosario))
                    ->map(fn($l) => trim($l))
                    ->filter(fn($l) => $l !== '' && !str_starts_with($l, '#'))
                    ->values();
                $promptEfectivo = mb_substr($lineasActivas->implode(' '), 0, 224);
            @endphp
            <div class="text-xs mb-2 flex gap-4" style="color: var(--kairo-text-dim)">
                <span>{{ $lineasActivas->count() }} términos activos</span>
                <span style="color: {{ mb_strlen($promptEfectivo) >= 220 ? '#fca5a5' : 'var(--kairo-text-dim)' }}">
                    {{ mb_strlen($promptEfectivo) }} / 224 chars
                </span>
            </div>
            <textarea wire:model="glosario" rows="12"
                placeholder="# Institución&#10;Clínica de Occidente&#10;# Personas&#10;Alexander Torres&#10;..."
                class="kairo-textarea font-mono"
                style="width: 100%; box-sizing: border-box; display: block; padding: .75rem; font-size: .75rem; resize: vertical;"></textarea>
            <div style="margin-top: .75rem;">
                <button wire:click="guardar('meet_glosario')" wire:loading.attr="disabled"
                    class="kairo-btn-primary"
                    style="padding: .45rem 1.25rem; font-size: .85rem;">
                    <span wire:loading wire:target="guardar('meet_glosario')">Guardando...</span>
                    <span wire:loading.remove wire:target="guardar('meet_glosario')">Guardar glosario</span>
                </button>
            </div>
        </div>
    </div>

</div>
