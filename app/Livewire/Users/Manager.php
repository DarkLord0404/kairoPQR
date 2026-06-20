<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Manager extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'administrativo';

    public function create(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12'],
            'role' => ['required', 'in:master,administrativo'],
        ]);

        User::create([...$data, 'password' => Hash::make($data['password']), 'is_active' => true, 'email_verified_at' => now()]);
        $this->reset('name', 'email', 'password');
        session()->flash('success', 'Usuario creado.');
    }

    public function toggle(int $id): void
    {
        $user = User::findOrFail($id);
        abort_if($user->id === auth()->id(), 422, 'No puedes desactivar tu propia cuenta.');
        $user->update(['is_active' => ! $user->is_active]);
    }

    public function render()
    {
        return view('livewire.users.manager', ['users' => User::orderBy('name')->get()]);
    }
}
