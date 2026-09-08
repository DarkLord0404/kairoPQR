# Arquitectura de KairoMeet — guía completa para replicar el sistema

Este documento explica, de punta a punta, cómo funciona el ecosistema de **KairoMeet** y su integración con **Kairo PQR**, para que el sistema completo se pueda reconstruir desde cero si fuera necesario.

## 1. Visión general

El sistema tiene tres piezas que trabajan juntas en el mismo VPS (`72.62.173.185`):

1. **El bot de grabación** (`/opt/kairomeet`, Python) — se une solo a reuniones de Google Meet, las grava, transcribe y genera el acta.
2. **La app web `meet.koqoi.com`** (Laravel + Livewire) — muestra el catálogo de reuniones grabadas: audio, transcripción, acta, participantes.
3. **La app web `pqr.koqoi.com`** (Laravel + Livewire, app hermana) — analiza quejas/PQR de pacientes con IA. Comparte usuarios y tiene inicio de sesión cruzado (SSO) con `meet.koqoi.com`.

Ambas apps web usan el mismo motor de IA: **OpenClaw**, un CLI que corre en el VPS con la **sesión de suscripción de OpenAI ya autenticada** (no usa la API de pago). Las transcripciones de audio usan **Groq Whisper** (cuota gratuita).

```
┌─────────────────────┐        ┌──────────────────────┐
│  /opt/kairomeet      │        │   OpenClaw (CLI)      │
│  (bot Python)        │──────► │  /root/.openclaw      │◄──────┐
│  watch.py            │        │  sesión OpenAI         │       │
│  runner.py           │        │  (suscripción)         │       │
│  meet.py             │        └──────────────────────┘       │
│  audio.py            │                                        │
│  transcribe.py       │        ┌──────────────────────┐       │
│  actas.py (Groq)     │        │   Groq API            │       │
│  actas_llm.py (LLM)  │──────► │  Whisper + Llama       │       │
│  notifier.py         │        │  (cuota gratis)        │       │
└──────────┬───────────┘        └──────────────────────┘       │
           │ guarda en /opt/kairomeet/salidas/                  │
           ▼                                                     │
┌─────────────────────┐        ┌──────────────────────┐         │
│  meet.koqoi.com      │        │  pqr.koqoi.com        │─────────┘
│  (Laravel/Livewire)  │◄──SSO─►│  (Laravel/Livewire)   │
│  DB: kairo_meet       │        │  DB: kairo_pqrs        │
└─────────────────────┘        └──────────────────────┘
```

## 2. El bot de grabación (`/opt/kairomeet`)

Corre en Linux con un display virtual (Xvfb) y audio virtual (PulseAudio), controlado por Playwright (Chromium).

### Servicios systemd (usuario `kairo`)

| Servicio | Función |
|---|---|
| `kairo-xvfb.service` | Display virtual `:99` (1280x720) donde corre el navegador. |
| `kairo-pulse.service` | PulseAudio en modo servidor, para crear sinks de audio aislados. |
| `kairo-watch.service` | Proceso que vigila el calendario y lanza `runner.py` cuando detecta una reunión. |

### Flujo de una reunión

1. **`watch.py`** — cada 60 segundos descarga los feeds **ICS** (iCal) configurados en `ICS_URLS` (`.env`), uno por cada cuenta de Google Calendar a vigilar (hoy: `alexandertorresviveros@gmail.com` y `kaironotes@gmail.com`). Busca eventos que empiecen en los próximos 3 minutos y que tengan un link de Google Meet (`MEET_RE` regex). Si lo encuentra y no se ha unido antes (set `ya_unidos` en memoria), lanza `runner.py <url> --titulo "<titulo>"` como proceso independiente.

2. **`runner.py`** — orquesta una reunión completa:
   - Crea un *sink* de audio aislado por reunión (`SessionAudio.prepare()`), para que reuniones simultáneas no se mezclen.
   - Copia el perfil de Chrome maestro (`PROFILE_MASTER`, logueado una sola vez por VNC con la cuenta `kaironotes@gmail.com`) a una carpeta temporal aislada.
   - Llama a **`meet.py::unirse()`**, que abre Meet con Playwright, espera admisión (hasta 10 min), y cuando entra, dispara `audio.start_recording()`.
   - Una vez termina la reunión (ver heurísticas abajo), transcribe cada segmento de audio, junta todo en un solo texto, genera el acta, y envía el correo.

3. **`meet.py::unirse()`** — bucle cada 15s que revisa si debe salir:
   - Tope duro: `MAX_MINUTOS` (120 min).
   - **Detección de "reunión terminada"**: si la URL ya no contiene el `room_code`, o el texto de la página contiene frases como "Has salido", "La llamada finalizó".
   - **Detección de "bot solo en la sala"**: busca frases como "Eres el único", "solo en esta llamada" en el texto de la página, 8 veces consecutivas (~2 min). **Punto débil conocido**: estas frases son frágiles porque Google cambia el texto de su interfaz; si dejan de coincidir, el bot se queda conectado hasta el tope de 120 min. Si esto vuelve a pasar, hay que actualizar las frases en `meet.py` mirando la pantalla real vía VNC.

4. **`audio.py::SessionAudio`** — desde el cambio reciente, grava con el *segment muxer* de ffmpeg:
   ```
   ffmpeg -f pulse -i <sink>.monitor -ac 1 -ar 16000 \
       -f segment -segment_time 1800 -reset_timestamps 1 \
       <base>_part%03d.wav
   ```
   Esto parte la grabación en archivos de **30 minutos** sin cortar audio ni perder continuidad — solo rota de archivo. Así, reuniones muy largas no generan un solo `.wav` gigante y se pueden transcribir/resumir por partes sin chocar contra límites de tamaño o de tasa de la API.

5. **`transcribe.py::transcribir()`** — por cada segmento de 30 min, lo vuelve a partir internamente en trozos de 8 minutos (límite de tamaño de Groq), los manda a **Groq Whisper** (`whisper-large-v3-turbo`) y concatena el texto. `runner.py` luego junta los textos de todos los segmentos de 30 min en un solo archivo `<base>.transcripcion.txt`, con marcadores `--- Minuto X-Y (segmento N/M) ---`.

6. **Generación del acta — dos motores disponibles**:
   - **`actas.py`** (Groq Llama, gratis): si la transcripción es larga (> 5.500 caracteres), la resume por fragmentos de ~5.500 caracteres (con pausa de 12s entre llamadas para no chocar con el límite de tokens-por-minuto de la cuenta gratuita de Groq) y luego compila el acta final con la misma plantilla.
   - **`actas_llm.py`** (suscripción OpenAI vía OpenClaw, mejor calidad): el mismo principio map-reduce, pero con fragmentos de 15.000 caracteres y pidiendo **detalle completo** (no resumir en exceso) en cada fragmento, para que el acta final sea extensa y no pierda contenido del cierre de la reunión. Es el motor recomendado para reuniones importantes; hay que invocarlo manualmente o cambiar `runner.py` para que lo use por defecto.

   **Importante técnico**: `actas_llm.py` invoca `openclaw` vía `sudo -H -u root` porque la sesión de OpenAI vive en `/root/.openclaw`, pero `runner.py` corre como usuario `kairo`. Esto requiere la regla de sudoers:
   ```
   # /etc/sudoers.d/kairo-meet-openclaw
   kairo ALL=(root) NOPASSWD: /usr/local/bin/openclaw agent --agent main *
   ```

7. **`notifier.py::enviar_acta()`** — envía un correo HTML (con plantilla de marca Kairo: header azul oscuro, tablas con estilo, sin volcar la transcripción completa en el cuerpo — esa va como adjunto). El destinatario se decide así:
   - Si el organizador del evento (`ORGANIZER` del ICS) es una de las cuentas institucionales propias (`alexander.torres@clinicadeoccidente.com`, `alexander.torres@correounivalle.edu.co`), el correo va **a esa cuenta**, con copia a la personal.
   - Si el organizador es cualquier otra persona (tercero, no controlado por el dueño del bot), el correo va **solo a la cuenta personal** — nunca se le manda nada no solicitado a un tercero.

### Variables de entorno clave (`/opt/kairomeet/.env`)

```
GROQ_API_KEY=...
GROQ_STT_MODEL=whisper-large-v3-turbo
GROQ_LLM_MODEL=llama-3.3-70b-versatile
IDIOMA=es
BOT_NOMBRE=Kairo (Notas)
MAX_MINUTOS=120
EMAIL_FROM=...
EMAIL_TO=...
EMAIL_APP_PASSWORD=...      # contraseña de aplicación de Gmail
ICS_URLS=<feed1>,<feed2>    # uno por cada calendario a vigilar
```

## 3. Invitación automática a reuniones (cron de Calendar)

Aparte del bot que se **une** a reuniones, hay un proceso independiente que **invita** a `kaironotes@gmail.com` como asistente a los eventos virtuales de varios calendarios — así, cuando Meet ve que el bot ya está invitado formalmente al evento, lo admite sin pedir aprobación manual.

- **Script**: `/opt/kairomeet/invitar_kairo.py`, corre por `crontab` del usuario `kairo` a las **6:00 y 12:00** todos los días.
- Usa la **API de Google Calendar** (no ICS) con OAuth, scope `calendar.events`, para 4 cuentas: personal, alterna, Clínica de Occidente y Univalle.
- Por cada cuenta, lista eventos de las próximas 14h, detecta si son virtuales (`hangoutLink`, `conferenceData` o regex de Meet en texto), y si lo son y Kairo no está ya invitado, lo agrega vía `events().patch(..., sendUpdates="none")` (sin notificar a los demás asistentes).
- Tokens guardados en `/opt/kairomeet/credentials/google_calendar_token_<cuenta>.json` (permisos `600`, solo legibles por `kairo`).

### Cómo se obtuvieron esos tokens (para repetir el proceso si vencen)

1. Cliente OAuth de tipo **"Aplicación web"** en Google Cloud Console (proyecto `registromovimientos-499002`), con URI de redirección `https://meet.koqoi.com/oauth/calendar/callback`. (El cliente de tipo "Escritorio" que ya existía no sirve porque no permite URIs de redirección personalizadas.)
2. Endpoint en `meet.koqoi.com`: `GoogleOAuthController::callback()` (ruta pública `oauth/calendar/callback`) recibe el `code`, lo cambia por tokens, y los guarda en `storage/app/private/google_calendar_token_<state>.json`.
3. Por cada cuenta a autorizar, se genera una URL de autorización con `login_hint=<correo>` y `state=<etiqueta>` distintos, se abre en el navegador, se inicia sesión con esa cuenta y se aprueba el permiso de Calendar.
4. Los archivos resultantes se copian manualmente a `/opt/kairomeet/credentials/` con `chown kairo:kairo` y `chmod 600`.

## 4. La app `meet.koqoi.com`

Laravel 12 + Livewire 3 (Volt para auth), Tailwind. Tema visual oscuro/azul ("Kairo theme", variables CSS en `resources/css/app.css`).

### Modelo de datos (`kairo_meet` DB)

- **`meetings`**: `base_path` (único, nombre de archivo sin extensión), `titulo`, `fecha_inicio`, `fecha_fin`, `duracion_segundos`, `num_segmentos`, `transcripcion_path`, `acta_path`, `estado` (`pendiente`/`transcrita`/`con_acta`).
- **`meeting_participants`**: preparado para diarización de voz (quién habló cuánto), aún no implementado — es la "próxima fase" mencionada en la UI.
- **`users`**: vive en la base de **pqr** (`kairo_pqrs`), no en `kairo_meet`. La conexión secundaria `pqr` en `config/database.php` apunta ahí. El `User` model de esta app usa esa conexión.

### `php artisan app:import-meetings`

Comando que escanea `/opt/kairomeet/salidas/*.wav`, agrupa por "base" (quitando el sufijo `_partNNN`), y crea/actualiza un registro `Meeting` por cada uno:
- Duración: suma la duración de todos los `.wav` del grupo vía `ffprobe`.
- Estado: según qué archivos existan (`.transcripcion.txt`, `.acta-llm.md` o `.acta.md`).
- **Título real**: se extrae con regex de la línea `**Título/Tema:** ...` dentro del acta generada (Groq o LLM) — el título nunca se guarda como metadato aparte, vive dentro del Markdown del acta.

Hay que correrlo manualmente o por cron después de cada reunión para que aparezca en la web (no hay watcher automático sobre la carpeta todavía).

### Permisos de archivos (importante)

`/opt/kairomeet/salidas` es propiedad de `kairo:kairo`. La app web corre como `www-data`. Para que el botón "Eliminar" funcione (borra el registro Y los archivos), `www-data` debe estar en el grupo `kairo` y el directorio debe tener permiso de escritura de grupo:
```
usermod -aG kairo www-data
chmod 775 /opt/kairomeet/salidas
systemctl restart kairo-meet-fpm.service   # para que tome el nuevo grupo
```
Los archivos sensibles de `kairo` (`.env`, tokens OAuth) son `600` (solo el propio dueño), así que esto no expone nada.

### Páginas principales

- **`/reuniones`** (`Listado.php`): listado con filtros (nombre con búsqueda en vivo, rango de fechas, duración, estado) colapsados detrás de un botón "Filtros", paginado a 10 por página (`WithPagination`).
- **`/reuniones/{meeting}`** (`Detalle.php`): pestañas Acta / Transcripción / Participantes, reproductor de audio por segmento, botón "Opciones" con "Eliminar" (solo visible para usuarios `master`, con `wire:confirm` de confirmación).
- Acta renderizada de Markdown a HTML vía `league/commonmark` (GFM, soporta tablas).

## 5. Inicio de sesión único entre `pqr` y `meet` (SSO)

Las dos apps comparten usuarios (tabla `users` en `kairo_pqrs`), pero tienen **sesiones de cookie independientes** por estar en subdominios distintos. Para pasar de una a otra sin volver a loguearse, se implementó un token de un solo uso:

1. **Tabla compartida** `sso_tokens` (vive en `kairo_pqrs`, la "owner" es `pqr`; `meet` tiene grant `SELECT, INSERT, UPDATE` solo sobre esa tabla puntual — no acceso general a la base de `pqr`).
   ```sql
   CREATE TABLE sso_tokens (
     token VARCHAR(64) PRIMARY KEY,
     user_id BIGINT,
     destino VARCHAR(50),
     expira_en TIMESTAMP,
     usado_en TIMESTAMP NULL,
     created_at, updated_at
   );
   ```
2. **`SsoController::ir()`** (en cada app, requiere `auth`): genera un token aleatorio de 64 caracteres con `expira_en = now() + 60s`, y redirige a `https://<otra-app>/sso/{token}`.
3. **`SsoController::consumir($token)`** (ruta pública, sin `auth`): valida que el token exista, no esté usado y no haya expirado; lo marca usado; hace `Auth::loginUsingId($registro->user_id)`; redirige a la home de esa app.
4. Botones "Ir a Meet" / "Ir a PQR" en la barra de navegación de cada app (sin clases que los oculten en pantallas pequeñas).

**Importante**: las dos apps deben tener la **misma zona horaria** (`APP_TIMEZONE=America/Bogota`) — si no, el cálculo de "expirado" se desincroniza y el token siempre parece vencido. En Laravel 12 nuevo, `config/app.php` trae `'timezone' => 'UTC'` **hardcodeado** (no usa `env()`); hay que cambiarlo a `env('APP_TIMEZONE', 'America/Bogota')` explícitamente.

## 6. Infraestructura del VPS

- **Nginx**: un server block por subdominio, cada uno con su propio `fastcgi_pass` a un socket PHP-FPM dedicado. `client_max_body_size` debe subirse (25M) si se van a pegar textos largos en formularios Livewire — el default de nginx es 1M.
- **PHP-FPM**: pools dedicados por app (`kairo-pqr-fpm.service`, `kairo-meet-fpm.service`), cada uno corriendo como `www-data` pero con su propio socket y `php_admin_value` (memory_limit, post_max_size, etc. — subir si se procesan textos largos).
- **MySQL**: una base por app (`kairo_pqrs`, `kairo_meet`), un usuario por base con permisos mínimos, más permisos puntuales cruzados cuando hace falta compartir una tabla específica (caso `sso_tokens`).
- **OpenClaw**: corre como gateway en `ws://127.0.0.1:18789` (servicio en background, no systemd — hay que asegurarse de que siga corriendo tras un reinicio del VPS). Las apps lo invocan vía `sudo -u root openclaw agent --agent main --session-key <key> --message <texto> --json`, con reglas de sudoers restringidas por app.
  - **Límite importante del sistema operativo**: un solo argumento de línea de comandos en Linux no puede superar ~131.072 bytes (`MAX_ARG_STRLEN`), sin importar que el límite total (`ARG_MAX`) sea mayor. Si el mensaje a mandar (prompt + contenido) supera ese tamaño, hay que **fragmentarlo** en varias llamadas dentro de la misma sesión (`--session-key` igual), donde los fragmentos intermedios solo se confirman y el último dispara el procesamiento real. Este patrón se implementó tanto en `KairoPqrService.php` (pqr) como en `actas_llm.py` (meet).

## 7. Cómo replicar esto desde cero (resumen de pasos)

1. Provisionar el VPS, instalar PHP, MySQL, Nginx, Node, Python, ffmpeg, Playwright + Chromium, Xvfb, PulseAudio.
2. Instalar y autenticar **OpenClaw** con la sesión de OpenAI (login interactivo una sola vez).
3. Crear las bases de datos y usuarios MySQL (uno por app, mínimo privilegio).
4. Clonar/desplegar las dos apps Laravel (`pqr`, `meet`), cada una con su `.env`, su pool PHP-FPM y su server block de Nginx.
5. Migrar bases de datos; crear la tabla `sso_tokens` y otorgar los grants cruzados.
6. Implementar los controladores/rutas de SSO en ambas apps (idénticos, solo intercambiando los dominios).
7. Configurar `/opt/kairomeet`: `.env` con credenciales de Groq y Gmail, perfil de Chrome logueado por VNC, servicios systemd (Xvfb, PulseAudio, watch).
8. Configurar OAuth de Google Calendar (cliente web, callback, autorización por cuenta) y el cron de invitación automática.
9. Ajustar permisos cruzados de archivos (grupo `kairo` + `www-data`) para que la web pueda borrar reuniones.
10. Probar el flujo completo con una reunión real antes de confiar en el sistema para producción.
