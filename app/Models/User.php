<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string|null $username
 * @property string $rol
 * @property int|null $empresa_id
 * @property string $email
 * @property string|null $telefono
 * @property bool $activo
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'username', 'rol', 'empresa_id', 'email', 'telefono', 'activo', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Los tres perfiles con los que se entra al sistema.
     *
     * El administrador es INGSOLMEP y lo ve todo. La institución es la IPS
     * cliente: entra a consultar los equipos que se le asignaron y el estado de
     * sus mantenimientos, nada más. El técnico es quien ejecuta las órdenes, y
     * sólo ve las que llevan su nombre.
     *
     * @var array<string, string>
     */
    public const ROLES = [
        'administrador' => 'Administrador',
        'institucion' => 'Institución',
        'tecnico' => 'Técnico',
    ];

    /**
     * Descripción de cada rol, para la pantalla de gestión.
     *
     * @var array<string, string>
     */
    public const DESCRIPCION_ROLES = [
        'administrador' => 'Acceso completo: inventario, empresas, asignación de mantenimientos, reportes y usuarios.',
        'institucion' => 'Consulta los equipos, mantenimientos y reportes de su propia institución.',
        'tecnico' => 'Ejecuta y cierra los mantenimientos que se le asignan, y emite sus reportes.',
    ];

    /** Icono con el que se distingue cada rol en la interfaz. */
    public const ICONO_ROLES = [
        'administrador' => 'shield-check',
        'institucion' => 'building-office-2',
        'tecnico' => 'wrench-screwdriver',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    // ------------------------------------------------------------------
    // Relaciones
    // ------------------------------------------------------------------

    /**
     * Institución por la que entra el usuario. Sólo la tiene el rol
     * «institucion»; en los demás es null.
     *
     * @return BelongsTo<Empresa, $this>
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Órdenes de las que este usuario es el técnico responsable.
     *
     * @return HasMany<Mantenimiento, $this>
     */
    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class, 'tecnico_id');
    }

    // ------------------------------------------------------------------
    // Consultas
    // ------------------------------------------------------------------

    /**
     * Usuarios de un rol dado que todavía pueden entrar.
     *
     * @param  Builder<self>  $consulta
     */
    public function scopeDelRol(Builder $consulta, string $rol): void
    {
        $consulta->where('rol', $rol);
    }

    /** @param  Builder<self>  $consulta */
    public function scopeActivos(Builder $consulta): void
    {
        $consulta->where('activo', true);
    }

    /**
     * Técnicos disponibles para asignarles una orden.
     *
     * @param  Builder<self>  $consulta
     */
    public function scopeTecnicos(Builder $consulta): void
    {
        $consulta->delRol('tecnico')->activos()->orderBy('name');
    }

    // ------------------------------------------------------------------
    // Rol
    // ------------------------------------------------------------------

    public function esAdministrador(): bool
    {
        return $this->rol === 'administrador';
    }

    public function esInstitucion(): bool
    {
        return $this->rol === 'institucion';
    }

    public function esTecnico(): bool
    {
        return $this->rol === 'tecnico';
    }

    public function rolEtiqueta(): string
    {
        return self::ROLES[$this->rol] ?? $this->rol;
    }

    /**
     * Discriminante del alcance de datos del usuario, para separar en la caché
     * lo que cada perfil ve. Dos usuarios de la misma IPS comparten resultado;
     * un técnico no comparte con nadie.
     */
    public function claveVisibilidad(): string
    {
        return match ($this->rol) {
            'institucion' => 'empresa:'.($this->empresa_id ?? 0),
            'tecnico' => 'tecnico:'.$this->id,
            default => 'todo',
        };
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
