<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PromptConfig extends Model
{
    protected $table = 'kairo_prompt_configs';

    protected $fillable = ['clave', 'nombre', 'prompt', 'updated_by'];

    public static function obtener(string $clave): ?string
    {
        return Cache::remember("prompt_config_{$clave}", 300, function () use ($clave) {
            return static::where('clave', $clave)->value('prompt');
        });
    }

    public static function guardar(string $clave, string $prompt, int $userId): void
    {
        static::where('clave', $clave)->update([
            'prompt'     => $prompt,
            'updated_by' => $userId,
        ]);
        Cache::forget("prompt_config_{$clave}");
    }
}
