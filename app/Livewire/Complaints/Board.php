<?php

namespace App\Livewire\Complaints;

use App\Models\Complaint;
use App\Models\ComplaintHistory;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Component;

class Board extends Component
{
    public string $contact_name = '';
    public string $contact_email = '';
    public string $contact_phone = '';
    public string $category = 'Atención en salud';
    public string $description = '';
    public ?int $selectedId = null;
    public string $response = '';

    public function create(): void
    {
        $data = $this->validate([
            'contact_name' => ['required', 'string', 'max:150'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:10000'],
        ]);

        $complaint = Complaint::create([...$data, 'reference' => 'PQR-'.now()->format('Y').'-'.Str::upper(Str::random(6)), 'created_by' => auth()->id(), 'assigned_to' => auth()->user()->isMaster() ? null : auth()->id()]);
        $this->history($complaint, 'Caso recibido', null, 'recibida');
        $this->reset('contact_name', 'contact_email', 'contact_phone', 'description');
        $this->dispatch('complaint-created');
        session()->flash('success', "Caso {$complaint->reference} registrado.");
    }

    public function select(int $id): void
    {
        $complaint = $this->visibleComplaints()->findOrFail($id);
        $this->selectedId = $complaint->id;
        $this->response = $complaint->response ?? '';
    }

    public function changeStatus(string $status): void
    {
        abort_unless(in_array($status, ['recibida', 'en proceso', 'respondida', 'cerrada'], true), 422);
        $complaint = $this->selectedComplaint();
        $old = $complaint->status;
        $complaint->status = $status;
        $complaint->closed_at = $status === 'cerrada' ? now() : null;
        $complaint->save();
        $this->history($complaint, 'Estado actualizado', $old, $status);
    }

    public function saveResponse(): void
    {
        $this->validate(['response' => ['required', 'string', 'max:15000']]);
        $complaint = $this->selectedComplaint();
        $complaint->update(['response' => $this->response, 'responded_at' => now(), 'status' => $complaint->status === 'cerrada' ? 'cerrada' : 'respondida']);
        $this->history($complaint, 'Respuesta registrada', null, null);
        session()->flash('success', 'Respuesta guardada.');
    }

    public function assign(int $userId): void
    {
        abort_unless(auth()->user()->isMaster(), 403);
        $complaint = $this->selectedComplaint();
        $old = $complaint->assigned_to;
        $complaint->update(['assigned_to' => User::where('is_active', true)->findOrFail($userId)->id]);
        $this->history($complaint, 'Responsable reasignado', (string) $old, (string) $userId);
    }

    private function selectedComplaint(): Complaint
    {
        abort_unless($this->selectedId, 422);
        return $this->visibleComplaints()->findOrFail($this->selectedId);
    }

    private function visibleComplaints()
    {
        return Complaint::query()->when(! auth()->user()->isMaster(), fn ($q) => $q->where('assigned_to', auth()->id()));
    }

    private function history(Complaint $complaint, string $action, ?string $from, ?string $to): void
    {
        ComplaintHistory::create(['complaint_id' => $complaint->id, 'user_id' => auth()->id(), 'action' => $action, 'from_value' => $from, 'to_value' => $to]);
    }

    public function render()
    {
        return view('livewire.complaints.board', [
            'complaints' => $this->visibleComplaints()->with('assignee')->latest('received_at')->get(),
            'selected' => $this->selectedId ? Complaint::with(['assignee', 'histories.user'])->find($this->selectedId) : null,
            'users' => auth()->user()->isMaster() ? User::where('is_active', true)->orderBy('name')->get() : collect(),
        ]);
    }
}
