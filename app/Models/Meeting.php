<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    protected $fillable = [
        'base_path', 'titulo', 'url_meet', 'organizador', 'fecha_inicio', 'fecha_fin',
        'duracion_segundos', 'num_segmentos', 'transcripcion_path',
        'acta_path', 'estado', 'diarizada', 'audio_aviso_enviado_en', 'audio_eliminado_en',
        'cumple_synced_at', 'cumple_synced_hash', 'cumple_sync_error',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'diarizada' => 'boolean',
            'audio_aviso_enviado_en' => 'datetime',
            'audio_eliminado_en' => 'datetime',
            'cumple_synced_at' => 'datetime',
        ];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MeetingParticipant::class);
    }

    /**
     * Rutas absolutas a los .wav de esta reunion que SI existen en disco
     * en este momento (puede estar vacio si ya se eliminaron).
     *
     * @return string[]
     */
    public function archivosAudio(): array
    {
        $dir = config('kairomeet.salidas_path');
        $sessionDir = $dir.'/'.$this->base_path;
        if (is_dir($sessionDir)) {
            $segmentados = glob($sessionDir.'/audio_part*.wav') ?: [];
            $archivos = $segmentados !== []
                ? $segmentados
                : (is_file($sessionDir.'/audio.wav') ? [$sessionDir.'/audio.wav'] : []);
            sort($archivos);

            return $archivos;
        }

        $patron = $this->num_segmentos > 1
            ? $dir.'/'.$this->base_path.'_part*.wav'
            : $dir.'/'.$this->base_path.'.wav';

        $archivos = glob($patron) ?: [];
        sort($archivos);

        return $archivos;
    }

    public function audioPesaBytes(): int
    {
        $total = 0;
        foreach ($this->archivosAudio() as $archivo) {
            $total += @filesize($archivo) ?: 0;
        }

        return $total;
    }

    public function audioPesaLegible(): string
    {
        $bytes = $this->audioPesaBytes();
        if ($bytes <= 0) {
            return '0 MB';
        }
        $mb = $bytes / 1024 / 1024;

        return $mb >= 1024
            ? round($mb / 1024, 2).' GB'
            : round($mb, 1).' MB';
    }

    public function tieneAudio(): bool
    {
        return count($this->archivosAudio()) > 0;
    }

    public function eliminarAudio(): void
    {
        foreach ($this->archivosAudio() as $archivo) {
            @unlink($archivo);
        }

        $this->forceFill(['audio_eliminado_en' => now()])->save();
    }

    protected function duracionLegible(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->duracion_segundos) {
                return null;
            }
            $horas = intdiv((int) $this->duracion_segundos, 3600);
            $minutos = intdiv((int) $this->duracion_segundos % 3600, 60);

            return $horas > 0 ? "{$horas}h {$minutos}min" : "{$minutos}min";
        });
    }
}
