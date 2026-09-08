<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAccesoEa
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()?->tieneAccesoEa()) {
            abort(403, 'No tienes acceso al módulo de Eventos Adversos.');
        }
        return $next($request);
    }
}