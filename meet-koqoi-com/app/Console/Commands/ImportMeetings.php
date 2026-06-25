<?php

namespace App\Console\Commands;

use App\Models\Meeting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Process;

#[Signature('app:import-meetings')]
#[Description('Escanea /opt/kairomeet/salidas y crea/actualiza registros de reuniones.')]
class ImportMeetings extends Command
{
    public function handle(): int
    {
        $dir = config('kairomeet.salidas_path');

        if (! is_dir($dir)) {
            $this->error("No existe el directorio: {$dir}");

            return self::FAILURE;
        }

        $bases = $this->descubrirBases($dir);
        $this->info('Bases encontradas: '.count($bases));

        foreach ($bases as $base) {
            $this->importarBase($dir, $base);
        }

        return self::SUCCESS;
    }

    /**
     * Descubre los "base names" unicos (sin extension ni sufijo _partNNN)
     * a partir de los .wav presentes en el directorio.
     *
     * @return string[]
     */
    private function descubrirBases(string $dir): array
    {
        $bases = [];

        foreach (glob($dir.'/*.wav') as $wav) {
            $nombre = basename($wav, '.wav');
            $nombre = preg_replace('/_part\d+$/', '', $nombre);
            $bases[$nombre] = true;
        }

        $bases = array_keys($bases);
        sort($bases);

        return $bases;
    }

    private function importarBase(string $dir, string $base): void
    {
        $basePath = $dir.'/'.$base;

        $segmentos = glob($basePath.'_part*.wav');
        sort($segmentos);
        $esSegmentado = count($segmentos) > 0;
        $wavsParaDuracion = $esSegmentado ? $segmentos : (file_exists($basePath.'.wav') ? [$basePath.'.wav'] : []);

        if (empty($wavsParaDuracion)) {
            $this->warn("Sin audio para {$base}, se omite.");

            return;
        }

        $fechaInicio = $this->fechaDesdeBase($base);
        $duracion = $this->duracionTotal($wavsParaDuracion);

        $transPath = $basePath.'.transcripcion.txt';
        $actaLlmPath = $basePath.'.acta-llm.md';
        $actaPath = $basePath.'.acta.md';

        $tieneTranscripcion = file_exists($transPath);
        $actaFinal = match (true) {
            file_exists($actaLlmPath) => $actaLlmPath,
            file_exists($actaPath) => $actaPath,
            default => null,
        };

        $estado = match (true) {
            $actaFinal !== null => 'con_acta',
            $tieneTranscripcion => 'transcrita',
            default => 'pendiente',
        };

        $titulo = $actaFinal ? $this->tituloDesdeActa($actaFinal) : null;

        $meeting = Meeting::updateOrCreate(
            ['base_path' => $base],
            [
                'titulo' => $titulo ?? 'Reunión',
                'url_meet' => null,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $duracion ? $fechaInicio->copy()->addSeconds((int) $duracion) : null,
                'duracion_segundos' => $duracion,
                'num_segmentos' => $esSegmentado ? count($segmentos) : 1,
                'transcripcion_path' => $tieneTranscripcion ? basename($transPath) : null,
                'acta_path' => $actaFinal ? basename($actaFinal) : null,
                'estado' => $estado,
            ]
        );

        $etiqueta = $meeting->wasRecentlyCreated ? 'nueva' : 'actualizada';
        $this->line("[{$etiqueta}] {$base} ({$estado})");
    }

    /**
     * El acta (Groq o LLM) trae el titulo real en una linea
     * "**Titulo/Tema:** ..." o "**Título/Tema:** ...". Lo extraemos de ahi
     * porque es lo unico que conserva el nombre real de la reunion.
     */
    private function tituloDesdeActa(string $actaPath): ?string
    {
        $contenido = @file_get_contents($actaPath);
        if ($contenido === false) {
            return null;
        }

        if (preg_match('/\*\*T[ií]tulo\/Tema:\*\*\s*(.+)/u', $contenido, $m)) {
            $titulo = trim($m[1]);

            return $titulo !== '' ? $titulo : null;
        }

        return null;
    }

    private function fechaDesdeBase(string $base): Carbon
    {
        if (preg_match('/^(\d{8})_(\d{6})_/', $base, $m)) {
            return Carbon::createFromFormat('Ymd_His', $m[1].'_'.$m[2]);
        }

        return Carbon::now();
    }

    private function duracionTotal(array $wavs): ?float
    {
        $total = 0.0;
        $algunaValida = false;

        foreach ($wavs as $wav) {
            $result = Process::timeout(20)->run([
                'ffprobe', '-v', 'error', '-show_entries', 'format=duration',
                '-of', 'default=noprint_wrappers=1:nokey=1', $wav,
            ]);

            if ($result->successful() && trim($result->output()) !== '') {
                $total += (float) trim($result->output());
                $algunaValida = true;
            }
        }

        return $algunaValida ? round($total, 2) : null;
    }
}
