import json
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch

import sys

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

import audio
import session_state


class AudioIsolationTest(unittest.TestCase):
    @patch("audio._pactl")
    def test_prepare_never_changes_the_global_default_sink(self, pactl):
        pactl.return_value = "42"
        session = audio.SessionAudio("abc12345", "/tmp/audio.wav")

        self.assertEqual("meet_abc12345", session.prepare())
        self.assertEqual(
            [unittest.mock.call(
                "load-module", "module-null-sink", "sink_name=meet_abc12345",
                "sink_properties=device.description=meet_abc12345",
            )],
            pactl.call_args_list,
        )


class SessionStateTest(unittest.TestCase):
    def test_state_updates_atomically_and_can_be_removed(self):
        with tempfile.TemporaryDirectory() as directory:
            with patch.object(session_state, "RUNTIME_DIR", Path(directory)):
                session_state.write("abc12345", pid=123, estado="conectando")
                session_state.write("abc12345", estado="grabando")
                data = json.loads((Path(directory) / "abc12345.json").read_text())

                self.assertEqual(123, data["pid"])
                self.assertEqual("grabando", data["estado"])
                session_state.remove("abc12345")
                self.assertFalse((Path(directory) / "abc12345.json").exists())


if __name__ == "__main__":
    unittest.main()
