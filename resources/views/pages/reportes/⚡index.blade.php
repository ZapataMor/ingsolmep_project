<?php

use App\Models\Empresa;
use App\Models\Mantenimiento;
use App\Models\Reporte;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Reportes')] class extends Component {
    use WithPagination;

    // ------------------------------------------------------------------
    // Filtros del listado
    // ------------------------------------------------------------------

    public string $buscar = '';

    public string $filtroTipo = '';

    public string $filtroEmpresa = '';

    public string $filtroDesde = '';

    public string $filtroHasta = '';

    /** Acota a los reportes emitidos dentro del mes corriente. */
    public bool $soloDelMes = false;

    public int $porPagina = 10;

    public string $ordenarPor = 'ultima_generacion';

    public string $ordenDireccion = 'desc';

    public ?int $reporteAEliminar = null;

    /**
     * Colores de la insignia de cada tipo de reporte, heredados del módulo de
     * mantenimientos para que una orden y su reporte se lean igual.
     *
     * @var array<string, string>
     */
    public const COLORES_TIPO = [
        'preventivo' => 'bg-signal/10 text-signal-600 dark:bg-signal/15 dark:text-signal',
        'correctivo' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    ];

    // ------------------------------------------------------------------
    // Datos derivados
    // ------------------------------------------------------------------

    /** @return LengthAwarePaginator<int, Reporte> */
    #[Computed]
    public function reportes(): LengthAwarePaginator
    {
        $ordenables = ['id', 'tipo', 'generado_en', 'ultima_generacion', 'veces_generado'];
        $columna = in_array($this->ordenarPor, $ordenables, true) ? $this->ordenarPor : 'ultima_generacion';

        return $this->consultaFiltrada()
            ->with([
                'mantenimiento.equipo.marca', 'mantenimiento.equipo.modelo', 'mantenimiento.equipo.area',
                'empresa', 'generadoPor', 'ultimoGeneradoPor',
            ])
            ->orderBy($columna, $this->ordenDireccion === 'asc' ? 'asc' : 'desc')
            ->orderBy('id', 'desc')
            ->paginate($this->porPagina);
    }

    /** @return array<string, int> */
    #[Computed]
    public function resumen(): array
    {
        return [
            'total' => Reporte::count(),
            'preventivos' => Reporte::where('tipo', 'preventivo')->count(),
            'correctivos' => Reporte::where('tipo', 'correctivo')->count(),
            'delMes' => Reporte::query()->delMes()->count(),
        ];
    }

    /**
     * Órdenes ejecutadas a las que todavía no se les ha emitido el reporte: es
     * el trabajo pendiente que da sentido al módulo.
     */
    #[Computed]
    public function ordenesSinReporte(): int
    {
        return Mantenimiento::query()
            ->where('estado', 'ejecutado')
            ->whereDoesntHave('reporte')
            ->count();
    }

    /** @return Collection<int, Empresa> */
    #[Computed]
    public function empresas(): Collection
    {
        return Empresa::orderBy('nombre')->get();
    }

    /** Reporte señalado para eliminar. */
    #[Computed]
    public function reporteEliminable(): ?Reporte
    {
        if ($this->reporteAEliminar === null) {
            return null;
        }

        return Reporte::with('mantenimiento.equipo')->find($this->reporteAEliminar);
    }

    #[Computed]
    public function hayFiltrosActivos(): bool
    {
        return $this->buscar !== ''
            || $this->filtroTipo !== ''
            || $this->filtroEmpresa !== ''
            || $this->filtroDesde !== ''
            || $this->filtroHasta !== ''
            || $this->soloDelMes;
    }

    // ------------------------------------------------------------------
    // Listado
    // ------------------------------------------------------------------

    public function updated(string $propiedad): void
    {
        if (str_starts_with($propiedad, 'filtro') || in_array($propiedad, ['buscar', 'porPagina', 'soloDelMes'], true)) {
            $this->resetPage();
        }
    }

    public function ordenar(string $columna): void
    {
        if ($this->ordenarPor === $columna) {
            $this->ordenDireccion = $this->ordenDireccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenarPor = $columna;
            $this->ordenDireccion = 'desc';
        }

        $this->resetPage();
    }

    /**
     * Las tarjetas de indicadores acotan el listado en lugar de abrir otra
     * vista: el reporte ya vive fuera de la aplicación, en su documento.
     */
    public function filtrarPorTipo(string $tipo): void
    {
        $this->filtroTipo = $this->filtroTipo === $tipo ? '' : $tipo;
        $this->soloDelMes = false;
        $this->resetPage();
    }

    public function alternarDelMes(): void
    {
        $this->soloDelMes = ! $this->soloDelMes;
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['buscar', 'filtroTipo', 'filtroEmpresa', 'filtroDesde', 'filtroHasta', 'soloDelMes']);

        $this->resetPage();
    }

    // ------------------------------------------------------------------
    // Eliminación
    // ------------------------------------------------------------------

    public function confirmarEliminacion(int $id): void
    {
        $this->reporteAEliminar = $id;
    }

    public function eliminar(): void
    {
        if ($this->reporteAEliminar === null) {
            return;
        }

        $reporte = Reporte::findOrFail($this->reporteAEliminar);
        $codigo = $reporte->codigo();
        $reporte->delete();

        $this->reporteAEliminar = null;

        unset($this->reportes, $this->resumen, $this->ordenesSinReporte);

        Flux::toast(
            variant: 'success',
            text: 'Reporte '.$codigo.' retirado del listado. La orden y su historial se conservan.',
        );
    }

    // ------------------------------------------------------------------
    // Apoyo
    // ------------------------------------------------------------------

    /**
     * Consulta del listado con los filtros de la pantalla ya aplicados.
     *
     * @return Builder<Reporte>
     */
    private function consultaFiltrada(): Builder
    {
        return Reporte::query()
            ->when($this->buscar !== '', function (Builder $consulta): void {
                $termino = '%'.$this->buscar.'%';

                $consulta->where(function (Builder $grupo) use ($termino): void {
                    $grupo->whereHas('mantenimiento', fn (Builder $m) => $m
                        ->where('tecnico', 'like', $termino)
                        ->orWhereHas('equipo', fn (Builder $e) => $e
                            ->where('descripcion', 'like', $termino)
                            ->orWhere('numero_serie', 'like', $termino)))
                        ->orWhereHas('empresa', fn (Builder $e) => $e->where('nombre', 'like', $termino))
                        ->orWhereHas('ultimoGeneradoPor', fn (Builder $u) => $u->where('name', 'like', $termino));
                });
            })
            ->when($this->filtroTipo !== '', fn (Builder $q) => $q->where('tipo', $this->filtroTipo))
            ->when($this->filtroEmpresa !== '', fn (Builder $q) => $q->where('empresa_id', $this->filtroEmpresa))
            ->when($this->filtroDesde !== '', fn (Builder $q) => $q->whereDate('ultima_generacion', '>=', $this->filtroDesde))
            ->when($this->filtroHasta !== '', fn (Builder $q) => $q->whereDate('ultima_generacion', '<=', $this->filtroHasta))
            ->when($this->soloDelMes, fn (Builder $q) => $q->delMes());
    }
}; ?>

<section class="eq-root w-full space-y-6">
    {{-- ───────────────── Encabezado ───────────────── --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <nav class="mb-1 flex items-center gap-1.5 text-[11.5px] font-medium text-zinc-400">
                <span class="text-zinc-500 dark:text-zinc-400">Reportes</span>
            </nav>

            <h1 class="text-2xl font-bold tracking-tight text-carbon dark:text-white">Reportes generados</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Constancia de los mantenimientos ejecutados a los que ya se les emitió el documento.
            </p>
        </div>

        <a href="{{ route('mantenimientos.index') }}" class="eq-btn eq-btn-ghost" wire:navigate>
            <flux:icon name="wrench-screwdriver" variant="mini" class="size-4" />
            Ir a mantenimientos
        </a>
    </div>

    {{-- ───────────────── Indicadores ───────────────── --}}
    @php
        $tarjetas = [
            ['clave' => '', 'accion' => "filtrarPorTipo('')", 'etiqueta' => 'Generados', 'valor' => $this->resumen['total'], 'color' => 'text-carbon dark:text-white', 'activa' => $filtroTipo === '' && ! $soloDelMes],
            ['clave' => 'preventivo', 'accion' => "filtrarPorTipo('preventivo')", 'etiqueta' => 'Preventivos', 'valor' => $this->resumen['preventivos'], 'color' => 'text-signal', 'activa' => $filtroTipo === 'preventivo'],
            ['clave' => 'correctivo', 'accion' => "filtrarPorTipo('correctivo')", 'etiqueta' => 'Correctivos', 'valor' => $this->resumen['correctivos'], 'color' => 'text-amber-600 dark:text-amber-500', 'activa' => $filtroTipo === 'correctivo'],
            ['clave' => 'mes', 'accion' => 'alternarDelMes', 'etiqueta' => 'Este mes', 'valor' => $this->resumen['delMes'], 'color' => 'text-lima-700 dark:text-lima', 'activa' => $soloDelMes],
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($tarjetas as $tarjeta)
            <button
                type="button"
                wire:click="{{ $tarjeta['accion'] }}"
                title="Acotar el listado a los reportes {{ mb_strtolower($tarjeta['etiqueta']) }}"
                @class([
                    'eq-panel flex w-full cursor-pointer flex-col items-center justify-center gap-1 px-4 py-5 text-center transition duration-300 outline-none hover:-translate-y-1 hover:shadow-lg focus-visible:ring-4 focus-visible:ring-lima/30',
                    'ring-2 ring-lima' => $tarjeta['activa'],
                ])
            >
                <p class="text-3xl leading-none font-bold {{ $tarjeta['color'] }}">{{ $tarjeta['valor'] }}</p>
                <p class="text-[11.5px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $tarjeta['etiqueta'] }}</p>
            </button>
        @endforeach
    </div>

    {{-- Órdenes cerradas que todavía esperan su documento. --}}
    @if ($this->ordenesSinReporte > 0)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 dark:border-amber-500/30 dark:bg-amber-500/10">
            <p class="flex items-start gap-3 text-[13px] text-amber-800 dark:text-amber-300">
                <flux:icon name="exclamation-triangle" variant="mini" class="mt-0.5 size-4 shrink-0" />
                <span>
                    Hay <strong>{{ $this->ordenesSinReporte }}</strong>
                    {{ $this->ordenesSinReporte === 1 ? 'orden ejecutada' : 'órdenes ejecutadas' }}
                    sin reporte generado.
                </span>
            </p>

            <a href="{{ route('mantenimientos.index') }}" class="eq-btn eq-btn-ghost !px-3 !py-1.5 !text-[12px]" wire:navigate>
                Generarlos ahora
            </a>
        </div>
    @endif

    {{-- ───────────────── Filtros ───────────────── --}}
    <div class="eq-panel p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <p class="flex items-center gap-2 text-[12px] font-bold tracking-wide text-carbon uppercase dark:text-zinc-200">
                <flux:icon name="funnel" variant="mini" class="size-4 text-lima" />
                Filtros
            </p>

            @if ($this->hayFiltrosActivos)
                <button type="button" class="eq-btn eq-btn-ghost !px-3 !py-1.5 !text-[12px]" wire:click="limpiarFiltros">
                    <flux:icon name="x-mark" variant="micro" class="size-3.5" />
                    Limpiar filtros
                </button>
            @endif
        </div>

        <div class="relative mb-4">
            <flux:icon name="magnifying-glass" variant="mini" class="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-zinc-400" />
            <input
                type="search"
                class="eq-input !rounded-2xl !py-3 !pl-11"
                placeholder="Buscar por equipo, serie, empresa, técnico o quien lo generó…"
                wire:model.live.debounce.400ms="buscar"
            >
            <div wire:loading.delay wire:target="buscar" class="absolute top-1/2 right-4 -translate-y-1/2">
                <flux:icon name="arrow-path" variant="mini" class="size-4 animate-spin text-lima" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="eq-label" for="r-tipo">Tipo de mantenimiento</label>
                <select id="r-tipo" class="eq-select" wire:model.live="filtroTipo">
                    <option value="">Ambos tipos</option>
                    @foreach (\App\Models\Mantenimiento::TIPOS as $clave => $etiqueta)
                        <option value="{{ $clave }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="eq-label" for="r-empresa">Empresa</label>
                <select id="r-empresa" class="eq-select" wire:model.live="filtroEmpresa">
                    <option value="">Todas las empresas</option>
                    @foreach ($this->empresas as $empresa)
                        <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="eq-label" for="r-desde">Generado desde</label>
                <input id="r-desde" type="date" class="eq-input" wire:model.live="filtroDesde">
            </div>

            <div>
                <label class="eq-label" for="r-hasta">Generado hasta</label>
                <input id="r-hasta" type="date" class="eq-input" wire:model.live="filtroHasta">
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-4">
            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-zinc-200 bg-white px-3.5 py-2.5 transition hover:border-lima dark:border-zinc-700 dark:bg-zinc-900">
                <input type="checkbox" class="size-4 rounded accent-lima" wire:model.live="soloDelMes">
                <span class="text-sm font-medium text-carbon dark:text-zinc-200">Sólo los de este mes</span>
            </label>

            <div class="w-full sm:w-48">
                <label class="eq-label" for="r-por-pagina">Resultados por página</label>
                <select id="r-por-pagina" class="eq-select" wire:model.live="porPagina">
                    @foreach ([10, 25, 50, 100] as $cantidad)
                        <option value="{{ $cantidad }}">{{ $cantidad }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- ───────────────── Tabla ───────────────── --}}
    <div class="eq-panel overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
            <p class="flex items-center gap-2 text-[13px] font-semibold text-carbon dark:text-zinc-200">
                Reportes generados
                <span class="eq-chip bg-lima-soft text-lima-700 dark:bg-lima/10 dark:text-lima">
                    {{ $this->reportes->total() }} {{ $this->reportes->total() === 1 ? 'resultado' : 'resultados' }}
                </span>
            </p>

            <p class="text-[12px] text-zinc-500 dark:text-zinc-400">Pulse una fila para abrir el documento.</p>
        </div>

        <div wire:loading.delay.class="opacity-50" class="overflow-x-auto transition-opacity">
            <table class="w-full min-w-4xl text-left text-[13px]">
                <thead class="bg-zinc-50/80 text-[11px] font-bold tracking-wide text-zinc-500 uppercase dark:bg-zinc-800/60 dark:text-zinc-400">
                    <tr>
                        @php
                            $columnas = [
                                ['clave' => 'id', 'titulo' => 'Reporte', 'ordenable' => true],
                                ['clave' => 'tipo', 'titulo' => 'Tipo', 'ordenable' => true],
                                ['clave' => null, 'titulo' => 'Equipo', 'ordenable' => false],
                                ['clave' => null, 'titulo' => 'Empresa / Área', 'ordenable' => false],
                                ['clave' => 'ultima_generacion', 'titulo' => 'Generado', 'ordenable' => true],
                                ['clave' => null, 'titulo' => 'Generado por', 'ordenable' => false],
                                ['clave' => null, 'titulo' => 'Acciones', 'ordenable' => false],
                            ];
                        @endphp

                        @foreach ($columnas as $columna)
                            <th scope="col" class="px-4 py-3 font-bold whitespace-nowrap">
                                @if ($columna['ordenable'])
                                    <button type="button" class="inline-flex cursor-pointer items-center gap-1 transition hover:text-lima" wire:click="ordenar('{{ $columna['clave'] }}')">
                                        {{ $columna['titulo'] }}
                                        @if ($ordenarPor === $columna['clave'])
                                            <flux:icon name="{{ $ordenDireccion === 'asc' ? 'chevron-up' : 'chevron-down' }}" variant="micro" class="size-3 text-lima" />
                                        @else
                                            <flux:icon name="chevron-up-down" variant="micro" class="size-3 opacity-40" />
                                        @endif
                                    </button>
                                @else
                                    {{ $columna['titulo'] }}
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->reportes as $reporte)
                        @php
                            $orden = $reporte->mantenimiento;
                            $equipoReporte = $orden?->equipo;
                        @endphp

                        <tr
                            wire:key="reporte-{{ $reporte->id }}"
                            x-data
                            x-on:click="window.open(@js($orden ? route('mantenimientos.reporte', $orden) : '#'), '_blank', 'noopener')"
                            tabindex="0"
                            x-on:keydown.enter="window.open(@js($orden ? route('mantenimientos.reporte', $orden) : '#'), '_blank', 'noopener')"
                            title="Abrir el documento {{ $reporte->codigo() }}"
                            class="cursor-pointer transition duration-150 outline-none hover:bg-lima-soft/40 focus-visible:bg-lima-soft/60 dark:hover:bg-zinc-800/50 dark:focus-visible:bg-zinc-800/70"
                        >
                            <td class="px-4 py-3 align-top">
                                <p class="font-mono text-[12.5px] font-semibold whitespace-nowrap text-carbon dark:text-zinc-100">{{ $reporte->codigo() }}</p>
                                <p class="text-[11.5px] whitespace-nowrap text-zinc-500 dark:text-zinc-400">
                                    Orden {{ $orden?->codigo() ?? '—' }}
                                </p>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <span class="eq-chip {{ $this::COLORES_TIPO[$reporte->tipo] ?? '' }}">
                                    <flux:icon name="{{ $reporte->tipo === 'correctivo' ? 'exclamation-triangle' : 'calendar-days' }}" variant="micro" class="size-3" />
                                    {{ $reporte->tipoEtiqueta() }}
                                </span>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-zinc-200 bg-white text-[11px] font-bold text-carbon shadow-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                        @if ($equipoReporte?->fotoUrl())
                                            <img src="{{ $equipoReporte->fotoUrl() }}" alt="Foto de {{ $equipoReporte->descripcion }}" class="size-full object-cover">
                                        @else
                                            {{ $equipoReporte?->iniciales() ?? '—' }}
                                        @endif
                                    </span>

                                    <div class="min-w-0">
                                        <p class="font-semibold text-carbon dark:text-zinc-100">{{ $equipoReporte?->descripcion ?? 'Equipo retirado' }}</p>
                                        <p class="text-[12px] text-zinc-500 dark:text-zinc-400">
                                            {{ $equipoReporte?->marca?->nombre ?? '—' }}
                                            @if ($equipoReporte?->modelo) · {{ $equipoReporte->modelo->nombre }} @endif
                                        </p>
                                        @if ($equipoReporte?->numero_serie)
                                            <p class="font-mono text-[11.5px] text-zinc-400">S/N {{ $equipoReporte->numero_serie }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <p class="font-medium text-carbon dark:text-zinc-200">{{ $reporte->empresa?->nombre ?? 'Sin empresa' }}</p>
                                @if ($equipoReporte?->area)
                                    <p class="flex items-center gap-1 text-[12px] text-zinc-500 dark:text-zinc-400">
                                        <flux:icon name="map-pin" variant="micro" class="size-3 text-lima" />
                                        {{ $equipoReporte->area->nombre }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top">
                                <p class="font-medium text-carbon dark:text-zinc-200">{{ $reporte->ultima_generacion->format('d/m/Y H:i') }}</p>

                                @if ($reporte->fueReemitido())
                                    <span class="eq-chip mt-1 bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300" title="Primera emisión el {{ $reporte->generado_en->format('d/m/Y H:i') }}">
                                        <flux:icon name="arrow-path" variant="micro" class="size-3" />
                                        {{ $reporte->veces_generado }} emisiones
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top">
                                @if ($reporte->ultimoGeneradoPor)
                                    <p class="flex items-center gap-1 font-medium text-carbon dark:text-zinc-200">
                                        <flux:icon name="user" variant="micro" class="size-3.5 text-zinc-400" />
                                        {{ $reporte->ultimoGeneradoPor->name }}
                                    </p>
                                @else
                                    <span class="text-zinc-400">Sin registrar</span>
                                @endif

                                @if ($orden?->tecnico)
                                    <p class="text-[11.5px] text-zinc-500 dark:text-zinc-400">Técnico: {{ $orden->tecnico }}</p>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="flex items-center gap-1">
                                    @if ($orden)
                                        <a
                                            x-on:click.stop
                                            href="{{ route('mantenimientos.reporte', $orden) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="eq-icon-btn hover:!bg-lima-soft hover:!text-lima-700 dark:hover:!bg-lima/10 dark:hover:!text-lima"
                                            title="Abrir el documento {{ $reporte->codigo() }}"
                                        >
                                            <flux:icon name="arrow-top-right-on-square" variant="mini" class="size-4" />
                                        </a>

                                        <a
                                            x-on:click.stop
                                            href="{{ route('mantenimientos.reporte', [$orden, 'imprimir' => 1]) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="eq-icon-btn"
                                            title="Imprimir o guardar en PDF {{ $reporte->codigo() }}"
                                        >
                                            <flux:icon name="printer" variant="mini" class="size-4" />
                                        </a>
                                    @endif

                                    <button
                                        type="button"
                                        class="eq-icon-btn hover:!bg-rose-50 hover:!text-rose-600 dark:hover:!bg-rose-500/10 dark:hover:!text-rose-400"
                                        wire:click.stop="confirmarEliminacion({{ $reporte->id }})"
                                        x-on:click.stop
                                        title="Retirar {{ $reporte->codigo() }} del listado"
                                    >
                                        <flux:icon name="trash" variant="mini" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                                    <span class="flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon name="document-text" class="size-7 text-zinc-400" />
                                    </span>
                                    <p class="text-[15px] font-semibold text-carbon dark:text-zinc-200">
                                        {{ $this->hayFiltrosActivos ? 'Ningún reporte coincide con los filtros' : 'Todavía no se ha generado ningún reporte' }}
                                    </p>
                                    <p class="text-[13px] text-zinc-500 dark:text-zinc-400">
                                        {{ $this->hayFiltrosActivos
                                            ? 'Ajuste o limpie los filtros para ver más resultados.'
                                            : 'Los reportes aparecen aquí al generarlos desde una orden de mantenimiento ejecutada.' }}
                                    </p>
                                    @if ($this->hayFiltrosActivos)
                                        <button type="button" class="eq-btn eq-btn-ghost" wire:click="limpiarFiltros">Limpiar filtros</button>
                                    @else
                                        <a href="{{ route('mantenimientos.index') }}" class="eq-btn eq-btn-accent" wire:navigate>
                                            <flux:icon name="wrench-screwdriver" variant="mini" class="size-4" />
                                            Ir a mantenimientos
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->reportes->hasPages())
            <div class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-800">
                {{ $this->reportes->links() }}
            </div>
        @endif
    </div>

    {{-- Los modales se teletransportan al <body>: dentro del contenedor de la
         página el velo `fixed` no llegaba a cubrir toda la pantalla. --}}
    @teleport('body')
        @include('pages.reportes.partials.modal-eliminar', ['reporte' => $this->reporteEliminable])
    @endteleport
</section>
