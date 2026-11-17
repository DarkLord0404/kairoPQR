<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SepsisDriveOAuthController extends Controller
{
    private const SCOPES = ['openid', 'email', 'https://www.googleapis.com/auth/drive', 'https://www.googleapis.com/auth/spreadsheets'];

    public function status()
    {
        $connection = Cache::remember('sepsis.drive.connection', 60, fn () => $this->connectionStatus());
        $historyPath = storage_path('app/private/sepsis/worker_history.json');
        $audit = is_readable($historyPath) ? json_decode(file_get_contents($historyPath), true) : null;
        if (!is_array($audit)) $audit = ['daily_limit' => 1, 'processing_enabled' => false, 'history' => []];
        $today = now()->timezone('America/Bogota')->format('Y-m-d');
        $audit['used_today'] = collect($audit['history'] ?? [])->where('dia', $today)->count();
        $audit['remaining_today'] = max(0, 1 - $audit['used_today']);
        return response()->view('sepsis.drive', compact('connection', 'audit'))->header('Cache-Control', 'no-store');
    }

    public function verify()
    {
        Cache::forget('sepsis.drive.connection');
        return redirect()->route('configuracion.sepsis');
    }

    private function connectionStatus(): array
    {
        $base = ['checked_at' => now()->timezone('America/Bogota')->format('d/m/Y H:i'), 'authorized_at' => null];
        $path = storage_path('app/private/sepsis/token.json');
        if (!is_readable($path)) return $base + ['state' => 'missing', 'title' => 'Sin autorización', 'message' => 'Autoriza la cuenta institucional para conectar Drive y Sheets.'];
        $token = json_decode(file_get_contents($path), true);
        if (!is_array($token) || empty($token['refresh_token'])) return $base + ['state' => 'expired', 'title' => 'Requiere reautorización', 'message' => 'La autorización guardada está incompleta.'];
        $base['authorized_at'] = $token['authorized_at'] ?? null;
        $scopes = explode(' ', $token['scope'] ?? '');
        if (!in_array(self::SCOPES[2], $scopes, true) || !in_array(self::SCOPES[3], $scopes, true)) return $base + ['state' => 'expired', 'title' => 'Faltan permisos', 'message' => 'Reautoriza para permitir el acceso a Drive y Sheets.'];
        try {
            $response = Http::asForm()->timeout(8)->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google_oauth.client_id'),
                'client_secret' => config('services.google_oauth.client_secret'),
                'refresh_token' => $token['refresh_token'], 'grant_type' => 'refresh_token',
            ]);
            if (!$response->successful()) {
                if ($response->json('error') === 'invalid_grant') return $base + ['state' => 'expired', 'title' => 'Autorización vencida o revocada', 'message' => 'Google rechazó la renovación. Pulsa Reautorizar Google Drive para restablecer el acceso.'];
                return $base + ['state' => 'unavailable', 'title' => 'No se pudo verificar', 'message' => 'Google no permitió comprobar la conexión. Reintenta la verificación; esto no confirma que el acceso haya vencido.'];
            }
            $folder = Http::withToken($response->json('access_token'))->timeout(8)->get('https://www.googleapis.com/drive/v3/files/1ctLLEnT5CLhVsoaz_xdb66rc38__8P59', ['supportsAllDrives' => 'true', 'fields' => 'id,capabilities(canAddChildren)']);
            if (!$folder->successful() || $folder->json('capabilities.canAddChildren') !== true) return $base + ['state' => 'unavailable', 'title' => 'Revisar acceso a la carpeta', 'message' => 'La cuenta se renueva, pero no se confirmó permiso de escritura en la carpeta de sepsis. Verifica los permisos de la unidad compartida.'];
            return $base + ['state' => 'ok', 'title' => 'Conexión activa', 'message' => 'Google permite renovar el acceso y escribir en la carpeta institucional de sepsis.'];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return $base + ['state' => 'unavailable', 'title' => 'Verificación no disponible', 'message' => 'No se pudo contactar a Google. Reintenta más tarde; no es necesario reautorizar por un fallo de red.'];
        }
    }

    public function connect(Request $request)
    {
        $state = 'sepsis_'.Str::random(48);
        $request->session()->put('sepsis_drive_state', ['state' => $state, 'expires' => time() + 900]);
        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google_oauth.client_id'),
            'redirect_uri' => config('services.google_oauth.redirect_uri'),
            'response_type' => 'code', 'scope' => implode(' ', self::SCOPES),
            'access_type' => 'offline', 'prompt' => 'consent', 'state' => $state,
            'login_hint' => 'alexander.torres@clinicadeoccidente.com',
        ]));
    }

    public function callback(Request $request)
    {
        $saved = $request->session()->pull('sepsis_drive_state');
        abort_unless(is_array($saved) && ($saved['expires'] ?? 0) >= time()
            && hash_equals($saved['state'], (string) $request->query('state', '')), 403, 'La autorización expiró. Inicie de nuevo desde Sepsis.');
        abort_if($request->has('error'), 400, 'Google no autorizó el acceso. Puede reintentarlo desde Sepsis.');
        abort_unless(is_string($request->query('code')), 400, 'Falta el código de autorización.');
        $client = [
            'client_id' => config('services.google_oauth.client_id'),
            'client_secret' => config('services.google_oauth.client_secret'),
        ];
        $response = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
            ...$client, 'code' => $request->query('code'),
            'redirect_uri' => config('services.google_oauth.redirect_uri'), 'grant_type' => 'authorization_code',
        ]);
        abort_unless($response->successful(), 400, 'Google no pudo completar la autorización.');
        $token = $response->json();
        abort_unless(!empty($token['refresh_token']), 400, 'Google no entregó acceso sin conexión. Reautorice con consentimiento.');
        $scopes = explode(' ', $token['scope'] ?? '');
        abort_unless(in_array(self::SCOPES[2], $scopes, true) && in_array(self::SCOPES[3], $scopes, true), 403, 'Faltan los permisos de Drive o Sheets.');
        $profile = Http::withToken($token['access_token'])->timeout(20)->get('https://www.googleapis.com/oauth2/v3/userinfo');
        abort_unless($profile->successful() && $profile->json('email') === 'alexander.torres@clinicadeoccidente.com'
            && $profile->json('email_verified') === true, 403, 'Debe autorizar con la cuenta institucional alexander.torres@clinicadeoccidente.com.');
        $folder = Http::withToken($token['access_token'])->timeout(20)->get('https://www.googleapis.com/drive/v3/files/1ctLLEnT5CLhVsoaz_xdb66rc38__8P59', [
            'supportsAllDrives' => 'true', 'fields' => 'id,capabilities(canAddChildren)',
        ]);
        abort_unless($folder->successful() && $folder->json('capabilities.canAddChildren') === true, 403, 'La cuenta no puede escribir en la carpeta institucional de sepsis.');
        $dir = storage_path('app/private/sepsis');
        if (!is_dir($dir)) mkdir($dir, 0700, true);
        chmod($dir, 0700);
        foreach (['client.json' => $client, 'token.json' => [
            'refresh_token' => $token['refresh_token'], 'scope' => $token['scope'],
            'account' => $profile->json('email'), 'authorized_at' => now()->toIso8601String(),
        ]] as $name => $data) {
            $temp = tempnam($dir, '.oauth-');
            chmod($temp, 0600);
            file_put_contents($temp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
            rename($temp, $dir.'/'.$name);
        }
        Cache::forget('sepsis.drive.connection');
        return redirect()->away('https://kairo.koqoi.com/conexiones');
    }
}
