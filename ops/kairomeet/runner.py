"""Orquesta UNA reunión: prepara audio, une el bot, graba, transcribe, genera acta y notifica."""
import argparse
import atexit
import datetime as dt
import json
import os
import shutil
import sys
import uuid
from pathlib import Path

import config
from audio import SessionAudio
from meet import unirse
import session_state


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("url")
    ap.add_argument("--titulo", default="Reunión")
    ap.add_argument("--organizador", default="")
    args = ap.parse_args()

    if not Path(config.PROFILE_MASTER).exists():
        sys.exit(f"Perfil no existe: {config.PROFILE_MASTER}\nCorre vnc_login.sh primero.")

    sid = uuid.uuid4().hex[:8]
    ahora = dt.datetime.now()
    base_name = f"{ahora:%Y%m%d_%H%M%S}_{sid}"
    session_dir = config.SALIDAS / base_name
    session_dir.mkdir(mode=0o750)
    wav_path = str(session_dir / "audio.wav")

    # Copia aislada del perfil
    perfil = f"/tmp/kairo-prof-{sid}"
    shutil.copytree(config.PROFILE_MASTER, perfil)
    for lock in ("SingletonLock", "SingletonCookie", "SingletonSocket"):
        try:
            os.remove(os.path.join(perfil, lock))
        except OSError:
            pass

    audio = SessionAudio(sid, wav_path)
    audio.prepare()
    session_state.write(
        sid, pid=os.getpid(), url=args.url, titulo=args.titulo,
        organizador=args.organizador, sink=audio.sink,
        output_dir=str(session_dir), estado="conectando",
        inicio=ahora.astimezone().isoformat(),
    )
    atexit.register(session_state.remove, sid)

    def iniciar_grabacion() -> None:
        audio.start_recording()
        session_state.write(sid, estado="grabando")

    entro = False
    muestras_hablante = []
    try:
        entro, muestras_hablante = unirse(
            url=args.url,
            perfil_dir=perfil,
            bot_nombre=config.BOT_NOMBRE,
            max_minutos=config.MAX_MINUTOS,
            al_estar_dentro=iniciar_grabacion,
            audio_sink=audio.sink,
        )
        session_state.write(sid, estado="guardando grabación")
    finally:
        audio.stop()
        shutil.rmtree(perfil, ignore_errors=True)

    if not entro:
        print("[runner] No se grabó nada (el bot no entró a la reunión).")
        return

    if args.organizador:
        try:
            (session_dir / "organizador.txt").write_text(args.organizador, encoding="utf-8")
        except Exception as e:
            print(f"[runner] No se pudo guardar el organizador (no afecta lo demas): {e}")

    if muestras_hablante:
        hablantes_path = str(session_dir / "hablantes.json")
        try:
            Path(hablantes_path).write_text(
                json.dumps(muestras_hablante, ensure_ascii=False, indent=2),
                encoding="utf-8",
            )
            print(f"[runner] {len(muestras_hablante)} muestra(s) de hablante guardadas -> {hablantes_path}")
        except Exception as e:
            print(f"[runner] No se pudo guardar muestras de hablante (no afecta lo demas): {e}")

    segmentos = audio.segmentos()
    if not segmentos and Path(wav_path).exists():
        segmentos = [wav_path]  # compatibilidad si por algun motivo no se segmento

    if not segmentos:
        print("[runner] No se encontraron segmentos de audio grabados.")
        return

    metadata = {
        "session_id": sid,
        "base_path": base_name,
        "titulo": args.titulo,
        "url": args.url,
        "organizador": args.organizador,
        "inicio": ahora.astimezone().isoformat(),
        "estado": "pendiente_groq",
        "segmentos": len(segmentos),
    }
    (session_dir / "session.json").write_text(
        json.dumps(metadata, ensure_ascii=False, indent=2), encoding="utf-8"
    )
    queue_state = {
        "phase": "pending_groq",
        "groq_attempts": 0,
        "openai_attempts": 0,
        "updated_at": dt.datetime.now(dt.timezone.utc).isoformat(),
    }
    queue_temp = session_dir / ".queue-state.tmp"
    queue_temp.write_text(
        json.dumps(queue_state, ensure_ascii=False, indent=2), encoding="utf-8"
    )
    os.replace(queue_temp, session_dir / "queue-state.json")
    (session_dir / "GRABACION_COMPLETA").touch()
    print(f"[runner] Grabación en cola para Groq -> {session_dir}")
    session_state.remove(sid)


if __name__ == "__main__":
    main()
