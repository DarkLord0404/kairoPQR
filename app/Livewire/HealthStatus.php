<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class HealthStatus extends Component
{
    public array $grupos = [];
    public ?string $ultimaVerificacion = null;
    public bool $hayFallos = false;
    public bool $hayAdvertencias = false;
    public bool $verificando = false;
    public int $totalVerificados = 0;
    public int $totalEsperados = 0;

    public function mount(): void
    {
        $this->cargar();
    }

    public function polling(): void
    {
        if ($this->verificando) {
            $this->cargar();
            if ($this->totalEsperados > 0 && $this->totalVerificados >= $this->totalEsperados) {
                $this->verificando = false;
            }
        }
    }

    public function cargar(): void
    {
        $rows = DB::table('kairo_health_checks')->orderBy('id')->get();

        // Agrupar
        $agrupados = [];
        foreach ($rows as $r) {
            $agrupados[$r->grupo][] = [
                'servicio'    => $r->servicio,
                'estado'      => $r->estado,
                'mensaje'     => $r->mensaje,
                'duracion_ms' => $r->duracion_ms,
            ];
        }
        $this->grupos = $agrupados;

        $ultima = $rows->last();
        $this->ultimaVerificacion = $ultima
            ? \Carbon\Carbon::parse($ultima->verificado_en)->setTimezone('America/Bogota')->format('d/m/Y H:i')
            : null;

        $this->hayFallos       = $rows->contains(fn ($r) => $r->estado === 'fallo');
        $this->hayAdvertencias = $rows->contains(fn ($r) => $r->estado === 'advertencia');
        $this->totalVerificados = $rows->count();
    }

    public function ejecutarAhora(): void
    {
        abort_unless(auth()->user()?->isMaster(), 403);

        DB::table('kairo_health_checks')->truncate();
        $this->grupos = [];
        $this->hayFallos = false;
        $this->hayAdvertencias = false;
        $this->totalVerificados = 0;
        $this->totalEsperados = count($this->listaServicios());
        $this->verificando = true;

        exec('nohup /usr/bin/php /var/www/app.koqoi.com/artisan app:health-check > /dev/null 2>&1 &');
    }

    private function listaServicios(): array
    {
        return [
            // ── Infraestructura ────────────────────────────────────────────
            ['grupo' => 'Infraestructura', 'nombre' => 'Base de datos MySQL'],
            ['grupo' => 'Infraestructura', 'nombre' => 'Correo saliente (SMTP)'],
            ['grupo' => 'Infraestructura', 'nombre' => 'Espacio en disco grabaciones'],

            // ── Inteligencia Artificial ────────────────────────────────────
            ['grupo' => 'Inteligencia Artificial', 'nombre' => 'Análisis de texto (OpenAI)'],
            ['grupo' => 'Inteligencia Artificial', 'nombre' => 'Transcripción de audio (Groq)'],

            // ── Calendarios Google ─────────────────────────────────────────
            ['grupo' => 'Calendarios Google', 'nombre' => 'Gmail personal (alexandertorresviveros)'],
            ['grupo' => 'Calendarios Google', 'nombre' => 'Gmail secundario (alex870404)'],
            ['grupo' => 'Calendarios Google', 'nombre' => 'Clínica de Occidente'],
            ['grupo' => 'Calendarios Google', 'nombre' => 'Universidad del Valle'],

            // ── KairoMeet ──────────────────────────────────────────────────
            ['grupo' => 'KairoMeet', 'nombre' => 'Archivos del bot (Python)'],
            ['grupo' => 'KairoMeet', 'nombre' => 'Display virtual para Chrome'],
            ['grupo' => 'KairoMeet', 'nombre' => 'Conversor de audio (ffmpeg)'],
        ];
    }

    public function render()
    {
        return view('livewire.health-status');
    }
}
