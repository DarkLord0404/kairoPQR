<?php

namespace App\Livewire\Meetings;

use App\Models\Meeting;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Detalle extends Component
{
    public Meeting $meeting;

    public string $pestana = 'acta';

    public function mount(Meeting $meeting): void
    {
        $this->meeting = $meeting;
    }

    public function cambiarPestana(string $pestana): void
    {
        $this->pestana = $pestana;
    }

    public function eliminar()
    {
        abort_unless(auth()->user()?->isMaster(), 403);

        $titulo = $this->meeting->titulo;
        $dir = $this->rutaSalidas();
        foreach (glob($dir.'/'.$this->meeting->base_path.'*') as $archivo) {
            @unlink($archivo);
        }

        $this->meeting->delete();

        session()->flash('status', "Reunión '{$titulo}' eliminada.");

        return $this->redirect(route('reuniones'), navigate: true);
    }

    private function rutaSalidas(): string
    {
        return config('kairomeet.salidas_path');
    }

    public function getActaHtmlProperty(): ?string
    {
        if (! $this->meeting->acta_path) {
            return null;
        }

        $ruta = $this->rutaSalidas().'/'.$this->meeting->acta_path;

        if (! is_file($ruta)) {
            return null;
        }

        $markdown = file_get_contents($ruta);
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
        return view('livewire.meetings.detalle');
    }
}
