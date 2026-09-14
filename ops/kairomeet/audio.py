"""Audio por sesión en Linux: null-sink aislado por reunión.

Cada Chrome recibe su sink mediante PULSE_SINK. Nunca se modifica el sink
predeterminado global, porque ese estado se comparte entre reuniones.

La grabación se parte en segmentos de SEGMENT_SECONDS (30 min por defecto)
usando el "segment muxer" de ffmpeg: esto no corta ni pierde audio, solo va
rotando de archivo de salida cada N segundos. Asi, reuniones largas no
generan un solo .wav gigante y la transcripcion/acta posterior se puede
procesar por partes sin chocar con limites de tamano/tokens.
"""
import glob
import os
import signal
import subprocess

import config

SEGMENT_SECONDS = 1800  # 30 minutos


def _env() -> dict:
    e = os.environ.copy()
    e["XDG_RUNTIME_DIR"] = config.XDG_RUNTIME_DIR
    return e


def _pactl(*args) -> str:
    return subprocess.check_output(["pactl", *args], env=_env(),
                                   stderr=subprocess.DEVNULL).decode().strip()


class SessionAudio:
    def __init__(self, session_id: str, out_wav: str):
        self.sink = f"meet_{session_id}"
        self.out_wav = out_wav
        self._module_id: str | None = None
        self._ff: subprocess.Popen | None = None
        self._patron_glob: str | None = None

    def prepare(self) -> str:
        """Crea el null-sink aislado. El proceso Chrome debe recibir PULSE_SINK."""
        self._module_id = _pactl(
            "load-module", "module-null-sink",
            f"sink_name={self.sink}",
            f"sink_properties=device.description={self.sink}",
        )
        print(f"[audio] Sink aislado preparado -> {self.sink}")
        return self.sink

    def start_recording(self) -> None:
        """Inicia la grabación del monitor en segmentos de SEGMENT_SECONDS.
        Llamar cuando el bot está dentro."""
        base, ext = os.path.splitext(self.out_wav)
        patron_salida = f"{base}_part%03d{ext}"
        self._patron_glob = f"{base}_part*{ext}"

        self._ff = subprocess.Popen(
            ["ffmpeg", "-y", "-hide_banner", "-loglevel", "error",
             "-f", "pulse", "-i", f"{self.sink}.monitor",
             "-ac", "1", "-ar", "16000",
             "-f", "segment", "-segment_time", str(SEGMENT_SECONDS),
             "-reset_timestamps", "1",
             patron_salida],
            env=_env(),
        )
        print(f"[audio] Grabando {self.sink}.monitor -> {patron_salida} "
              f"(segmentos de {SEGMENT_SECONDS // 60} min)")

    def segmentos(self) -> list[str]:
        """Lista (en orden) los archivos de segmento ya grabados."""
        if not self._patron_glob:
            return []
        return sorted(glob.glob(self._patron_glob))

    def stop(self) -> None:
        """Detiene la grabación y libera el sink."""
        if self._ff:
            self._ff.send_signal(signal.SIGINT)
            try:
                self._ff.wait(timeout=12)
            except subprocess.TimeoutExpired:
                self._ff.kill()
            self._ff = None

        if self._module_id:
            try:
                _pactl("unload-module", self._module_id)
            except Exception:
                pass
            self._module_id = None
        print(f"[audio] Sink {self.sink} liberado.")
