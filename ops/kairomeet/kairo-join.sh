#!/bin/bash
# Llamado por PHP (www-data) vía sudo -u kairo
URL="$1"
TITULO="${2:-Manual}"
export DISPLAY=:99
export XDG_RUNTIME_DIR=/run/kairo
nohup /opt/kairomeet/venv/bin/python /opt/kairomeet/runner.py "$URL" --titulo "$TITULO" > /dev/null 2>&1 &
