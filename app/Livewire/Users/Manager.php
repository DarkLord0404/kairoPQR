<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Manager extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'administrativo';
    public bool $accesoPqr = true;
    public bool $accesoEa = false;
    public bool $accesoReuniones = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isMaster(), 403);
    }

    public function create(): void
    {
        $data = $this->validate([
            'name'           => ['required', 'string', 'max:150'],
            'email'          => ['required', 'email', 'unique:users,email'],
            'password'       => ['required', 'string', 'min:6'],
            'role'           => ['required', 'in:master,administrativo'],
            'accesoPqr'      => ['boolean'],
            'accesoEa'       => ['boolean'],
            'accesoReuniones' => ['boolean'],
        ]);

        User::create([
            'name'             => $data['name'],
            'email'            => $data['email'],
            'role'             => $data['role'],
            'password'         => Hash::make($data['password']),
            'is_active'        => true,
            'email_verified_at' => now(),
            'acceso_pqr'       => $data['accesoPqr'],
            'acceso_ea'        => $data['accesoEa'],
            'acceso_reuniones' => $data['accesoReuniones'],
        ]);

        $this->reset('name', 'email', 'password', 'accesoPqr', 'accesoEa', 'accesoReuniones');
        session()->flash('success', 'Usuario creado.');
    }

    public function toggle(int $id): void
    {
        $user = User::findOrFail($id);
        abort_if($user->id === auth()->id(), 422, 'No puedes desactivar tu propia cuenta.');
        $user->update(['is_active' => !$user->is_active]);
    }

    public function toggleAcceso(int $id, string $campo): void
    {
        abort_unless(in_array($campo, ['acceso_pqr', 'acceso_ea', 'acceso_reuniones'], true), 422);
        $user = User::findOrFail($id);
        abort_if($user->isMaster(), 422, 'El usuario master tiene acceso total.');
        $user->update([$campo => !$user->{$campo}]);
    }

    public function render()
    {
        return view('livewire.users.manager', ['users' => User::orderBy('name')->get()]);
    }
}
