<?php

namespace App\Livewire\Pqr;

use App\Models\PqrAnalysis;
use Livewire\Component;
use Livewire\WithPagination;

class HistoryList extends Component
{
    use WithPagination;

    public function render()
    {
        $analyses = PqrAnalysis::with('user')->latest()->paginate(15);

        return view('livewire.pqr.history-list', [
            'analyses' => $analyses,
            'total' => PqrAnalysis::count(),
            'totalJuridica' => PqrAnalysis::where('requiere_revision_juridica', true)->count(),
        ]);
    }
}
