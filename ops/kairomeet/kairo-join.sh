#!/bin/bash
set -euo pipefail

# Llamado por PHP (www-data) vía sudo -u kairo
URL="$1"
TITULO="${2:-Manual}"
export DISPLAY=:99
export XDG_RUNTIME_DIR=/run/kairo

LOG_DIR=/opt/kairomeet/logs
mkdir -p "$LOG_DIR"
LOG_FILE="$LOG_DIR/manual-$(date +%Y%m%d-%H%M%S)-$$.log"

nohup /opt/kairomeet/venv/bin/python -u /opt/kairomeet/runner.py \
    "$URL" --titulo "$TITULO" >> "$LOG_FILE" 2>&1 &

echo "$!|$LOG_FILE"
