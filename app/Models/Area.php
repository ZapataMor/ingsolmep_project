<?php

namespace App\Models;

use App\Contracts\RestringiblePorRol;
use App\Models\Scopes\Visibilidad;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $empresa_id
 * @property string $nombre
 */
#[Fillable(['empresa_id', 'nombre'])]
#[ScopedBy(Visibilidad::class)]
class Area extends Model implements RestringiblePorRol
{
    protected $table = 'areas';

    /** @return BelongsTo<Empresa, $this> */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /** @return HasMany<Equipo, $this> */
    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    /**
     * El área hereda la visibilidad de su institución: la subconsulta ya sale
     * recortada por el scope global de Empresa.
     */
    public function restringirVisibilidad(Builder $consulta, User $usuario): void
    {
        $consulta->whereIn(
            $this->qualifyColumn('empresa_id'),
            Empresa::query()->select('empresas.id'),
        );
    }
}
