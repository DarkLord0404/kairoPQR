"""Automatiza unirse a una reunión de Google Meet con Playwright (Linux/VPS).

Chrome corre sobre Xvfb :99. Cada proceso recibe su propio null-sink de
PulseAudio mediante PULSE_SINK, sin alterar el dispositivo global.
La reunión termina cuando la URL cambia fuera del room code.
"""
import os
import time

from playwright.sync_api import sync_playwright

import config

TEXTOS_UNIRSE = ["Unirse ahora", "Join now", "Participar ahora"]
TEXTOS_SOLICITAR = ["Solicitar unirse", "Ask to join", "Pedir unirse"]
TEXTOS_SALIR = ["Salir de la llamada", "Leave call", "Abandonar la llamada"]


def _click_texto(page, textos, timeout=5000) -> bool:
    for t in textos:
        try:
            btn = page.get_by_role("button", name=t, exact=False)
            if btn.count() > 0:
                btn.first.click(timeout=timeout)
                return True
        except Exception:
            continue
    return False


def _apagar_mic_cam(page) -> None:
    for combo in ("Control+e", "Control+d"):
        try:
            page.keyboard.press(combo)
            time.sleep(0.4)
        except Exception:
            pass
    for label in ["Desactivar micrófono", "Turn off microphone",
                  "Desactivar cámara", "Turn off camera"]:
        try:
            el = page.get_by_role("button", name=label, exact=False)
            if el.count() > 0:
                el.first.click(timeout=2000)
        except Exception:
            pass


def _volcar_aria_labels_debug(page) -> None:
    """SOLO LECTURA, se ejecuta UNA sola vez por reunion (la primera vez que
    hay 2+ minutos de duracion, para dar tiempo a que alguien este hablando).
    Guarda todos los aria-label visibles en ese momento en un archivo de
    diagnostico, para poder calibrar el patron real de Meet sin adivinar.
    Nunca debe afectar la grabacion: cualquier fallo se ignora."""
    import datetime as _dt
    try:
        etiquetas = page.eval_on_selector_all(
            "[aria-label]",
            "els => els.map(e => e.getAttribute('aria-label')).filter(Boolean)",
            timeout=3000,
        )
        etiquetas = sorted(set(etiquetas))
        ts = _dt.datetime.now().strftime("%Y%m%d_%H%M%S")
        ruta = f"/opt/kairomeet/salidas/_debug_aria_labels_{ts}.txt"
        with open(ruta, "w", encoding="utf-8") as f:
            f.write("\n".join(etiquetas))
        print(f"[meet] Diagnostico de aria-label guardado -> {ruta} ({len(etiquetas)} etiquetas)")
    except Exception as e:
        print(f"[meet] No se pudo volcar diagnostico de aria-label (no afecta nada): {e}")


def _muestrear_hablante(page) -> str | None:
    """Best-effort, SOLO LECTURA: intenta detectar quien esta hablando en este
    instante a partir del aria-label que Google Meet pone en el tile activo.
    Nunca hace click ni modifica nada de la pagina. Si no logra determinarlo
    (cambio de interfaz, idioma distinto, etc.) devuelve None sin lanzar error;
    esto es secundario y NUNCA debe afectar la grabacion ni la union/salida."""
    patrones = [" is speaking", " está hablando", " esta hablando"]
    for patron in patrones:
        try:
            els = page.locator(f'[aria-label*="{patron}"]')
            total = els.count()
            for i in range(min(total, 3)):
                aria = els.nth(i).get_attribute("aria-label", timeout=1000) or ""
                nombre = aria.split(patron)[0].strip()
                if nombre:
                    return nombre
        except Exception:
            continue
    return None


def _reunion_terminada(page, room_code: str) -> bool:
    """La reunión terminó si la URL ya no contiene el room code."""
    try:
        url = page.url
        if room_code not in url:
            return True
        # También chequear texto como respaldo
        body = page.inner_text("body", timeout=2000)
        return any(f in body for f in [
            "Has salido", "You've left", "La llamada finalizó",
            "Te quitaron", "Removed from", "La reunión terminó",
            "Volver a la pantalla", "Return to home",
            "fue rechazada", "was rejected", "No puedes unirte",
            "Can't join", "No se puede unir",
        ])
    except Exception:
        return False


def unirse(url: str, perfil_dir: str, bot_nombre: str,
           max_minutos: int,
           al_estar_dentro=None,
           audio_sink: str | None = None) -> tuple[bool, list[dict]]:
    """Se une a `url`. Llama `al_estar_dentro()` al confirmar entrada.
    Devuelve (entro, muestras_hablante). `muestras_hablante` es una lista de
    {"segundo": int, "nombre": str} tomadas cada ~15s mientras dura la
    reunion (puede quedar vacia si nunca se detecto nada; eso no es un error)."""
    room_code = url.rstrip("/").split("/")[-1]
    env = os.environ.copy()
    env["DISPLAY"] = config.DISPLAY
    env["XDG_RUNTIME_DIR"] = config.XDG_RUNTIME_DIR
    if audio_sink:
        # PulseAudio resuelve el destino por proceso. Así dos Chrome simultáneos
        # nunca dependen del dispositivo predeterminado global.
        env["PULSE_SINK"] = audio_sink

    entro = False
    muestras: list[dict] = []
    with sync_playwright() as pw:
        ctx = pw.chromium.launch_persistent_context(
            perfil_dir,
            channel="chrome",
            headless=False,
            env=env,
            args=[
                "--use-fake-ui-for-media-stream",
                "--disable-blink-features=AutomationControlled",
                "--disable-features=Translate",
                "--no-sandbox",
                "--disable-gpu",
                "--start-maximized",
            ],
            viewport=None,
            permissions=["microphone", "camera"],
        )
        page = ctx.pages[0] if ctx.pages else ctx.new_page()
        try:
            print(f"[meet] Abriendo {url}")
            page.goto(url, wait_until="load", timeout=60000)
            time.sleep(5)

            # Cerrar diálogos iniciales
            for t in ["Entendido", "Got it", "Continuar sin micrófono",
                      "Continue without microphone", "Continuar"]:
                _click_texto(page, [t], timeout=2000)

            # Nombre si pide (invitado sin login)
            try:
                for ph in ["Tu nombre", "Your name"]:
                    campo = page.get_by_placeholder(ph)
                    if campo.count() > 0:
                        campo.first.fill(bot_nombre)
                        break
            except Exception:
                pass

            _apagar_mic_cam(page)
            time.sleep(1)

            if not _click_texto(page, TEXTOS_UNIRSE, timeout=6000):
                _click_texto(page, TEXTOS_SOLICITAR, timeout=6000)
            print("[meet] Solicitud enviada. Esperando admisión...")

            # Esperar admisión: "Salir de la llamada" aparece TANTO en sala de
            # espera como dentro — para distinguirlos verificamos que el texto
            # de sala de espera NO esté presente.
            TEXTOS_SALA_ESPERA = [
                "Pidiendo que te admitan", "Asking to be let in",
                "Pronto te admitirán", "You'll be admitted",
            ]
            dentro = False
            for _ in range(120):  # hasta ~10 min
                if room_code not in page.url:
                    print("[meet] Reunión cerrada antes de admitir.")
                    break
                has_leave = any(
                    page.get_by_role("button", name=t, exact=False).count() > 0
                    for t in TEXTOS_SALIR
                )
                if has_leave:
                    try:
                        body = page.inner_text("body", timeout=1500)
                        en_sala = any(t in body for t in TEXTOS_SALA_ESPERA)
                    except Exception:
                        en_sala = False
                    if not en_sala:
                        dentro = True
                        break
                time.sleep(5)

            if not dentro:
                print("[meet] No se confirmó la entrada.")
                return False, muestras

            entro = True
            print("[meet] Dentro de la reunión. Iniciando grabación.")
            if al_estar_dentro:
                al_estar_dentro()

            # Cerrar popup de Google Translate si aparece
            for label in ["Cerrar", "Close"]:
                try:
                    page.get_by_role("button", name=label, exact=True).click(timeout=2000)
                except Exception:
                    pass

            inicio = time.time()
            solo_count = 0
            debug_volcado = False
            while True:
                time.sleep(15)
                elapsed = (time.time() - inicio) / 60

                if not debug_volcado and elapsed >= 2:
                    _volcar_aria_labels_debug(page)
                    debug_volcado = True

                if elapsed >= max_minutos:
                    print(f"[meet] Tope de {max_minutos} min alcanzado.")
                    break
                if _reunion_terminada(page, room_code):
                    print("[meet] Reunión terminada (detectado por URL/texto).")
                    break
                # Heurística: bot solo varios minutos -> salir
                try:
                    body = page.inner_text("body", timeout=2000)
                    if any(s in body for s in ["Eres el único", "You're the only one",
                                               "solo en esta llamada"]):
                        solo_count += 1
                    else:
                        solo_count = 0
                    if solo_count >= 8:  # ~2 min solo
                        print("[meet] Bot quedó solo. Saliendo.")
                        break
                except Exception:
                    pass

                # Muestreo de quien esta hablando (best-effort, no bloqueante).
                # Cualquier fallo aqui se ignora por completo: esto es
                # secundario y nunca debe afectar la grabacion en curso.
                try:
                    nombre = _muestrear_hablante(page)
                    if nombre:
                        muestras.append({
                            "segundo": int(time.time() - inicio),
                            "nombre": nombre,
                        })
                except Exception:
                    pass

            _click_texto(page, TEXTOS_SALIR, timeout=5000)
            time.sleep(2)
        finally:
            ctx.close()
    return entro, muestras
