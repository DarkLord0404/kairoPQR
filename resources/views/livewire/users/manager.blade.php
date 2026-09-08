<div class="max-w-5xl mx-auto space-y-5">

    <div class="kairo-intro kairo-panel flex items-start gap-4 p-5">
        <img src="{{ asset('kairo.png') }}" alt="Kairo" class="w-12 h-12 kairo-avatar-ring flex-shrink-0">
        <div class="text-sm leading-relaxed" style="color: var(--kairo-blue-dim)">
            <strong style="color: var(--kairo-blue-light)">Administración de usuarios</strong><br>
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
              style="background: rgba(13,20,36,0.75); border: 1px solid var(--kairo-border);">

            <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--kairo-border);">
                <h2 class="font-semibold text-sm" style="color: var(--kairo-blue-light)">Nuevo usuario</h2>
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

                <div style="padding-top:.75rem; border-top: 1px solid var(--kairo-border);">
                    <div class="text-xs font-semibold" style="color: var(--kairo-text-dim); margin-bottom:.75rem;">Acceso a módulos</div>
                    <div style="display:flex; flex-direction:column; gap:.6rem;">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="accesoPqr" class="rounded">
                            <div>
                                <div class="text-sm font-medium" style="color: var(--kairo-text)">PQR</div>
                                <div class="text-xs" style="color: var(--kairo-text-dim)">Analizar quejas y peticiones</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="accesoEa" class="rounded">
                            <div>
                                <div class="text-sm font-medium" style="color: var(--kairo-text)">Eventos adversos</div>
                                <div class="text-xs" style="color: var(--kairo-text-dim)">Análisis de seguridad del paciente</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="accesoReuniones" class="rounded">
                            <div>
                                <div class="text-sm font-medium" style="color: var(--kairo-text)">KairoMeet</div>
                                <div class="text-xs" style="color: var(--kairo-text-dim)">Grabación y actas de reuniones</div>
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
             style="background: rgba(13,20,36,0.75); border: 1px solid var(--kairo-border);">

            <div style="padding: .875rem 1.25rem; border-bottom: 1px solid var(--kairo-border);">
                <span class="text-sm font-semibold" style="color: var(--kairo-blue-light)">Usuarios registrados</span>
                <span class="text-xs ml-2" style="color: var(--kairo-text-dim)">{{ $users->count() }} en total</span>
            </div>

            @foreach ($users as $user)
            <div style="padding: 1rem 1.25rem; {{ !$loop->last ? 'border-bottom: 1px solid var(--kairo-border);' : '' }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-medium text-sm" style="color: var(--kairo-text)">{{ $user->name }}</span>
                            @if ($user->isMaster())
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wide"
                                    style="background: rgba(139,92,246,0.2); color: #c4b5fd">Master</span>
                            @endif
                            @if (!$user->is_active)
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wide"
                                    style="background: rgba(127,29,29,0.3); color: #fca5a5">Inactivo</span>
                            @endif
                        </div>
                        <div class="text-xs mt-0.5" style="color: var(--kairo-text-dim)">{{ $user->email }}</div>
                    </div>
                    <button wire:click="toggle({{ $user->id }})"
                        wire:confirm="¿Confirmas cambiar el estado de este usuario?"
                        class="text-xs flex-shrink-0 rounded-md font-medium"
                        style="padding:.3rem .75rem; {{ $user->is_active ? 'color:#94a3b8;background:rgba(148,163,184,0.1)' : 'color:#6ee7b7;background:rgba(6,78,59,0.2)' }}"
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
                                  ? 'background:rgba(59,130,246,0.2); color:#93c5fd; border:1px solid rgba(99,179,255,0.3);'
                                  : 'background:rgba(148,163,184,0.08); color:#475569; border:1px solid rgba(148,163,184,0.15);' }}">
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
                                  ? 'background:rgba(59,130,246,0.2); color:#93c5fd; border:1px solid rgba(99,179,255,0.3);'
                                  : 'background:rgba(148,163,184,0.08); color:#475569; border:1px solid rgba(148,163,184,0.15);' }}">
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
                                  ? 'background:rgba(59,130,246,0.2); color:#93c5fd; border:1px solid rgba(99,179,255,0.3);'
                                  : 'background:rgba(148,163,184,0.08); color:#475569; border:1px solid rgba(148,163,184,0.15);' }}">
                        @if ($user->acceso_reuniones)
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                        @else
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/></svg>
                        @endif
                        KairoMeet
                    </button>

                </div>
                @else
                <div class="mt-2 text-xs" style="color: var(--kairo-text-dim)">Acceso completo a todos los módulos</div>
                @endif
            </div>
            @endforeach

        </div>

    </div>
</div>
