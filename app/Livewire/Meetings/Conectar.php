<?php

namespace App\Livewire\Meetings;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Conectar extends Component
{
    public string $url = '';
    public string $titulo = '';
    public string $error = '';

    // Estado actual del bot (null = libre, array = en reunión)
    public ?array $reunionActual = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isMaster(), 403);
        $this->actualizarEstado();
    }

    public function polling(): void
    {
        $this->actualizarEstado();
    }

    private function actualizarEstado(): void
    {
        $raw = shell_exec('/usr/bin/pgrep -u kairo -af "runner.py" 2>/dev/null') ?? '';

        foreach (explode("\n", trim($raw)) as $line) {
            if (!str_contains($line, 'meet.google.com')) {
                continue;
            }
            // Línea ejemplo: "12345 /opt/kairomeet/venv/bin/python /opt/kairomeet/runner.py https://... --titulo Titulo"
            if (preg_match(
                '/^(\d+)\s+\S+\s+\S+runner\.py\s+(https:\/\/meet\.google\.com\/[a-z0-9\-]+)(?:\s+--titulo\s+(.+))?$/i',
                trim($line),
                $m
            )) {
                $this->reunionActual = [
                    'pid'    => $m[1],
                    'url'    => $m[2],
                    'titulo' => trim($m[3] ?? 'Manual'),
                ];
                return;
            }
        }

        $this->reunionActual = null;
    }

    public function conectar(): void
    {
        abort_unless(auth()->user()?->isMaster(), 403);

        $this->error = '';
        $url = trim($this->url);

        if (!preg_match('/^https:\/\/meet\.google\.com\/[a-z0-9][a-z0-9\-]+[a-z0-9]$/i', $url)) {
            $this->error = 'URL inválida. Formato esperado: https://meet.google.com/xxx-xxxx-xxx';
            return;
        }

        $this->actualizarEstado();
        if ($this->reunionActual !== null) {
            $this->error = 'Kairo ya está en la reunión "' . e($this->reunionActual['titulo']) . '". Desconéctalo primero.';
            return;
        }

        $titulo = trim($this->titulo) ?: 'Manual';
        exec('sudo -u kairo /opt/kairomeet/kairo-join.sh ' . escapeshellarg($url) . ' ' . escapeshellarg($titulo) . ' 2>/dev/null');

        $this->url = '';
        $this->titulo = '';
        $this->error = '';

        // Pequeña pausa para que el proceso arranque antes del primer poll
        usleep(800000);
        $this->actualizarEstado();
    }

    public function desconectar(): void
    {
        abort_unless(auth()->user()?->isMaster(), 403);
        exec('sudo -u kairo /opt/kairomeet/kairo-disconnect.sh 2>/dev/null');
        usleep(600000);
        $this->actualizarEstado();
    }

    public function render()
    {
        return view('livewire.meetings.conectar');
    }
}
