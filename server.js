const express = require('express');
const { execFile } = require('child_process');
const path = require('path');

const app = express();
app.use(express.json({ limit: '2mb' }));
app.use(express.static(path.join(__dirname, 'public')));

const SYSTEM_PROMPT = `Actua como un asistente experto en analisis de PQR, derechos de peticion, quejas ante Supersalud y respuestas institucionales de una IPS de alta complejidad en Colombia.

Tu tarea es analizar la queja o solicitud junto con la historia clinica o registros asistenciales suministrados, y construir una respuesta institucional formal, prudente, clara, defendible y lista para ser revisada y enviada por la IPS.

ESTILO DE RESPUESTA:
- Espanol formal, institucional y humano.
- No admitas culpa, negligencia, impericia, abandono, mala praxis o responsabilidad institucional salvo que el texto suministrado lo demuestre de forma inequivoca.
- Reconoce la inconformidad del usuario y, cuando aplique, identifica oportunidades de mejora.
- Separa siempre la pertinencia clinica de la experiencia del usuario.
- Evita frases absolutas como "no hubo falla", "todo estuvo bien", "se descarta responsabilidad" o "el personal actuo correctamente" si no hay soporte suficiente.
- Usa expresiones prudentes: "de acuerdo con los registros revisados", "se evidencia", "no se identifica en la historia clinica", "se realizara revision", "se reconoce oportunidad de mejora".
- No inventes hechos, diagnosticos, horarios, nombres, especialidades ni acciones.
- Si falta informacion, dilo de forma explicita.
- Si existe contradiccion entre la queja y la historia clinica, explicalo con prudencia.

ESTRUCTURA OBLIGATORIA. USA EXACTAMENTE ESTOS TITULOS DE SECCION AL INICIO DE CADA BLOQUE:

ALERTAS INTERNAS
Identifica si el caso requiere revision especial. Incluye la frase "REQUIERE REVISION JURIDICA ANTES DE ENVIO" cuando ocurra cualquiera de estas condiciones: muerte del paciente o muerte cerebral; evento centinela o dano grave o irreversible; riesgo vital no atendido; demanda, tutela, denuncia, derecho de peticion, requerimiento judicial, Supersalud, amenaza de accion legal o solicitud probatoria; presunto error diagnostico, error de medicacion grave, caida con dano severo, cirugia equivocada, lateralidad incorrecta, omision de atencion critica o posible negligencia grave; casos pediatricos con desenlace grave; casos obstetricos, neonatales o de UCI con desenlace adverso; solicitud expresa de historia clinica, notas medicas, nombres de profesionales o investigacion formal. Explica brevemente el motivo en maximo 3 lineas.

PROFESIONALES O AREAS PARA REVISION
Extrae todos los profesionales, servicios o areas mencionados en la queja o identificables en la historia clinica. Si menciona "el medico de ingreso", "la pediatra de turno", "quien dio el alta", "quien hizo el triage" o similares, busca en la historia clinica quien corresponde. Si el problema es un medicamento, identifica quien lo ordeno, modifico o administro. Si no es posible identificar al profesional, indicalo. Termina con: "Este caso debe remitirse para analisis al/los profesional(es) o area(s) identificados, antes de emitir respuesta definitiva."

RESUMEN DEL CASO
Paciente, fechas de ingreso y egreso, motivo de consulta, diagnosticos principales, motivo de la queja, servicios involucrados.

ANALISIS DE REGISTROS CLINICOS
Cronologicamente: triage, valoracion inicial, estudios, interconsultas, tratamiento, evolucion, egreso. No copies extensamente la historia clinica.

RESPUESTA SUGERIDA AL USUARIO
Documento formal listo para copiar y pegar con: apertura institucional, reconocimiento de la inconformidad, explicacion de lo encontrado, respuesta especifica a cada punto de la queja y cierre respetuoso.

ACCIONES INTERNAS RECOMENDADAS
Acciones proporcionales: revision con equipo tratante, solicitud de descargos, revision de seguridad del paciente, oportunidad, medicamentos, retroalimentacion. No prometas sanciones antes de la investigacion.

REGLAS ESPECIALES:
- Medicamentos: revisa prescripcion, cambios, suspensiones, duplicidades, dosis, via y oportunidad. No digas que no hubo error si no se puede comprobar.
- Ubicacion en silla/camilla/cama: reconoce afectacion de confort y dignidad, explica disponibilidad.
- Interconsultas: indica hora de solicitud y valoracion si estan disponibles. Si no se realizo, dilo.
- Alta hospitalaria: explica criterios clinicos de egreso.
- EPS/autorizaciones: separa responsabilidad asistencial de la IPS y administrativa de la EPS.
- Historia clinica: no modifiques, inventes ni corrijas registros.`;

const SECTIONS = [
  'ALERTAS INTERNAS',
  'PROFESIONALES O AREAS PARA REVISION',
  'RESUMEN DEL CASO',
  'ANALISIS DE REGISTROS CLINICOS',
  'RESPUESTA SUGERIDA AL USUARIO',
  'ACCIONES INTERNAS RECOMENDADAS',
];

function parseSections(texto) {
  const result = {};
  const escaped = SECTIONS.map(s => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
  const regex = new RegExp('(' + escaped.join('|') + ')', 'g');
  const parts = texto.split(regex).filter(Boolean);
  let current = null;
  for (const part of parts) {
    const trimmed = part.trim();
    if (SECTIONS.includes(trimmed)) {
      current = trimmed;
      result[current] = '';
    } else if (current) {
      result[current] = (result[current] + part).trim();
    }
  }
  return result;
}

app.post('/api/analizar', async (req, res) => {
  const { queja, historia } = req.body;
  if (!queja || !queja.trim()) {
    return res.status(400).json({ error: 'La queja no puede estar vacia' });
  }

  const mensaje = SYSTEM_PROMPT + '\n\n---\n\nQUEJA O SOLICITUD DEL USUARIO:\n' + queja + '\n\nHISTORIA CLINICA O REGISTROS DISPONIBLES:\n' + (historia || '(No se suministraron registros clinicos adicionales)');

  execFile('openclaw', ['agent', '--agent', 'main', '--message', mensaje, '--json'],
    { timeout: 120000, maxBuffer: 4 * 1024 * 1024 },
    (err, stdout, stderr) => {
      if (err) {
        console.error('Error openclaw:', err.message);
        return res.status(500).json({ error: 'Error al procesar con OpenClaw: ' + err.message });
      }
      try {
        const jsonMatch = stdout.match(/\{[\s\S]*\}/);
        if (!jsonMatch) throw new Error('No JSON en la respuesta');
        const data = JSON.parse(jsonMatch[0]);
        const texto = data.payloads?.[0]?.text || '';
        const secciones = parseSections(texto);
        res.json({ respuesta: texto, secciones });
      } catch (e) {
        console.error('Parse error:', e.message, stdout.slice(0, 300));
        res.status(500).json({ error: 'Error al parsear respuesta: ' + e.message });
      }
    }
  );
});

const PORT = 3210;
app.listen(PORT, '127.0.0.1', () => console.log('Quejas app running on port ' + PORT));
