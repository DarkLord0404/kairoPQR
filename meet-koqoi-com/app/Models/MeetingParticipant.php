<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingParticipant extends Model
{
    protected $fillable = ['meeting_id', 'etiqueta', 'nombre', 'segundos_hablados', 'porcentaje'];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function nombreVisible(): string
    {
        return $this->nombre ?: $this->etiqueta;
    }
}
