<?php

namespace App\Console\Commands;

use App\Mail\AvisoEliminacionAudio;
use App\Models\Meeting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('app:cleanup-audio')]
#[Description('Avisa un día antes y elimina audios de reuniones con más de 7 días, conservando acta y transcripción.')]
class CleanupAudio extends Command
{
    private const DIAS_RETENCION = 7;

    /**
     * Cuentas institucionales propias del usuario; si el organizador de la
     * reunion es una de estas, el aviso tambien se le envia ahi (con copia
     * a la personal). Si el organizador es un tercero, solo se avisa a la
     * cuenta personal (nunca se le escribe a un tercero sin que lo pida).
     */
    private const CUENTAS_PROPIAS = [
        'alexandertorresviveros@gmail.com',
        'alex870404@gmail.com',
        'alexander.torres@clinicadeoccidente.com',
        'alexander.torres@correounivalle.edu.co',
    ];

    private const CORREO_PERSONAL = 'alexandertorresviveros@gmail.com';

    public function handle(): int
    {
        $this->avisarProximasAEliminar();
        $this->eliminarVencidas();
        $this->eliminarAudiosHuerfanosHeredados();

        return self::SUCCESS;
    }

    private function avisarProximasAEliminar(): void
    {
        $desde = now()->subDays(self::DIAS_RETENCION);
        $hasta = now()->subDays(self::DIAS_RETENCION - 1);

        $meetings = Meeting::whereNull('audio_aviso_enviado_en')
            ->whereNull('audio_eliminado_en')
            ->where('fecha_inicio', '>', $desde)
            ->where('fecha_inicio', '<=', $hasta)
            ->get()
            ->filter(fn (Meeting $m) => $m->tieneAudio());

        foreach ($meetings as $meeting) {
            $this->enviarAviso($meeting);
            $meeting->forceFill(['audio_aviso_enviado_en' => now()])->save();
            $this->line("[aviso] {$meeting->titulo} ({$meeting->audioPesaLegible()})");
        }
    }

    private function eliminarVencidas(): void
    {
        $limite = now()->subDays(self::DIAS_RETENCION);

        $meetings = Meeting::whereNull('audio_eliminado_en')
            ->where('fecha_inicio', '<=', $limite)
            ->get()
            ->filter(fn (Meeting $m) => $m->tieneAudio());

        foreach ($meetings as $meeting) {
            $peso = $meeting->audioPesaLegible();
            $meeting->eliminarAudio();
            $this->line("[eliminado] {$meeting->titulo} ({$peso} liberados)");
        }
    }

    /**
     * Las versiones antiguas guardaban los segmentos directamente en la raíz
     * de "salidas". Algunos quedaron sin registro en la base de datos, por lo
     * que no los alcanza la limpieza normal de reuniones.
     */
    private function eliminarAudiosHuerfanosHeredados(): void
    {
        $directorio = config('kairomeet.salidas_path');
        $limite = now()->subDays(self::DIAS_RETENCION)->getTimestamp();

        if (! is_dir($directorio)) {
            return;
        }

        $reuniones = Meeting::all();

        foreach (new \FilesystemIterator($directorio, \FilesystemIterator::SKIP_DOTS) as $archivo) {
            if (! $archivo->isFile()
                || ! in_array(strtolower($archivo->getExtension()), ['wav', 'flac'], true)
                || $archivo->getMTime() > $limite) {
                continue;
            }

            $ruta = $archivo->getPathname();
            $estaVinculado = $reuniones->contains(
                fn (Meeting $meeting) => in_array($ruta, $meeting->archivosAudio(), true)
            );

            if (! $estaVinculado && @unlink($ruta)) {
                $this->line("[eliminado huérfano] {$archivo->getFilename()}");
            }
        }
    }

    private function enviarAviso(Meeting $meeting): void
    {
        $destinatarios = [self::CORREO_PERSONAL];

        if ($meeting->organizador && in_array($meeting->organizador, self::CUENTAS_PROPIAS, true)
            && $meeting->organizador !== self::CORREO_PERSONAL) {
            $destinatarios[] = $meeting->organizador;
        }

        Mail::to($destinatarios)->send(new AvisoEliminacionAudio($meeting));
    }
}
