<?php

namespace App\Livewire\Configuracion;

use App\Models\PromptConfig;
use Livewire\Component;

class Prompts extends Component
{
    public string $pqr      = '';
    public string $ea       = '';
    public string $fragmento = '';
    public string $final    = '';
    public string $glosario = '';

    public ?string $guardado = null;

    private const GLOSARIO_PATH = '/opt/kairomeet/glosario.txt';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isMaster(), 403);
        $this->pqr       = PromptConfig::obtener('pqr')            ?? '';
        $this->ea        = PromptConfig::obtener('ea')             ?? '';
        $this->fragmento = PromptConfig::obtener('meet_fragmento') ?? '';
        $this->final     = PromptConfig::obtener('meet_final')     ?? '';
        $this->glosario  = is_file(self::GLOSARIO_PATH)
            ? file_get_contents(self::GLOSARIO_PATH)
            : '';
    }

    public function guardar(string $clave): void
    {
        abort_unless(auth()->user()?->isMaster(), 403);

        if ($clave === 'meet_glosario') {
            file_put_contents(self::GLOSARIO_PATH, $this->glosario);
            $this->guardado = $clave;
            $this->dispatch('guardado-meet_glosario');
            return;
        }

        $campos = ['pqr' => 'pqr', 'ea' => 'ea', 'meet_fragmento' => 'fragmento', 'meet_final' => 'final'];
        if (! isset($campos[$clave])) {
            return;
        }

        $prop = $campos[$clave];
        PromptConfig::guardar($clave, $this->$prop, auth()->id());
        $this->guardado = $clave;
        $this->dispatch('guardado-'.$clave);
    }

    public function render()
    {
        return view('livewire.configuracion.prompts')
            ->layout('layouts.app', ['title' => 'Configuración de prompts']);
    }
}
