<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $descripcion
 * @property string|null $numero_serie
 * @property bool $activo
 * @property Carbon|null $garantia_vence
 * @property Carbon|null $ultimo_mantenimiento
 * @property array<string, bool>|null $subtareas
 * @property array<string, string>|null $accesorios_estado
 */
#[Fillable([
    'empresa_id', 'area_id', 'marca_id', 'modelo_id',
    'descripcion', 'numero_serie', 'registro_invima', 'clasificacion_riesgo',
    'clasificacion_especialidad', 'fabricante', 'pais_origen', 'telefono_fabricante',
    'tipo_adquisicion', 'prioridad', 'observaciones_tecnicas', 'observaciones_generales',
    'foto_path', 'mantenimiento', 'activo', 'estado_operativo', 'garantia_vence', 'ultimo_mantenimiento',
    'suministro_electrico', 'voltaje', 'amperaje', 'frecuencia', 'corriente', 'potencia',
    'voltios', 'temperatura', 'presion', 'peso', 'velocidad', 'tecnologia_predominante',
    'subtareas', 'accesorios_estado', 'componentes', 'observaciones_ot',
])]
class Equipo extends Model
{
    use SoftDeletes;

    protected $table = 'equipos';

    /**
     * Subtareas de mantenimiento que se marcan por defecto para el equipo.
     *
     * @var array<string, string>
     */
    public const SUBTAREAS = [
        'prueba_funcionamiento' => 'Prueba de funcionamiento',
        'desarmado_limpieza' => 'Desarmado y limpieza',
        'prueba_fugas' => 'Prueba de fugas',
        'revision_alarma' => 'Revisión de alarma',
        'revision_conectores' => 'Revisión de conectores',
        'ajuste_sistema_electronico' => 'Rev. y ajuste sistema electrónico',
        'ajuste_sistema_electrico' => 'Rev. y ajuste sistema eléctrico',
        'limpieza_tarjetas' => 'Limpieza tarjetas electrónicas',
        'ajuste_extractores' => 'Ajuste y limpieza de extractores',
        'limpieza_ajuste_mecanicos' => 'Limpieza y ajuste mecánicos',
        'revision_panel_control' => 'Revisión panel de control',
        'limpieza_filtros' => 'Limpieza y revisión de filtros',
        'revision_sistema_neumatico' => 'Revisión sistema neumático',
        'ajuste_pieza_mano' => 'Ajuste y limpieza pieza de mano',
        'cambio_accesorios' => 'Cambio de accesorios',
    ];

    /**
     * Accesorios cuyo estado (B/R/M) se evalúa en cada mantenimiento.
     *
     * @var array<string, string>
     */
    public const ACCESORIOS = [
        'cable_ac' => 'Cable de AC',
        'cable_ecg' => 'Cable ECG',
        'sensor_spo2' => 'Sensor de SpO2',
        'manguera_nibp' => 'Manguera NIBP',
        'brazalete' => 'Brazalete',
        'control' => 'Control',
        'chupas_precordiales' => 'Chupas precordiales',
        'pieza_mano' => 'Pieza de mano',
        'transductor' => 'Transductor',
        'sensor_temperatura' => 'Sensor de temperatura',
        'sensor_oxigeno' => 'Sensor de oxígeno',
        'manguera_oxigeno' => 'Manguera de oxígeno',
        'manguera_aire' => 'Manguera de aire',
        'pala_ekg' => 'Pala EKG',
        'bateria' => 'Batería',
    ];

    /** @var array<string, string> */
    public const ESTADOS_ACCESORIO = [
        'B' => 'Bueno',
        'R' => 'Regular',
        'M' => 'Malo',
    ];

    /**
     * Si el equipo está prestando servicio. Es un eje distinto de `activo`:
     * un equipo dado de baja salió del inventario, uno fuera de servicio sigue
     * en él y es un problema abierto en la institución.
     *
     * @var array<string, string>
     */
    public const ESTADOS_OPERATIVOS = [
        'operativo' => 'Operativo',
        'fuera_servicio' => 'Fuera de servicio',
        'dado_baja' => 'Dado de baja',
    ];

    /** @var array<string, string> */
    public const RIESGOS = [
        'I' => 'I — Bajo riesgo',
        'IIA' => 'IIA — Riesgo moderado',
        'IIB' => 'IIB — Riesgo alto',
        'III' => 'III — Riesgo muy alto',
    ];

    /** @var list<string> */
    public const TIPOS_ADQUISICION = ['Compra', 'Comodato', 'Alquiler', 'Donación', 'Leasing'];

    /** @var list<string> */
    public const PRIORIDADES = ['Alta', 'Media', 'Baja'];

    /** @var array<string, string> */
    public const SUMINISTROS = [
        'ac' => 'AC (Corriente alterna)',
        'dc' => 'DC (Corriente continua)',
        'mixto' => 'Mixto (AC/DC)',
        'na' => 'N/A (No eléctrico)',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'garantia_vence' => 'date',
            'ultimo_mantenimiento' => 'date',
            'subtareas' => 'array',
            'accesorios_estado' => 'array',
        ];
    }

    /** @return BelongsTo<Empresa, $this> */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /** @return BelongsTo<Area, $this> */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /** @return BelongsTo<Marca, $this> */
    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    /** @return BelongsTo<Modelo, $this> */
    public function modelo(): BelongsTo
    {
        return $this->belongsTo(Modelo::class);
    }

    /** @return HasMany<Mantenimiento, $this> */
    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class);
    }

    /**
     * Equipos vigentes en el inventario. No dice nada sobre si están prestando
     * servicio: eso lo responde `estado_operativo`.
     *
     * @param  Builder<self>  $consulta
     */
    public function scopeActivos(Builder $consulta): void
    {
        $consulta->where('activo', true);
    }

    /**
     * Equipos parados. Cada uno es una institución con un problema abierto.
     *
     * @param  Builder<self>  $consulta
     */
    public function scopeFueraDeServicio(Builder $consulta): void
    {
        $consulta->activos()->where('estado_operativo', 'fuera_servicio');
    }

    /**
     * Equipos donde un preventivo ejecutado dejó una novedad reportada y
     * después nadie abrió un correctivo para atenderla.
     *
     * La novedad se ancla en la fecha de ejecución del preventivo, que es
     * cuando el técnico la encontró, y no en la de programación. Un correctivo
     * cancelado no cuenta como seguimiento: el hueco sigue igual de abierto.
     *
     * @param  Builder<self>  $consulta
     */
    public function scopeConNovedadPendiente(Builder $consulta): void
    {
        $consulta->activos()->whereHas('mantenimientos', function (Builder $preventivo) {
            $preventivo
                ->where('tipo', 'preventivo')
                ->where('presenta_novedad', true)
                ->where('estado', 'ejecutado')
                ->whereNotExists(function (QueryBuilder $seguimiento) {
                    $seguimiento
                        ->selectRaw('1')
                        ->from('mantenimientos as seguimiento')
                        ->whereColumn('seguimiento.equipo_id', 'mantenimientos.equipo_id')
                        ->whereNull('seguimiento.deleted_at')
                        ->where('seguimiento.tipo', 'correctivo')
                        ->where('seguimiento.estado', '<>', 'cancelado')
                        ->whereRaw('seguimiento.fecha_programada >= COALESCE(mantenimientos.fecha_ejecucion, mantenimientos.fecha_programada)');
                });
        });
    }

    /**
     * Garantías que vencen dentro de la ventana indicada. El límite inferior es
     * hoy a propósito: una garantía ya vencida no es una oportunidad de ahorro,
     * es historia.
     *
     * @param  Builder<self>  $consulta
     */
    public function scopeGarantiaPorVencer(Builder $consulta, ?int $dias = null): void
    {
        $dias ??= (int) config('panel.umbrales.garantia_por_vencer', 60);

        $consulta->activos()->whereBetween('garantia_vence', [
            Date::today()->toDateString(),
            Date::today()->addDays($dias)->toDateString(),
        ]);
    }

    /**
     * Equipos fuera de la rutina: sin mantenimiento ejecutado en el plazo
     * indicado, o sin ninguno registrado nunca.
     *
     * @param  Builder<self>  $consulta
     */
    public function scopeSinMantenimiento(Builder $consulta, ?int $dias = null): void
    {
        $dias ??= (int) config('panel.umbrales.sin_mantenimiento', 180);
        $limite = Date::today()->subDays($dias)->toDateString();

        $consulta->activos()->where(function (Builder $sin) use ($limite) {
            $sin->whereNull('ultimo_mantenimiento')
                ->orWhereDate('ultimo_mantenimiento', '<', $limite);
        });
    }

    /**
     * Equipos a los que les falta alguno de los tres datos sin los que no se
     * puede emitir una hoja de vida: serie, área o registro INVIMA.
     *
     * Se comprueba también la cadena vacía además del nulo: parte del inventario
     * entró por importación del sistema anterior y trae campos en blanco.
     *
     * @param  Builder<self>  $consulta
     */
    public function scopeDatosIncompletos(Builder $consulta): void
    {
        $consulta->activos()->where(function (Builder $incompleto) {
            $incompleto->whereNull('numero_serie')
                ->orWhere('numero_serie', '')
                ->orWhereNull('area_id')
                ->orWhereNull('registro_invima')
                ->orWhere('registro_invima', '');
        });
    }

    /**
     * URL pública de la foto, o null si el equipo no tiene una cargada.
     */
    public function fotoUrl(): ?string
    {
        return $this->foto_path ? Storage::disk('public')->url($this->foto_path) : null;
    }

    /**
     * Iniciales de la descripción, usadas como respaldo cuando no hay foto.
     */
    public function iniciales(): string
    {
        $palabras = preg_split('/\s+/', trim($this->descripcion)) ?: [];

        return mb_strtoupper(mb_substr($palabras[0] ?? '?', 0, 1).mb_substr($palabras[1] ?? '', 0, 1));
    }
}
