<?php

namespace App\Livewire\Ea;

use App\Models\EaAnalysis;
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

    public function updated(string $property): void
    {
        if (in_array($property, ['busqueda', 'fechaDesde', 'fechaHasta', 'clasificacionFiltro'])) {
            $this->resetPage();
        }
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['busqueda', 'fechaDesde', 'fechaHasta', 'clasificacionFiltro']);
        $this->resetPage();
    }

    public function render()
    {
        $query = EaAnalysis::with('user')->latest();

        if ($this->busqueda !== '') {
            $query->where('caso', 'like', '%'.$this->busqueda.'%');
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

        $analisis = $query->paginate(self::POR_PAGINA);

        return view('livewire.ea.history-list', ['analisis' => $analisis]);
    }
}
