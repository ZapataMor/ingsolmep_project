<?php

use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Panel principal.
 *
 * No responde «cuántas cosas hay» sino «qué necesita mi atención hoy». Cada
 * cifra de la pantalla es un enlace a su listado con el filtro ya puesto, y
 * cada una se calcula con el mismo scope que ese listado aplica: así el número
 * del panel y el del listado son el mismo por construcción, no por casualidad.
 *
 * Nada se guarda: no hay tabla de notificaciones con estado leído. Cada bloque
 * es su propia consulta con caché corta, para que una consulta lenta no arrastre
 * a las demás.
 */
new #[Title('Panel')] class extends Component {
    /**
     * Las gráficas y la tabla de reposición sólo se piden después del primer
     * pintado. Son las consultas más pesadas y no deben retrasar la bandeja,
     * que es lo que el usuario viene a mirar todos los días.
     */
    public bool $analisisPedido = false;

    /**
     * Abreviaturas de los meses para el eje de la gráfica.
     *
     * @var array<int, string>
     */
    public const MESES = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
        7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
    ];

    public function cargarAnalisis(): void
    {
        $this->analisisPedido = true;
    }

    // ------------------------------------------------------------------
    // Indicadores principales
    // ------------------------------------------------------------------

    /**
     * Cumplimiento del cronograma del mes y órdenes ejecutadas, con el mes
     * anterior como referencia.
     *
     * Los cuatro números salen de una sola agregación sobre una ventana de dos
     * meses. Separarlos en cuatro consultas sería leer dos veces el mismo rango.
     *
     * @return array{
     *     programadas: int, ejecutadas: int, porcentaje: int,
     *     programadasAnterior: int, ejecutadasAnterior: int, porcentajeAnterior: int,
     *     variacionEjecutadas: ?int
     * }
     */
    #[Computed]
    public function cronograma(): array
    {
        return $this->recordar('cronograma', function (): array {
            $inicioActual = $this->hoy()->startOfMonth()->toDateString();

            $fila = Mantenimiento::query()
                ->whereBetween('fecha_programada', [
                    $this->hoy()->subMonth()->startOfMonth()->toDateString(),
                    $this->hoy()->endOfMonth()->toDateString(),
                ])
                ->selectRaw(
                    'SUM(fecha_programada >= ?) as prog_actual,'
                    .' SUM(fecha_programada >= ? AND estado = ?) as ejec_actual,'
                    .' SUM(fecha_programada < ?) as prog_anterior,'
                    .' SUM(fecha_programada < ? AND estado = ?) as ejec_anterior',
                    [$inicioActual, $inicioActual, 'ejecutado', $inicioActual, $inicioActual, 'ejecutado'],
                )
                ->first();

            $programadas = (int) ($fila->prog_actual ?? 0);
            $ejecutadas = (int) ($fila->ejec_actual ?? 0);
            $programadasAnterior = (int) ($fila->prog_anterior ?? 0);
            $ejecutadasAnterior = (int) ($fila->ejec_anterior ?? 0);

            return [
                'programadas' => $programadas,
                'ejecutadas' => $ejecutadas,
                'porcentaje' => $this->porcentaje($ejecutadas, $programadas),
                'programadasAnterior' => $programadasAnterior,
                'ejecutadasAnterior' => $ejecutadasAnterior,
                'porcentajeAnterior' => $this->porcentaje($ejecutadasAnterior, $programadasAnterior),
                'variacionEjecutadas' => $ejecutadasAnterior === 0
                    ? null
                    : (int) round(($ejecutadas - $ejecutadasAnterior) / $ejecutadasAnterior * 100),
            ];
        });
    }

    /**
     * Órdenes pendientes cuya fecha programada ya pasó. Es lo único de la
     * pantalla que exige actuar hoy, así que caduca mucho antes que el resto.
     */
    #[Computed]
    public function vencidas(): int
    {
        return Cache::remember(
            $this->clave('vencidas'),
            (int) config('panel.cache.segundos_vencidas', 60),
            fn (): int => Mantenimiento::query()->vencidos()->count(),
        );
    }

    /** Equipos parados: cada uno es una institución con un problema abierto. */
    #[Computed]
    public function fueraDeServicio(): int
    {
        return $this->recordar('fuera_servicio', fn (): int => Equipo::query()->fueraDeServicio()->count());
    }

    // ------------------------------------------------------------------
    // Bandeja de atención
    // ------------------------------------------------------------------

    /**
     * Grupos que hoy piden una acción, ordenados por urgencia y no por
     * cantidad. Los vacíos se descartan: una bandeja con seis ceros enseña al
     * usuario a ignorarla.
     *
     * @return list<array{clave: string, titulo: string, detalle: string, conteo: int, url: string}>
     */
    #[Computed]
    public function bandeja(): array
    {
        return collect($this->gruposDeBandeja())
            ->filter(fn (array $grupo): bool => $grupo['conteo'] > 0)
            ->values()
            ->all();
    }

    /**
     * Definición de cada grupo. El conteo va en su propia consulta con su
     * propia caché: nada de un cálculo gigante que lo resuelva todo junto.
     *
     * @return list<array{clave: string, titulo: string, detalle: string, conteo: int, url: string}>
     */
    private function gruposDeBandeja(): array
    {
        $umbrales = config('panel.umbrales');

        $grupos = [
            [
                'clave' => 'novedad',
                'titulo' => 'Equipos con novedad reportada sin correctivo abierto',
                'detalle' => 'El técnico dejó constancia de un hallazgo y nadie abrió la orden de seguimiento.',
                'conteo' => $this->recordar(
                    'bandeja.novedad',
                    fn (): int => Equipo::query()->conNovedadPendiente()->count(),
                ),
                'url' => route('equipos.index', ['bandeja' => 'novedad']),
            ],
            [
                'clave' => 'estancados',
                'titulo' => 'Correctivos abiertos hace más de '.$umbrales['correctivo_estancado'].' días',
                'detalle' => 'Órdenes correctivas sin cerrar desde su fecha programada.',
                'conteo' => $this->recordar(
                    'bandeja.estancados',
                    fn (): int => Mantenimiento::query()->estancados()->count(),
                ),
                'url' => route('mantenimientos.index', ['bandeja' => 'estancados']),
            ],
            [
                'clave' => 'garantia',
                'titulo' => 'Garantías que vencen en los próximos '.$umbrales['garantia_por_vencer'].' días',
                'detalle' => 'Cobrar un mantenimiento de un equipo en garantía es regalar trabajo del fabricante.',
                'conteo' => $this->recordar(
                    'bandeja.garantia',
                    fn (): int => Equipo::query()->garantiaPorVencer()->count(),
                ),
                'url' => route('equipos.index', ['bandeja' => 'garantia']),
            ],
            [
                'clave' => 'sin_mantenimiento',
                'titulo' => 'Equipos sin mantenimiento hace más de 6 meses',
                'detalle' => 'Sin rutina ejecutada en los últimos '.$umbrales['sin_mantenimiento'].' días, o sin ninguna registrada.',
                'conteo' => $this->recordar(
                    'bandeja.sin_mantenimiento',
                    fn (): int => Equipo::query()->sinMantenimiento()->count(),
                ),
                'url' => route('equipos.index', ['bandeja' => 'sin_mantenimiento']),
            ],
        ];

        // El reclamo por un cronograma que no arranca sólo tiene sentido pasada
        // la mitad del mes: antes de esa fecha no hay retraso que reportar. La
        // comprobación va aquí fuera y no dentro de la caché, para no gastar la
        // consulta los primeros quince días.
        if ($this->hoy()->day > $umbrales['dia_corte_cronograma']) {
            $grupos[] = [
                'clave' => 'cronograma',
                'titulo' => 'Instituciones con el cronograma del mes sin iniciar',
                'detalle' => 'Tienen órdenes programadas para este mes y no han ejecutado ninguna.',
                'conteo' => $this->recordar(
                    'bandeja.cronograma',
                    fn (): int => Empresa::query()->cronogramaSinIniciar($this->hoy())->count(),
                ),
                'url' => route('empresas.index', ['bandeja' => 'cronograma']),
            ];
        }

        // Va de último: importante, pero no urgente.
        $grupos[] = [
            'clave' => 'incompletos',
            'titulo' => 'Equipos con datos incompletos',
            'detalle' => 'Sin número de serie, sin área asignada o sin registro INVIMA.',
            'conteo' => $this->recordar(
                'bandeja.incompletos',
                fn (): int => Equipo::query()->datosIncompletos()->count(),
            ),
            'url' => route('equipos.index', ['bandeja' => 'incompletos']),
        ];

        return $grupos;
    }

    // ------------------------------------------------------------------
    // Análisis diferido
    // ------------------------------------------------------------------

    /**
     * Órdenes por mes de los últimos doce, separadas en preventivos y
     * correctivos. Es la gráfica que demuestra si el preventivo está
     * conteniendo la falla.
     *
     * @return list<array{etiqueta: string, mes: string, preventivo: int, correctivo: int}>
     */
    #[Computed]
    public function ordenesPorMes(): array
    {
        return $this->recordar('ordenes_por_mes', function (): array {
            $desde = $this->hoy()->startOfMonth()->subMonths(11);

            $filas = Mantenimiento::query()
                ->whereBetween('fecha_programada', [
                    $desde->toDateString(),
                    $this->hoy()->endOfMonth()->toDateString(),
                ])
                // SUBSTR sobre la fecha en lugar de una función de calendario:
                // es la forma de agrupar por mes que entienden por igual MySQL
                // y el SQLite de las pruebas.
                ->selectRaw('SUBSTR(fecha_programada, 1, 7) as periodo, tipo, COUNT(*) as total')
                ->groupBy('periodo', 'tipo')
                ->get();

            $conteos = $filas->groupBy('periodo');
            $serie = [];

            for ($desplazamiento = 0; $desplazamiento < 12; $desplazamiento++) {
                $mes = $desde->addMonths($desplazamiento);
                $clave = $mes->format('Y-m');
                $delMes = $conteos->get($clave, collect());

                $serie[] = [
                    'mes' => $clave,
                    // Rótulo del eje desde una tabla fija y no desde el
                    // formateador de fechas: la gráfica no debe depender de que
                    // el locale del sistema tenga el español instalado.
                    'etiqueta' => self::MESES[(int) $mes->format('n')],
                    'preventivo' => (int) ($delMes->firstWhere('tipo', 'preventivo')->total ?? 0),
                    'correctivo' => (int) ($delMes->firstWhere('tipo', 'correctivo')->total ?? 0),
                ];
            }

            return $serie;
        });
    }

    /**
     * Cumplimiento del mes por institución, de menor a mayor: la que tiene
     * problemas aparece siempre arriba.
     *
     * @return list<array{id: int, nombre: string, programadas: int, ejecutadas: int, porcentaje: int}>
     */
    #[Computed]
    public function cumplimientoPorInstitucion(): array
    {
        return $this->recordar('cumplimiento_institucion', function (): array {
            return Mantenimiento::query()
                ->programadosEnElMes($this->hoy())
                ->join('empresas', 'empresas.id', '=', 'mantenimientos.empresa_id')
                ->selectRaw('empresas.id as empresa_id, empresas.nombre as nombre, COUNT(*) as programadas')
                ->selectRaw('SUM(mantenimientos.estado = ?) as ejecutadas', ['ejecutado'])
                ->groupBy('empresas.id', 'empresas.nombre')
                ->get()
                ->map(fn ($fila): array => [
                    'id' => (int) $fila->empresa_id,
                    'nombre' => $fila->nombre,
                    'programadas' => (int) $fila->programadas,
                    'ejecutadas' => (int) $fila->ejecutadas,
                    'porcentaje' => $this->porcentaje((int) $fila->ejecutadas, (int) $fila->programadas),
                ])
                ->sortBy(['porcentaje', 'nombre'])
                ->values()
                ->all();
        });
    }

    /**
     * Equipos con más correctivos en los últimos doce meses. Son los candidatos
     * a reposición: la recomendación que convierte a INGSOLMEP en asesor y no
     * sólo en proveedor de mantenimiento.
     *
     * Devuelve filas planas y no modelos: lo que va a la caché se serializa, y
     * un modelo Eloquent guardado hoy y leído tras un despliegue vuelve roto.
     *
     * @return list<array{id: int, descripcion: string, numero_serie: ?string, empresa: ?string, area: ?string, correctivos: int}>
     */
    #[Computed]
    public function equiposConMasCorrectivos(): array
    {
        return $this->recordar('reposicion', function (): array {
            return Equipo::query()
                ->join('mantenimientos', 'mantenimientos.equipo_id', '=', 'equipos.id')
                ->leftJoin('empresas', 'empresas.id', '=', 'equipos.empresa_id')
                ->leftJoin('areas', 'areas.id', '=', 'equipos.area_id')
                ->whereNull('mantenimientos.deleted_at')
                ->where('mantenimientos.tipo', 'correctivo')
                ->where('mantenimientos.fecha_programada', '>=', $this->hoy()->subYear()->toDateString())
                ->selectRaw('equipos.id as id, equipos.descripcion as descripcion, equipos.numero_serie as numero_serie')
                ->selectRaw('empresas.nombre as empresa, areas.nombre as area, COUNT(mantenimientos.id) as correctivos')
                ->groupBy('equipos.id', 'equipos.descripcion', 'equipos.numero_serie', 'empresas.nombre', 'areas.nombre')
                ->orderByDesc('correctivos')
                ->orderBy('equipos.descripcion')
                ->limit(10)
                ->get()
                ->map(fn ($fila): array => [
                    'id' => (int) $fila->id,
                    'descripcion' => (string) $fila->descripcion,
                    'numero_serie' => $fila->numero_serie,
                    'empresa' => $fila->empresa,
                    'area' => $fila->area,
                    'correctivos' => (int) $fila->correctivos,
                ])
                ->all();
        });
    }

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    /** Hoy en la zona del cliente, que es donde se decide qué está vencido. */
    private function hoy(): CarbonInterface
    {
        return Date::today();
    }

    /**
     * Clave de caché con el mes en curso dentro: al cambiar de mes, todo lo que
     * dependía del anterior queda invalidado solo.
     */
    private function clave(string $bloque): string
    {
        return 'panel:'.$this->hoy()->format('Y-m-d').':'.$bloque;
    }

    /**
     * @template TValor
     *
     * @param  \Closure(): TValor  $calculo
     * @return TValor
     */
    private function recordar(string $bloque, \Closure $calculo): mixed
    {
        return Cache::remember(
            $this->clave($bloque),
            (int) config('panel.cache.segundos', 300),
            $calculo,
        );
    }

    private function porcentaje(int $parte, int $total): int
    {
        return $total === 0 ? 0 : (int) round($parte / $total * 100);
    }

    /** Meta de cumplimiento acordada con el cliente. */
    #[Computed]
    public function meta(): int
    {
        return (int) config('panel.meta_cumplimiento', 95);
    }
}; ?>

<section class="eq-root w-full space-y-5" wire:init="cargarAnalisis">
    @php
        $cronograma = $this->cronograma;
        $meta = $this->meta;

        // El color del cumplimiento es el único juicio de valor de la pantalla:
        // sólo aparece cuando el número se aleja de la meta.
        $colorCumplimiento = match (true) {
            $cronograma['porcentaje'] >= $meta => 'text-emerald-600 dark:text-emerald-400',
            $cronograma['porcentaje'] >= $meta - 15 => 'text-amber-600 dark:text-amber-500',
            default => 'text-rose-600 dark:text-rose-400',
        };
    @endphp

    {{-- ═══════════════ 1. Indicadores principales ═══════════════ --}}
    <div class="grid gap-4 lg:grid-cols-4">
        {{-- Cumplimiento: el más importante, y por eso el más grande. --}}
        <a
            href="{{ route('mantenimientos.index', ['bandeja' => 'mes']) }}"
            wire:navigate
            class="eq-panel group flex flex-col justify-between gap-3 px-5 py-4 transition duration-200 hover:border-zinc-300 lg:col-span-2 dark:hover:border-zinc-700"
        >
            <p class="text-[11.5px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">
                Cumplimiento del cronograma
            </p>

            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <p class="text-5xl leading-none font-bold tabular-nums {{ $colorCumplimiento }}">
                    {{ $cronograma['porcentaje'] }}<span class="text-2xl font-semibold">%</span>
                </p>

                <p class="text-sm font-medium tabular-nums text-zinc-500 dark:text-zinc-400">
                    {{ $cronograma['ejecutadas'] }}/{{ $cronograma['programadas'] }} órdenes
                </p>
            </div>

            <p class="text-[12px] tabular-nums text-zinc-500 dark:text-zinc-400">
                Meta {{ $meta }} % · Mes anterior {{ $cronograma['porcentajeAnterior'] }} %
            </p>
        </a>

        {{-- OT vencidas: el rojo más fuerte de la interfaz, y el único. --}}
        <a
            href="{{ route('mantenimientos.index', ['vencidos' => 1]) }}"
            wire:navigate
            @class([
                'eq-panel flex flex-col justify-between gap-3 px-5 py-4 transition duration-200',
                'border-rose-300 bg-rose-50 hover:border-rose-400 dark:border-rose-500/40 dark:bg-rose-500/10 dark:hover:border-rose-500/60' => $this->vencidas > 0,
                'hover:border-zinc-300 dark:hover:border-zinc-700' => $this->vencidas === 0,
            ])
        >
            <p @class([
                'text-[11.5px] font-semibold tracking-wide uppercase',
                'text-rose-700 dark:text-rose-300' => $this->vencidas > 0,
                'text-zinc-500 dark:text-zinc-400' => $this->vencidas === 0,
            ])>
                Órdenes vencidas
            </p>

            <p @class([
                'text-4xl leading-none font-bold tabular-nums',
                'text-rose-600 dark:text-rose-400' => $this->vencidas > 0,
                'text-carbon dark:text-white' => $this->vencidas === 0,
            ])>{{ $this->vencidas }}</p>

            <p class="text-[12px] text-zinc-500 dark:text-zinc-400">
                {{ $this->vencidas > 0 ? 'Pendientes con la fecha ya pasada' : 'Ninguna orden con la fecha pasada' }}
            </p>
        </a>

        {{-- Equipos fuera de servicio. --}}
        <a
            href="{{ route('equipos.index', ['bandeja' => 'fuera_servicio']) }}"
            wire:navigate
            class="eq-panel flex flex-col justify-between gap-3 px-5 py-4 transition duration-200 hover:border-zinc-300 dark:hover:border-zinc-700"
        >
            <p class="text-[11.5px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">
                Equipos fuera de servicio
            </p>

            <p @class([
                'text-4xl leading-none font-bold tabular-nums',
                'text-amber-600 dark:text-amber-500' => $this->fueraDeServicio > 0,
                'text-carbon dark:text-white' => $this->fueraDeServicio === 0,
            ])>{{ $this->fueraDeServicio }}</p>

            <p class="text-[12px] text-zinc-500 dark:text-zinc-400">
                Cada uno es un problema abierto en su institución
            </p>
        </a>
    </div>

    {{-- Órdenes ejecutadas del mes, con su variación. Va en la misma banda que
         los otros tres pero ocupa menos: es el que menos decisión cambia. --}}
    <a
        href="{{ route('mantenimientos.index', ['bandeja' => 'mes', 'estado' => 'ejecutado']) }}"
        wire:navigate
        class="eq-panel flex flex-wrap items-center justify-between gap-3 px-5 py-3 transition duration-200 hover:border-zinc-300 dark:hover:border-zinc-700"
    >
        <div class="flex items-baseline gap-3">
            <p class="text-2xl leading-none font-bold tabular-nums text-carbon dark:text-white">
                {{ $cronograma['ejecutadas'] }}
            </p>
            <p class="text-[13px] font-medium text-zinc-600 dark:text-zinc-300">Órdenes ejecutadas este mes</p>
        </div>

        <p class="text-[12px] tabular-nums text-zinc-500 dark:text-zinc-400">
            @if ($cronograma['variacionEjecutadas'] === null)
                Sin órdenes ejecutadas el mes anterior para comparar
            @else
                <span @class([
                    'font-semibold',
                    'text-emerald-600 dark:text-emerald-400' => $cronograma['variacionEjecutadas'] > 0,
                    'text-rose-600 dark:text-rose-400' => $cronograma['variacionEjecutadas'] < 0,
                ])>{{ $cronograma['variacionEjecutadas'] > 0 ? '+' : '' }}{{ $cronograma['variacionEjecutadas'] }} %</span>
                frente a {{ $cronograma['ejecutadasAnterior'] }} del mes anterior
            @endif
        </p>
    </a>

    {{-- ═══════════════ 2. Bandeja de atención ═══════════════ --}}
    <div class="eq-panel overflow-hidden">
        <div class="border-b border-zinc-200 px-5 py-3 dark:border-zinc-800">
            <h2 class="text-[13px] font-bold tracking-wide text-carbon uppercase dark:text-zinc-200">
                Bandeja de atención
            </h2>
        </div>

        @forelse ($this->bandeja as $grupo)
            <a
                href="{{ $grupo['url'] }}"
                wire:navigate
                class="group flex items-center gap-4 border-b border-zinc-100 px-5 py-2.5 transition duration-150 last:border-b-0 hover:bg-zinc-50 dark:border-zinc-800/70 dark:hover:bg-zinc-800/50"
            >
                <span class="w-10 shrink-0 text-right text-lg leading-none font-bold tabular-nums text-carbon dark:text-white">
                    {{ $grupo['conteo'] }}
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[13.5px] font-medium text-carbon dark:text-zinc-100">
                        {{ $grupo['titulo'] }}
                    </span>
                    <span class="block truncate text-[11.5px] text-zinc-500 dark:text-zinc-400">
                        {{ $grupo['detalle'] }}
                    </span>
                </span>

                <flux:icon
                    name="chevron-right"
                    variant="mini"
                    class="size-4 shrink-0 text-zinc-300 transition duration-150 group-hover:translate-x-0.5 group-hover:text-zinc-500 dark:text-zinc-600 dark:group-hover:text-zinc-400"
                />
            </a>
        @empty
            <p class="px-5 py-6 text-center text-[13px] text-zinc-500 dark:text-zinc-400">
                Nada pendiente de atención hoy.
            </p>
        @endforelse
    </div>

    {{-- ═══════════════ 3. Gráficas ═══════════════ --}}
    <div class="grid gap-4 lg:grid-cols-2">
        @if ($this->analisisPedido)
            @include('pages.panel.partials.grafica-ordenes')
            @include('pages.panel.partials.grafica-instituciones')
        @else
            @foreach (['Órdenes por mes', 'Cumplimiento por institución'] as $titulo)
                <div class="eq-panel px-5 py-4">
                    <p class="text-[13px] font-bold tracking-wide text-carbon uppercase dark:text-zinc-200">{{ $titulo }}</p>
                    <div class="mt-4 h-40 animate-pulse rounded-lg bg-zinc-100 dark:bg-zinc-800"></div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- ═══════════════ 4. Equipos con más correctivos ═══════════════ --}}
    @if ($this->analisisPedido)
        @include('pages.panel.partials.tabla-reposicion')
    @endif
</section>
