<?php

namespace App\Services;

use App\Models\PromptConfig;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class KairoEaService
{
    private const MAX_CASO_CHARS = 40000;

    private const MAX_HISTORIA_CHARS = 700000;

    private const MAX_ARG_BYTES = 120000;

    private const TIMEOUT_FRAGMENTO_SEGUNDOS = 60;

    private const TIMEOUT_FINAL_SEGUNDOS = 260;

    public const SECTIONS = [
        'ANÁLISIS DE CAUSALIDAD',
        'ACCIONES INSEGURAS IDENTIFICADAS',
        'FACTORES CONTRIBUTIVOS',
        'FACTORES RELACIONADOS CON LA ADMINISTRACIÓN DE LA CULTURA ORGANIZACIONAL',
        'LECCIONES APRENDIDAS',
        'PLANES DE ACCIÓN',
        'CONCLUSIONES',
    ];

    public function analizar(string $caso, ?string $historia): array
    {
        $caso = $this->limitarTexto($caso, self::MAX_CASO_CHARS);
        $historia = $this->prepararHistoria($historia);

        $mensaje = $this->systemPrompt()
            ."\n\n---\n\nCASO CLÍNICO A ANALIZAR:\n".$caso
            ."\n\nHISTORIA CLÍNICA O REGISTROS DISPONIBLES:\n"
            .($historia !== null && $historia !== '' ? $historia : '(No se suministraron registros clínicos adicionales)');

        $inicio = microtime(true);
        $sessionKey = 'agent:main:ea-'.Str::uuid();
        $resultados = [];
        $fragmentosProcesados = 1;

        if (strlen($mensaje) <= self::MAX_ARG_BYTES) {
            $result = $this->llamarOpenClaw($sessionKey, $mensaje, self::TIMEOUT_FINAL_SEGUNDOS);
            $resultados[] = $result;
        } else {
            $fragmentos = $this->dividirEnFragmentos($mensaje, self::MAX_ARG_BYTES);
            $total = count($fragmentos);
            $fragmentosProcesados = $total;
            $result = null;

            foreach ($fragmentos as $i => $fragmento) {
                $numero = $i + 1;
                $esUltimo = $numero === $total;

                $envio = $esUltimo
                    ? $fragmento."\n\n[FRAGMENTO {$numero} DE {$total} - FRAGMENTO FINAL. Ya recibiste el mensaje completo repartido en {$total} fragmentos. Genera ahora el análisis completo siguiendo EXACTAMENTE las instrucciones y la estructura de secciones indicadas al inicio del mensaje.]"
                    : $fragmento."\n\n[FRAGMENTO {$numero} DE {$total} - NO analices todavía. Responde ÚNICAMENTE: 'Fragmento {$numero} recibido.' y espera el siguiente fragmento.]";

                $result = $this->llamarOpenClaw(
                    $sessionKey,
                    $envio,
                    $esUltimo ? self::TIMEOUT_FINAL_SEGUNDOS : self::TIMEOUT_FRAGMENTO_SEGUNDOS
                );
                $resultados[] = $result;
            }
        }

        $duracion = round(microtime(true) - $inicio, 1);
        $resultado = $this->decodificarResultado($result?->output() ?? '');
        $texto = trim($resultado['payloads'][0]['text'] ?? '');

        if ($texto === '') {
            throw new \RuntimeException('Openclaw devolvió una respuesta vacía');
        }

        $secciones = $this->parseSecciones($texto);
        $clasificacion = $this->extraerClasificacion($secciones['CONCLUSIONES'] ?? $texto);
        $metricas = $this->agregarMetricas($resultados);

        return [
            'texto_completo' => $texto,
            'secciones' => $secciones,
            'clasificacion' => $clasificacion,
            ...$metricas,
            'llamadas_modelo' => count($resultados),
            'fragmentos' => $fragmentosProcesados,
            'duracion_segundos' => $duracion,
        ];
    }

    private function llamarOpenClaw(string $sessionKey, string $mensaje, int $timeoutSegundos): ProcessResult
    {
        $result = Process::timeout($timeoutSegundos)->run([
            'sudo', '-H', '-u', 'root',
            'openclaw', 'agent', '--agent', 'main',
            '--session-key', $sessionKey,
            '--thinking', 'off',
            '--message', $mensaje,
            '--json',
        ]);

        if (! $result->successful()) {
            Log::error('[KairoEaService] openclaw falló', ['stderr' => $result->errorOutput()]);
            throw new \RuntimeException('Openclaw falló: '.$this->categorizarError($result->errorOutput()));
        }

        return $result;
    }

    private function decodificarResultado(string $stdout): array
    {
        if (! preg_match('/\{.*\}/s', $stdout, $matches)) {
            throw new \RuntimeException('Openclaw no devolvió una respuesta JSON válida');
        }

        $data = json_decode($matches[0], true, flags: JSON_THROW_ON_ERROR);

        return $data['result'] ?? $data;
    }

    /** @param array<int, ProcessResult> $resultados */
    private function agregarMetricas(array $resultados): array
    {
        $totales = ['input' => 0, 'output' => 0, 'cache' => 0, 'total' => 0];
        $modelo = null;
        $encontroUso = false;

        foreach ($resultados as $result) {
            try {
                $respuesta = $this->decodificarResultado($result->output());
            } catch (\Throwable) {
                continue;
            }

            $meta = $respuesta['meta']['agentMeta'] ?? [];
            $usage = $meta['usage'] ?? [];
            $modelo ??= $meta['model'] ?? $respuesta['model'] ?? null;

            if (! is_array($usage) || $usage === []) {
                continue;
            }

            $encontroUso = true;
            $totales['input'] += (int) ($usage['input'] ?? $usage['inputTokens'] ?? 0);
            $totales['output'] += (int) ($usage['output'] ?? $usage['outputTokens'] ?? 0);
            $totales['cache'] += (int) ($usage['cacheRead'] ?? 0) + (int) ($usage['cacheWrite'] ?? 0);
            $totales['total'] += (int) ($usage['total'] ?? $usage['totalTokens'] ?? 0);
        }

        return [
            'tokens_entrada' => $encontroUso ? $totales['input'] : null,
            'tokens_salida' => $encontroUso ? $totales['output'] : null,
            'tokens_cache' => $encontroUso ? $totales['cache'] : null,
            'tokens_totales' => $encontroUso ? $totales['total'] : null,
            'modelo' => $modelo,
        ];
    }

    private function dividirEnFragmentos(string $texto, int $maxBytes): array
    {
        $fragmentos = [];
        while (strlen($texto) > 0) {
            if (strlen($texto) <= $maxBytes) {
                $fragmentos[] = $texto;
                break;
            }
            $corte = $maxBytes;
            $salto = strrpos(substr($texto, 0, $corte), "\n");
            if ($salto !== false && $salto > $maxBytes * 0.5) {
                $corte = $salto;
            }
            $fragmentos[] = substr($texto, 0, $corte);
            $texto = substr($texto, $corte);
        }

        return $fragmentos;
    }

    private function prepararHistoria(?string $historia): ?string
    {
        if ($historia === null || trim($historia) === '') {
            return null;
        }
        $historia = $this->normalizarTexto($historia);

        return $this->limitarTexto($historia, self::MAX_HISTORIA_CHARS);
    }

    private function limitarTexto(string $texto, int $maximo): string
    {
        return strlen($texto) > $maximo ? substr($texto, 0, $maximo) : $texto;
    }

    private function normalizarTexto(string $texto): string
    {
        $texto = preg_replace('/\r\n|\r/', "\n", $texto);

        return preg_replace('/\n{3,}/', "\n\n", $texto);
    }

    private function categorizarError(string $error): string
    {
        if (str_contains($error, 'timed out')) {
            return 'Tiempo de espera agotado';
        }
        if (str_contains($error, 'empty')) {
            return 'Respuesta vacía del modelo';
        }

        return substr($error, 0, 200);
    }

    private function extraerClasificacion(string $conclusiones): ?string
    {
        $c = strtolower($conclusiones);
        if (str_contains($c, 'no evento adverso')) {
            return 'No evento adverso';
        }
        if (str_contains($c, 'evento adverso')) {
            return 'Evento adverso';
        }
        if (str_contains($c, 'incidente')) {
            return 'Incidente';
        }
        if (str_contains($c, 'reconsulta')) {
            return 'Reconsulta por evolución';
        }

        return null;
    }

    private function parseSecciones(string $texto): array
    {
        $result = [];
        $current = null;

        foreach (preg_split('/\R/u', $texto) ?: [] as $linea) {
            $seccion = $this->detectarSeccion($linea);

            if ($seccion !== null) {
                $current = $seccion;
                $result[$current] ??= '';

                continue;
            }

            if ($current !== null) {
                $result[$current] = trim($result[$current]."\n".$linea);
            }
        }

        return $result;
    }

    private function detectarSeccion(string $linea): ?string
    {
        $normalizada = Str::upper(Str::ascii(strip_tags($linea)));
        $normalizada = preg_replace('/^[\s#>*_\-\d.)]+/u', '', $normalizada) ?? $normalizada;
        $normalizada = trim($normalizada, " \t\n\r\0\x0B:;.*_-");

        $aliases = [
            'ANÁLISIS DE CAUSALIDAD' => [
                'ANALISIS DE CAUSALIDAD',
            ],
            'ACCIONES INSEGURAS IDENTIFICADAS' => [
                'ACCIONES INSEGURAS IDENTIFICADAS',
            ],
            'FACTORES CONTRIBUTIVOS' => [
                'FACTORES CONTRIBUTIVOS',
            ],
            'FACTORES RELACIONADOS CON LA ADMINISTRACIÓN DE LA CULTURA ORGANIZACIONAL' => [
                'FACTORES RELACIONADOS CON LA ADMINISTRACION DE LA CULTURA ORGANIZACIONAL',
                'FACTORES ORGANIZACIONALES Y CULTURALES',
                'FACTORES ORGANIZACIONALES',
            ],
            'LECCIONES APRENDIDAS' => [
                'LECCIONES APRENDIDAS',
            ],
            'PLANES DE ACCIÓN' => [
                'PLANES DE ACCION',
                'PLAN DE ACCION',
            ],
            'CONCLUSIONES' => [
                'CONCLUSIONES',
                'CONCLUSION',
            ],
        ];

        foreach ($aliases as $canonica => $variantes) {
            foreach ($variantes as $variante) {
                if ($normalizada === $variante
                    || str_starts_with($normalizada, $variante.' (')) {
                    return $canonica;
                }
            }
        }

        return null;
    }

    private function systemPrompt(): string
    {
        return PromptConfig::obtener('ea') ?? $this->defaultPrompt();
    }

    private function defaultPrompt(): string
    {
        return 'Actua como un medico coordinador de urgencias con experiencia en seguridad del paciente. Analiza el caso clinico con enfoque institucional y no punitivo. Usa las secciones: ANALISIS DE CAUSALIDAD, ACCIONES INSEGURAS IDENTIFICADAS, FACTORES CONTRIBUTIVOS, FACTORES RELACIONADOS CON LA ADMINISTRACION DE LA CULTURA ORGANIZACIONAL, LECCIONES APRENDIDAS, PLANES DE ACCION, CONCLUSIONES.';
    }
}
