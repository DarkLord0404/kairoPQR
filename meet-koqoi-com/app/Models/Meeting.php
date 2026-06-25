<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    protected $fillable = [
        'base_path', 'titulo', 'url_meet', 'fecha_inicio', 'fecha_fin',
        'duracion_segundos', 'num_segmentos', 'transcripcion_path',
        'acta_path', 'estado', 'diarizada',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'diarizada' => 'boolean',
        ];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MeetingParticipant::class);
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
