<?php

namespace App\Contracts;

use App\Models\Scopes\Visibilidad;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modelo cuyo contenido no es el mismo para todos los perfiles.
 *
 * Cada uno declara aquí qué parte de su tabla le corresponde ver a un usuario
 * que no es administrador. El recorte lo aplica el scope global
 * {@see Visibilidad} a toda consulta, para que ninguna
 * pantalla tenga que acordarse de filtrar.
 */
interface RestringiblePorRol
{
    /**
     * Acota la consulta a lo que el usuario tiene derecho a ver.
     *
     * Nunca se llama con un administrador: ese caso lo resuelve el scope antes.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $consulta
     */
    public function restringirVisibilidad(Builder $consulta, User $usuario): void;
}
