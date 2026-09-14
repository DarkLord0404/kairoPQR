"""Procesa de forma durable las colas Groq -> OpenClaw de KairoMeet."""
import argparse
import json
import os
import time
from datetime import datetime, timezone
from pathlib import Path

from groq import Groq
from pydub import AudioSegment

import actas_llm
import config
from notifier import enviar_acta

POLL_SECONDS = 10
GROQ_CHUNK_MINUTES = 8
MAX_RETRY_SECONDS = 1800


def _atomic_text(path: Path, content: str) -> None:
    temp = path.with_name(f".{path.name}.tmp-{os.getpid()}")
    temp.write_text(content, encoding="utf-8")
    os.replace(temp, path)


def _atomic_json(path: Path, data: dict) -> None:
    _atomic_text(path, json.dumps(data, ensure_ascii=False, indent=2))


def _read_json(path: Path, default: dict | None = None) -> dict:
    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except Exception:
        return dict(default or {})


def _state(job: Path) -> dict:
    return _read_json(job / "queue-state.json", {
        "phase": "pending_groq",
        "groq_attempts": 0,
        "openai_attempts": 0,
    })


def _save_state(job: Path, **fields) -> dict:
    state = _state(job)
    state.update(fields)
    state["updated_at"] = datetime.now(timezone.utc).isoformat()
    _atomic_json(job / "queue-state.json", state)
    metadata_path = job / "session.json"
    metadata = _read_json(metadata_path)
    if metadata:
        metadata["estado"] = state["phase"]
        _atomic_json(metadata_path, metadata)
    return state


def _eligible(state: dict) -> bool:
    retry_at = float(state.get("retry_at", 0) or 0)
    return retry_at <= time.time()


def _fail(job: Path, service: str, error: Exception) -> None:
    key = f"{service}_attempts"
    attempts = int(_state(job).get(key, 0)) + 1
    delay = min(30 * (2 ** min(attempts, 6)), MAX_RETRY_SECONDS)
    _save_state(
        job,
        phase=f"waiting_{service}",
        **{
            key: attempts,
            "retry_at": time.time() + delay,
            "last_error": str(error)[-1000:],
            "last_error_service": service,
        },
    )
    print(f"[processor] {job.name}: {service} no disponible; reintento en {delay}s: {error}")


def _jobs() -> list[Path]:
    return sorted(
        p for p in config.SALIDAS.iterdir()
        if p.is_dir() and (p / "GRABACION_COMPLETA").exists()
        and not (p / "COMPLETADA").exists()
    )


def _audio_files(job: Path) -> list[Path]:
    files = sorted(job.glob("audio_part*.wav"))
    if not files and (job / "audio.wav").exists():
        files = [job / "audio.wav"]
    return files


def _prompt_glosario() -> str:
    path = Path("/opt/kairomeet/glosario.txt")
    if not path.exists():
        return ""
    lines = [line.strip() for line in path.read_text(encoding="utf-8").splitlines()
             if line.strip() and not line.lstrip().startswith("#")]
    return " ".join(lines)[:224]


def process_groq(job: Path) -> None:
    state = _state(job)
    if state.get("phase") not in {"pending_groq", "waiting_groq", "transcribing_groq"}:
        return
    if not _eligible(state):
        return
    if not config.GROQ_API_KEY:
        _fail(job, "groq", RuntimeError("GROQ_API_KEY no configurada"))
        return

    try:
        audio_files = _audio_files(job)
        if not audio_files:
            raise RuntimeError("no hay archivos de audio")
        chunks_dir = job / "groq-chunks"
        chunks_dir.mkdir(exist_ok=True, mode=0o750)
        _save_state(job, phase="transcribing_groq", retry_at=0, last_error=None)
        client = Groq(api_key=config.GROQ_API_KEY)
        prompt = _prompt_glosario()
        pieces: list[str] = []
        absolute_minute = 0

        for audio_index, audio_path in enumerate(audio_files):
            audio = AudioSegment.from_file(audio_path).set_channels(1).set_frame_rate(16000)
            chunk_ms = GROQ_CHUNK_MINUTES * 60 * 1000
            for offset in range(0, len(audio), chunk_ms):
                chunk_number = offset // chunk_ms
                cached = chunks_dir / f"audio{audio_index:03d}-chunk{chunk_number:03d}.txt"
                if cached.exists():
                    text = cached.read_text(encoding="utf-8")
                else:
                    temporary = job / f".groq-{audio_index:03d}-{chunk_number:03d}.flac"
                    audio[offset:offset + chunk_ms].export(temporary, format="flac")
                    try:
                        with temporary.open("rb") as handle:
                            kwargs = {
                                "file": (temporary.name, handle.read()),
                                "model": config.GROQ_STT_MODEL,
                                "language": config.IDIOMA,
                                "response_format": "text",
                            }
                            if prompt:
                                kwargs["prompt"] = prompt
                            response = client.audio.transcriptions.create(**kwargs)
                        text = str(response).strip()
                        _atomic_text(cached, text)
                    finally:
                        temporary.unlink(missing_ok=True)
                end_minute = absolute_minute + min(
                    GROQ_CHUNK_MINUTES,
                    max(1, (len(audio) - offset + 59999) // 60000),
                )
                pieces.append(
                    f"--- Minuto {absolute_minute}-{end_minute} ---\n{text}"
                )
                absolute_minute = end_minute

        transcript = "\n\n".join(pieces)
        _atomic_text(job / "transcripcion.txt", transcript)
        _save_state(
            job,
            phase="pending_openai",
            retry_at=0,
            groq_completed_at=datetime.now(timezone.utc).isoformat(),
            groq_chunks=len(pieces),
            last_error=None,
        )
        print(f"[processor] {job.name}: Groq completado ({len(pieces)} fragmentos)")
    except Exception as error:
        _fail(job, "groq", error)


def process_openai(job: Path) -> None:
    state = _state(job)
    if state.get("phase") not in {"pending_openai", "waiting_openai", "processing_openai"}:
        return
    if not _eligible(state):
        return

    try:
        metadata = _read_json(job / "session.json")
        transcript = (job / "transcripcion.txt").read_text(encoding="utf-8")
        fragments = actas_llm._dividir(transcript, actas_llm.TAMANO_FRAGMENTO)
        notes_dir = job / "openai-chunks"
        notes_dir.mkdir(exist_ok=True, mode=0o750)
        _save_state(job, phase="processing_openai", retry_at=0, last_error=None)
        notes: list[str] = []

        for index, fragment in enumerate(fragments, start=1):
            cached = notes_dir / f"fragment{index:03d}.md"
            if cached.exists():
                summary = cached.read_text(encoding="utf-8")
            else:
                summary = actas_llm._llamar_openclaw(
                    actas_llm.PROMPT_FRAGMENTO.format(
                        n=index, total=len(fragments), texto=fragment,
                    )
                )
                _atomic_text(cached, summary)
            notes.append(f"--- Fragmento {index}/{len(fragments)} ---\n{summary}")

        date = str(metadata.get("inicio", ""))[:16].replace("T", " ")
        final = actas_llm._llamar_openclaw(
            actas_llm.PROMPT_FINAL.format(
                titulo=metadata.get("titulo", "Reunión"),
                fecha=date,
                notas="\n\n".join(notes),
            )
        )
        acta_path = job / "acta-llm.md"
        _atomic_text(acta_path, final)
        metadata["estado"] = "completada"
        _atomic_json(job / "session.json", metadata)
        _save_state(
            job,
            phase="completed",
            retry_at=0,
            openai_completed_at=datetime.now(timezone.utc).isoformat(),
            openai_chunks=len(fragments),
            last_error=None,
        )
        (job / "COMPLETADA").touch()
        print(f"[processor] {job.name}: acta completada")
        try:
            enviar_acta(
                titulo=metadata.get("titulo", "Reunión"),
                fecha=date,
                acta_path=str(acta_path),
                trans_path=str(job / "transcripcion.txt"),
                organizador=metadata.get("organizador", ""),
            )
        except Exception as error:
            _save_state(job, email_error=str(error)[-1000:])
            print(f"[processor] {job.name}: acta lista, correo pendiente: {error}")
    except Exception as error:
        _fail(job, "openai", error)


def run_once(stage: str) -> bool:
    jobs = _jobs()
    phases = {
        "groq": {"pending_groq", "waiting_groq", "transcribing_groq"},
        "openai": {"pending_openai", "waiting_openai", "processing_openai"},
    }[stage]
    job = next((j for j in jobs if _state(j).get("phase") in phases
                and _eligible(_state(j))), None)
    if job:
        (process_groq if stage == "groq" else process_openai)(job)
    return job is not None


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--stage", required=True, choices=("groq", "openai"))
    args = parser.parse_args()
    print(f"[processor] Cola persistente {args.stage} activa")
    while True:
        worked = run_once(args.stage)
        time.sleep(1 if worked else POLL_SECONDS)


if __name__ == "__main__":
    main()
