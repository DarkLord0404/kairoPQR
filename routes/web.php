<?php

use App\Livewire\Pqr\Analyzer;
use App\Livewire\Pqr\HistoryList;
use App\Livewire\Users\Manager;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('dashboard', Analyzer::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::get('historial', HistoryList::class)
    ->middleware(['auth'])
    ->name('historial');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('usuarios', Manager::class)->middleware(['auth', 'master'])->name('users');

require __DIR__.'/auth.php';
