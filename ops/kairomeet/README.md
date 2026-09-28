# KairoMeet — operación, arquitectura y revisión

Este documento es la fuente de contexto antes de revisar o cambiar KairoMeet.
Describe el flujo actualmente desplegado en `app.koqoi.com` y el VPS.

## 1. Flujo completo

1. `watch.py` consulta cada 60 segundos los calendarios ICS y el caché generado por Google Calendar.
2. Antes de lanzar una URL comprueba `/run/kairo/meet-sessions`; una reunión activa nunca se duplica.
3. `runner.py` crea una sesión y una carpeta exclusiva en `/opt/kairomeet/salidas/YYYYMMDD_HHMMSS_<sesion>`.
4. Cada sesión recibe una copia independiente del perfil de Chrome y un dispositivo PulseAudio `meet_<sesion>`.
5. `meet.py` abre Meet con Playwright, apaga cámara y micrófono, solicita acceso y espera admisión.
6. Tras confirmar el ingreso, `audio.py` captura WAV mono a 16 kHz y rota archivos cada 30 minutos.
7. Hay un latido cada 15 segundos. Registra bytes y minutos, detecta silencios de 60 segundos y recupera únicamente el FFmpeg de la sesión que falle.
8. Al terminar, se publican atómicamente `session.json`, `queue-state.json` y `GRABACION_COMPLETA`.
9. `kairo-process@groq` procesa fragmentos de ocho minutos, guarda cada avance y genera `transcripcion.txt`.
10. `kairo-process@openai` procesa la transcripción por fragmentos, usa OpenClaw y publica `acta-llm.md` y `COMPLETADA`.
11. Laravel importa sesiones terminadas y presenta audio, transcripción, acta, participantes y cobertura.
12. El audio se elimina después de siete días; transcripción y acta se conservan.

## 2. Archivos y estados por reunión

```text
salidas/<sesion>/
├── audio_part000.wav
├── capture-events.jsonl
├── session.json
├── queue-state.json
├── GRABACION_COMPLETA
├── groq-chunks/
├── transcripcion.txt
├── openai-chunks/
├── acta-llm.md
└── COMPLETADA
```

Fases: `pending_groq`, `transcribing_groq`, `waiting_groq`, `pending_openai`,
`processing_openai`, `waiting_openai` y `completed`. Los fallos externos no
descartan el trabajo: hay espera exponencial de hasta 30 minutos y se continúa
desde los fragmentos ya guardados.

## 3. Simultaneidad y reintentos

- Cada reunión tiene navegador, perfil, sink, FFmpeg, carpeta y estado propios.
- Una reunión nueva nunca ordena cerrar otra.
- Una URL activa no se despacha dos veces, incluso tras reiniciar `watch.py`.
- Si no logra entrar, Kairo reintenta hasta cinco veces, cada 120 segundos.
- Una URL recurrente vuelve a ser elegible al salir de la ventana temporal.

## 4. Servicios

| Servicio | Función |
|---|---|
| `kairo-watch` | Detecta reuniones y crea runners |
| `kairo-pulse` | Servidor de audio virtual |
| `kairo-xvfb` | Display virtual para Chrome |
| `kairo-process@groq` | Cola durable de transcripción |
| `kairo-process@openai` | Cola durable de actas |
| `kairo-app-fpm` | Aplicación web |

`kairo-watch` debe conservar `KillMode=process`; esto permite actualizar el
vigilante sin matar grabaciones activas creadas por él.

## 5. Diagnóstico sin interrumpir grabaciones

```bash
pgrep -af '/opt/kairomeet/runner.py|ffmpeg'
for f in /run/kairo/meet-sessions/*.json; do echo "=== $f"; cat "$f"; done
journalctl -u kairo-watch --since '30 minutes ago' --no-pager
systemctl status kairo-process@groq kairo-process@openai --no-pager
```

Para confirmar crecimiento, ejecutar `stat` dos veces sobre el último WAV con
varios segundos de diferencia. Nunca reemplazar ni reiniciar su FFmpeg.

## 6. Despliegue seguro

1. Revisar procesos activos y guardar sus PID.
2. Ejecutar pruebas Python y Laravel.
3. Subir únicamente código; nunca audios, usuarios, sesiones o `.env`.
4. Hacer `git pull --ff-only` en `/var/www/app.koqoi.com`.
5. Ejecutar `php artisan migrate --force` y `php artisan optimize:clear`.
6. Copiar a `/opt/kairomeet` solo los módulos Python modificados, con propietario `kairo:kairo`.
7. No reiniciar PulseAudio, Xvfb, Chrome, FFmpeg ni runners activos.
8. Si cambió `watch.py`, reiniciar solo `kairo-watch` tras confirmar `KillMode=process`.
9. Comparar PID y crecimiento del WAV antes y después.
10. Verificar web, servicios, migraciones y logs.

## 7. Pruebas

```bash
python -m unittest discover -s ops/kairomeet/tests -v
php artisan test
python -m compileall -q ops/kairomeet
```

Cubren aislamiento, estado atómico, recuperación del grabador, colas,
reintentos y prevención de duplicados.

## 8. Límites conocidos

- Kairo captura desde que logra entrar; no reconstruye minutos anteriores.
- Un silencio es una advertencia de posible hueco, no prueba de falla.
- Google puede cambiar los textos usados para detectar el final de Meet.
- La transcripción nativa de Google no bloquea ni forma parte obligatoria del flujo.
- Credenciales, tokens y secretos viven fuera de Git.

