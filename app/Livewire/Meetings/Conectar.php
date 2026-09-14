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

    public array $reunionesActivas = [];

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
        $this->reunionesActivas = [];
        foreach (glob('/run/kairo/meet-sessions/*.json') ?: [] as $path) {
            $data = json_decode((string) @file_get_contents($path), true);
            if (! is_array($data) || ! isset($data['session_id'], $data['pid'], $data['url'])) {
                continue;
            }
            if (! is_dir('/proc/'.(int) $data['pid'])) {
                continue;
            }
            $this->reunionesActivas[] = [
                'session_id' => $data['session_id'],
                'pid' => $data['pid'],
                'url' => $data['url'],
                'titulo' => $data['titulo'] ?? 'Reunión',
                'estado' => $data['estado'] ?? 'activa',
            ];
        }
    }

    public function conectar(): void
    {
        abort_unless(auth()->user()?->isMaster(), 403);

        $this->error = '';
        $url = trim($this->url);

        if (! preg_match('/^https:\/\/meet\.google\.com\/[a-z0-9][a-z0-9\-]+[a-z0-9]$/i', $url)) {
            $this->error = 'URL inválida. Formato esperado: https://meet.google.com/xxx-xxxx-xxx';

            return;
        }

        $this->actualizarEstado();
        if (collect($this->reunionesActivas)->contains('url', $url)) {
            $this->error = 'Kairo ya está conectado a este enlace.';

            return;
        }

        $titulo = trim($this->titulo) ?: 'Manual';
        exec('sudo -u kairo /opt/kairomeet/kairo-join.sh '.escapeshellarg($url).' '.escapeshellarg($titulo).' 2>/dev/null');

        $this->url = '';
        $this->titulo = '';
        $this->error = '';

        // Pequeña pausa para que el proceso arranque antes del primer poll
        usleep(800000);
        $this->actualizarEstado();
    }

    public function desconectar(string $sessionId): void
    {
        abort_unless(auth()->user()?->isMaster(), 403);
        abort_unless((bool) preg_match('/^[a-f0-9]{8}$/', $sessionId), 422);
        exec('sudo -u kairo /opt/kairomeet/kairo-disconnect.sh '.escapeshellarg($sessionId).' 2>/dev/null');
        usleep(600000);
        $this->actualizarEstado();
    }

    public function render()
    {
        return view('livewire.meetings.conectar');
    }
}
