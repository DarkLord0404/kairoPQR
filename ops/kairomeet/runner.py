"""Orquesta UNA reunión: prepara audio, une el bot, graba, transcribe, genera acta y notifica."""
import argparse
import atexit
import datetime as dt
import json
import os
import shutil
import sys
import uuid
import time
from pathlib import Path

import config
from audio import SessionAudio
from meet import unirse
import session_state


def main() -> int:
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

    events_path = session_dir / "capture-events.jsonl"
    capture_gaps: list[dict] = []
    alerted: set[str] = set()

    def audio_event(kind: str, data: dict) -> None:
        event = {"type": kind, "at": dt.datetime.now(dt.timezone.utc).isoformat(), **data}
        with events_path.open("a", encoding="utf-8") as handle:
            handle.write(json.dumps(event, ensure_ascii=False) + "\n")
        if kind in {"silence_detected", "recording_stalled", "recorder_stopped"}:
            capture_gaps.append(event)
            alerted.add(kind)
        session_state.write(sid, audio_status=kind, capture_alerts=len(capture_gaps))

    audio = SessionAudio(sid, wav_path, on_event=audio_event)
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

    def latido(segundo: int) -> None:
        health = audio.health()
        session_state.write(
            sid, estado="grabando", heartbeat_second=segundo,
            audio_bytes=health["bytes"], audio_alive=health["alive"],
            recorder_restarted=health["restarted"],
        )

    entro = False
    muestras_hablante = []
    try:
        entro, muestras_hablante = unirse(
            url=args.url,
            perfil_dir=perfil,
            bot_nombre=config.BOT_NOMBRE,
            max_minutos=config.MAX_MINUTOS,
            al_estar_dentro=iniciar_grabacion,
            al_latido=latido,
            audio_sink=audio.sink,
        )
        session_state.write(sid, estado="guardando grabación")
    finally:
        audio.stop()
        shutil.rmtree(perfil, ignore_errors=True)

    if not entro:
        print("[runner] No se grabó nada (el bot no entró a la reunión).")
        return 2

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
        return 3

    metadata = {
        "session_id": sid,
        "base_path": base_name,
        "titulo": args.titulo,
        "url": args.url,
        "organizador": args.organizador,
        "inicio": ahora.astimezone().isoformat(),
        "estado": "pendiente_groq",
        "segmentos": len(segmentos),
        "fin": dt.datetime.now().astimezone().isoformat(),
        "capture_report": {
            "status": "warning" if capture_gaps else "complete",
            "alerts": capture_gaps,
            "audio_bytes": sum(Path(p).stat().st_size for p in segmentos),
        },
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
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
