<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nombre
 */
#[Fillable(['nombre'])]
class Marca extends Model
{
    protected $table = 'marcas';

    /** @return HasMany<Modelo, $this> */
    public function modelos(): HasMany
    {
        return $this->hasMany(Modelo::class);
    }

    /** @return HasMany<Equipo, $this> */
    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }
}
