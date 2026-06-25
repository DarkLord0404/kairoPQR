<?php

namespace App\Livewire\Pqr;

use App\Models\PqrAnalysis;
use App\Services\KairoPqrService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Analyzer extends Component
{
    public string $queja = '';

    public string $historia = '';

    public bool $analizando = false;

    public ?int $resultadoId = null;

    public ?array $secciones = null;

    public ?int $tokensTotales = null;

    public ?float $duracionSegundos = null;

    public ?string $error = null;

    public function analizar(KairoPqrService $service): void
    {
        $this->error = null;
        $this->secciones = null;
        $this->resultadoId = null;

        $queja = trim($this->queja);

        if ($queja === '') {
            $this->error = 'Ingrese el texto de la queja antes de analizar.';

            return;
        }

        $this->analizando = true;

        try {
            $resultado = $service->analizar($queja, trim($this->historia) ?: null);

            $registro = PqrAnalysis::create([
                'user_id' => auth()->id(),
                'queja' => $queja,
                'historia' => trim($this->historia) ?: null,
                'respuesta_completa' => $resultado['texto_completo'],
                'clasificacion' => $resultado['clasificacion'],
                'requiere_revision_juridica' => $resultado['requiere_revision_juridica'],
                'es_queja_valida' => $resultado['es_queja_valida'],
                'secciones' => $resultado['secciones'],
                'tokens_totales' => $resultado['tokens_totales'],
                'duracion_segundos' => $resultado['duracion_segundos'],
            ]);

            $this->resultadoId = $registro->id;
            $this->secciones = $resultado['secciones'];
            $this->tokensTotales = $resultado['tokens_totales'];
            $this->duracionSegundos = $resultado['duracion_segundos'];
        } catch (\Throwable $e) {
            report($e);
            $this->error = 'No fue posible completar el analisis: '.$e->getMessage();
        } finally {
            $this->analizando = false;
        }
    }

    public function nuevoAnalisis(): void
    {
        $this->reset(['queja', 'historia', 'resultadoId', 'secciones', 'error', 'tokensTotales', 'duracionSegundos']);
    }

    public function render()
    {
        return view('livewire.pqr.analyzer');
    }
}
