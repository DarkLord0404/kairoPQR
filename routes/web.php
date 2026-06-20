<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Users\Manager;

Route::redirect('/', '/dashboard');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('usuarios', Manager::class)->middleware(['auth', 'master'])->name('users');

require __DIR__.'/auth.php';
