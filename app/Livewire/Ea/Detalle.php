<?php

namespace App\Livewire\Ea;

use App\Models\EaAnalysis;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Detalle extends Component
{
    public EaAnalysis $analysis;

    public function mount(EaAnalysis $analysis): void
    {
        abort_unless(auth()->user()?->isMaster(), 403);
        $this->analysis = $analysis;
    }

    public function eliminar(): void
    {
        abort_unless(auth()->user()?->isMaster(), 403);
        $this->analysis->delete();
        session()->flash('status', 'Análisis de evento adverso eliminado del historial.');
        $this->redirect(route('ea.historial'), navigate: true);
    }

    public function render()
    {
        return view('livewire.ea.detalle');
    }
}
