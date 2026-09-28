# Kairo — guía de contexto del sistema

Este repositorio contiene la aplicación unificada de Kairo en `app.koqoi.com`:

- análisis de PQR;
- análisis de eventos adversos (EA);
- KairoMeet: captura, transcripción y elaboración de actas de reuniones;
- usuarios, permisos, consumo y configuración administrativa.

Antes de modificar el flujo de reuniones se debe leer
[`ops/kairomeet/README.md`](ops/kairomeet/README.md). Allí están la arquitectura
vigente, las colas, los servicios, las garantías de concurrencia, las rutas de
datos, las pruebas y el procedimiento seguro de despliegue.

## Componentes principales

| Componente | Ubicación en producción | Responsabilidad |
|---|---|---|
| Laravel + Livewire | `/var/www/app.koqoi.com` | Interfaz web, usuarios, permisos e historial |
| KairoMeet Python | `/opt/kairomeet` | Calendario, navegador, captura y colas |
| PostgreSQL | base configurada por Laravel | Datos de la aplicación |
| PulseAudio + Xvfb | servicios `kairo-pulse` y `kairo-xvfb` | Audio y pantalla virtual por reunión |
| Groq | API externa | Transcripción de audio |
| OpenClaw | sesión instalada en el VPS | Compilación de transcripciones y actas |

## Regla crítica de operación

Nunca se deben reiniciar `kairo-pulse`, `kairo-xvfb`, matar procesos
`runner.py`, Chrome o FFmpeg mientras exista una grabación activa. El servicio
`kairo-watch` usa `KillMode=process` para poder actualizar el vigilante sin
interrumpir sus grabadores hijos.

## Verificación rápida

```bash
systemctl is-active kairo-watch kairo-pulse kairo-xvfb \
  kairo-process@groq kairo-process@openai kairo-app-fpm
pgrep -af '/opt/kairomeet/runner.py'
find /run/kairo/meet-sessions -type f -maxdepth 1 -print
```

La documentación administrativa visible en la aplicación está en
`resources/markdown/kairomeet.md` y solo es accesible para el usuario maestro.
