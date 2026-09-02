<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

/**
 * @property int $id
 * @property int $equipo_id
 * @property int|null $empresa_id
 * @property string $tipo
 * @property string $estado
 * @property string|null $prioridad
 * @property Carbon $fecha_programada
 * @property Carbon|null $fecha_ejecucion
 * @property string|null $tecnico
 * @property bool $presenta_novedad
 * @property string|null $novedad
 * @property array<string, bool>|null $subtareas
 * @property array<string, string>|null $accesorios_estado
 */
#[Fillable([
    'equipo_id', 'empresa_id', 'tipo', 'estado', 'prioridad',
    'fecha_programada', 'fecha_ejecucion', 'tecnico',
    'motivo', 'descripcion', 'repuestos', 'observaciones', 'costo',
    'presenta_novedad', 'novedad',
    'subtareas', 'accesorios_estado',
])]
class Mantenimiento extends Model
{
    use SoftDeletes;

    protected $table = 'mantenimientos';

    /**
     * Los dos tipos de mantenimiento que se asignan a un equipo: el preventivo
     * es la rutina programada, el correctivo atiende una falla reportada.
     *
     * @var array<string, string>
     */
    public const TIPOS = [
        'preventivo' => 'Preventivo',
        'correctivo' => 'Correctivo',
    ];

    /** @var array<string, string> */
    public const ESTADOS = [
        'programado' => 'Programado',
        'en_proceso' => 'En proceso',
        'ejecutado' => 'Ejecutado',
        'cancelado' => 'Cancelado',
    ];

    /**
     * Estados en los que el mantenimiento sigue pendiente de cerrarse.
     *
     * @var list<string>
     */
    public const ESTADOS_ABIERTOS = ['programado', 'en_proceso'];

    /** @var list<string> */
    public const PRIORIDADES = ['Alta', 'Media', 'Baja'];

    protected function casts(): array
    {
        return [
            'fecha_programada' => 'date',
            'fecha_ejecucion' => 'date',
            'costo' => 'decimal:2',
            'presenta_novedad' => 'boolean',
            'subtareas' => 'array',
            'accesorios_estado' => 'array',
        ];
    }

    /** @return BelongsTo<Equipo, $this> */
    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    /** @return BelongsTo<Empresa, $this> */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /** @return HasOne<Reporte, $this> */
    public function reporte(): HasOne
    {
        return $this->hasOne(Reporte::class);
    }

    /**
     * Mantenimientos todavía pendientes de ejecutarse o cerrarse.
     *
     * @param  Builder<self>  $consulta
     */
    public function scopeAbiertos(Builder $consulta): void
    {
        $consulta->whereIn('estado', self::ESTADOS_ABIERTOS);
    }

    /**
     * Mantenimientos pendientes cuya fecha programada ya pasó.
     *
     * @param  Builder<self>  $consulta
     */
    public function scopeVencidos(Builder $consulta): void
    {
        $consulta->abiertos()->whereDate('fecha_programada', '<', Date::today());
    }

    /**
     * Correctivos abiertos hace más de los días indicados, contados desde su
     * fecha programada. Son las órdenes que se quedaron sin cerrar.
     *
     * @param  Builder<self>  $consulta
     */
    public function scopeEstancados(Builder $consulta, ?int $dias = null): void
    {
        $dias ??= (int) config('panel.umbrales.correctivo_estancado', 15);

        $consulta->where('tipo', 'correctivo')
            ->abiertos()
            ->whereDate('fecha_programada', '<', Date::today()->subDays($dias));
    }

    /**
     * Órdenes programadas dentro del mes al que pertenece la fecha dada.
     *
     * @param  Builder<self>  $consulta
     */
    public function scopeProgramadosEnElMes(Builder $consulta, CarbonInterface $mes): void
    {
        // La comparación va por fecha y no por el valor crudo de la columna: en
        // SQLite el campo guarda «2026-09-30 00:00:00», que en una comparación
        // de cadenas queda por encima de «2026-09-30» y dejaría fuera el último
        // día de cada mes.
        $consulta
            ->whereDate('fecha_programada', '>=', $mes->copy()->startOfMonth()->toDateString())
            ->whereDate('fecha_programada', '<=', $mes->copy()->endOfMonth()->toDateString());
    }

    /**
     * Código legible de la orden: MP para preventivo, MC para correctivo.
     */
    public function codigo(): string
    {
        $prefijo = $this->tipo === 'correctivo' ? 'MC' : 'MP';

        return $prefijo.'-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function tipoEtiqueta(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function estadoEtiqueta(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function estaAbierto(): bool
    {
        return in_array($this->estado, self::ESTADOS_ABIERTOS, true);
    }

    public function estaVencido(): bool
    {
        return $this->estaAbierto() && $this->fecha_programada->startOfDay()->lt(Date::today());
    }

    /**
     * Días que faltan para la fecha programada; negativo si ya pasó.
     */
    public function diasRestantes(): int
    {
        return (int) Date::today()->diffInDays($this->fecha_programada->startOfDay(), false);
    }
}
