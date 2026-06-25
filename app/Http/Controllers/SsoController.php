<?php

namespace App\Http\Controllers;

use App\Models\SsoToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class SsoController extends Controller
{
    public function ir(): RedirectResponse
    {
        $token = SsoToken::emitir(auth()->id(), 'meet');

        return redirect()->away('https://meet.koqoi.com/sso/'.$token);
    }

    public function consumir(string $token): RedirectResponse
    {
        $registro = SsoToken::find($token);

        abort_if(! $registro || ! $registro->esValido(), 403, 'Enlace invalido o vencido. Inicia sesion de nuevo.');

        $registro->forceFill(['usado_en' => now()])->save();

        Auth::loginUsingId($registro->user_id);

        return redirect()->route('dashboard');
    }
}
