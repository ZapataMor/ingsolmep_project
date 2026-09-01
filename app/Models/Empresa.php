<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nombre
 * @property string|null $nit
 * @property string|null $ciudad
 * @property bool $activo
 */
#[Fillable(['nombre', 'nit', 'ciudad', 'direccion', 'telefono', 'activo'])]
class Empresa extends Model
{
    protected $table = 'empresas';

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
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
}
