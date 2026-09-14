<?php

namespace App\Console\Commands;

use App\Models\Meeting;
use App\Models\MeetingParticipant;
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

        // Formato aislado: una carpeta por sesión. Solo se importa cuando el
        // runner publica la marca atómica COMPLETADA.
        foreach (glob($dir.'/*/COMPLETADA') ?: [] as $marker) {
            $bases[basename(dirname($marker))] = true;
        }

        $bases = array_keys($bases);
        sort($bases);

        return $bases;
    }

    private function importarBase(string $dir, string $base): void
    {
        $basePath = $dir.'/'.$base;
        $esCarpeta = is_dir($basePath);

        $segmentos = $esCarpeta
            ? (glob($basePath.'/audio_part*.wav') ?: [])
            : (glob($basePath.'_part*.wav') ?: []);
        sort($segmentos);
        $esSegmentado = count($segmentos) > 0;
        $audioUnico = $esCarpeta ? $basePath.'/audio.wav' : $basePath.'.wav';
        $wavsParaDuracion = $esSegmentado ? $segmentos : (file_exists($audioUnico) ? [$audioUnico] : []);

        if (empty($wavsParaDuracion)) {
            $this->warn("Sin audio para {$base}, se omite.");

            return;
        }

        $fechaInicio = $this->fechaDesdeBase($base);
        $duracion = $this->duracionTotal($wavsParaDuracion);

        $transPath = $esCarpeta ? $basePath.'/transcripcion.txt' : $basePath.'.transcripcion.txt';
        $actaLlmPath = $esCarpeta ? $basePath.'/acta-llm.md' : $basePath.'.acta-llm.md';
        $actaPath = $esCarpeta ? $basePath.'/acta.md' : $basePath.'.acta.md';

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
        $organizador = $this->organizadorDesdeArchivo($basePath);

        $meeting = Meeting::updateOrCreate(
            ['base_path' => $base],
            [
                'titulo' => $titulo ?? 'Reunión',
                'url_meet' => null,
                'organizador' => $organizador,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $duracion ? $fechaInicio->copy()->addSeconds((int) $duracion) : null,
                'duracion_segundos' => $duracion,
                'num_segmentos' => $esSegmentado ? count($segmentos) : 1,
                'transcripcion_path' => $tieneTranscripcion ? $this->rutaRelativa($dir, $transPath) : null,
                'acta_path' => $actaFinal ? $this->rutaRelativa($dir, $actaFinal) : null,
                'estado' => $estado,
            ]
        );

        $this->importarHablantes($basePath, $meeting);

        $etiqueta = $meeting->wasRecentlyCreated ? 'nueva' : 'actualizada';
        $this->line("[{$etiqueta}] {$base} ({$estado})");
    }

    private function rutaRelativa(string $dir, string $path): string
    {
        return ltrim(str_replace('\\', '/', substr($path, strlen(rtrim($dir, '/')))), '/');
    }

    /**
     * Lee {base}.hablantes.json (muestras cada ~15s de quien hablaba, segun
     * la interfaz de Meet) y calcula el porcentaje de interaccion de cada
     * persona. Si el archivo no existe (reunion vieja o deteccion fallo),
     * simplemente no se crea nada; esto nunca debe fallar la importacion.
     */
    private function importarHablantes(string $basePath, Meeting $meeting): void
    {
        $path = is_dir($basePath) ? $basePath.'/hablantes.json' : $basePath.'.hablantes.json';

        if (! file_exists($path)) {
            return;
        }

        try {
            $muestras = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return;
        }

        if (! is_array($muestras) || empty($muestras)) {
            return;
        }

        $conteos = [];
        foreach ($muestras as $m) {
            $nombre = trim((string) ($m['nombre'] ?? ''));
            if ($nombre === '') {
                continue;
            }
            $conteos[$nombre] = ($conteos[$nombre] ?? 0) + 1;
        }

        if (empty($conteos)) {
            return;
        }

        $totalMuestras = array_sum($conteos);
        $intervaloSegundos = 15;

        $meeting->participants()->delete();

        foreach ($conteos as $nombre => $cantidad) {
            MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'etiqueta' => $nombre,
                'nombre' => $nombre,
                'segundos_hablados' => $cantidad * $intervaloSegundos,
                'porcentaje' => round(($cantidad / $totalMuestras) * 100, 2),
            ]);
        }
    }

    /**
     * El acta (Groq o LLM) trae el titulo real en una linea
     * "**Titulo/Tema:** ..." o "**Título/Tema:** ...". Lo extraemos de ahi
     * porque es lo unico que conserva el nombre real de la reunion.
     */
    private function organizadorDesdeArchivo(string $basePath): ?string
    {
        $path = is_dir($basePath) ? $basePath.'/organizador.txt' : $basePath.'.organizador.txt';
        if (! file_exists($path)) {
            return null;
        }

        $valor = trim((string) @file_get_contents($path));

        return $valor !== '' ? $valor : null;
    }

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
