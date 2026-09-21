<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión de un usuario que ya fue desactivado.
 *
 * Los usuarios no se borran: el nombre del técnico sigue colgando de cada orden
 * que ejecutó y de cada reporte firmado. Desactivar es la forma de quitarle el
 * acceso sin romper ese historial, y esto es lo que hace que la casilla sirva
 * también para la sesión que ya estaba abierta.
 */
class UsuarioActivo
{
    public function handle(Request $peticion, Closure $siguiente): Response
    {
        $usuario = $peticion->user();

        if ($usuario instanceof User && ! $usuario->activo) {
            Auth::logout();

            $peticion->session()->invalidate();
            $peticion->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Esta cuenta está desactivada. Comunícate con el administrador.',
            ]);
        }

        return $siguiente($peticion);
    }
}
