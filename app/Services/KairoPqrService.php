<?php

namespace App\Services;

use App\Models\PromptConfig;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class KairoPqrService
{
    private const PROCESS_TIMEOUT_SECONDS = 280;

    private const MAX_QUEJA_CHARS = 40000;

    private const MAX_HISTORIA_CHARS = 700000;

    /**
     * Linux limita a ~131072 bytes la longitud de UN SOLO argumento de linea
     * de comandos (MAX_ARG_STRLEN), sin importar que ARG_MAX total sea mayor.
     * Si el mensaje completo no cabe ahi, lo partimos en fragmentos y los
     * enviamos uno por uno en la MISMA sesion del agente: los fragmentos
     * intermedios solo se confirman, y el ultimo dispara el analisis completo
     * ya con todo el contexto acumulado en la conversacion.
     */
    private const MAX_ARG_BYTES = 120000;

    private const TIMEOUT_FRAGMENTO_SEGUNDOS = 60;

    private const TIMEOUT_FINAL_SEGUNDOS = 260;

    /**
     * Estas secciones deben coincidir EXACTAMENTE con los titulos que el
     * prompt le pide al modelo que use. Si cambias uno, cambia el otro.
     */
    public const SECTIONS = [
        'ALERTAS INTERNAS',
        'RESUMEN PARA VERIFICACION INTERNA',
        'PROFESIONALES O AREAS PARA REVISION',
        'RESPUESTA SUGERIDA AL USUARIO',
        'ACCIONES INTERNAS RECOMENDADAS',
    ];

    public function analizar(string $queja, ?string $historia): array
    {
        $queja = $this->limitarTexto($queja, self::MAX_QUEJA_CHARS);
        $historia = $this->prepararHistoria($historia);

        $mensaje = $this->systemPrompt()
            ."\n\n---\n\nQUEJA O SOLICITUD DEL USUARIO:\n".$queja
            ."\n\nHISTORIA CLINICA O REGISTROS DISPONIBLES:\n"
            .($historia !== null && $historia !== '' ? $historia : '(No se suministraron registros clinicos adicionales)');

        // IMPORTANTE: se invoca el CLI de OpenClaw, que corre con la sesion
        // de suscripcion OpenAI (runtime Codex) ya autenticada en el VPS.
        // NUNCA usar la API de OpenAI aqui (no hay OPENAI_API_KEY de por medio).
        // El pool de PHP-FPM corre como www-data (aislado del resto del sistema);
        // openclaw requiere la config de /root/.openclaw, por eso se invoca via
        // sudo con una regla restringida en /etc/sudoers.d/kairo-pqr-openclaw que
        // SOLO permite ejecutar este comando exacto, nada mas de /root.
        $inicio = microtime(true);
        $sessionKey = 'agent:main:pqr-'.Str::uuid();
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
                    ? $fragmento."\n\n[FRAGMENTO {$numero} DE {$total} - FRAGMENTO FINAL. Ya recibiste el mensaje completo (instrucciones, queja e historia clinica) repartido en {$total} fragmentos. Genera ahora el analisis completo siguiendo EXACTAMENTE las instrucciones y la estructura de secciones indicadas al inicio del mensaje.]"
                    : $fragmento."\n\n[FRAGMENTO {$numero} DE {$total} - NO respondas ni analices todavia. Responde UNICAMENTE: 'Fragmento {$numero} recibido.' y espera el siguiente fragmento.]";

                $result = $this->llamarOpenClaw(
                    $sessionKey,
                    $envio,
                    $esUltimo ? self::TIMEOUT_FINAL_SEGUNDOS : self::TIMEOUT_FRAGMENTO_SEGUNDOS
                );
                $resultados[] = $result;

                if ($result->failed()) {
                    break;
                }
            }
        }

        if ($result->failed()) {
            Log::warning('OpenClaw no completo un analisis PQR.', [
                'exit_code' => $result->exitCode(),
                'category' => $this->categorizarError($result->errorOutput()),
            ]);

            throw new \RuntimeException('El servicio de analisis no pudo completar la solicitud. Intente nuevamente en unos minutos.');
        }

        $stdout = $result->output();

        if (! preg_match('/\{.*\}/s', $stdout, $matches)) {
            throw new \RuntimeException('OpenClaw no devolvio una respuesta JSON valida.');
        }

        $data = json_decode($matches[0], true, flags: JSON_THROW_ON_ERROR);

        // El gateway envuelve la respuesta en "result"; el fallback embedded
        // la devuelve sin envoltura. Aceptamos ambas formas.
        $resultado = $data['result'] ?? $data;
        $texto = $resultado['payloads'][0]['text'] ?? '';

        if ($texto === '') {
            Log::error('OpenClaw devolvio una respuesta vacia. Payload crudo adjunto.', [
                'userId' => auth()->id(),
                'raw' => $resultado,
            ]);

            throw new \RuntimeException('OpenClaw devolvio una respuesta vacia.');
        }

        $secciones = $this->parseSecciones($texto);
        $metricas = $this->agregarMetricas($resultados);

        $clasificacion = $this->extraerClasificacion($secciones['ALERTAS INTERNAS'] ?? '');
        if (isset($secciones['ALERTAS INTERNAS'])) {
            $secciones['ALERTAS INTERNAS'] = trim(preg_replace(
                '/\[CLASIFICACION:\s*(URGENCIAS|OTRO SERVICIO|MIXTA)\]/i',
                '',
                $secciones['ALERTAS INTERNAS']
            ));
        }

        return [
            'texto_completo' => $texto,
            'secciones' => $secciones,
            'es_queja_valida' => ! $this->esNoQueja($secciones),
            'requiere_revision_juridica' => Str::contains(
                $secciones['ALERTAS INTERNAS'] ?? '', 'REVISION JURIDICA', ignoreCase: true
            ),
            'clasificacion' => $clasificacion,
            ...$metricas,
            'llamadas_modelo' => count($resultados),
            'fragmentos' => $fragmentosProcesados,
            'duracion_segundos' => round(microtime(true) - $inicio, 2),
        ];
    }

    /** @param array<int, ProcessResult> $resultados */
    private function agregarMetricas(array $resultados): array
    {
        $totales = ['input' => 0, 'output' => 0, 'cache' => 0, 'total' => 0];
        $modelo = null;
        $encontroUso = false;

        foreach ($resultados as $result) {
            if (! preg_match('/\{.*\}/s', $result->output(), $matches)) {
                continue;
            }

            $data = json_decode($matches[0], true);
            if (! is_array($data)) {
                continue;
            }

            $respuesta = $data['result'] ?? $data;
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

    private function llamarOpenClaw(string $sessionKey, string $mensaje, int $timeoutSegundos): ProcessResult
    {
        try {
            return Process::timeout($timeoutSegundos)->run([
                'sudo', '-H', '-u', 'root', 'openclaw', 'agent',
                '--agent', 'main',
                '--session-key', $sessionKey,
                '--thinking', 'off',
                '--timeout', (string) $timeoutSegundos,
                '--message', $mensaje,
                '--json',
            ]);
        } catch (ProcessTimedOutException) {
            throw new \RuntimeException(
                'El servicio de analisis tardo mas de lo esperado. La historia fue reducida de forma segura; intente nuevamente en unos minutos.'
            );
        }
    }

    /**
     * Divide un texto en fragmentos de a lo sumo $maxBytes BYTES (no caracteres),
     * sin partir nunca un caracter multibyte UTF-8 por la mitad.
     *
     * @return string[]
     */
    private function dividirEnFragmentos(string $texto, int $maxBytes): array
    {
        $fragmentos = [];
        $actual = '';
        $bytesActuales = 0;

        foreach (mb_str_split($texto) as $caracter) {
            $bytesCaracter = strlen($caracter);

            if ($bytesActuales + $bytesCaracter > $maxBytes && $actual !== '') {
                $fragmentos[] = $actual;
                $actual = '';
                $bytesActuales = 0;
            }

            $actual .= $caracter;
            $bytesActuales += $bytesCaracter;
        }

        if ($actual !== '') {
            $fragmentos[] = $actual;
        }

        return $fragmentos;
    }

    private function prepararHistoria(?string $historia): ?string
    {
        if ($historia === null || trim($historia) === '') {
            return null;
        }

        $historia = trim($this->normalizarTexto($historia));
        if (mb_strlen($historia) <= self::MAX_HISTORIA_CHARS) {
            return $historia;
        }

        $inicio = mb_substr($historia, 0, 320000);
        $final = mb_substr($historia, -350000);

        return $inicio
            ."\n\n[... REGISTROS INTERMEDIOS OMITIDOS PARA OPTIMIZAR EL ANALISIS ...]\n\n"
            .$final;
    }

    private function limitarTexto(string $texto, int $maximo): string
    {
        $texto = trim($this->normalizarTexto($texto));

        return mb_strlen($texto) <= $maximo
            ? $texto
            : mb_substr($texto, 0, $maximo)."\n[... TEXTO OMITIDO ...]";
    }

    private function normalizarTexto(string $texto): string
    {
        $texto = mb_scrub($texto, 'UTF-8');
        $limpio = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $texto);

        return $limpio ?? $texto;
    }

    private function categorizarError(string $error): string
    {
        $error = Str::lower(mb_scrub($error, 'UTF-8'));

        return match (true) {
            Str::contains($error, ['utf', 'encoding', 'invalid character']) => 'encoding',
            Str::contains($error, ['rate limit', 'quota', 'too many requests']) => 'rate_limit',
            Str::contains($error, ['auth', 'unauthorized', 'forbidden', 'credential']) => 'authentication',
            Str::contains($error, ['gateway', 'connection', 'network', 'socket']) => 'connection',
            Str::contains($error, ['context', 'too long', 'token']) => 'context_size',
            default => 'unknown',
        };
    }

    private function esNoQueja(array $secciones): bool
    {
        $alertas = $secciones['ALERTAS INTERNAS'] ?? '';

        return Str::contains($alertas, 'NO_ES_QUEJA', ignoreCase: true);
    }

    private function extraerClasificacion(string $alertas): ?string
    {
        if (preg_match('/\[CLASIFICACION:\s*(URGENCIAS|OTRO SERVICIO|MIXTA)\]/i', $alertas, $m)) {
            return Str::upper($m[1]);
        }

        return null;
    }

    private function parseSecciones(string $texto): array
    {
        $keys = self::SECTIONS;
        // Strip markdown bold markers (**TEXT** or *TEXT*)
        $normalizado = preg_replace('/\*{1,3}([^*\n]+)\*{1,3}/', '$1', $texto);
        $escaped = array_map(fn ($s) => preg_quote($s, '/'), $keys);
        // Case-insensitive match, optional trailing colon/spaces
        $pattern = '/(?:^|\n)[ \t]*('.implode('|', $escaped).')[ \t]*:?[ \t]*(?:\n|$)/im';
        $parts = array_values(array_filter(
            preg_split($pattern, $normalizado, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY),
            'trim'
        ));
        $result = [];
        $current = null;
        $keysUpper = array_map('strtoupper', $keys);
        foreach ($parts as $part) {
            $trimmed = strtoupper(trim($part));
            $idx = array_search($trimmed, $keysUpper, true);
            if ($idx !== false) {
                $current = $keys[$idx];
                $result[$current] = '';
            } elseif ($current !== null) {
                $result[$current] = trim($result[$current]."\n".$part);
            }
        }

        return $result;
    }

    private function systemPrompt(): string
    {
        return PromptConfig::obtener('pqr') ?? $this->defaultPrompt();
    }

    private function defaultPrompt(): string
    {
        return 'Actua como un experto en analisis de PQR y quejas del servicio de urgencias. Responde con las secciones: ALERTAS INTERNAS, RESUMEN PARA VERIFICACION INTERNA, PROFESIONALES O AREAS PARA REVISION, RESPUESTA SUGERIDA AL USUARIO, ACCIONES INTERNAS RECOMENDADAS.';
    }
}
