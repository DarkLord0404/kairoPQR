<?php

namespace App\Livewire\Meetings;

use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Cuentas extends Component
{
    // Cuentas configuradas con su nombre para mostrar
    private const CUENTAS = [
        'alexandertorresviveros' => 'Gmail personal (Alexander Torres)',
        'alex870404'             => 'Gmail secundario (alex870404)',
        'clinicadeoccidente'     => 'Clínica de Occidente',
        'correounivalle'         => 'Universidad del Valle',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->isMaster(), 403);
    }

    public function urlAutorizacion(string $cuenta): string
    {
        $params = http_build_query([
            'client_id'     => config('services.google_oauth.client_id'),
            'redirect_uri'  => config('services.google_oauth.redirect_uri'),
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/calendar.events',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $cuenta,
            'login_hint'    => $this->loginHint($cuenta),
        ]);
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    private function loginHint(string $cuenta): string
    {
        return match ($cuenta) {
            'alexandertorresviveros' => 'alexandertorresviveros@gmail.com',
            'alex870404'             => 'alex870404@gmail.com',
            'clinicadeoccidente'     => 'kairo@clinicadeoccidente.com.co',
            'correounivalle'         => 'alexander.torres@correounivalle.edu.co',
            default                  => '',
        };
    }

    private function estadoToken(string $cuenta): array
    {
        $path = "/opt/kairomeet/credentials/google_calendar_token_{$cuenta}.json";

        if (!file_exists($path)) {
            return ['estado' => 'sin_token', 'mensaje' => 'Sin autorización — nunca se ha configurado'];
        }

        $data = json_decode(file_get_contents($path), true);
        if (!($data['refresh_token'] ?? '')) {
            return ['estado' => 'sin_token', 'mensaje' => 'Token incompleto — necesita re-autorización'];
        }

        $resp = Http::timeout(8)->post('https://oauth2.googleapis.com/token', [
            'client_id'     => config('services.google_oauth.client_id'),
            'client_secret' => config('services.google_oauth.client_secret'),
            'refresh_token' => $data['refresh_token'],
            'grant_type'    => 'refresh_token',
        ]);

        if (!$resp->ok()) {
            return ['estado' => 'error', 'mensaje' => 'Token vencido o revocado — requiere re-autorización'];
        }

        $obtenido = $data['obtenido_en'] ?? null;
        $hace = $obtenido
            ? \Carbon\Carbon::parse($obtenido)->setTimezone('America/Bogota')->diffForHumans()
            : 'fecha desconocida';

        return ['estado' => 'ok', 'mensaje' => "Activo · autorizado {$hace}"];
    }

    public function render()
    {
        $cuentas = [];
        foreach (self::CUENTAS as $id => $nombre) {
            $estado = $this->estadoToken($id);
            $cuentas[] = [
                'id'      => $id,
                'nombre'  => $nombre,
                'estado'  => $estado['estado'],
                'mensaje' => $estado['mensaje'],
                'url'     => $this->urlAutorizacion($id),
            ];
        }

        return view('livewire.meetings.cuentas', compact('cuentas'))
            ->title('Cuentas de Google Calendar');
    }
}
