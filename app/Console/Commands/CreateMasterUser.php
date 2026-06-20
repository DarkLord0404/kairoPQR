<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateMasterUser extends Command
{
    protected $signature = 'kairo:create-master {name?} {email?} {--password=}';
    protected $description = 'Crea o actualiza el usuario master de Kairo PQRS';

    public function handle(): int
    {
        $name = $this->argument('name') ?: $this->ask('Nombre completo');
        $email = $this->argument('email') ?: $this->ask('Correo electrónico');
        $password = $this->option('password') ?: $this->secret('Contraseña');

        if (! $name || ! $email || ! $password) {
            $this->error('Nombre, correo y contraseña son obligatorios.');
            return self::FAILURE;
        }

        User::updateOrCreate(['email' => $email], [
            'name' => $name,
            'password' => Hash::make($password),
            'role' => 'master',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->info("Usuario master listo: {$email}");
        return self::SUCCESS;
    }
}
