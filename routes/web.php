<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('equipos', 'pages::equipos.index')->name('equipos.index');
    Route::livewire('empresas', 'pages::empresas.index')->name('empresas.index');
});

require __DIR__.'/settings.php';
