# Acerca de KairoMeet

KairoMeet convierte una reunión de Google Meet en una grabación organizada,
una transcripción y un acta, sin mezclar conversaciones cuando existen varias
reuniones simultáneas.

## Recorrido de una reunión

1. **Detección:** Kairo revisa los calendarios conectados cada minuto. También se puede conectar manualmente.
2. **Ingreso:** abre un navegador independiente, apaga cámara y micrófono y espera admisión cuando sea necesaria.
3. **Captura aislada:** crea un canal de audio exclusivo. Otra reunión puede comenzar sin cerrar ni contaminar la primera.
4. **Vigilancia:** cada 15 segundos comprueba el grabador. Un silencio de 60 segundos queda como posible hueco; si FFmpeg se detiene, se recupera en un segmento nuevo.
5. **Transcripción:** los audios entran en una cola persistente. Groq los procesa por fragmentos y conserva cada avance.
6. **Acta:** una segunda cola entrega la transcripción a OpenClaw, conserva resultados parciales y compila el documento final.
7. **Publicación:** el historial muestra fecha, duración, audio, transcripción, acta, participantes y cobertura.
8. **Retención:** el audio se conserva siete días; el acta y la transcripción permanecen.

## Reuniones simultáneas

Cada reunión recibe su propio identificador, navegador, perfil, canal de audio,
grabador, carpeta y trabajo de procesamiento. Terminar o fallar una sesión no
ordena cerrar la otra.

## Si falla un servicio

- Si Kairo no logra entrar, reintenta hasta cinco veces.
- Si Groq no responde, el audio permanece en cola.
- Si OpenClaw no responde, la transcripción permanece en cola.
- Los reintentos reutilizan fragmentos terminados y no comienzan desde cero.
- Las alertas de captura y posibles huecos quedan asociadas a la reunión.

## Fuentes y límites

El flujo principal siempre es la captura propia de Kairo. La transcripción de
Google Meet no se espera ni bloquea el acta porque Kairo puede ser invitado y
no tener acceso a los artefactos del organizador.

Kairo conserva lo sucedido desde el momento en que logra entrar. Por eso son
esenciales la conexión temprana, la admisión y las alertas de audio.

## Privacidad y acceso

Los usuarios con permiso de reuniones consultan únicamente las funciones
autorizadas. Esta explicación operativa, los calendarios, la conexión manual y
la administración del proceso están reservados al usuario maestro.
