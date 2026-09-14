"""Registro atómico y no clínico de las reuniones activas de KairoMeet."""
import json
import os
from datetime import datetime, timezone
from pathlib import Path

RUNTIME_DIR = Path("/run/kairo/meet-sessions")


def _path(session_id: str) -> Path:
    return RUNTIME_DIR / f"{session_id}.json"


def write(session_id: str, **fields) -> None:
    RUNTIME_DIR.mkdir(parents=True, exist_ok=True, mode=0o750)
    path = _path(session_id)
    data = {}
    if path.exists():
        try:
            data = json.loads(path.read_text(encoding="utf-8"))
        except Exception:
            data = {}
    data.update(fields)
    data["session_id"] = session_id
    data["updated_at"] = datetime.now(timezone.utc).isoformat()
    temp = path.with_suffix(f".tmp-{os.getpid()}")
    temp.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")
    temp.chmod(0o640)
    os.replace(temp, path)


def remove(session_id: str) -> None:
    try:
        _path(session_id).unlink()
    except FileNotFoundError:
        pass
