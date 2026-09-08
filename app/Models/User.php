<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'acceso_pqr', 'acceso_ea', 'acceso_reuniones'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'acceso_pqr' => 'boolean',
            'acceso_ea' => 'boolean',
            'acceso_reuniones' => 'boolean',
        ];
    }

    public function isMaster(): bool
    {
        return $this->role === 'master';
    }

    public function tieneAccesoPqr(): bool
    {
        return $this->isMaster() || (bool) $this->acceso_pqr;
    }

    public function tieneAccesoEa(): bool
    {
        return $this->isMaster() || (bool) $this->acceso_ea;
    }

    public function tieneAccesoReuniones(): bool
    {
        return $this->isMaster() || (bool) $this->acceso_reuniones;
    }

}
