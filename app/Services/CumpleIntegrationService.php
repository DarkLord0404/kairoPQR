<?php

namespace App\Services;

use App\Models\Meeting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CumpleIntegrationService
{
    public function sendDraft(Meeting $meeting): array
    {
        $url = rtrim((string) config('services.cumple.url'), '/');
        $token = (string) config('services.cumple.token');
        if ($url === '' || $token === '') {
            throw new RuntimeException('La integración con CUMPLE no está configurada.');
        }

        $meeting->loadMissing('participants');
        $response = $this->client($token)->post($url.'/api/integrations/kairo/meetings', [
            'external_reference' => (string) $meeting->id,
            'title' => $meeting->titulo ?: 'Reunión de Kairo',
            'held_at' => $meeting->fecha_inicio?->toIso8601String() ?? now()->toIso8601String(),
            'organizer' => $meeting->organizador,
            'location' => $meeting->url_meet ?: 'Google Meet',
            'minutes_markdown' => $this->readOutput($meeting->acta_path),
            'transcript' => $this->readOutput($meeting->transcripcion_path),
            'participants' => $meeting->participants->map(fn ($participant) => [
                'name' => $participant->nombreVisible(),
            ])->values()->all(),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('CUMPLE rechazó el envío ('.$response->status().'): '.$response->body());
        }

        $meeting->forceFill([
            'cumple_synced_at' => now(),
            'cumple_synced_hash' => $this->contentHash($meeting),
            'cumple_sync_error' => null,
        ])->save();

        return $response->json();
    }

    public function isCurrent(Meeting $meeting): bool
    {
        return $meeting->cumple_synced_hash && hash_equals($meeting->cumple_synced_hash, $this->contentHash($meeting));
    }

    private function client(string $token): PendingRequest
    {
        return Http::asJson()->acceptJson()->withToken($token)->timeout(25)->retry(2, 300);
    }

    private function readOutput(?string $file): ?string
    {
        if (! $file) {
            return null;
        }
        $path = rtrim((string) config('kairomeet.salidas_path'), '/').'/'.$file;

        return is_file($path) ? file_get_contents($path) ?: null : null;
    }

    private function contentHash(Meeting $meeting): string
    {
        return hash('sha256', implode('|', [
            $meeting->titulo, $meeting->organizador, $meeting->fecha_inicio?->toIso8601String(),
            $this->readOutput($meeting->acta_path), $this->readOutput($meeting->transcripcion_path),
        ]));
    }
}
