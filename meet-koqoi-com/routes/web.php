<?php

use App\Http\Controllers\GoogleOAuthController;
use App\Http\Controllers\MeetingFileController;
use App\Http\Controllers\SsoController;
use App\Livewire\Meetings\Detalle;
use App\Livewire\Meetings\Listado;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/reuniones');

Route::middleware(['auth'])->group(function () {
    Route::get('reuniones', Listado::class)->name('reuniones');
    Route::get('reuniones/{meeting}', Detalle::class)->name('reuniones.detalle');
    Route::get('reuniones/{meeting}/audio/{segmento?}', [MeetingFileController::class, 'audio'])
        ->name('reuniones.audio')
        ->defaults('segmento', 0);

    Route::view('profile', 'profile')->name('profile');

    Route::get('ir-a-pqr', [SsoController::class, 'ir'])->name('sso.ir-a-pqr');
});

Route::get('sso/{token}', [SsoController::class, 'consumir'])->name('sso.consumir');

Route::get('oauth/calendar/callback', [GoogleOAuthController::class, 'callback']);

require __DIR__.'/auth.php';
