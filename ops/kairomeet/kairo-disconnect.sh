#!/bin/bash
set -euo pipefail

# Desconecta una sola reunión. Nunca mata todos los runner.py del servidor.
SID="${1:-}"
if [[ ! "$SID" =~ ^[a-f0-9]{8}$ ]]; then
    echo "Uso: kairo-disconnect.sh SESSION_ID" >&2
    exit 2
fi

STATE="/run/kairo/meet-sessions/${SID}.json"
if [[ ! -f "$STATE" ]]; then
    echo "La sesión no está activa: $SID" >&2
    exit 3
fi

PID=$(/usr/bin/python3 -c 'import json,sys; print(int(json.load(open(sys.argv[1]))["pid"]))' "$STATE")
CMD=$(/usr/bin/ps -o args= -p "$PID" 2>/dev/null || true)
if [[ "$CMD" != *"/opt/kairomeet/runner.py"* ]]; then
    echo "El PID registrado no pertenece a KairoMeet" >&2
    exit 4
fi

/usr/bin/kill -INT "$PID"
echo "Desconexión solicitada para $SID"
