<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Date;

/**
 * Constancia de que la orden de mantenimiento ya tiene su reporte emitido.
 *
 * El documento no se archiva: se vuelve a componer desde la orden cada vez que
 * se abre. Lo que se guarda aquí es el hecho de haberlo generado, con su
 * trazabilidad, que es lo que alimenta el listado del módulo de reportes.
 *
 * @property int $id
 * @property int $mantenimiento_id
 * @property int|null $empresa_id
 * @property string $tipo
 * @property int|null $generado_por
 * @property int|null $ultimo_generado_por
 * @property CarbonImmutable $generado_en
 * @property CarbonImmutable $ultima_generacion
 * @property int $veces_generado
 */
#[Fillable([
    'mantenimiento_id', 'empresa_id', 'tipo',
    'generado_por', 'ultimo_generado_por',
    'generado_en', 'ultima_generacion', 'veces_generado',
])]
class Reporte extends Model
{
    protected $table = 'reportes';

    protected function casts(): array
    {
        return [
            'generado_en' => 'datetime',
            'ultima_generacion' => 'datetime',
            'veces_generado' => 'integer',
        ];
    }

    /** @return BelongsTo<Mantenimiento, $this> */
    public function mantenimiento(): BelongsTo
    {
        return $this->belongsTo(Mantenimiento::class);
    }

    /** @return BelongsTo<Empresa, $this> */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /** @return BelongsTo<User, $this> */
    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por');
    }

    /** @return BelongsTo<User, $this> */
    public function ultimoGeneradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ultimo_generado_por');
    }

    /**
     * Deja constancia de la emisión. Si la orden ya tenía reporte se actualiza
     * la última generación en lugar de añadir otra fila al listado.
     */
    public static function registrar(Mantenimiento $mantenimiento, ?User $usuario = null): self
    {
        $reporte = static::firstOrNew(['mantenimiento_id' => $mantenimiento->id]);

        if (! $reporte->exists) {
            $reporte->generado_en = Date::now();
            $reporte->generado_por = $usuario?->id;
            $reporte->veces_generado = 0;
        }

        // El tipo y la empresa se refrescan: la orden pudo editarse entre una
        // generación y la siguiente.
        $reporte->empresa_id = $mantenimiento->empresa_id;
        $reporte->tipo = $mantenimiento->tipo;
        $reporte->ultima_generacion = Date::now();
        $reporte->ultimo_generado_por = $usuario?->id;
        $reporte->veces_generado++;
        $reporte->save();

        return $reporte;
    }

    /**
     * Reportes emitidos dentro del mes corriente.
     *
     * @param  Builder<self>  $consulta
     */
    public function scopeDelMes(Builder $consulta): void
    {
        $consulta->where('ultima_generacion', '>=', Date::now()->startOfMonth());
    }

    /**
     * Código legible del reporte, independiente del de la orden que documenta.
     */
    public function codigo(): string
    {
        return 'RP-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function tipoEtiqueta(): string
    {
        return Mantenimiento::TIPOS[$this->tipo] ?? $this->tipo;
    }

    /** Se ha emitido más de una vez. */
    public function fueReemitido(): bool
    {
        return $this->veces_generado > 1;
    }
}
