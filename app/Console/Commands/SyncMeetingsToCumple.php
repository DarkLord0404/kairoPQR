<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Models\Meeting;
use App\Services\CumpleIntegrationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:sync-meetings-to-cumple {--force : Ejecutar aunque el envío automático esté apagado}')]
#[Description('Envía a CUMPLE las actas nuevas o actualizadas de Kairo.')]
class SyncMeetingsToCumple extends Command
{
    public function handle(CumpleIntegrationService $integration): int
    {
        if (! $this->option('force') && ! AppSetting::boolean('cumple_auto_sync')) {
            $this->line('Envío automático a CUMPLE desactivado.');
            return self::SUCCESS;
        }

        $sent = 0;
        $since = AppSetting::string('cumple_auto_sync_since');
        Meeting::where('estado', 'con_acta')->whereNotNull('acta_path')
            ->when(! $this->option('force') && $since, fn ($query) => $query->where('updated_at', '>=', $since))
            ->orderBy('id')->each(function (Meeting $meeting) use ($integration, &$sent): void {
            if ($integration->isCurrent($meeting)) {
                return;
            }
            try {
                $integration->sendDraft($meeting);
                $sent++;
            } catch (\Throwable $exception) {
                report($exception);
                $meeting->forceFill(['cumple_sync_error' => mb_substr($exception->getMessage(), 0, 4000)])->save();
                $this->error("{$meeting->id}: {$exception->getMessage()}");
            }
        });

        $this->info("Borradores enviados: {$sent}");
        return self::SUCCESS;
    }
}
