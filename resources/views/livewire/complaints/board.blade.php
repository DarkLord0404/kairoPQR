<div class="min-h-screen bg-slate-950 text-slate-200">
    <header class="border-b border-blue-900 bg-gradient-to-r from-slate-950 to-blue-950">
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-6 py-4">
            <img src="{{ asset('kairo.png') }}" alt="Kairo" class="h-12 w-12 rounded-full border-2 border-blue-500 object-cover shadow-lg shadow-blue-500/30">
            <div><h1 class="font-bold">Gestor de PQRS</h1><p class="text-sm text-blue-300">Kairo · Gestión institucional</p></div>
            @if (auth()->user()->isMaster()) <a href="{{ route('users') }}" class="ml-auto rounded border border-blue-700 px-3 py-2 text-sm text-blue-200 hover:bg-blue-950">Administrar usuarios</a> @endif
        </div>
    </header>
    <main class="mx-auto grid max-w-7xl gap-6 p-6 lg:grid-cols-3">
        <section class="rounded-xl border border-blue-900 bg-slate-900/80 p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-blue-300">Radicar queja</h2>
            @if (session('success')) <p class="mb-3 rounded bg-emerald-950 p-3 text-sm text-emerald-300">{{ session('success') }}</p> @endif
            <form wire:submit="create" class="space-y-3">
                <input wire:model="contact_name" class="w-full rounded border-slate-700 bg-slate-950" placeholder="Nombre de contacto">
                <input wire:model="contact_email" class="w-full rounded border-slate-700 bg-slate-950" placeholder="Correo electrónico">
                <input wire:model="contact_phone" class="w-full rounded border-slate-700 bg-slate-950" placeholder="Teléfono">
                <select wire:model="category" class="w-full rounded border-slate-700 bg-slate-950"><option>Atención en salud</option><option>Trato y humanización</option><option>Tiempos de atención</option><option>Administrativa</option><option>Otra</option></select>
                <textarea wire:model="description" rows="7" class="w-full rounded border-slate-700 bg-slate-950" placeholder="Detalle de la queja o solicitud"></textarea>
                @error('description') <p class="text-sm text-red-300">{{ $message }}</p> @enderror
                <button class="w-full rounded bg-blue-600 px-4 py-2 font-semibold hover:bg-blue-500">Registrar caso</button>
            </form>
        </section>
        <section class="lg:col-span-2 rounded-xl border border-blue-900 bg-slate-900/80 p-5">
            <div class="mb-4 flex items-center justify-between"><h2 class="text-sm font-bold uppercase tracking-wider text-blue-300">Casos {{ auth()->user()->isMaster() ? 'institucionales' : 'asignados' }}</h2><span class="text-xs text-slate-400">{{ $complaints->count() }} casos</span></div>
            <div class="space-y-2">
                @forelse ($complaints as $complaint)
                    <button wire:click="select({{ $complaint->id }})" class="w-full rounded-lg border p-4 text-left {{ $selectedId === $complaint->id ? 'border-blue-500 bg-blue-950/40' : 'border-slate-700 hover:border-blue-700' }}">
                        <div class="flex justify-between gap-3"><strong>{{ $complaint->reference }}</strong><span class="text-xs text-blue-300">{{ $complaint->status }}</span></div><p class="mt-1 text-sm">{{ $complaint->contact_name }} · {{ $complaint->category }}</p><p class="mt-1 text-xs text-slate-400">Responsable: {{ $complaint->assignee?->name ?? 'Sin asignar' }}</p>
                    </button>
                @empty <p class="py-10 text-center text-slate-500">No hay casos para mostrar.</p> @endforelse
            </div>
        </section>
        @if ($selected)
            <section class="lg:col-span-3 rounded-xl border border-blue-800 bg-slate-900 p-5">
                <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="font-bold text-blue-200">{{ $selected->reference }} · {{ $selected->contact_name }}</h2><div class="flex gap-2">@foreach (['recibida','en proceso','respondida','cerrada'] as $status)<button wire:click="changeStatus('{{ $status }}')" class="rounded border border-slate-600 px-2 py-1 text-xs hover:border-blue-400">{{ $status }}</button>@endforeach</div></div>
                <p class="mt-4 whitespace-pre-line text-sm text-slate-300">{{ $selected->description }}</p>
                @if (auth()->user()->isMaster()) <select wire:change="assign($event.target.value)" class="mt-4 rounded border-slate-700 bg-slate-950 text-sm"><option>Asignar responsable…</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected($selected->assigned_to === $user->id)>{{ $user->name }} ({{ $user->role }})</option>@endforeach</select> @endif
                <form wire:submit="saveResponse" class="mt-5"><label class="mb-2 block text-sm font-semibold text-blue-200">Respuesta institucional</label><textarea wire:model="response" rows="6" class="w-full rounded border-slate-700 bg-slate-950"></textarea><button class="mt-2 rounded bg-blue-600 px-4 py-2 text-sm font-semibold">Guardar respuesta</button></form>
                <div class="mt-5 border-t border-slate-700 pt-4"><h3 class="text-sm font-bold text-blue-300">Historial</h3>@foreach($selected->histories as $history)<p class="mt-2 text-sm text-slate-400">{{ $history->created_at->format('d/m/Y H:i') }} · {{ $history->action }} @if($history->to_value) → {{ $history->to_value }} @endif · {{ $history->user?->name ?? 'Sistema' }}</p>@endforeach</div>
            </section>
        @endif
    </main>
</div>
