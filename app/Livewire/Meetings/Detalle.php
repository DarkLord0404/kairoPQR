<?php

namespace App\Livewire\Meetings;

use App\Mail\EnvioActa;
use App\Models\Meeting;
use App\Services\CumpleIntegrationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Detalle extends Component
{
    public int $meetingId;

    public string $pestana = 'acta';

    public string $emailDestino = '';

    public ?string $mensajeEnvio = null;

    public ?string $mensajeCumple = null;

    public function mount(Meeting $meeting): void
    {
        $this->meetingId = $meeting->id;
    }

    #[Computed]
    public function meeting(): Meeting
    {
        return Meeting::findOrFail($this->meetingId);
    }

    public function cambiarPestana(string $pestana): void
    {
        $this->pestana = $pestana;
        $this->mensajeEnvio = null;
    }

    public function enviarActa(): void
    {
        $validated = Validator::make(
            ['email' => $this->emailDestino],
            ['email' => ['required', 'email:rfc,dns']],
            ['email.required' => 'Ingresa un correo.', 'email.email' => 'El correo no es válido.']
        );

        if ($validated->fails()) {
            $this->mensajeEnvio = '❌ '.$validated->errors()->first('email');

            return;
        }

        $acta = $this->getActaTexto();
        if (! $acta) {
            $this->mensajeEnvio = '❌ El acta no está disponible.';

            return;
        }

        try {
            Mail::to($this->emailDestino)->send(new EnvioActa($this->meeting, $acta));
            $this->mensajeEnvio = '✅ Acta enviada a '.$this->emailDestino;
            $this->emailDestino = '';
            $this->dispatch('acta-enviada');
        } catch (\Throwable $e) {
            $this->mensajeEnvio = '❌ No se pudo enviar: '.$e->getMessage();
        }
    }

    public function enviarACumple(CumpleIntegrationService $integration): void
    {
        abort_unless(auth()->user()?->isMaster(), 403);

        try {
            $result = $integration->sendDraft($this->meeting);
            $this->mensajeCumple = '✅ '.($result['message'] ?? 'Borrador enviado a CUMPLE.');
        } catch (\Throwable $exception) {
            report($exception);
            $this->mensajeCumple = '❌ No se pudo enviar a CUMPLE: '.$exception->getMessage();
        }
    }

    public function eliminar()
    {
        abort_unless(auth()->user()?->isMaster(), 403);

        $meeting = Meeting::find($this->meetingId);

        if (! $meeting) {
            return $this->redirectRoute('reuniones');
        }

        $titulo = $meeting->titulo;
        $dir = $this->rutaSalidas();
        $sessionPath = $dir.'/'.$meeting->base_path;
        if (is_dir($sessionPath)) {
            foreach (glob($sessionPath.'/*') ?: [] as $archivo) {
                if (is_file($archivo)) {
                    @unlink($archivo);
                }
            }
            @rmdir($sessionPath);
        } else {
            foreach (glob($sessionPath.'*') ?: [] as $archivo) {
                @unlink($archivo);
            }
        }

        $meeting->delete();

        session()->flash('status', "Reunión '{$titulo}' eliminada.");

        return $this->redirectRoute('reuniones');
    }

    public function eliminarAudio(): void
    {
        abort_unless(auth()->user()?->isMaster(), 403);

        $this->meeting->eliminarAudio();
    }

    private function rutaSalidas(): string
    {
        return config('kairomeet.salidas_path');
    }

    private function getActaTexto(): ?string
    {
        if (! $this->meeting->acta_path) {
            return null;
        }

        $ruta = $this->rutaSalidas().'/'.$this->meeting->acta_path;

        return is_file($ruta) ? file_get_contents($ruta) : null;
    }

    public function getActaHtmlProperty(): ?string
    {
        $markdown = $this->getActaTexto();

        if (! $markdown) {
            return null;
        }

        $converter = new GithubFlavoredMarkdownConverter(['html_input' => 'strip']);

        return (string) $converter->convert($markdown);
    }

    public function getTranscripcionProperty(): ?string
    {
        if (! $this->meeting->transcripcion_path) {
            return null;
        }

        $ruta = $this->rutaSalidas().'/'.$this->meeting->transcripcion_path;

        return is_file($ruta) ? file_get_contents($ruta) : null;
    }

    public function render()
    {
        return view('livewire.meetings.detalle', ['meeting' => $this->meeting()]);
    }
}
