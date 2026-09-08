<?php

namespace App\Livewire\Pqr;

use App\Models\PqrAnalysis;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class HistoryList extends Component
{
    use WithPagination;

    private const POR_PAGINA = 15;

    #[Url]
    public string $busqueda = '';

    #[Url]
    public string $fechaDesde = '';

    #[Url]
    public string $fechaHasta = '';

    #[Url]
    public string $clasificacionFiltro = '';

    #[Url]
    public string $juridicaFiltro = '';

    public function updated($property): void
    {
        if (in_array($property, ['busqueda', 'fechaDesde', 'fechaHasta', 'clasificacionFiltro', 'juridicaFiltro'])) {
            $this->resetPage();
        }
    }

    public function limpiarFiltros(): void
    {
        $this->reset('busqueda', 'fechaDesde', 'fechaHasta', 'clasificacionFiltro', 'juridicaFiltro');
        $this->resetPage();
    }

    public function render()
    {
        $query = PqrAnalysis::with('user');

        if ($this->busqueda !== '') {
            $query->where('queja', 'like', '%'.$this->busqueda.'%');
        }

        if ($this->fechaDesde !== '') {
            $query->whereDate('created_at', '>=', $this->fechaDesde);
        }

        if ($this->fechaHasta !== '') {
            $query->whereDate('created_at', '<=', $this->fechaHasta);
        }

        if ($this->clasificacionFiltro !== '') {
            $query->where('clasificacion', $this->clasificacionFiltro);
        }

        if ($this->juridicaFiltro !== '') {
            $query->where('requiere_revision_juridica', $this->juridicaFiltro === 'si');
        }

        return view('livewire.pqr.history-list', [
            'analyses' => $query->latest()->paginate(self::POR_PAGINA),
            'total' => PqrAnalysis::count(),
            'totalJuridica' => PqrAnalysis::where('requiere_revision_juridica', true)->count(),
        ]);
    }
}
