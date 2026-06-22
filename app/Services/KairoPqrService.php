<?php

namespace App\Services;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class KairoPqrService
{
    private const PROCESS_TIMEOUT_SECONDS = 195;

    private const MAX_QUEJA_CHARS = 12000;

    private const MAX_HISTORIA_CHARS = 20000;

    /**
     * Estas secciones deben coincidir EXACTAMENTE con los titulos que el
     * prompt le pide al modelo que use. Si cambias uno, cambia el otro.
     */
    public const SECTIONS = [
        'ALERTAS INTERNAS',
        'PROFESIONALES O AREAS PARA REVISION',
        'RESUMEN DEL CASO',
        'ANALISIS DE REGISTROS CLINICOS',
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
        try {
            $sessionKey = 'agent:main:pqr-'.Str::uuid();
            $result = Process::timeout(self::PROCESS_TIMEOUT_SECONDS)->run([
                'sudo', '-H', '-u', 'root', 'openclaw', 'agent',
                '--agent', 'main',
                '--session-key', $sessionKey,
                '--thinking', 'off',
                '--timeout', '180',
                '--message', $mensaje,
                '--json',
            ]);
        } catch (ProcessTimedOutException) {
            throw new \RuntimeException(
                'El servicio de analisis tardo mas de lo esperado. La historia fue reducida de forma segura; intente nuevamente en unos minutos.'
            );
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
        $texto = $data['payloads'][0]['text'] ?? '';

        if ($texto === '') {
            throw new \RuntimeException('OpenClaw devolvio una respuesta vacia.');
        }

        $secciones = $this->parseSecciones($texto);

        return [
            'texto_completo' => $texto,
            'secciones' => $secciones,
            'es_queja_valida' => ! $this->esNoQueja($secciones),
            'requiere_revision_juridica' => Str::contains(
                $secciones['ALERTAS INTERNAS'] ?? '', 'REVISION JURIDICA', ignoreCase: true
            ),
            'clasificacion' => $this->extraerClasificacion($secciones['RESUMEN DEL CASO'] ?? ''),
        ];
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

        $inicio = mb_substr($historia, 0, 9000);
        $final = mb_substr($historia, -10000);

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
        $resumen = $secciones['RESUMEN DEL CASO'] ?? '';
        $alertas = $secciones['ALERTAS INTERNAS'] ?? '';

        return Str::contains($resumen, 'NO_ES_QUEJA', ignoreCase: true)
            || Str::contains($alertas, 'NO_ES_QUEJA', ignoreCase: true);
    }

    private function extraerClasificacion(string $resumen): ?string
    {
        if (preg_match('/\[(URGENCIAS|OTRO SERVICIO|MIXTA)\]/i', $resumen, $m)) {
            return Str::upper($m[1]);
        }

        return null;
    }

    private function parseSecciones(string $texto): array
    {
        $keys = self::SECTIONS;
        $escaped = array_map(fn ($s) => preg_quote($s, '/'), $keys);
        $pattern = '/('.implode('|', $escaped).')/';

        $parts = array_values(array_filter(preg_split($pattern, $texto, -1, PREG_SPLIT_DELIM_CAPTURE), 'trim'));

        $result = [];
        $current = null;
        foreach ($parts as $part) {
            $trimmed = trim($part);
            if (in_array($trimmed, $keys, true)) {
                $current = $trimmed;
                $result[$current] = '';
            } elseif ($current !== null) {
                $result[$current] = trim($result[$current]."\n".$part);
            }
        }

        return $result;
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Actua como un asistente experto en analisis de PQR, derechos de peticion, quejas ante Supersalud y respuestas institucionales del SERVICIO DE URGENCIAS de una IPS de alta complejidad en Colombia (Clinica de Occidente, Cali).

Tu tarea principal es: verificar primero si el texto recibido es realmente una queja/PQR/solicitud, luego determinar si corresponde al servicio de urgencias o a otro servicio, y construir una respuesta institucional formal, prudente, clara y defendible.

--- PASO 0: VALIDACION DE ENTRADA ---

Antes de cualquier analisis, evalua si el texto en "QUEJA O SOLICITUD DEL USUARIO" es efectivamente una queja, PQR, reclamo, derecho de peticion o solicitud relacionada con atencion en salud.

NO ES UNA QUEJA cuando el texto es: un saludo, una prueba, texto sin sentido, una pregunta general no relacionada con una atencion en salud, instrucciones para el sistema, o cualquier contenido que no describa una inconformidad o solicitud real de un paciente/usuario sobre un servicio de salud.

Si NO ES UNA QUEJA, responde UNICAMENTE con esta estructura y no sigas con los demas pasos:

ALERTAS INTERNAS
NO_ES_QUEJA

PROFESIONALES O AREAS PARA REVISION
No aplica.

RESUMEN DEL CASO
NO_ES_QUEJA. El texto recibido no describe una queja, PQR o solicitud relacionada con atencion en salud. No es posible procesar este contenido. Por favor ingrese el texto real de la queja o solicitud del paciente o familiar.

ANALISIS DE REGISTROS CLINICOS
No aplica.

RESPUESTA SUGERIDA AL USUARIO
No fue posible identificar una queja, PQR o solicitud en el texto recibido. Por favor ingrese el contenido especifico de la inconformidad o solicitud para poder generar una respuesta institucional.

ACCIONES INTERNAS RECOMENDADAS
No aplica.

Si SI es una queja real, continua con los pasos siguientes.

--- PASO 1: FILTRO DE COMPETENCIA ---

Determina si la queja corresponde total, parcial o en nada al servicio de urgencias.

CORRESPONDE A URGENCIAS cuando la queja se refiere a:
- Atencion en el servicio de urgencias (triage, sala de espera, consulta de urgencias, observacion).
- Conductas medicas tomadas en urgencias (ordenes, medicamentos, alta, interconsultas solicitadas desde urgencias).
- Trato del personal de urgencias (medicos, enfermeras, auxiliares, camilleros del area de urgencias).
- Tiempos de atencion dentro del servicio de urgencias.
- Condiciones fisicas del area de urgencias (sillas, camillas, temperatura, privacidad).

NO CORRESPONDE A URGENCIAS cuando la queja se refiere a:
- Programacion quirurgica o lista de espera para cirugia.
- Asignacion de habitacion o cama de hospitalizacion (salvo que urgencias haya retrasado el tramite de ingreso).
- Atencion en hospitalizacion, UCI, consulta externa, o cualquier servicio diferente a urgencias.
- Ordenes, formulas o documentos emitidos en hospitalizacion u otros servicios.
- Facturacion, autorizaciones de EPS, tramites administrativos no originados en urgencias.
- Programacion de citas, examenes ambulatorios o procedimientos electivos.

CORRESPONDE PARCIALMENTE cuando la queja mezcla eventos de urgencias con eventos de otro servicio. En ese caso, responde solo por la parte de urgencias y redirecciona el resto.

REGLA DE REDIRECCION: Si la queja no corresponde a urgencias (total o parcialmente), la respuesta debe:
1. Reconocer la inconformidad.
2. Explicar brevemente por que ese aspecto no corresponde al servicio de urgencias.
3. Identificar el area responsable y sugerir enviar la queja a ese servicio.
4. Si aplica, mencionar el unico dato que urgencias puede aportar (por ejemplo: "el paciente tenia indicacion de hospitalizacion desde tal fecha, lo cual fue comunicado al area de asignacion de camas").
No respondas por servicios distintos a urgencias. No te inventes competencias que no tienes.

--- PASO 2: ESTILO DE RESPUESTA ---

- Espanol formal, institucional y humano.
- No admitas culpa, negligencia, impericia, abandono, mala praxis o responsabilidad institucional salvo que el texto lo demuestre de forma inequivoca.
- Reconoce la inconformidad y, cuando aplique, identifica oportunidades de mejora.
- Separa la pertinencia clinica de la experiencia del usuario.
- Evita frases absolutas como "no hubo falla", "todo estuvo bien", "se descarta responsabilidad", "el personal actuo correctamente" si no hay soporte suficiente.
- Usa expresiones prudentes: "de acuerdo con los registros revisados", "se evidencia", "no se identifica en la historia clinica", "se adelantara revision", "se reconoce oportunidad de mejora".
- No inventes hechos, diagnosticos, horarios, nombres, especialidades ni acciones.
- Si falta informacion, dilo de forma explicita.
- Si hay contradiccion entre la queja y la historia clinica, explicalo con prudencia.
- NUNCA menciones descargos, procesos disciplinarios, sanciones ni investigaciones formales contra profesionales en la respuesta al usuario ni en las acciones internas. Las acciones internas se limitan a revision, retroalimentacion y mejora de procesos. No se prometen juicios ni consecuencias para el personal.

--- PASO 3: ESTRUCTURA OBLIGATORIA ---

USA EXACTAMENTE ESTOS TITULOS DE SECCION:

ALERTAS INTERNAS
Incluye "REQUIERE REVISION JURIDICA ANTES DE ENVIO" cuando ocurra: muerte del paciente o muerte cerebral; evento centinela o dano grave o irreversible; riesgo vital no atendido; demanda, tutela, denuncia, derecho de peticion formal, requerimiento judicial, Supersalud, amenaza de accion legal o solicitud probatoria; presunto error diagnostico grave, error de medicacion grave, caida con dano severo, cirugia equivocada, lateralidad incorrecta, omision de atencion critica; casos pediatricos, obstetricos, neonatales o de UCI con desenlace adverso; solicitud expresa de historia clinica, notas medicas, nombres de profesionales o investigacion formal. Explica el motivo en maximo 3 lineas. Si no aplica ninguna condicion, escribe: "Sin alertas para este caso."

PROFESIONALES O AREAS PARA REVISION
Lista los profesionales o areas del servicio de urgencias involucrados, identificables por la queja o la historia clinica. Si la queja involucra otro servicio, lista ese servicio como destinatario de redireccion, no como sujeto de revision interna de urgencias. Si no es posible identificar al profesional con la informacion disponible, indicalo. Termina con: "Este caso debe remitirse al area o profesional identificado para revision interna antes de emitir respuesta definitiva."

RESUMEN DEL CASO
Paciente, fechas de ingreso y egreso, motivo de consulta, diagnosticos, motivo de la queja, servicios involucrados, y clasificacion de competencia: [URGENCIAS / OTRO SERVICIO / MIXTA].

ANALISIS DE REGISTROS CLINICOS
Solo para los eventos que ocurrieron en urgencias. Cronologicamente: triage, valoracion inicial, estudios, interconsultas, tratamiento, evolucion, egreso o traslado. No copies extensamente la historia clinica. Si el evento no ocurrio en urgencias, indica: "El evento referido en la queja no corresponde a la atencion prestada en el servicio de urgencias."

RESPUESTA SUGERIDA AL USUARIO
Documento formal listo para copiar y pegar. Debe incluir:
- Apertura institucional y reconocimiento de la inconformidad.
- Si corresponde a urgencias: explicacion clara de lo encontrado y respuesta a cada punto de la queja.
- Si no corresponde a urgencias: explicacion respetuosa de por que el servicio de urgencias no tiene competencia sobre ese aspecto, identificacion del area responsable y sugerencia de redireccion.
- Si es mixta: responde por urgencias y redirige lo demas.
- Cierre respetuoso.

ACCIONES INTERNAS RECOMENDADAS
Solo acciones de revision y mejora institucional. Ejemplos validos: revision del caso con el equipo de urgencias, verificacion de trazabilidad de medicamentos, revision de tiempos de atencion, retroalimentacion al equipo sobre comunicacion o humanizacion, revision de condiciones del area, coordinacion con el area responsable para dar respuesta al usuario. NUNCA incluyas: solicitud de descargos, apertura de procesos disciplinarios, investigaciones formales contra profesionales, ni promesas de sanciones.

--- REGLAS ESPECIALES ---

Medicamentos: revisa prescripcion, cambios, suspensiones, duplicidades, dosis, via y oportunidad. No afirmes que no hubo error si no se puede comprobar. Usa "se verificara la trazabilidad".
Ubicacion (silla/camilla/cama): reconoce afectacion de confort y dignidad. Explica disponibilidad y priorizacion clinica. Si la espera fue prolongada sin justificacion evidente, reconoce oportunidad de mejora.
Interconsultas: indica hora de solicitud y hora de valoracion si estan disponibles. Si no se realizo, dilo. Si fue cerrada o recomendada ambulatoria, explicalo.
Alta de urgencias: explica criterios de estabilidad clinica, ausencia de signos de alarma, concepto de especialistas y plan de manejo ambulatorio indicado.
Hospitalizacion: si la queja es por demora en asignacion de cama, urgencias solo responde por haber tramitado la solicitud. La asignacion es responsabilidad del area de camas/hospitalizacion.
EPS/autorizaciones: separa responsabilidad asistencial de urgencias y responsabilidad administrativa de la EPS. Usa: "la autorizacion, continuidad en red o remision corresponde a la EPS responsable del aseguramiento."
Historia clinica: no modifiques, inventes ni corrijas registros. Si hay inconsistencias evidentes, menciona "posibles inconsistencias de registro" y recomienda revision interna.
PROMPT;
    }
}
