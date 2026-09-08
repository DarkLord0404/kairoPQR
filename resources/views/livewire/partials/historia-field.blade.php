<div class="kairo-panel p-5"
    x-data="{
        archivoNombre: null,
        cargando: false,
        errorArchivo: null,
        UMBRAL: 500,
        async seleccionarArchivo(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.errorArchivo = null;
            this.cargando = true;
            this.archivoNombre = file.name;
            if (file.name.toLowerCase().endsWith('.txt')) {
                const texto = await file.text();
                $wire.set('historia', texto);
                this.cargando = false;
                return;
            }
            const fd = new FormData();
            fd.append('archivo', file);
            fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
            try {
                const r = await fetch('/extraer-texto', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.error) { this.errorArchivo = data.error; this.archivoNombre = null; }
                else { $wire.set('historia', data.texto); }
            } catch(ex) {
                this.errorArchivo = 'Error al subir el archivo.';
                this.archivoNombre = null;
            }
            this.cargando = false;
        },
        manejarPegado(e) {
            const texto = (e.clipboardData || window.clipboardData).getData('text');
            if (!texto || texto.length < this.UMBRAL) return;
            e.preventDefault();
            $wire.set('historia', texto);
            this.archivoNombre = 'texto-pegado.txt';
            this.errorArchivo = null;
        },
        quitar() {
            this.archivoNombre = null;
            this.errorArchivo = null;
            $wire.set('historia', '');
            if (this.$refs.fileInputHc) this.$refs.fileInputHc.value = '';
        }
    }">

    <div class="flex items-center justify-between mb-2">
        <label class="kairo-label">Historia clínica o registros disponibles</label>
        <label x-show="!archivoNombre"
            class="flex items-center gap-1.5 cursor-pointer text-xs px-2.5 py-1 rounded-md transition-colors"
            style="color:var(--kairo-blue-dim);border:1px solid rgba(99,179,255,0.25);background:rgba(59,130,246,0.08)">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
            </svg>
            Adjuntar
            <input type="file" x-ref="fileInputHc" class="hidden" accept=".txt,.pdf"
                @change="seleccionarArchivo($event)">
        </label>
    </div>

    {{-- Chip de archivo --}}
    <div x-show="archivoNombre" x-cloak
        class="flex items-center gap-2 mb-3 px-3 py-2 rounded-lg text-xs"
        style="background:rgba(59,130,246,0.1);border:1px solid rgba(99,179,255,0.25);color:var(--kairo-blue-dim)">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <span x-show="cargando" class="animate-pulse flex-1">Extrayendo texto...</span>
        <span x-show="!cargando" x-text="archivoNombre" class="flex-1 truncate font-medium"></span>
        <span x-show="!cargando" class="flex-shrink-0 opacity-60 text-[10px]"
            x-text="$wire.historia ? $wire.historia.length.toLocaleString('es') + ' car.' : ''"></span>
        <button x-show="!cargando" type="button" @click="quitar()"
            class="flex-shrink-0 opacity-50 hover:opacity-100 ml-1">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                    clip-rule="evenodd"/>
            </svg>
        </button>
    </div>

    {{-- Error --}}
    <div x-show="errorArchivo" x-cloak
        class="mb-2 px-3 py-2 rounded-lg text-xs"
        style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#fca5a5">
        <span x-text="errorArchivo"></span>
    </div>

    {{-- Textarea sin archivo — intercepta paste largo --}}
    <textarea x-show="!archivoNombre" wire:model="historia" rows="9"
        placeholder="Pegue aquí historia clínica, evoluciones, notas, órdenes, epicrisis, triage..."
        class="kairo-textarea w-full text-sm p-3"
        @paste="manejarPegado($event)"></textarea>

    {{-- Vista previa con archivo --}}
    <div x-show="archivoNombre && !cargando" x-cloak
        class="kairo-textarea w-full text-xs p-3 overflow-y-auto"
        style="height:216px;white-space:pre-wrap;word-break:break-word;color:var(--kairo-text-dim)"
        x-text="$wire.historia ? $wire.historia.substring(0,1500) + ($wire.historia.length > 1500 ? '\n\n[...]' : '') : ''">
    </div>
</div>
