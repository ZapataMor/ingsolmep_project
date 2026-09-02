<?php

namespace App\Http\Controllers;

use App\Models\Mantenimiento;
use App\Models\Reporte;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Documento imprimible de una orden de mantenimiento ya ejecutada.
 */
class ReporteMantenimientoController extends Controller
{
    public function __invoke(Request $peticion, Mantenimiento $mantenimiento): View
    {
        // El reporte certifica un trabajo hecho: mientras la orden siga abierta
        // o cancelada no hay nada que reportar y el documento no existe.
        abort_unless($mantenimiento->estado === 'ejecutado', 404);

        $mantenimiento->load([
            'equipo.marca', 'equipo.modelo', 'equipo.area', 'equipo.empresa', 'empresa',
        ]);

        // Emitir el documento es lo que da por generado el reporte: aquí queda
        // constancia, y con ella la fila del módulo de reportes.
        Reporte::registrar($mantenimiento, $peticion->user());

        return view('reportes.mantenimiento', ['mantenimiento' => $mantenimiento]);
    }
}
