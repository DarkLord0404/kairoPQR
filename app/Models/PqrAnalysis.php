<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PqrAnalysis extends Model
{
    protected $fillable = [
        'user_id', 'queja', 'historia', 'respuesta_completa',
        'clasificacion', 'requiere_revision_juridica', 'es_queja_valida', 'secciones',
        'tokens_totales', 'duracion_segundos',
    ];

    protected function casts(): array
    {
        return [
            'secciones' => 'array',
            'requiere_revision_juridica' => 'boolean',
            'es_queja_valida' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
