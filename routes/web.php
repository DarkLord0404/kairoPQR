<?php

use App\Http\Controllers\ExtractTextController;
use App\Http\Controllers\GoogleOAuthController;
use App\Http\Controllers\MeetingFileController;
use App\Livewire\AcercaDe;
use App\Livewire\Configuracion\Consumo;
use App\Livewire\Configuracion\Prompts;
use App\Livewire\Ea\Analyzer as EaAnalyzer;
use App\Livewire\Ea\Detalle as EaDetalle;
use App\Livewire\Ea\HistoryList as EaHistoryList;
use App\Livewire\Meetings\Conectar as ReunionConectar;
use App\Livewire\Meetings\Cuentas as MeetingCuentas;
use App\Livewire\Meetings\Detalle as ReunionDetalle;
use App\Livewire\Meetings\Listado as ReunionListado;
use App\Livewire\Pqr\Analyzer;
use App\Livewire\Pqr\Detalle as PqrDetalle;
use App\Livewire\Pqr\HistoryList;
use App\Livewire\Users\Manager;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth'])->group(function () {

    // --- PQR ---
    Route::get('dashboard', Analyzer::class)
        ->middleware(['acceso.pqr'])
        ->name('dashboard');

    Route::get('historial', HistoryList::class)
        ->middleware(['acceso.pqr', 'master'])
        ->name('historial');

    Route::get('historial/{analysis}', PqrDetalle::class)
        ->middleware(['acceso.pqr', 'master'])
        ->name('historial.detalle');

    // --- Eventos Adversos ---
    Route::get('eventos-adversos', EaAnalyzer::class)
        ->middleware(['acceso.ea'])
        ->name('ea.analyzer');

    Route::get('eventos-adversos/historial', EaHistoryList::class)
        ->middleware(['acceso.ea', 'master'])
        ->name('ea.historial');

    Route::get('eventos-adversos/historial/{eaAnalysis}', EaDetalle::class)
        ->middleware(['acceso.ea', 'master'])
        ->name('ea.historial.detalle');

    // --- Reuniones (KairoMeet) ---
    Route::get('reuniones', ReunionListado::class)
        ->middleware(['acceso.reuniones'])
        ->name('reuniones');

    Route::get('reuniones/cuentas', MeetingCuentas::class)->middleware(['master'])->name('reuniones.cuentas');

    Route::get('reuniones/conectar', ReunionConectar::class)->middleware(['master'])->name('reuniones.conectar');

    Route::get('reuniones/{meeting}', ReunionDetalle::class)
        ->middleware(['acceso.reuniones'])
        ->name('reuniones.detalle');

    Route::get('reuniones/{meeting}/audio/{segmento?}', [MeetingFileController::class, 'audio'])
        ->middleware(['acceso.reuniones'])
        ->name('reuniones.audio')
        ->defaults('segmento', 0);

    // --- Comunes ---
    Route::post('extraer-texto', ExtractTextController::class)->middleware(['acceso.pqr'])->name('extraer.texto');

    Route::get('configuracion/prompts', Prompts::class)->middleware(['master'])->name('configuracion.prompts');
    Route::get('configuracion/consumo', Consumo::class)->middleware(['master'])->name('configuracion.consumo');
    Route::get('acerca-de', AcercaDe::class)->name('acerca-de');

    Route::view('profile', 'profile')->name('profile');

    Route::get('usuarios', Manager::class)->middleware(['master'])->name('users');
});

// Callback de OAuth de Google Calendar: publico (lo llama Google, no un usuario logueado)
Route::get('oauth/calendar/callback', [GoogleOAuthController::class, 'callback']);

require __DIR__.'/auth.php';
