<?php

namespace App\Livewire\Meetings;

use App\Models\AppSetting;
use App\Models\Meeting;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Listado extends Component
{
    use WithPagination;

    private const POR_PAGINA = 10;

    #[Url]
    public string $busqueda = '';

    #[Url]
    public string $fechaDesde = '';

    #[Url]
    public string $fechaHasta = '';

    #[Url]
    public string $duracionFiltro = '';

    #[Url]
    public string $estadoFiltro = '';

    public bool $envioAutomaticoCumple = false;

    public function mount(): void
    {
        $this->envioAutomaticoCumple = AppSetting::boolean('cumple_auto_sync');
    }

    public function updatedEnvioAutomaticoCumple(bool $enabled): void
    {
        abort_unless(auth()->user()?->isMaster(), 403);
        AppSetting::putBoolean('cumple_auto_sync', $enabled);
        if ($enabled) {
            AppSetting::putString('cumple_auto_sync_since', now()->toIso8601String());
        }
        session()->flash('status', $enabled ? 'Envío automático a CUMPLE activado.' : 'Envío automático a CUMPLE desactivado.');
    }

    public function updated($property): void
    {
        if (in_array($property, ['busqueda', 'fechaDesde', 'fechaHasta', 'duracionFiltro', 'estadoFiltro'])) {
            $this->resetPage();
        }
    }

    public function limpiarFiltros(): void
    {
        $this->reset('busqueda', 'fechaDesde', 'fechaHasta', 'duracionFiltro', 'estadoFiltro');
        $this->resetPage();
    }

    #[Computed]
    public function pesoTotalAudio(): string
    {
        $bytes = Meeting::all()->sum(fn ($m) => $m->audioPesaBytes());
        if ($bytes <= 0) {
            return '0 MB';
        }
        $mb = $bytes / 1024 / 1024;

        return $mb >= 1024
            ? round($mb / 1024, 2).' GB'
            : round($mb, 1).' MB';
    }

    #[Computed]
    public function reunionesConAudio(): int
    {
        return Meeting::all()->filter(fn ($m) => $m->tieneAudio())->count();
    }

    public function render()
    {
        $query = Meeting::query();

        if ($this->busqueda !== '') {
            $query->where('titulo', 'like', '%'.$this->busqueda.'%');
        }

        if ($this->fechaDesde !== '') {
            $query->whereDate('fecha_inicio', '>=', $this->fechaDesde);
        }

        if ($this->fechaHasta !== '') {
            $query->whereDate('fecha_inicio', '<=', $this->fechaHasta);
        }

        match ($this->duracionFiltro) {
            'corta' => $query->where('duracion_segundos', '<', 30 * 60),
            'media' => $query->whereBetween('duracion_segundos', [30 * 60, 60 * 60]),
            'larga' => $query->where('duracion_segundos', '>', 60 * 60),
            default => null,
        };

        if ($this->estadoFiltro !== '') {
            $query->where('estado', $this->estadoFiltro);
        }

        return view('livewire.meetings.listado', [
            'reuniones' => $query->orderByDesc('fecha_inicio')->paginate(self::POR_PAGINA),
        ]);
    }
}
