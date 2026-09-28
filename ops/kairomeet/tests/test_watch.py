import importlib
import sys
import tempfile
import types
import unittest
from pathlib import Path
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))


class FakeProcess:
    def __init__(self, code):
        self.code = code

    def poll(self):
        return self.code


class WatchRetriesTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        config = types.ModuleType("config")
        config.SALIDAS = Path(tempfile.gettempdir()) / "kairo-test"
        config.EMAIL_FROM = ""
        config.EMAIL_TO = ""
        config.EMAIL_APP_PASSWORD = ""
        config.ICS_URLS = []
        config.DISPLAY = ":99"
        config.XDG_RUNTIME_DIR = "/run/kairo"
        sys.modules["config"] = config
        icalendar = types.ModuleType("icalendar")
        icalendar.Calendar = object
        sys.modules["icalendar"] = icalendar
        sys.modules.pop("watch", None)
        cls.watch = importlib.import_module("watch")

    def setUp(self):
        self.watch.procesos.clear()
        self.watch.reintentos.clear()
        self.watch.completadas.clear()

    def test_failed_join_is_retried_before_being_marked_complete(self):
        url = "https://meet.google.com/abc-defg-hij"
        self.watch.procesos[url] = FakeProcess(2)
        self.watch._recolectar_procesos()
        self.assertEqual(1, self.watch.reintentos[url])
        self.assertNotIn(url, self.watch.completadas)

    def test_active_url_is_never_launched_twice(self):
        with patch.object(self.watch, "_urls_activas", return_value={"meet"}), \
             patch.object(self.watch.subprocess, "Popen") as popen:
            self.watch._lanzar("Prueba", "meet")
        popen.assert_not_called()


if __name__ == "__main__":
    unittest.main()
