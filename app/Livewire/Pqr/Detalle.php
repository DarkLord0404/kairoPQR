<?php

namespace App\Livewire\Pqr;

use App\Models\PqrAnalysis;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Detalle extends Component
{
    public PqrAnalysis $analysis;

    public function mount(PqrAnalysis $analysis): void
    {
        abort_unless(auth()->user()?->isMaster(), 403);

        $this->analysis = $analysis;
    }

    public function eliminar()
    {
        abort_unless(auth()->user()?->isMaster(), 403);

        $analysis = $this->analysis;
        unset($this->analysis);
        $analysis->delete();

        session()->flash('status', 'Análisis eliminado del historial.');

        return $this->redirect(route('historial'));
    }

    public function render()
    {
        return view('livewire.pqr.detalle');
    }
}
