<?php

use App\Http\Controllers\SsoController;
use App\Livewire\Pqr\Analyzer;
use App\Livewire\Pqr\HistoryList;
use App\Livewire\Users\Manager;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('dashboard', Analyzer::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::get('historial', HistoryList::class)
    ->middleware(['auth', 'master'])
    ->name('historial');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('usuarios', Manager::class)->middleware(['auth', 'master'])->name('users');

Route::get('ir-a-meet', [SsoController::class, 'ir'])->middleware(['auth'])->name('sso.ir-a-meet');
Route::get('sso/{token}', [SsoController::class, 'consumir'])->name('sso.consumir');

require __DIR__.'/auth.php';
