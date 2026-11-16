<div class="max-w-5xl mx-auto space-y-5">

    <div class="kairo-intro kairo-panel flex items-start gap-4 p-5">
        <img src="{{ asset('clara.webp') }}" alt="Clara" class="w-12 h-12 kairo-avatar-ring flex-shrink-0">
        <div class="text-sm leading-relaxed" style="color: var(--c-text-soft)">
            <strong style="color: var(--c-accent-soft)">Administración de usuarios</strong><br>
            Crea cuentas y controla a qué módulos tiene acceso cada usuario. Los cambios de acceso aplican de inmediato.
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg p-3 text-sm" style="background: rgba(6,78,59,0.25); border: 1px solid #064e3b; color: #6ee7b7">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-5">

        {{-- Formulario nuevo usuario --}}
        <form wire:submit="create" class="lg:col-span-2 self-start rounded-xl overflow-hidden"
              style="background: rgb(var(--c-surface-rgb) / 0.75); border: 1px solid var(--c-border);">

            <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--c-border);">
                <h2 class="font-semibold text-sm" style="color: var(--c-text)">Nuevo usuario</h2>
            </div>

            <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: .875rem;">

                <div>
                    <label class="kairo-label" style="display:block; margin-bottom:.35rem;">Nombre</label>
                    <input wire:model="name" type="text" placeholder="Nombre completo"
                           class="kairo-textarea text-sm"
                           style="width:100%; box-sizing:border-box; display:block; padding:.55rem .75rem;">
                    @error('name') <p class="text-xs mt-1" style="color:#fca5a5">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="kairo-label" style="display:block; margin-bottom:.35rem;">Correo electrónico</label>
                    <input wire:model="email" type="email" placeholder="correo@ejemplo.com"
                           class="kairo-textarea text-sm"
                           style="width:100%; box-sizing:border-box; display:block; padding:.55rem .75rem;">
                    @error('email') <p class="text-xs mt-1" style="color:#fca5a5">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="kairo-label" style="display:block; margin-bottom:.35rem;">Contraseña</label>
                    <input wire:model="password" type="password" placeholder="Mínimo 6 caracteres"
                           class="kairo-textarea text-sm"
                           style="width:100%; box-sizing:border-box; display:block; padding:.55rem .75rem;">
                    @error('password') <p class="text-xs mt-1" style="color:#fca5a5">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="kairo-label" style="display:block; margin-bottom:.35rem;">Rol</label>
                    <select wire:model="role"
                            class="kairo-textarea text-sm"
                            style="width:100%; box-sizing:border-box; display:block; padding:.55rem .75rem;">
                        <option value="administrativo">Administrativo</option>
                        <option value="master">Master</option>
                    </select>
                </div>

                <div style="padding-top:.75rem; border-top: 1px solid var(--c-border);">
                    <div class="text-xs font-semibold" style="color: var(--c-text-dim); margin-bottom:.75rem;">Acceso a módulos</div>
                    <div style="display:flex; flex-direction:column; gap:.6rem;">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="accesoPqr" class="rounded">
                            <div>
                                <div class="text-sm font-medium" style="color: var(--c-text)">PQR</div>
                                <div class="text-xs" style="color: var(--c-text-dim)">Analizar quejas y peticiones</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="accesoEa" class="rounded">
                            <div>
                                <div class="text-sm font-medium" style="color: var(--c-text)">Eventos adversos</div>
                                <div class="text-xs" style="color: var(--c-text-dim)">Análisis de seguridad del paciente</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="accesoReuniones" class="rounded">
                            <div>
                                <div class="text-sm font-medium" style="color: var(--c-text)">KairoMeet</div>
                                <div class="text-xs" style="color: var(--c-text-dim)">Grabación y actas de reuniones</div>
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="kairo-btn-primary"
                        style="width:100%; padding:.6rem; font-size:.875rem; margin-top:.25rem;">
                    Crear usuario
                </button>

            </div>
        </form>

        {{-- Lista de usuarios --}}
        <div class="lg:col-span-3 self-start rounded-xl overflow-hidden"
             style="background: rgb(var(--c-surface-rgb) / 0.75); border: 1px solid var(--c-border);">

            <div style="padding: .875rem 1.25rem; border-bottom: 1px solid var(--c-border);">
                <span class="text-sm font-semibold" style="color: var(--c-text)">Usuarios registrados</span>
                <span class="text-xs ml-2" style="color: var(--c-text-dim)">{{ $users->count() }} en total</span>
            </div>

            @foreach ($users as $user)
            <div style="padding: 1rem 1.25rem; {{ !$loop->last ? 'border-bottom: 1px solid var(--c-border);' : '' }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-medium text-sm" style="color: var(--c-text)">{{ $user->name }}</span>
                            @if ($user->isMaster())
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wide"
                                    style="background: rgb(var(--c-teal-rgb) / 0.2); color: var(--c-teal-soft)">Master</span>
                            @endif
                            @if (!$user->is_active)
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wide"
                                    style="background: rgba(127,29,29,0.3); color: #fca5a5">Inactivo</span>
                            @endif
                        </div>
                        <div class="text-xs mt-0.5" style="color: var(--c-text-dim)">{{ $user->email }}</div>
                    </div>
                    <button wire:click="toggle({{ $user->id }})"
                        wire:confirm="¿Confirmas cambiar el estado de este usuario?"
                        class="text-xs flex-shrink-0 rounded-md font-medium"
                        style="padding:.3rem .75rem; {{ $user->is_active ? 'color:var(--c-text-dim);background:rgb(var(--c-steel-rgb) / 0.1)' : 'color:#6ee7b7;background:rgba(6,78,59,0.2)' }}"
                        {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                        {{ $user->is_active ? 'Desactivar' : 'Activar' }}
                    </button>
                </div>

                @if (!$user->isMaster())
                <div style="margin-top:.75rem; display:flex; flex-wrap:wrap; gap:.4rem;">

                    {{-- PQR --}}
                    <button type="button" wire:click="toggleAcceso({{ $user->id }}, 'acceso_pqr')"
                        class="flex items-center gap-1.5 text-xs font-medium rounded-md"
                        style="padding:.3rem .7rem; transition: all .15s;
                               {{ $user->acceso_pqr
                                  ? 'background:rgb(var(--c-accent-rgb) / 0.2); color:var(--c-text-soft); border:1px solid rgb(var(--c-accent-rgb) / 0.3);'
                                  : 'background:rgb(var(--c-steel-rgb) / 0.08); color:var(--c-text-faint); border:1px solid rgb(var(--c-steel-rgb) / 0.15);' }}">
                        @if ($user->acceso_pqr)
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                        @else
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/></svg>
                        @endif
                        PQR
                    </button>

                    {{-- Eventos adversos --}}
                    <button type="button" wire:click="toggleAcceso({{ $user->id }}, 'acceso_ea')"
                        class="flex items-center gap-1.5 text-xs font-medium rounded-md"
                        style="padding:.3rem .7rem; transition: all .15s;
                               {{ $user->acceso_ea
                                  ? 'background:rgb(var(--c-accent-rgb) / 0.2); color:var(--c-text-soft); border:1px solid rgb(var(--c-accent-rgb) / 0.3);'
                                  : 'background:rgb(var(--c-steel-rgb) / 0.08); color:var(--c-text-faint); border:1px solid rgb(var(--c-steel-rgb) / 0.15);' }}">
                        @if ($user->acceso_ea)
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                        @else
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/></svg>
                        @endif
                        Eventos adversos
                    </button>

                    {{-- KairoMeet --}}
                    <button type="button" wire:click="toggleAcceso({{ $user->id }}, 'acceso_reuniones')"
                        class="flex items-center gap-1.5 text-xs font-medium rounded-md"
                        style="padding:.3rem .7rem; transition: all .15s;
                               {{ $user->acceso_reuniones
                                  ? 'background:rgb(var(--c-accent-rgb) / 0.2); color:var(--c-text-soft); border:1px solid rgb(var(--c-accent-rgb) / 0.3);'
                                  : 'background:rgb(var(--c-steel-rgb) / 0.08); color:var(--c-text-faint); border:1px solid rgb(var(--c-steel-rgb) / 0.15);' }}">
                        @if ($user->acceso_reuniones)
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                        @else
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/></svg>
                        @endif
                        KairoMeet
                    </button>

                </div>
                @else
                <div class="mt-2 text-xs" style="color: var(--c-text-dim)">Acceso completo a todos los módulos</div>
                @endif
            </div>
            @endforeach

        </div>

    </div>
</div>
