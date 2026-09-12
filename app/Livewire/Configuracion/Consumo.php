<?php

namespace App\Livewire\Configuracion;

use App\Models\EaAnalysis;
use App\Models\PqrAnalysis;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Consumo extends Component
{
    public string $fechaDesde = '';

    public string $fechaHasta = '';

    public string $modulo = '';

    public string $modelo = '';

    public function mount(): void
    {
        $this->fechaDesde = now()->subDays(29)->toDateString();
        $this->fechaHasta = now()->toDateString();
    }

    public function limpiarFiltros(): void
    {
        $this->fechaDesde = now()->subDays(29)->toDateString();
        $this->fechaHasta = now()->toDateString();
        $this->modulo = '';
        $this->modelo = '';
    }

    public function render()
    {
        $filas = collect();

        if ($this->modulo !== 'ea') {
            $filas = $filas->concat($this->obtenerFilas(PqrAnalysis::query(), 'PQR'));
        }

        if ($this->modulo !== 'pqr') {
            $filas = $filas->concat($this->obtenerFilas(EaAnalysis::query(), 'EA'));
        }

        $filas = $filas->sortByDesc('created_at')->values();

        return view('livewire.configuracion.consumo', [
            'resumen' => $this->resumir($filas),
            'porModulo' => collect(['PQR', 'EA'])->mapWithKeys(
                fn (string $modulo) => [$modulo => $this->resumir($filas->where('modulo', $modulo))]
            ),
            'recientes' => $filas->take(20),
            'modelos' => $this->modelosDisponibles(),
        ]);
    }

    private function obtenerFilas(Builder $query, string $modulo): Collection
    {
        $query->select([
            'id', 'user_id', 'created_at', 'tokens_totales', 'tokens_entrada',
            'tokens_salida', 'tokens_cache', 'modelo', 'llamadas_modelo',
            'fragmentos', 'duracion_segundos',
        ])->with('user:id,name');

        if ($this->fechaDesde !== '') {
            $query->where('created_at', '>=', Carbon::parse($this->fechaDesde)->startOfDay());
        }

        if ($this->fechaHasta !== '') {
            $query->where('created_at', '<=', Carbon::parse($this->fechaHasta)->endOfDay());
        }

        if ($this->modelo !== '') {
            $query->where('modelo', $this->modelo);
        }

        return $query->get()->map(function ($analysis) use ($modulo): array {
            return [
                'id' => $analysis->id,
                'modulo' => $modulo,
                'usuario' => $analysis->user?->name ?? '—',
                'created_at' => $analysis->created_at,
                'tokens_totales' => $analysis->tokens_totales,
                'tokens_entrada' => $analysis->tokens_entrada,
                'tokens_salida' => $analysis->tokens_salida,
                'tokens_cache' => $analysis->tokens_cache,
                'modelo' => $analysis->modelo,
                'llamadas_modelo' => $analysis->llamadas_modelo,
                'fragmentos' => $analysis->fragmentos,
                'duracion_segundos' => $analysis->duracion_segundos,
            ];
        });
    }

    private function resumir(Collection $filas): array
    {
        $medidas = $filas->whereNotNull('tokens_totales');

        return [
            'analisis' => $filas->count(),
            'medidos' => $medidas->count(),
            'tokens' => (int) $medidas->sum('tokens_totales'),
            'promedio' => $medidas->isEmpty() ? null : (int) round($medidas->avg('tokens_totales')),
            'maximo' => $medidas->isEmpty() ? null : (int) $medidas->max('tokens_totales'),
            'entrada' => (int) $filas->sum('tokens_entrada'),
            'salida' => (int) $filas->sum('tokens_salida'),
            'cache' => (int) $filas->sum('tokens_cache'),
            'llamadas' => (int) $filas->sum('llamadas_modelo'),
        ];
    }

    private function modelosDisponibles(): Collection
    {
        return PqrAnalysis::query()->whereNotNull('modelo')->distinct()->pluck('modelo')
            ->merge(EaAnalysis::query()->whereNotNull('modelo')->distinct()->pluck('modelo'))
            ->unique()->sort()->values();
    }
}
