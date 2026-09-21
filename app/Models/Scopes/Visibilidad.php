<?php

namespace App\Models\Scopes;

use App\Contracts\RestringiblePorRol;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Recorta cada consulta a lo que el usuario autenticado puede ver.
 *
 * Va como scope global y no como filtro en cada pantalla a propósito: el
 * aislamiento entre instituciones no puede depender de que alguien se acuerde
 * de añadir un `where`. Un módulo nuevo, un conteo del panel o un enlace
 * profundo con el id de otra IPS quedan cubiertos sin tocar nada.
 *
 * Sin sesión —consola, seeders, migraciones— no recorta nada.
 *
 * @implements Scope<Model>
 */
class Visibilidad implements Scope
{
    /**
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $usuario = Auth::user();

        if (! $usuario instanceof User || $usuario->esAdministrador()) {
            return;
        }

        if (! $model instanceof RestringiblePorRol) {
            return;
        }

        $model->restringirVisibilidad($builder, $usuario);
    }
}
