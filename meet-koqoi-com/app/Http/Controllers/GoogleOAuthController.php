<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GoogleOAuthController extends Controller
{
    /**
     * Endpoint reutilizable: recibe el "code" de Google tras la pantalla de
     * consentimiento, lo cambia por tokens (incluyendo refresh_token) y los
     * guarda en storage/app/google_calendar_token_{state}.json, donde
     * {state} identifica la cuenta (viene del parametro state del link de
     * autorizacion). Desde ahi se copian manualmente a /opt/kairomeet para
     * que el cron de invitacion a reuniones los use.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return response('Autorizacion rechazada: '.$request->get('error'), 400);
        }

        $code = $request->get('code');
        abort_if(! $code, 400, 'Falta el parametro code.');

        $state = $request->get('state', 'default');
        $state = Str::slug($state, '_') ?: 'default';

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('services.google_oauth.client_id'),
            'client_secret' => config('services.google_oauth.client_secret'),
            'redirect_uri' => config('services.google_oauth.redirect_uri'),
            'grant_type' => 'authorization_code',
        ]);

        if (! $response->successful()) {
            return response('Error al cambiar el code por tokens: '.$response->body(), 500);
        }

        $data = $response->json();

        if (empty($data['refresh_token'])) {
            return response(
                'Google no devolvio refresh_token (puede pasar si ya habias autorizado antes sin "prompt=consent"). '.
                'Vuelve a generar el link de autorizacion con prompt=consent y vuelve a intentarlo.',
                400
            );
        }

        $archivo = "google_calendar_token_{$state}.json";

        Storage::disk('local')->put($archivo, json_encode([
            'state' => $state,
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_in' => $data['expires_in'] ?? null,
            'scope' => $data['scope'] ?? null,
            'token_type' => $data['token_type'] ?? null,
            'obtenido_en' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));

        return response(
            "Listo. El token de Google Calendar para '{$state}' quedo guardado correctamente. ".
            'Ya puedes cerrar esta pestana.'
        );
    }
}
