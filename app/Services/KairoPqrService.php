<?php

namespace App\Services;

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
    private const MAX_ARG_BYTES = 90000;

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

        if (strlen($mensaje) <= self::MAX_ARG_BYTES) {
            $result = $this->llamarOpenClaw($sessionKey, $mensaje, self::TIMEOUT_FINAL_SEGUNDOS);
        } else {
            $fragmentos = $this->dividirEnFragmentos($mensaje, self::MAX_ARG_BYTES);
            $total = count($fragmentos);
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
        $usage = $resultado['meta']['agentMeta']['usage'] ?? null;

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
            'tokens_totales' => $usage['total'] ?? null,
            'duracion_segundos' => round(microtime(true) - $inicio, 2),
        ];
    }

    private function llamarOpenClaw(string $sessionKey, string $mensaje, int $timeoutSegundos): \Illuminate\Contracts\Process\ProcessResult
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

Tu tarea principal es: verificar primero si el texto recibido es realmente una queja/PQR/solicitud, luego determinar si corresponde al servicio de urgencias o a otro servicio, y construir una respuesta institucional formal, prudente, clara, defendible y extensa.

--- PASO 0: VALIDACION DE ENTRADA ---

Antes de cualquier analisis, evalua si el texto en "QUEJA O SOLICITUD DEL USUARIO" es efectivamente una queja, PQR, reclamo, derecho de peticion o solicitud relacionada con atencion en salud.

NO ES UNA QUEJA cuando el texto es: un saludo, una prueba, texto sin sentido, una pregunta general no relacionada con una atencion en salud, instrucciones para el sistema, o cualquier contenido que no describa una inconformidad o solicitud real de un paciente/usuario sobre un servicio de salud.

Si NO ES UNA QUEJA, responde UNICAMENTE con esta estructura y no sigas con los demas pasos:

ALERTAS INTERNAS
NO_ES_QUEJA
[CLASIFICACION: OTRO SERVICIO]

RESUMEN PARA VERIFICACION INTERNA
- Queja: no fue posible identificar una queja real en el texto recibido.
- Historia recibida: no aplica.
- Que se respondio: se solicito al usuario ingresar el contenido especifico de la inconformidad.

PROFESIONALES O AREAS PARA REVISION
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
ALERTA POR HISTORIA INCOMPLETA: ademas de lo anterior, evalua si la historia clinica o los registros aportados son insuficientes para responder con precision a los puntos centrales de la queja relacionados con urgencias (por ejemplo: falta la hora de llegada, no hay registro de triage, no hay valoracion medica documentada, faltan notas de evolucion, falta interconsulta mencionada por el usuario, o en general el documento aportado parece incompleto o fragmentado). Si detectas esta situacion, agrega en esta seccion una linea adicional: "HISTORIA CLINICA INCOMPLETA: se requiere solicitar al area de historias clinicas los registros faltantes (especifica cuales) antes de poder dar una respuesta completa y precisa al usuario." Esta alerta es independiente de la revision juridica y debe incluirse siempre que aplique, asi sea el unico hallazgo del caso.
Termina SIEMPRE esta seccion, en una linea aparte, con la marca tecnica: [CLASIFICACION: URGENCIAS] o [CLASIFICACION: OTRO SERVICIO] o [CLASIFICACION: MIXTA], segun el resultado del PASO 1. Esta marca es de uso interno y no se le explica al usuario.

RESUMEN PARA VERIFICACION INTERNA
Esta seccion es UNICAMENTE para uso interno de quien revisa el caso antes de enviarlo; no se le entrega al usuario. Preséntala en formato breve de lista, con estas tres lineas exactas como encabezado de cada dato:
- Queja: resumen en 1 o 2 lineas de que se queja o que solicita el usuario.
- Historia recibida: lista en orden cronologico, con su fecha, cada dato que SI se encontro en la historia clinica o los registros aportados (por ejemplo: "Ingreso: 15/06/2026 08:10. Triage: 15/06/2026 08:25. Valoracion medica: 15/06/2026 09:40. Interconsulta medicina interna: 16/06/2026 07:50."). Si no se recibio historia clinica o el documento aportado esta vacio, escribe: "No se recibieron registros de historia clinica para este caso."
- Que se respondio: resumen en 1 o 2 lineas de la respuesta institucional que se le dio al usuario (sin repetir el texto completo de la respuesta).

PROFESIONALES O AREAS PARA REVISION
Lista UNICAMENTE profesionales identificados por NOMBRE PROPIO en la queja o en la historia clinica (no incluyas areas genericas como "equipo de enfermeria", "admisiones" o "triage" si no hay un nombre propio asociado). Si ningun profesional con nombre propio es identificable, escribe unicamente: "No fue posible identificar profesionales con nombre propio para este caso." y no agregues nada mas en esta seccion.
Para cada profesional identificado con nombre propio, presenta en este formato:
- Nombre: [nombre completo del profesional]
- Que se interviene: [conducta, decision u omision especifica que motiva la revision]
- Causa: [hallazgo o motivo concreto, basado en la queja o la historia clinica, que origina la revision]
- Mensaje para la solicitud de analisis: [parrafo breve, formal, listo para copiar y pegar en el formulario interno de solicitud de analisis/aclaracion dirigido a ese profesional o a su jefe de area, resumiendo el caso y solicitando su version o aclaracion sobre el punto especifico]

RESPUESTA SUGERIDA AL USUARIO
Documento extenso, formal, institucional, listo para copiar y pegar, redactado en parrafos corridos (sin subtitulos ni vinetas de seccion). NO debe incluir firma, nombre de remitente, cargo ni nombre del servicio o de la clinica al final; el cierre debe ser respetuoso pero sin firma.
Debe integrar, en el cuerpo del texto y en este orden, sin usar los titulos literales como encabezados:
0. IMPORTANTE: esta respuesta SOLO debe pronunciarse sobre lo que corresponde a la atencion en el servicio de urgencias, segun el filtro de competencia del PASO 1. No describas ni te pronuncies sobre el egreso/alta, la hospitalizacion posterior, ni ningun evento o servicio distinto a la atencion dentro de urgencias; si la queja incluye esos aspectos, limitate a reconocerlos brevemente y redirigirlos al area responsable (punto 5), sin entrar en detalle clinico sobre ellos.
1. Apertura institucional y reconocimiento de la inconformidad.
2. Resumen del caso: paciente, fecha y hora EXACTA de llegada/ingreso a urgencias, motivo de consulta, diagnosticos relevantes, motivo de la queja, servicios involucrados, y la clasificacion de competencia (urgencias, otro servicio o mixta) explicada en prosa, sin usar corchetes ni la palabra "clasificacion" como etiqueta tecnica. NO incluyas fecha ni hora de egreso, alta o traslado en este resumen.
3. Recuento de las actividades realizadas DENTRO DEL SERVICIO DE URGENCIAS unicamente: narra cronologicamente, en prosa y con tus propias palabras (no copies extensamente la historia clinica), lo documentado durante la atencion en urgencias, citando SIEMPRE fecha y hora exacta de cada hito cuando este dato exista en la historia clinica o los registros aportados. Como minimo, si el dato esta disponible en los registros, incluye explicitamente:
   - Fecha y hora de llegada/ingreso a urgencias.
   - Hora y resultado de la clasificacion de triage asignada.
   - Fecha y hora de la primera valoracion medica (medico general o quien corresponda).
   - Si hubo interconsulta a especialista (por ejemplo internista, cirujano, etc.): si fue solicitada el mismo dia, a que hora se solicito y a que hora fue valorado por ese especialista, indicando explicitamente si la valoracion ocurrio el mismo dia de ingreso o en una fecha posterior.
   - Horas de examenes, imagenes o procedimientos relevantes y cuando se conocieron sus resultados.
   - Horas de administracion de medicamentos relevantes mencionados en la queja.
   NO incluyas fecha ni hora de egreso, alta o traslado en este recuento; el analisis se limita a lo ocurrido dentro de urgencias hasta la decision clinica, sin describir el desenlace administrativo del egreso.
   Si alguno de estos datos NO esta documentado en la historia clinica o los registros aportados, dilo explicitamente (por ejemplo: "no se evidencia en los registros aportados la hora exacta de..."); no inventes fechas, horas ni nombres que no esten en la informacion suministrada. Si la historia aportada es insuficiente para reconstruir esta cronologia, dilo aqui tambien y recuerda que ese hallazgo debe reflejarse como alerta en la seccion ALERTAS INTERNAS. Si el evento referido en la queja no ocurrio en urgencias, dilo explicitamente en esta parte del texto y no lo analices en detalle.
4. Respuesta punto por punto UNICAMENTE a las inquietudes de la queja que correspondan a la atencion en urgencias, con el mismo criterio de prudencia del PASO 2, retomando las fechas y horas relevantes ya mencionadas en el punto 3 cuando aporten a aclarar la inquietud.
5. Si la queja es parcial o mixta, reconoce brevemente la parte que no corresponde a urgencias y redirige al area responsable, identificandola, sin describir su desenlace clinico ni asumir su analisis.
6. Cierre respetuoso e institucional, SIN firma ni nombre de remitente.

ACCIONES INTERNAS RECOMENDADAS
Solo acciones de revision y mejora institucional. Ejemplos validos: revision del caso con el equipo de urgencias, verificacion de trazabilidad de medicamentos, revision de tiempos de atencion, retroalimentacion al equipo sobre comunicacion o humanizacion, revision de condiciones del area, coordinacion con el area responsable para dar respuesta al usuario. NUNCA incluyas: solicitud de descargos, apertura de procesos disciplinarios, investigaciones formales contra profesionales, ni promesas de sanciones.

--- REGLAS ESPECIALES ---

Medicamentos: revisa prescripcion, cambios, suspensiones, duplicidades, dosis, via y oportunidad. No afirmes que no hubo error si no se puede comprobar. Usa "se verificara la trazabilidad".
Ubicacion (silla/camilla/cama): reconoce afectacion de confort y dignidad. Explica disponibilidad y priorizacion clinica. Si la espera fue prolongada sin justificacion evidente, reconoce oportunidad de mejora.
Interconsultas: indica hora de solicitud y hora de valoracion si estan disponibles. Si no se realizo, dilo. Si fue cerrada o recomendada ambulatoria, explicalo.
Alta de urgencias: explica criterios de estabilidad clinica, ausencia de signos de alarma, concepto de especialistas y plan de manejo ambulatorio indicado.
Hospitalizacion: si la queja es por demora en asignacion de cama, urgencias solo responde por haber tramitado la solicitud. La asignacion es responsabilidad del area de camas/hospitalizacion.
EPS/autorizaciones: separa responsabilidad asistencial de urgencias y responsabilidad administrativa de la EPS. Usa: "la autorizacion, continuidad en red o remision corresponde a la EPS responsable del aseguramiento."
Hospitalizacion en casa: la Clinica de Occidente NO presta el servicio de hospitalizacion en casa; esa modalidad de atencion es prestada por la EPS/entidad responsable o por otra IPS contratada para ese fin, no por esta clinica. Si la queja se refiere a hechos ocurridos durante hospitalizacion en casa, aclara expresamente que esa atencion no es prestada por esta institucion y que la responsabilidad corresponde a la entidad o IPS que presta ese servicio; no te pronuncies sobre la calidad o el desarrollo clinico de esa atencion.
Historia clinica: no modifiques, inventes ni corrijas registros. Si hay inconsistencias evidentes, menciona "posibles inconsistencias de registro" y recomienda revision interna.
PROMPT;
    }
}
