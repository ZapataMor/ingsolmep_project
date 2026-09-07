<?php

use App\Http\Controllers\ReporteMantenimientoController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified', 'activo'])->group(function () {
    // Pantalla de aterrizaje: lo que necesita atención hoy, no el inventario.
    // Cada perfil ve la suya, porque las consultas salen ya recortadas por el
    // scope global de visibilidad.
    Route::livewire('panel', 'pages::panel.index')->name('panel');

    Route::livewire('equipos', 'pages::equipos.index')->name('equipos.index');
    Route::livewire('mantenimientos', 'pages::mantenimientos.index')->name('mantenimientos.index');
    Route::livewire('reportes', 'pages::reportes.index')->name('reportes.index');

    // El directorio de clientes y las cuentas de acceso son cosa de INGSOLMEP:
    // no son pantallas recortadas para los demás perfiles, son pantallas que no
    // les corresponden.
    Route::livewire('empresas', 'pages::empresas.index')
        ->middleware('can:gestionar-empresas')
        ->name('empresas.index');

    Route::livewire('usuarios', 'pages::usuarios.index')
        ->middleware('can:gestionar-usuarios')
        ->name('usuarios.index');

    // Documento imprimible de una orden ejecutada, que se abre en su propia
    // pestaña para guardarse como PDF desde el navegador. Quien no pueda ver la
    // orden recibe un 404 en el enlace directo: el scope global no la encuentra.
    Route::get('mantenimientos/{mantenimiento}/reporte', ReporteMantenimientoController::class)
        ->name('mantenimientos.reporte');
});

require __DIR__.'/settings.php';
