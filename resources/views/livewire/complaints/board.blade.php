<div class="min-h-screen bg-zinc-950 text-zinc-200">
    <header class="border-b border-accent-900 bg-gradient-to-r from-zinc-950 to-accent-950">
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-6 py-4">
            <img src="{{ asset('kairo.png') }}" alt="Kairo" class="h-12 w-12 rounded-full border-2 border-accent-500 object-cover shadow-lg shadow-accent-500/30">
            <div><h1 class="font-bold">Gestor de PQRS</h1><p class="text-sm text-accent-300">Kairo · Gestión institucional</p></div>
            @if (auth()->user()->isMaster()) <a href="{{ route('users') }}" class="ml-auto rounded border border-accent-700 px-3 py-2 text-sm text-accent-200 hover:bg-accent-950">Administrar usuarios</a> @endif
        </div>
    </header>
    <main class="mx-auto grid max-w-7xl gap-6 p-6 lg:grid-cols-3">
        <section class="rounded-xl border border-accent-900 bg-zinc-900/80 p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-accent-300">Radicar queja</h2>
            @if (session('success')) <p class="mb-3 rounded bg-emerald-950 p-3 text-sm text-emerald-300">{{ session('success') }}</p> @endif
            <form wire:submit="create" class="space-y-3">
                <input wire:model="contact_name" class="w-full rounded border-zinc-700 bg-zinc-950" placeholder="Nombre de contacto">
                <input wire:model="contact_email" class="w-full rounded border-zinc-700 bg-zinc-950" placeholder="Correo electrónico">
                <input wire:model="contact_phone" class="w-full rounded border-zinc-700 bg-zinc-950" placeholder="Teléfono">
                <select wire:model="category" class="w-full rounded border-zinc-700 bg-zinc-950"><option>Atención en salud</option><option>Trato y humanización</option><option>Tiempos de atención</option><option>Administrativa</option><option>Otra</option></select>
                <textarea wire:model="description" rows="7" class="w-full rounded border-zinc-700 bg-zinc-950" placeholder="Detalle de la queja o solicitud"></textarea>
                @error('description') <p class="text-sm text-red-300">{{ $message }}</p> @enderror
                <button class="w-full rounded bg-accent-600 px-4 py-2 font-semibold hover:bg-accent-500">Registrar caso</button>
            </form>
        </section>
        <section class="lg:col-span-2 rounded-xl border border-accent-900 bg-zinc-900/80 p-5">
            <div class="mb-4 flex items-center justify-between"><h2 class="text-sm font-bold uppercase tracking-wider text-accent-300">Casos {{ auth()->user()->isMaster() ? 'institucionales' : 'asignados' }}</h2><span class="text-xs text-zinc-400">{{ $complaints->count() }} casos</span></div>
            <div class="space-y-2">
                @forelse ($complaints as $complaint)
                    <button wire:click="select({{ $complaint->id }})" class="w-full rounded-lg border p-4 text-left {{ $selectedId === $complaint->id ? 'border-accent-500 bg-accent-950/40' : 'border-zinc-700 hover:border-accent-700' }}">
                        <div class="flex justify-between gap-3"><strong>{{ $complaint->reference }}</strong><span class="text-xs text-accent-300">{{ $complaint->status }}</span></div><p class="mt-1 text-sm">{{ $complaint->contact_name }} · {{ $complaint->category }}</p><p class="mt-1 text-xs text-zinc-400">Responsable: {{ $complaint->assignee?->name ?? 'Sin asignar' }}</p>
                    </button>
                @empty <p class="py-10 text-center text-zinc-500">No hay casos para mostrar.</p> @endforelse
            </div>
        </section>
        @if ($selected)
            <section class="lg:col-span-3 rounded-xl border border-accent-800 bg-zinc-900 p-5">
                <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="font-bold text-accent-200">{{ $selected->reference }} · {{ $selected->contact_name }}</h2><div class="flex gap-2">@foreach (['recibida','en proceso','respondida','cerrada'] as $status)<button wire:click="changeStatus('{{ $status }}')" class="rounded border border-zinc-600 px-2 py-1 text-xs hover:border-accent-400">{{ $status }}</button>@endforeach</div></div>
                <p class="mt-4 whitespace-pre-line text-sm text-zinc-300">{{ $selected->description }}</p>
                @if (auth()->user()->isMaster()) <select wire:change="assign($event.target.value)" class="mt-4 rounded border-zinc-700 bg-zinc-950 text-sm"><option>Asignar responsable…</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected($selected->assigned_to === $user->id)>{{ $user->name }} ({{ $user->role }})</option>@endforeach</select> @endif
                <form wire:submit="saveResponse" class="mt-5"><label class="mb-2 block text-sm font-semibold text-accent-200">Respuesta institucional</label><textarea wire:model="response" rows="6" class="w-full rounded border-zinc-700 bg-zinc-950"></textarea><button class="mt-2 rounded bg-accent-600 px-4 py-2 text-sm font-semibold">Guardar respuesta</button></form>
                <div class="mt-5 border-t border-zinc-700 pt-4"><h3 class="text-sm font-bold text-accent-300">Historial</h3>@foreach($selected->histories as $history)<p class="mt-2 text-sm text-zinc-400">{{ $history->created_at->format('d/m/Y H:i') }} · {{ $history->action }} @if($history->to_value) → {{ $history->to_value }} @endif · {{ $history->user?->name ?? 'Sistema' }}</p>@endforeach</div>
            </section>
        @endif
    </main>
</div>
