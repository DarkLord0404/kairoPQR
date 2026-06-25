<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SsoToken extends Model
{
    // Esta tabla vive en la base de datos de pqr.koqoi.com (conexion 'pqr');
    // el usuario kairo_meet solo tiene GRANT SELECT/INSERT/UPDATE sobre ELLA.
    protected $connection = 'pqr';

    protected $table = 'sso_tokens';

    protected $primaryKey = 'token';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['token', 'user_id', 'destino', 'expira_en'];

    protected function casts(): array
    {
        return [
            'expira_en' => 'datetime',
            'usado_en' => 'datetime',
        ];
    }

    public static function emitir(int $userId, string $destino): string
    {
        $token = Str::random(64);

        static::create([
            'token' => $token,
            'user_id' => $userId,
            'destino' => $destino,
            'expira_en' => Carbon::now()->addSeconds(60),
        ]);

        return $token;
    }

    public function esValido(): bool
    {
        return $this->usado_en === null && Carbon::now()->lessThan($this->expira_en);
    }
}
