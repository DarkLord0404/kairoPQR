import importlib
import json
import sys
import tempfile
import types
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))


class FakeAudio:
    def set_channels(self, _value):
        return self

    def set_frame_rate(self, _value):
        return self

    def __len__(self):
        return 60_000

    def __getitem__(self, _value):
        return self

    def export(self, path, format):
        Path(path).write_bytes(b"audio")


class FakeTranscriptions:
    def create(self, **_kwargs):
        return "texto confirmado por Groq"


class ProcessorTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        config = types.ModuleType("config")
        config.SALIDAS = Path(self.temp.name)
        config.GROQ_API_KEY = "test"
        config.GROQ_STT_MODEL = "whisper-large-v3-turbo"
        config.IDIOMA = "es"
        sys.modules["config"] = config

        groq = types.ModuleType("groq")
        groq.Groq = lambda **_kwargs: types.SimpleNamespace(
            audio=types.SimpleNamespace(transcriptions=FakeTranscriptions())
        )
        sys.modules["groq"] = groq

        pydub = types.ModuleType("pydub")
        pydub.AudioSegment = types.SimpleNamespace(from_file=lambda _path: FakeAudio())
        sys.modules["pydub"] = pydub

        actas = types.ModuleType("actas_llm")
        actas.TAMANO_FRAGMENTO = 15000
        actas.PROMPT_FRAGMENTO = "fragmento {n}/{total}: {texto}"
        actas.PROMPT_FINAL = "final {titulo} {fecha}: {notas}"
        actas._dividir = lambda text, size: [text[i:i + size] for i in range(0, len(text), size)]
        actas._llamar_openclaw = lambda prompt: "ACTA" if prompt.startswith("final") else "NOTAS"
        sys.modules["actas_llm"] = actas

        notifier = types.ModuleType("notifier")
        notifier.enviar_acta = lambda **_kwargs: None
        sys.modules["notifier"] = notifier

        sys.modules.pop("processor", None)
        self.processor = importlib.import_module("processor")

    def tearDown(self):
        self.temp.cleanup()

    def test_pipeline_persists_each_stage_and_completes(self):
        job = Path(self.temp.name) / "20260914_120000_deadbeef"
        job.mkdir()
        (job / "audio_part000.wav").write_bytes(b"audio")
        (job / "GRABACION_COMPLETA").touch()
        (job / "session.json").write_text(json.dumps({
            "titulo": "Comité",
            "inicio": "2026-09-14T12:00:00-05:00",
            "organizador": "",
            "estado": "pending_groq",
        }), encoding="utf-8")

        self.processor.process_groq(job)
        self.assertTrue((job / "groq-chunks/audio000-chunk000.txt").exists())
        self.assertEqual("pending_openai", self.processor._state(job)["phase"])

        self.processor.process_openai(job)
        self.assertTrue((job / "openai-chunks/fragment001.md").exists())
        self.assertTrue((job / "acta-llm.md").exists())
        self.assertTrue((job / "COMPLETADA").exists())
        self.assertEqual("completed", self.processor._state(job)["phase"])


if __name__ == "__main__":
    unittest.main()
