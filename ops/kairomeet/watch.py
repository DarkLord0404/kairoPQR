"""Vigila Google Calendar (ICS) y lanza runner.py cuando empieza una reunión con Meet.

Instalar como servicio:  systemctl enable --now kairo-watch.service
O correr manual:          venv/bin/python watch.py
"""
import datetime as dt
import json
import re
import smtplib
import ssl
import subprocess
import sys
import time
from pathlib import Path

import requests
from icalendar import Calendar

import config

MEET_RE       = re.compile(r"https://meet\.google\.com/[a-z0-9\-]+", re.I)
INTERVALO     = 60   # revisar el calendario cada 60 segundos
VENTANA_MIN   = 3    # unirse si la reunión empieza en los próximos N minutos
VENTANA_ATRAS = 30   # unirse si empezó hace hasta N minutos (invitaciones tardías)

# Cache escrito por invitar_kairo.py con URLs de Meet confirmadas via API.
# Cubre reuniones donde el ICS recurrente omite el conferenceData.
CACHE_PATH = Path(config.SALIDAS).parent / "upcoming_meetings.json"

# URLs de reuniones ya despachadas en esta sesión (evita unirse dos veces)
ya_unidos: set[str] = set()
# Rastrea estado de cada ICS: True = OK, False = en error (para no repetir alertas)
estado_calendarios: dict[str, bool] = {}

VENV_PY = str(Path(config.SALIDAS).parent / "venv" / "bin" / "python")
RUNNER  = str(Path(config.SALIDAS).parent / "runner.py")


def _enviar_alerta(asunto: str, cuerpo: str) -> None:
    try:
        msg_bytes = (
            f"From: {config.EMAIL_FROM}\r\n"
            f"To: {config.EMAIL_TO}\r\n"
            f"Subject: {asunto}\r\n"
            f"Content-Type: text/plain; charset=utf-8\r\n\r\n"
            f"{cuerpo}"
        ).encode("utf-8")
        ctx = ssl.create_default_context()
        with smtplib.SMTP_SSL("smtp.gmail.com", 465, context=ctx) as s:
            s.login(config.EMAIL_FROM, config.EMAIL_APP_PASSWORD)
            s.sendmail(config.EMAIL_FROM, [config.EMAIL_TO], msg_bytes)
        print(f"[watch] Alerta enviada: {asunto}")
    except Exception as e:
        print(f"[watch] No se pudo enviar alerta por correo: {e}")


def _nombre_calendar(url: str) -> str:
    if "alexandertorresviveros" in url:
        return "Gmail personal (alexandertorresviveros)"
    if "kaironotes" in url:
        return "Kairo/Clínica (kaironotes)"
    return url[:60]


def eventos_proximos():
    """Genera (titulo, url) para reuniones en ventana de tiempo leídas del ICS."""
    ahora = dt.datetime.now(dt.timezone.utc)
    for ics_url in config.ICS_URLS:
        nombre = _nombre_calendar(ics_url)
        try:
            cal = Calendar.from_ical(requests.get(ics_url, timeout=30).content)

            if estado_calendarios.get(ics_url) is False:
                print(f"[watch] Calendario recuperado: {nombre}")
                _enviar_alerta(
                    "✅ Kairo: calendario recuperado",
                    f"El calendario '{nombre}' volvió a estar disponible.\n\n"
                    f"Kairo retoma la vigilancia normal de tus reuniones.",
                )
            estado_calendarios[ics_url] = True

        except Exception as e:
            if estado_calendarios.get(ics_url) is not False:
                print(f"[watch] ERROR leyendo {nombre}: {e}")
                _enviar_alerta(
                    f"⚠️ Kairo: no puedo leer el calendario '{nombre}'",
                    f"Kairo no pudo acceder al calendario '{nombre}'.\n\n"
                    f"Error: {e}\n\n"
                    f"Mientras dure el problema, Kairo NO se unirá automáticamente "
                    f"a las reuniones de ese calendario. Puedes invitar manualmente "
                    f"a kaironotes@gmail.com a tus reuniones como alternativa.\n\n"
                    f"Kairo seguirá reintentando cada {INTERVALO} segundos y te avisará "
                    f"cuando el acceso se restablezca.",
                )
            estado_calendarios[ics_url] = False
            continue

        for ev in cal.walk("VEVENT"):
            dtstart = ev.get("DTSTART")
            if not dtstart:
                continue
            inicio = dtstart.dt
            if isinstance(inicio, dt.date) and not isinstance(inicio, dt.datetime):
                continue
            if inicio.tzinfo is None:
                inicio = inicio.replace(tzinfo=dt.timezone.utc)
            mins = (inicio - ahora).total_seconds() / 60
            if not (-VENTANA_ATRAS <= mins <= VENTANA_MIN):
                continue
            texto = " ".join(str(ev.get(c, "")) for c in
                             ("SUMMARY", "LOCATION", "DESCRIPTION", "X-GOOGLE-CONFERENCE"))
            m = MEET_RE.search(texto)
            if m:
                yield str(ev.get("SUMMARY", "Reunión")), m.group(0)


def eventos_de_cache():
    """Genera (titulo, url) desde el cache de invitar_kairo.py.
    Cubre reuniones donde el ICS recurrente omite el link de Meet."""
    if not CACHE_PATH.exists():
        return
    try:
        meetings = json.loads(CACHE_PATH.read_text(encoding="utf-8"))
    except Exception:
        return
    ahora = dt.datetime.now(dt.timezone.utc)
    for m in meetings:
        try:
            raw = m.get("inicio_utc", "")
            if not raw:
                continue
            if raw.endswith("Z"):
                raw = raw[:-1] + "+00:00"
            inicio = dt.datetime.fromisoformat(raw)
            if inicio.tzinfo is None:
                inicio = inicio.replace(tzinfo=dt.timezone(dt.timedelta(hours=-5)))
            mins = (inicio - ahora).total_seconds() / 60
            if -VENTANA_ATRAS <= mins <= VENTANA_MIN:
                yield m["titulo"], m["url"]
        except Exception:
            continue


def _lanzar(titulo: str, url: str, fuente: str = "ICS") -> None:
    ya_unidos.add(url)
    print(f"[watch] Reunión detectada ({fuente}): '{titulo}' -> {url}")
    subprocess.Popen(
        [VENV_PY, RUNNER, url, "--titulo", titulo],
        env={**__import__("os").environ,
             "DISPLAY": config.DISPLAY,
             "XDG_RUNTIME_DIR": config.XDG_RUNTIME_DIR},
        cwd=str(Path(config.SALIDAS).parent),
    )


def main() -> None:
    if not config.ICS_URLS:
        sys.exit("Falta ICS_URLS en /opt/kairomeet/.env — agrégala y reinicia.")

    print("[watch] Vigilando el calendario. Ctrl+C para detener.")
    print(f"[watch] Ventana: -{VENTANA_ATRAS} / +{VENTANA_MIN} min. Revisando cada {INTERVALO}s.")

    while True:
        try:
            for titulo, url in eventos_proximos():
                if url not in ya_unidos:
                    _lanzar(titulo, url, "ICS")
            for titulo, url in eventos_de_cache():
                if url not in ya_unidos:
                    _lanzar(titulo, url, "cache-API")
        except Exception as e:
            print(f"[watch] Error inesperado en el bucle principal: {e}")
        time.sleep(INTERVALO)


if __name__ == "__main__":
    main()
