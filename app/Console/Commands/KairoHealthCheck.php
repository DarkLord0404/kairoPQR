<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

class KairoHealthCheck extends Command
{
    protected $signature = 'app:health-check
                            {--full : Incluye una petición real mínima al modelo de OpenClaw}';

    protected $description = 'Verifica el estado de todos los servicios que usa Kairo';

    public function handle(): void
    {
        DB::table('kairo_health_checks')->truncate();

        // ── Infraestructura ──────────────────────────────────────────────
        $this->verificar('Infraestructura', 'Base de datos MySQL', fn () => $this->checkDatabase());
        $this->verificar('Infraestructura', 'Correo saliente (SMTP)', fn () => $this->checkSmtp());
        $this->verificar('Infraestructura', 'Espacio en disco grabaciones', fn () => $this->checkDisco());

        // ── Inteligencia Artificial ──────────────────────────────────────
        $openClawNombre = $this->option('full')
            ? 'Análisis de texto (OpenAI, prueba integral)'
            : 'Análisis de texto (OpenClaw, comprobación técnica)';
        $this->verificar('Inteligencia Artificial', $openClawNombre, fn () => $this->checkOpenClaw((bool) $this->option('full')));
        $this->verificar('Inteligencia Artificial', 'Transcripción de audio (Groq)', fn () => $this->checkGroq());

        // ── Calendarios Google ───────────────────────────────────────────
        $this->verificar('Calendarios Google', 'Gmail personal (alexandertorresviveros)', fn () => $this->checkGoogleToken('alexandertorresviveros'));
        $this->verificar('Calendarios Google', 'Gmail secundario (alex870404)', fn () => $this->checkGoogleToken('alex870404'));
        $this->verificar('Calendarios Google', 'Clínica de Occidente', fn () => $this->checkGoogleToken('clinicadeoccidente'));
        $this->verificar('Calendarios Google', 'Universidad del Valle', fn () => $this->checkGoogleToken('correounivalle'));

        // ── KairoMeet ────────────────────────────────────────────────────
        $this->verificar('KairoMeet', 'Archivos del bot (Python)', fn () => $this->checkBotFiles());
        $this->verificar('KairoMeet', 'Display virtual para Chrome', fn () => $this->checkXvfb());
        $this->verificar('KairoMeet', 'Conversor de audio (ffmpeg)', fn () => $this->checkFfmpeg());

        $this->info('Health check completado: 12 servicios verificados.');
    }

    private function verificar(string $grupo, string $nombre, callable $check): void
    {
        $inicio = microtime(true);
        try {
            [$estado, $mensaje] = $check();
        } catch (\Throwable $e) {
            $estado = 'fallo';
            $mensaje = $e->getMessage();
        }
        $ms = (int) ((microtime(true) - $inicio) * 1000);

        DB::table('kairo_health_checks')->insert([
            'grupo' => $grupo,
            'servicio' => $nombre,
            'estado' => $estado,
            'mensaje' => $mensaje,
            'duracion_ms' => $ms,
            'verificado_en' => now(),
        ]);

        $icon = $estado === 'ok' ? '✅' : ($estado === 'advertencia' ? '⚠️' : '❌');
        $this->line("  {$icon} [{$grupo}] {$nombre}: {$mensaje}");
    }

    // ── Checks ──────────────────────────────────────────────────────────────

    private function checkDatabase(): array
    {
        DB::select('SELECT 1');
        $pqr = DB::table('pqr_analyses')->count();
        $ea = DB::table('ea_analyses')->count();
        $meet = DB::table('meetings')->count();

        return ['ok', "{$pqr} análisis PQR · {$ea} eventos adversos · {$meet} reuniones grabadas"];
    }

    private function checkSmtp(): array
    {
        $host = env('MAIL_HOST', 'smtp.gmail.com');
        $port = (int) env('MAIL_PORT', 465);
        if (! $host) {
            return ['advertencia', 'MAIL_HOST no configurado en .env'];
        }
        $conn = @fsockopen($host, $port, $errno, $errstr, 5);
        if (! $conn) {
            return ['fallo', "Sin conexión con {$host}:{$port} — los correos no se enviarán"];
        }
        fclose($conn);

        return ['ok', "Conexión con servidor de correo {$host}:{$port} OK"];
    }

    private function checkDisco(): array
    {
        $dir = '/opt/kairomeet/salidas';
        if (! is_dir($dir)) {
            return ['fallo', 'Carpeta de grabaciones no existe en el servidor'];
        }
        $du = Process::timeout(5)->run(['du', '-sh', $dir]);
        $uso = trim(explode("\t", $du->output())[0] ?? '?');
        $df = Process::timeout(5)->run(['df', '-h', $dir]);
        $dfLines = explode("\n", trim($df->output()));
        $cols = preg_split('/\s+/', end($dfLines));
        $libre = $cols[3] ?? '?';
        $pct = (int) ($cols[4] ?? 0);
        $estado = $pct > 90 ? 'fallo' : ($pct > 80 ? 'advertencia' : 'ok');
        $msg = "Grabaciones ocupan {$uso} · Disco libre: {$libre} ({$pct}% usado)";
        if ($pct > 90) {
            $msg .= ' — DISCO CASI LLENO';
        } elseif ($pct > 80) {
            $msg .= ' — espacio reducido';
        }

        return [$estado, $msg];
    }

    private function checkOpenClaw(bool $full = false): array
    {
        $gateway = @fsockopen('127.0.0.1', 18789, $errno, $error, 3);
        if (! $gateway) {
            return ['fallo', "Gateway OpenClaw inaccesible en 127.0.0.1:18789 ({$errno}: {$error}) — PQR y EA no funcionarán"];
        }
        fclose($gateway);

        $status = Process::timeout(15)->run([
            'sudo', '-n', '-H', '-u', 'root',
            '/usr/local/sbin/kairo-openclaw-status',
        ]);
        if (! $status->successful()) {
            return ['fallo', 'Gateway activo, pero OpenClaw no pudo validar su configuración: '.substr($status->errorOutput(), 0, 100)];
        }

        $models = json_decode($status->output(), true);
        $modelo = $models['resolvedDefault'] ?? $models['defaultModel'] ?? 'modelo configurado';
        $faltantes = $models['missingProvidersInUse'] ?? [];
        if ($faltantes !== []) {
            return ['fallo', 'Gateway activo, pero faltan credenciales para: '.implode(', ', $faltantes)];
        }

        if (! $full) {
            return ['ok', "Gateway y autenticación configurados · {$modelo} · sin consumir tokens del modelo"];
        }

        $result = Process::timeout(30)->run([
            'sudo', '-n', '-H', '-u', 'root',
            '/usr/local/sbin/kairo-openclaw-probe',
        ]);
        if (! $result->successful() || trim($result->output()) === '') {
            return ['fallo', 'Sin respuesta — los análisis PQR y EA no funcionarán: '.substr($result->errorOutput(), 0, 100)];
        }

        return ['ok', "Prueba integral correcta vía suscripción OpenAI · {$modelo}"];
    }

    private function checkGroq(): array
    {
        $key = $this->groqApiKey();
        if (! $key) {
            return ['fallo', 'Clave de API no configurada — la transcripción de audio no está disponible'];
        }
        $resp = Http::timeout(10)->withToken($key)->get('https://api.groq.com/openai/v1/models');
        if (! $resp->successful()) {
            return ['fallo', 'API de Groq no responde (HTTP '.$resp->status().') — transcripción no disponible'];
        }
        $whisper = collect($resp->json('data', []))->contains(fn ($m) => str_contains($m['id'] ?? '', 'whisper'));

        return ['ok', 'API activa · Modelo Whisper '.($whisper ? 'disponible para transcripción' : 'no listado')];
    }

    private function checkGoogleToken(string $cuenta): array
    {
        $path = "/opt/kairomeet/credentials/google_calendar_token_{$cuenta}.json";
        if (! file_exists($path)) {
            return ['fallo', 'No hay token guardado — necesita re-autorización en GCP'];
        }
        $data = json_decode(file_get_contents($path), true);
        $refreshToken = $data['refresh_token'] ?? '';
        if (! $refreshToken) {
            return ['fallo', 'Token incompleto — necesita re-autorización'];
        }

        $resp = Http::timeout(10)->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google_oauth.client_id'),
            'client_secret' => config('services.google_oauth.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);
        if (! $resp->successful()) {
            return ['fallo', 'Token vencido o revocado — Kairo no puede leer este calendario'];
        }
        $age = isset($data['obtenido_en'])
            ? now()->diffForHumans(Carbon::parse($data['obtenido_en']), true)
            : '?';

        return ['ok', "Acceso al calendario autorizado · Token obtenido hace {$age}"];
    }

    private function checkBotFiles(): array
    {
        $archivos = [
            '/opt/kairomeet/meet.py' => 'Unión a reuniones',
            '/opt/kairomeet/runner.py' => 'Orquestador principal',
            '/opt/kairomeet/audio.py' => 'Grabación de audio',
            '/opt/kairomeet/actas_llm.py' => 'Generación de actas',
            '/opt/kairomeet/notifier.py' => 'Envío de correos',
            '/opt/kairomeet/invitar_kairo.py' => 'Invitador de calendario',
        ];
        $faltantes = array_filter(array_keys($archivos), fn ($f) => ! file_exists($f));
        if ($faltantes) {
            $nombres = array_map(fn ($f) => $archivos[$f], $faltantes);

            return ['fallo', 'Módulos faltantes: '.implode(', ', $nombres).' — bot incompleto'];
        }

        return ['ok', 'Todos los módulos del bot presentes ('.count($archivos).' archivos)'];
    }

    private function checkXvfb(): array
    {
        $r = Process::timeout(5)->run(['pgrep', '-f', 'Xvfb.*:99']);
        if (! $r->successful() || trim($r->output()) === '') {
            return ['advertencia', 'Pantalla virtual apagada — el bot no puede abrir Chrome para unirse a Meet'];
        }

        return ['ok', 'Pantalla virtual activa · Chrome puede ejecutarse en segundo plano'];
    }

    private function checkFfmpeg(): array
    {
        $r = Process::timeout(5)->run(['ffmpeg', '-version']);
        if (! $r->successful()) {
            return ['fallo', 'ffmpeg no instalado — grabación de audio no funcionará'];
        }
        $ver = substr(explode("\n", $r->output())[0], 8, 50);

        return ['ok', "Instalado: {$ver}"];
    }

    private function groqApiKey(): string
    {
        $env = '/opt/kairomeet/.env';
        if (! file_exists($env)) {
            return env('GROQ_API_KEY', '');
        }
        foreach (file($env) as $line) {
            if (str_starts_with(trim($line), 'GROQ_API_KEY=')) {
                return trim(explode('=', $line, 2)[1]);
            }
        }

        return env('GROQ_API_KEY', '');
    }
}
