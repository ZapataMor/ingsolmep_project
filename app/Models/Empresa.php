<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
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
class Empresa extends Model
{
    use SoftDeletes;

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

    /** @return HasMany<Mantenimiento, $this> */
    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class);
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
