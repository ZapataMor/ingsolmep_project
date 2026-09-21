<?php

namespace App\Models;

use App\Contracts\RestringiblePorRol;
use App\Models\Scopes\Visibilidad;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $nombre
 * @property string|null $nit
 * @property string|null $email
 * @property string|null $ciudad
 * @property string|null $direccion
 * @property string|null $telefono
 * @property string|null $celular
 * @property string|null $whatsapp
 * @property string|null $logo_path
 * @property bool $activo
 */
#[Fillable([
    'nombre', 'nit', 'email', 'ciudad', 'direccion',
    'telefono', 'celular', 'whatsapp', 'logo_path', 'activo',
])]
#[ScopedBy(Visibilidad::class)]
class Empresa extends Model implements RestringiblePorRol
{
    use SoftDeletes;

    protected $table = 'empresas';

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    /**
     * Usuarios que entran al sistema por esta institución.
     *
     * @return HasMany<User, $this>
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Area, $this> */
    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    /** @return HasMany<Equipo, $this> */
    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    /** @return HasMany<Mantenimiento, $this> */
    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class);
    }

    /**
     * La institución sólo se ve a sí misma. El técnico ve aquellas en las que
     * tiene trabajo asignado, que es lo que necesita para ubicar una orden.
     */
    public function restringirVisibilidad(Builder $consulta, User $usuario): void
    {
        match (true) {
            $usuario->esInstitucion() => $consulta->whereKey($usuario->empresa_id ?? 0),
            $usuario->esTecnico() => $consulta->whereIn($this->qualifyColumn('id'), Mantenimiento::query()
                ->withoutGlobalScope(Visibilidad::class)
                ->where('mantenimientos.tecnico_id', $usuario->id)
                ->whereNotNull('mantenimientos.empresa_id')
                ->select('mantenimientos.empresa_id')),
            // Un rol que todavía no sabemos leer no ve nada: es el lado seguro.
            default => $consulta->whereRaw('1 = 0'),
        };
    }

    /**
     * Instituciones que tienen órdenes programadas para el mes dado y todavía
     * no han ejecutado ninguna de ellas: el cronograma del mes sin arrancar.
     *
     * @param  Builder<self>  $consulta
     */
    public function scopeCronogramaSinIniciar(Builder $consulta, CarbonInterface $mes): void
    {
        // Se cruza contra `mantenimientos.empresa_id`, que está denormalizado
        // justo para esto, en lugar de dos EXISTS correlacionados.
        $delMes = fn (): Builder => Mantenimiento::query()
            ->programadosEnElMes($mes)
            ->whereNotNull('empresa_id');

        $consulta
            ->whereIn('id', $delMes()->select('empresa_id'))
            // El `whereNotNull` de arriba no es cosmético: un NULL dentro de un
            // `NOT IN` deja la comparación indefinida y la consulta no
            // devolvería ninguna fila.
            ->whereNotIn('id', $delMes()->where('estado', 'ejecutado')->select('empresa_id'));
    }

    /**
     * URL pública del logo, o null si la empresa no tiene uno cargado.
     */
    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    /**
     * Iniciales del nombre, usadas como respaldo cuando no hay logo.
     */
    public function iniciales(): string
    {
        $palabras = preg_split('/\s+/', trim($this->nombre)) ?: [];

        return mb_strtoupper(mb_substr($palabras[0] ?? '?', 0, 1).mb_substr($palabras[1] ?? '', 0, 1));
    }

    /**
     * Enlace directo de WhatsApp, o null si no hay número registrado.
     */
    public function whatsappUrl(): ?string
    {
        $numero = preg_replace('/\D/', '', (string) $this->whatsapp);

        return $numero ? 'https://wa.me/'.$numero : null;
    }
}
