<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EaAnalysis extends Model
{
    protected $fillable = [
        'user_id', 'caso', 'historia', 'respuesta_completa',
        'clasificacion', 'secciones', 'tokens_totales', 'tokens_entrada',
        'tokens_salida', 'tokens_cache', 'modelo', 'llamadas_modelo',
        'fragmentos', 'duracion_segundos',
    ];

    protected function casts(): array
    {
        return ['secciones' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
