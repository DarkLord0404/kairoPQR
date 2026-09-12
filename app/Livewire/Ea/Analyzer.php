<?php

namespace App\Livewire\Ea;

use App\Models\EaAnalysis;
use App\Services\KairoEaService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Analyzer extends Component
{
    public string $caso = '';

    public string $historia = '';

    public bool $analizando = false;

    public ?int $resultadoId = null;

    public ?array $secciones = null;

    public ?float $duracionSegundos = null;

    public ?int $tokensTotales = null;

    public ?int $tokensEntrada = null;

    public ?int $tokensSalida = null;

    public ?int $tokensCache = null;

    public ?string $modelo = null;

    public ?int $llamadasModelo = null;

    public ?int $fragmentos = null;

    public ?string $error = null;

    public function analizar(KairoEaService $service): void
    {
        $this->error = null;
        $this->secciones = null;
        $this->resultadoId = null;

        $caso = trim($this->caso);

        if ($caso === '') {
            $this->error = 'Ingrese la descripción del caso antes de analizar.';

            return;
        }

        $this->analizando = true;

        try {
            $resultado = $service->analizar($caso, trim($this->historia) ?: null);

            $registro = EaAnalysis::create([
                'user_id' => auth()->id(),
                'caso' => $caso,
                'historia' => trim($this->historia) ?: null,
                'respuesta_completa' => $resultado['texto_completo'],
                'clasificacion' => $resultado['clasificacion'],
                'secciones' => $resultado['secciones'],
                'tokens_totales' => $resultado['tokens_totales'],
                'tokens_entrada' => $resultado['tokens_entrada'],
                'tokens_salida' => $resultado['tokens_salida'],
                'tokens_cache' => $resultado['tokens_cache'],
                'modelo' => $resultado['modelo'],
                'llamadas_modelo' => $resultado['llamadas_modelo'],
                'fragmentos' => $resultado['fragmentos'],
                'duracion_segundos' => $resultado['duracion_segundos'],
            ]);

            $this->resultadoId = $registro->id;
            $this->secciones = $resultado['secciones'];
            $this->duracionSegundos = $resultado['duracion_segundos'];
            $this->tokensTotales = $resultado['tokens_totales'];
            $this->tokensEntrada = $resultado['tokens_entrada'];
            $this->tokensSalida = $resultado['tokens_salida'];
            $this->tokensCache = $resultado['tokens_cache'];
            $this->modelo = $resultado['modelo'];
            $this->llamadasModelo = $resultado['llamadas_modelo'];
            $this->fragmentos = $resultado['fragmentos'];
        } catch (\Throwable $e) {
            report($e);
            $this->error = 'No fue posible completar el análisis: '.$e->getMessage();
        } finally {
            $this->analizando = false;
        }
    }

    public function nuevoAnalisis(): void
    {
        $this->reset(['caso', 'historia', 'resultadoId', 'secciones', 'error', 'duracionSegundos',
            'tokensTotales', 'tokensEntrada', 'tokensSalida', 'tokensCache', 'modelo',
            'llamadasModelo', 'fragmentos']);
    }

    public function render()
    {
        return view('livewire.ea.analyzer');
    }
}
