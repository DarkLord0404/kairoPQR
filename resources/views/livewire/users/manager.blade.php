<div class="max-w-5xl mx-auto">
    <a href="{{ route('dashboard') }}" wire:navigate class="text-sm" style="color: var(--kairo-blue-dim)">&larr; Volver a analizar</a>
    <h1 class="mt-3 mb-6 text-xl font-bold" style="color: var(--kairo-text)">Administracion de usuarios</h1>

    @if (session('success'))
        <div class="mb-5 rounded-lg p-3 text-sm" style="background: rgba(6,78,59,0.25); border: 1px solid #064e3b; color: #6ee7b7">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 md:grid-cols-3">
        <form wire:submit="create" class="kairo-panel p-5 space-y-3">
            <h2 class="kairo-label mb-2">Nuevo usuario</h2>

            <input wire:model="name" class="kairo-textarea w-full text-sm p-2.5" placeholder="Nombre">
            @error('name') <p class="text-xs" style="color:#fca5a5">{{ $message }}</p> @enderror

            <input wire:model="email" class="kairo-textarea w-full text-sm p-2.5" placeholder="Correo">
            @error('email') <p class="text-xs" style="color:#fca5a5">{{ $message }}</p> @enderror

            <input wire:model="password" type="password" class="kairo-textarea w-full text-sm p-2.5" placeholder="Contraseña (min. 12)">
            @error('password') <p class="text-xs" style="color:#fca5a5">{{ $message }}</p> @enderror

            <select wire:model="role" class="kairo-textarea w-full text-sm p-2.5">
                <option value="administrativo">Administrativo</option>
                <option value="master">Master</option>
            </select>
            @error('role') <p class="text-xs" style="color:#fca5a5">{{ $message }}</p> @enderror

            <button class="kairo-btn-primary w-full py-2.5 mt-2">Crear usuario</button>
        </form>

        <div class="md:col-span-2 kairo-panel overflow-hidden">
            <table class="kairo-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <span style="color: var(--kairo-text)">{{ $user->name }}</span><br>
                                <span class="text-xs" style="color: var(--kairo-text-dim)">{{ $user->email }}</span>
                            </td>
                            <td style="color: var(--kairo-text-dim)">{{ $user->role }}</td>
                            <td>
                                @if ($user->is_active)
                                    <span class="text-xs font-semibold px-2 py-1 rounded-md" style="background: rgba(6,78,59,0.3); color:#6ee7b7">Activo</span>
                                @else
                                    <span class="text-xs font-semibold px-2 py-1 rounded-md" style="background: rgba(127,29,29,0.3); color:#fca5a5">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <button wire:click="toggle({{ $user->id }})" wire:confirm="¿Confirmas este cambio de estado?" class="text-xs font-semibold" style="color: var(--kairo-blue-light)">
                                    {{ $user->is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
