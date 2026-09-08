<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleOAuthController extends Controller
{
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return $this->paginaResultado('error', 'Autorización rechazada: ' . $request->get('error'));
        }

        $code = $request->get('code');
        abort_if(!$code, 400, 'Falta el parámetro code.');

        $state = Str::slug($request->get('state', 'default'), '_') ?: 'default';

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => config('services.google_oauth.client_id'),
            'client_secret' => config('services.google_oauth.client_secret'),
            'redirect_uri'  => config('services.google_oauth.redirect_uri'),
            'grant_type'    => 'authorization_code',
        ]);

        if (!$response->successful()) {
            return $this->paginaResultado('error', 'Error al intercambiar el código: ' . $response->body());
        }

        $data = $response->json();

        if (empty($data['refresh_token'])) {
            return $this->paginaResultado('error',
                'Google no devolvió refresh_token. Vuelve a autorizar desde la app para forzar la pantalla de consentimiento.'
            );
        }

        $token = [
            'state'         => $state,
            'access_token'  => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_in'    => $data['expires_in'] ?? null,
            'scope'         => $data['scope'] ?? null,
            'token_type'    => $data['token_type'] ?? null,
            'obtenido_en'   => now()->toIso8601String(),
        ];

        $json = json_encode($token, JSON_PRETTY_PRINT);

        // Guardar en /opt/kairomeet/credentials/ (donde lo usa el bot)
        $destino = "/opt/kairomeet/credentials/google_calendar_token_{$state}.json";
        file_put_contents($destino, $json);

        return $this->paginaResultado('ok', $state);
    }

    private function paginaResultado(string $tipo, string $dato): \Illuminate\Http\Response
    {
        if ($tipo === 'error') {
            $html = <<<HTML
<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">
<title>Error de autorización</title>
<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#0f172a;color:#f8fafc}
.box{text-align:center;padding:2rem;max-width:480px}.icon{font-size:3rem}.title{font-size:1.25rem;font-weight:bold;color:#fca5a5;margin:.75rem 0}
.msg{font-size:.875rem;color:#94a3b8;line-height:1.5}.btn{display:inline-block;margin-top:1.5rem;padding:.6rem 1.5rem;background:#1e40af;color:#fff;border-radius:.5rem;text-decoration:none;font-size:.875rem}</style>
</head><body><div class="box"><div class="icon">❌</div>
<div class="title">Autorización fallida</div>
<div class="msg">{$dato}</div>
<a href="javascript:window.close()" class="btn">Cerrar ventana</a>
</div></body></html>
HTML;
            return response($html, 400)->header('Content-Type', 'text/html');
        }

        // éxito
        $html = <<<HTML
<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">
<title>Cuenta autorizada</title>
<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#0f172a;color:#f8fafc}
.box{text-align:center;padding:2rem;max-width:480px}.icon{font-size:3rem}.title{font-size:1.25rem;font-weight:bold;color:#6ee7b7;margin:.75rem 0}
.cuenta{font-size:.875rem;color:#93c5fd;background:rgba(59,130,246,.15);padding:.4rem 1rem;border-radius:2rem;display:inline-block;margin:.5rem 0}
.msg{font-size:.875rem;color:#94a3b8;line-height:1.5;margin-top:.75rem}
.btn{display:inline-block;margin-top:1.5rem;padding:.6rem 1.5rem;background:#065f46;color:#6ee7b7;border-radius:.5rem;text-decoration:none;font-size:.875rem;cursor:pointer}</style>
<script>setTimeout(()=>window.close(),3000)</script>
</head><body><div class="box"><div class="icon">✅</div>
<div class="title">Cuenta autorizada correctamente</div>
<div class="cuenta">{$dato}</div>
<div class="msg">El token quedó guardado. Esta ventana se cerrará en 3 segundos.</div>
<a href="javascript:window.close()" class="btn">Cerrar ahora</a>
</div></body></html>
HTML;
        return response($html)->header('Content-Type', 'text/html');
    }
}
