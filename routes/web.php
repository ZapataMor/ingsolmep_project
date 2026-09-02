<?php

use App\Http\Controllers\ReporteMantenimientoController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Pantalla de aterrizaje: lo que necesita atención hoy, no el inventario.
    Route::livewire('panel', 'pages::panel.index')->name('panel');

    Route::livewire('equipos', 'pages::equipos.index')->name('equipos.index');
    Route::livewire('empresas', 'pages::empresas.index')->name('empresas.index');
    Route::livewire('mantenimientos', 'pages::mantenimientos.index')->name('mantenimientos.index');
    Route::livewire('reportes', 'pages::reportes.index')->name('reportes.index');

    // Documento imprimible de una orden ejecutada, que se abre en su propia
    // pestaña para guardarse como PDF desde el navegador.
    Route::get('mantenimientos/{mantenimiento}/reporte', ReporteMantenimientoController::class)
        ->name('mantenimientos.reporte');
});

require __DIR__.'/settings.php';
