<?php

use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Mantenimientos')] class extends Component {
    use WithPagination;

    // ------------------------------------------------------------------
    // Filtros del listado
    // ------------------------------------------------------------------

    #[Url(as: 'q')]
    public string $buscar = '';

    #[Url(as: 'tipo')]
    public string $filtroTipo = '';

    #[Url(as: 'estado')]
    public string $filtroEstado = '';

    #[Url(as: 'empresa')]
    public string $filtroEmpresa = '';

    #[Url(as: 'prioridad')]
    public string $filtroPrioridad = '';

    #[Url(as: 'tecnico')]
    public string $filtroTecnico = '';

    #[Url(as: 'desde')]
    public string $filtroDesde = '';

    #[Url(as: 'hasta')]
    public string $filtroHasta = '';

    /** Acota el listado a los pendientes cuya fecha programada ya pasó. */
    #[Url(as: 'vencidos')]
    public bool $soloVencidos = false;

    /**
     * Situación concreta que trae el usuario desde el panel principal. Cada
     * valor apunta al mismo scope con el que el panel contó la cifra, así lo
     * que se ve aquí es exactamente lo que decía el indicador.
     */
    #[Url(as: 'bandeja')]
    public string $filtroBandeja = '';

    /**
     * Situaciones que enlaza el panel, con el rótulo que se muestra al llegar.
     *
     * @var array<string, string>
     */
    public const BANDEJAS = [
        'estancados' => 'Correctivos abiertos hace más de 15 días',
        'mes' => 'Cronograma del mes en curso',
        'novedad' => 'Órdenes con novedad reportada sin correctivo posterior',
    ];

    public int $porPagina = 10;

    #[Url(as: 'orden')]
    public string $ordenarPor = 'fecha_programada';

    #[Url(as: 'dir')]
    public string $ordenDireccion = 'desc';

    // ------------------------------------------------------------------
    // Formulario de asignación
    // ------------------------------------------------------------------

    public bool $mostrarFormulario = false;

    /** Hay datos escritos en el formulario que todavía no se han guardado. */
    public bool $formularioSucio = false;

    /** Muestra el aviso previo a cerrar el formulario con cambios pendientes. */
    public bool $confirmarCierreFormulario = false;

    public ?int $mantenimientoId = null;

    public string $equipo_id = '';

    /** Filtra el desplegable de equipos del formulario, que sólo trae un tope. */
    public string $buscarEquipo = '';

    public string $tipo = 'preventivo';

    public string $estado = 'programado';

    public string $prioridad = '';

    public string $fecha_programada = '';

    public string $fecha_ejecucion = '';

    public string $tecnico = '';

    public string $motivo = '';

    public string $descripcion = '';

    public string $repuestos = '';

    public string $observaciones = '';

    public string $costo = '';

    /** @var array<string, bool> */
    public array $subtareas = [];

    /** @var array<string, string> */
    public array $accesorios_estado = [];

    public ?int $mantenimientoAEliminar = null;

    // ------------------------------------------------------------------
    // Vistas de sólo lectura
    // ------------------------------------------------------------------

    public ?int $mantenimientoVisto = null;

    public string $listadoVisto = '';

    /**
     * Listados de sólo lectura que abren las tarjetas de indicadores.
     *
     * @var array<string, array{titulo: string, descripcion: string, icono: string}>
     */
    public const LISTADOS = [
        'total' => [
            'titulo' => 'Mantenimientos asignados',
            'descripcion' => 'Todas las órdenes preventivas y correctivas del sistema.',
            'icono' => 'wrench-screwdriver',
        ],
        'preventivos' => [
            'titulo' => 'Mantenimientos preventivos',
            'descripcion' => 'Rutinas programadas para conservar el equipo en servicio.',
            'icono' => 'calendar-days',
        ],
        'correctivos' => [
            'titulo' => 'Mantenimientos correctivos',
            'descripcion' => 'Intervenciones abiertas por una falla reportada.',
            'icono' => 'exclamation-triangle',
        ],
        'pendientes' => [
            'titulo' => 'Mantenimientos pendientes',
            'descripcion' => 'Órdenes programadas o en proceso, todavía sin cerrar.',
            'icono' => 'clock',
        ],
        'vencidos' => [
            'titulo' => 'Mantenimientos vencidos',
            'descripcion' => 'Órdenes pendientes cuya fecha programada ya pasó.',
            'icono' => 'bell-alert',
        ],
        'ejecutados' => [
            'titulo' => 'Mantenimientos ejecutados',
            'descripcion' => 'Órdenes cerradas con su fecha de ejecución registrada.',
            'icono' => 'check-badge',
        ],
    ];

    /**
     * Colores de la insignia de cada tipo de mantenimiento.
     *
     * @var array<string, string>
     */
    public const COLORES_TIPO = [
        'preventivo' => 'bg-signal/10 text-signal-600 dark:bg-signal/15 dark:text-signal',
        'correctivo' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    ];

    /**
     * Colores de la insignia de cada estado de la orden.
     *
     * @var array<string, string>
     */
    public const COLORES_ESTADO = [
        'programado' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
        'en_proceso' => 'bg-lima-soft text-lima-700 dark:bg-lima/15 dark:text-lima',
        'ejecutado' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
        'cancelado' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400',
    ];

    /** Máximo de filas que muestra el modal de listado antes de recortar. */
    public const TOPE_LISTADO = 100;

    /** Máximo de equipos que ofrece el desplegable del formulario. */
    public const TOPE_EQUIPOS = 50;

    public function mount(): void
    {
        $this->reiniciarFormulario();
    }

    // ------------------------------------------------------------------
    // Datos derivados
    // ------------------------------------------------------------------

    /** @return LengthAwarePaginator<int, Mantenimiento> */
    #[Computed]
    public function mantenimientos(): LengthAwarePaginator
    {
        $ordenables = ['id', 'tipo', 'estado', 'fecha_programada', 'fecha_ejecucion', 'created_at'];
        $columna = in_array($this->ordenarPor, $ordenables, true) ? $this->ordenarPor : 'fecha_programada';

        return $this->consultaFiltrada()
            // `reporte` distingue en la tabla las órdenes que ya se reportaron.
            ->with(['equipo.marca', 'equipo.modelo', 'equipo.area', 'empresa', 'reporte'])
            ->orderBy($columna, $this->ordenDireccion === 'asc' ? 'asc' : 'desc')
            ->orderBy('id', 'desc')
            ->paginate($this->porPagina);
    }

    /** @return array<string, int> */
    #[Computed]
    public function resumen(): array
    {
        return [
            'total' => Mantenimiento::count(),
            'preventivos' => Mantenimiento::where('tipo', 'preventivo')->count(),
            'correctivos' => Mantenimiento::where('tipo', 'correctivo')->count(),
            'pendientes' => Mantenimiento::abiertos()->count(),
            'vencidos' => Mantenimiento::vencidos()->count(),
            'ejecutados' => Mantenimiento::where('estado', 'ejecutado')->count(),
        ];
    }

    /** @return Collection<int, Empresa> */
    #[Computed]
    public function empresas(): Collection
    {
        return Empresa::orderBy('nombre')->get();
    }

    /**
     * Técnicos ya registrados, para el filtro y las sugerencias del formulario.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function tecnicos(): Collection
    {
        return Mantenimiento::query()
            ->whereNotNull('tecnico')
            ->where('tecnico', '<>', '')
            ->distinct()
            ->orderBy('tecnico')
            ->pluck('tecnico');
    }

    /**
     * Equipos que ofrece el formulario. El inventario puede ser largo, así que
     * se recorta y se acota con el buscador propio del formulario; el equipo ya
     * elegido se añade siempre, para que no desaparezca del desplegable.
     *
     * @return Collection<int, Equipo>
     */
    #[Computed]
    public function equiposParaFormulario(): Collection
    {
        $equipos = Equipo::query()
            ->with(['empresa', 'marca', 'modelo'])
            ->when($this->buscarEquipo !== '', function (Builder $consulta): void {
                $termino = '%'.$this->buscarEquipo.'%';

                $consulta->where(function (Builder $grupo) use ($termino): void {
                    $grupo->where('descripcion', 'like', $termino)
                        ->orWhere('numero_serie', 'like', $termino)
                        ->orWhereHas('marca', fn (Builder $m) => $m->where('nombre', 'like', $termino))
                        ->orWhereHas('modelo', fn (Builder $m) => $m->where('nombre', 'like', $termino))
                        ->orWhereHas('empresa', fn (Builder $m) => $m->where('nombre', 'like', $termino));
                });
            })
            ->orderBy('descripcion')
            ->limit(self::TOPE_EQUIPOS)
            ->get();

        $elegido = $this->equipoSeleccionado;

        if ($elegido && ! $equipos->contains('id', $elegido->id)) {
            $equipos->prepend($elegido);
        }

        return $equipos;
    }

    /** Equipo elegido en el formulario, con sus relaciones cargadas. */
    #[Computed]
    public function equipoSeleccionado(): ?Equipo
    {
        if ($this->equipo_id === '') {
            return null;
        }

        return Equipo::with(['empresa', 'area', 'marca', 'modelo'])->find($this->equipo_id);
    }

    /** Mantenimiento abierto en la ficha, con sus relaciones cargadas. */
    #[Computed]
    public function mantenimientoDetalle(): ?Mantenimiento
    {
        if ($this->mantenimientoVisto === null) {
            return null;
        }

        return Mantenimiento::with(['equipo.marca', 'equipo.modelo', 'equipo.area', 'empresa'])
            ->find($this->mantenimientoVisto);
    }

    /** Mantenimiento señalado para eliminar. */
    #[Computed]
    public function mantenimientoEliminable(): ?Mantenimiento
    {
        if ($this->mantenimientoAEliminar === null) {
            return null;
        }

        return Mantenimiento::with('equipo')->find($this->mantenimientoAEliminar);
    }

    /**
     * Mantenimientos que muestra el modal de una tarjeta de indicadores.
     *
     * @return Collection<int, Mantenimiento>
     */
    #[Computed]
    public function listadoMantenimientos(): Collection
    {
        if ($this->listadoVisto === '') {
            return collect();
        }

        return $this->consultaDelListado()
            ->with(['equipo', 'empresa'])
            ->orderBy('fecha_programada', 'desc')
            ->limit(self::TOPE_LISTADO)
            ->get();
    }

    #[Computed]
    public function listadoTotal(): int
    {
        return $this->listadoVisto === '' ? 0 : $this->consultaDelListado()->count();
    }

    #[Computed]
    public function hayFiltrosActivos(): bool
    {
        return $this->buscar !== ''
            || $this->filtroTipo !== ''
            || $this->filtroEstado !== ''
            || $this->filtroEmpresa !== ''
            || $this->filtroPrioridad !== ''
            || $this->filtroTecnico !== ''
            || $this->filtroDesde !== ''
            || $this->filtroHasta !== ''
            || $this->soloVencidos
            || $this->filtroBandeja !== '';
    }

    // ------------------------------------------------------------------
    // Listado
    // ------------------------------------------------------------------

    public function updated(string $propiedad, mixed $valor = null): void
    {
        // Al elegir el equipo se hereda su rutina de mantenimiento, pero sólo
        // en una asignación nueva: editando ya hay una rutina registrada.
        if ($propiedad === 'equipo_id' && $this->mantenimientoId === null) {
            $this->heredarRutinaDelEquipo();
        }

        // Cerrar la orden el mismo día es lo habitual: se propone la fecha.
        if ($propiedad === 'estado' && $this->estado === 'ejecutado' && $this->fecha_ejecucion === '') {
            $this->fecha_ejecucion = Date::today()->format('Y-m-d');
        }

        if (str_starts_with($propiedad, 'filtro') || in_array($propiedad, ['buscar', 'porPagina', 'soloVencidos'], true)) {
            $this->resetPage();

            return;
        }

        // Cualquier campo tocado con el formulario abierto cuenta como cambio
        // pendiente, para avisar antes de cerrarlo y perder lo escrito. El
        // buscador del desplegable de equipos no escribe nada en la orden.
        if ($this->mostrarFormulario && $propiedad !== 'buscarEquipo') {
            $this->formularioSucio = true;
        }
    }

    public function ordenar(string $columna): void
    {
        if ($this->ordenarPor === $columna) {
            $this->ordenDireccion = $this->ordenDireccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenarPor = $columna;
            $this->ordenDireccion = 'asc';
        }

        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'buscar', 'filtroTipo', 'filtroEstado', 'filtroEmpresa',
            'filtroPrioridad', 'filtroTecnico', 'filtroDesde', 'filtroHasta', 'soloVencidos',
            'filtroBandeja',
        ]);

        $this->resetPage();
    }

    /**
     * Cierre rápido desde la tabla: deja la orden ejecutada con fecha de hoy.
     */
    public function marcarEjecutado(int $id): void
    {
        $mantenimiento = Mantenimiento::findOrFail($id);

        if (! $mantenimiento->estaAbierto()) {
            return;
        }

        $mantenimiento->estado = 'ejecutado';
        $mantenimiento->fecha_ejecucion = Date::today();
        $mantenimiento->save();

        $this->sincronizarUltimoMantenimiento($mantenimiento);

        unset($this->mantenimientos, $this->resumen);

        Flux::toast(
            variant: 'success',
            text: 'Mantenimiento '.$mantenimiento->codigo().' marcado como ejecutado.',
        );
    }

    // ------------------------------------------------------------------
    // Vistas de sólo lectura
    // ------------------------------------------------------------------

    public function verMantenimiento(int $id): void
    {
        $this->mantenimientoVisto = $id;
    }

    public function cerrarDetalle(): void
    {
        $this->mantenimientoVisto = null;
    }

    public function verListado(string $tipo): void
    {
        if (! array_key_exists($tipo, self::LISTADOS)) {
            return;
        }

        $this->listadoVisto = $tipo;
    }

    public function cerrarListado(): void
    {
        $this->listadoVisto = '';
    }

    // ------------------------------------------------------------------
    // Formulario
    // ------------------------------------------------------------------

    public function abrirCreacion(string $tipo = 'preventivo'): void
    {
        $this->resetValidation();
        $this->reiniciarFormulario();

        if (array_key_exists($tipo, Mantenimiento::TIPOS)) {
            $this->tipo = $tipo;
        }

        $this->mostrarFormulario = true;
    }

    public function editar(int $id): void
    {
        $mantenimiento = Mantenimiento::findOrFail($id);

        // Editar desde la ficha o desde un listado los reemplaza por el formulario.
        $this->mantenimientoVisto = null;
        $this->listadoVisto = '';

        $this->resetValidation();
        $this->reiniciarFormulario();

        $this->mantenimientoId = $mantenimiento->id;
        $this->equipo_id = (string) $mantenimiento->equipo_id;
        $this->tipo = $mantenimiento->tipo;
        $this->estado = $mantenimiento->estado;

        foreach ($this->camposDirectos() as $campo) {
            $this->{$campo} = (string) ($mantenimiento->{$campo} ?? '');
        }

        $this->fecha_programada = $mantenimiento->fecha_programada->format('Y-m-d');
        $this->fecha_ejecucion = $mantenimiento->fecha_ejecucion?->format('Y-m-d') ?? '';
        $this->costo = $mantenimiento->costo !== null ? (string) $mantenimiento->costo : '';

        $this->subtareas = array_merge($this->subtareas, array_map(
            static fn ($valor): bool => (bool) $valor,
            $mantenimiento->subtareas ?? [],
        ));
        $this->accesorios_estado = array_merge($this->accesorios_estado, array_map(
            static fn ($valor): string => (string) $valor,
            $mantenimiento->accesorios_estado ?? [],
        ));

        $this->mostrarFormulario = true;
    }

    public function marcarTodasLasSubtareas(): void
    {
        $this->subtareas = array_fill_keys(array_keys(Equipo::SUBTAREAS), true);
        $this->formularioSucio = true;
    }

    public function limpiarSubtareas(): void
    {
        $this->subtareas = array_fill_keys(array_keys(Equipo::SUBTAREAS), false);
        $this->formularioSucio = true;
    }

    public function guardar(): void
    {
        $this->validate($this->reglas(), [], $this->etiquetas());

        $equipo = Equipo::findOrFail((int) $this->equipo_id);

        $datos = [
            'equipo_id' => $equipo->id,
            // La empresa se copia del equipo: es la que responde por la orden.
            'empresa_id' => $equipo->empresa_id,
            'tipo' => $this->tipo,
            'estado' => $this->estado,
            'costo' => $this->costo !== '' ? (float) $this->costo : null,
            // La rutina de subtareas sólo tiene sentido en el preventivo.
            'subtareas' => $this->tipo === 'preventivo' ? $this->subtareas : null,
            'accesorios_estado' => array_filter(
                $this->accesorios_estado,
                static fn (string $estado): bool => $estado !== '',
            ),
        ];

        foreach ($this->camposDirectos() as $campo) {
            $datos[$campo] = trim((string) $this->{$campo}) ?: null;
        }

        $mantenimiento = $this->mantenimientoId
            ? Mantenimiento::findOrFail($this->mantenimientoId)
            : new Mantenimiento;

        $mantenimiento->fill($datos)->save();

        $this->sincronizarUltimoMantenimiento($mantenimiento);

        $asignado = $this->mantenimientoId === null;

        $this->cerrarFormulario();

        unset($this->mantenimientos, $this->resumen, $this->tecnicos);

        Flux::toast(
            variant: 'success',
            text: $asignado
                ? 'Mantenimiento '.$mantenimiento->codigo().' asignado a «'.$equipo->descripcion.'».'
                : 'Mantenimiento '.$mantenimiento->codigo().' actualizado correctamente.',
        );
    }

    /**
     * Cierre pedido por el usuario: si hay datos escritos sin guardar, primero
     * se pregunta; si no, se cierra directamente.
     */
    public function intentarCerrarFormulario(): void
    {
        if ($this->formularioSucio) {
            $this->confirmarCierreFormulario = true;

            return;
        }

        $this->cerrarFormulario();
    }

    public function continuarEditando(): void
    {
        $this->confirmarCierreFormulario = false;
    }

    public function cerrarFormulario(): void
    {
        $this->mostrarFormulario = false;
        $this->confirmarCierreFormulario = false;
        $this->resetValidation();
        $this->reiniciarFormulario();
    }

    // ------------------------------------------------------------------
    // Eliminación
    // ------------------------------------------------------------------

    public function confirmarEliminacion(int $id): void
    {
        // Eliminar desde la ficha la reemplaza por la confirmación.
        $this->mantenimientoVisto = null;

        $this->mantenimientoAEliminar = $id;
    }

    public function eliminar(): void
    {
        if ($this->mantenimientoAEliminar === null) {
            return;
        }

        $mantenimiento = Mantenimiento::findOrFail($this->mantenimientoAEliminar);
        $codigo = $mantenimiento->codigo();
        $mantenimiento->delete();

        $this->mantenimientoAEliminar = null;

        unset($this->mantenimientos, $this->resumen);

        Flux::toast(variant: 'success', text: 'Mantenimiento '.$codigo.' eliminado.');
    }

    // ------------------------------------------------------------------
    // Apoyo
    // ------------------------------------------------------------------

    /**
     * Consulta del listado con los filtros de la pantalla ya aplicados.
     *
     * @return Builder<Mantenimiento>
     */
    private function consultaFiltrada(): Builder
    {
        return Mantenimiento::query()
            ->when($this->buscar !== '', function (Builder $consulta): void {
                $termino = '%'.$this->buscar.'%';

                $consulta->where(function (Builder $grupo) use ($termino): void {
                    $grupo->where('tecnico', 'like', $termino)
                        ->orWhere('motivo', 'like', $termino)
                        ->orWhere('descripcion', 'like', $termino)
                        ->orWhere('observaciones', 'like', $termino)
                        ->orWhere('repuestos', 'like', $termino)
                        ->orWhereHas('equipo', fn (Builder $e) => $e
                            ->where('descripcion', 'like', $termino)
                            ->orWhere('numero_serie', 'like', $termino))
                        ->orWhereHas('empresa', fn (Builder $e) => $e->where('nombre', 'like', $termino));
                });
            })
            ->when($this->filtroTipo !== '', fn (Builder $q) => $q->where('tipo', $this->filtroTipo))
            ->when($this->filtroEstado !== '', fn (Builder $q) => $q->where('estado', $this->filtroEstado))
            ->when($this->filtroEmpresa !== '', fn (Builder $q) => $q->where('empresa_id', $this->filtroEmpresa))
            ->when($this->filtroPrioridad !== '', fn (Builder $q) => $q->where('prioridad', $this->filtroPrioridad))
            ->when($this->filtroTecnico !== '', fn (Builder $q) => $q->where('tecnico', $this->filtroTecnico))
            ->when($this->filtroDesde !== '', fn (Builder $q) => $q->whereDate('fecha_programada', '>=', $this->filtroDesde))
            ->when($this->filtroHasta !== '', fn (Builder $q) => $q->whereDate('fecha_programada', '<=', $this->filtroHasta))
            ->when($this->soloVencidos, fn (Builder $q) => $q->vencidos())
            ->when($this->filtroBandeja !== '', fn (Builder $q) => $this->aplicarBandeja($q));
    }

    /**
     * Traduce la situación que trae el usuario desde el panel al mismo scope
     * con el que allí se contó. Es lo que garantiza que la cifra del indicador
     * y el número de resultados de este listado coincidan.
     *
     * @param  Builder<Mantenimiento>  $consulta
     */
    private function aplicarBandeja(Builder $consulta): void
    {
        match ($this->filtroBandeja) {
            'estancados' => $consulta->estancados(),
            'mes' => $consulta->programadosEnElMes(Date::today()),
            'novedad' => $consulta->where('tipo', 'preventivo')
                ->where('presenta_novedad', true)
                ->where('estado', 'ejecutado')
                ->whereIn('equipo_id', Equipo::query()->conNovedadPendiente()->select('id')),
            default => null,
        };
    }

    /**
     * Consulta base del listado que abre cada tarjeta de indicadores.
     *
     * @return Builder<Mantenimiento>
     */
    private function consultaDelListado(): Builder
    {
        return match ($this->listadoVisto) {
            'preventivos' => Mantenimiento::query()->where('tipo', 'preventivo'),
            'correctivos' => Mantenimiento::query()->where('tipo', 'correctivo'),
            'pendientes' => Mantenimiento::query()->abiertos(),
            'vencidos' => Mantenimiento::query()->vencidos(),
            'ejecutados' => Mantenimiento::query()->where('estado', 'ejecutado'),
            default => Mantenimiento::query(),
        };
    }

    /**
     * Una orden cerrada actualiza la fecha del último mantenimiento del equipo,
     * que es el dato del que vive el inventario.
     */
    private function sincronizarUltimoMantenimiento(Mantenimiento $mantenimiento): void
    {
        if ($mantenimiento->estado !== 'ejecutado' || $mantenimiento->fecha_ejecucion === null) {
            return;
        }

        $equipo = $mantenimiento->equipo;

        if ($equipo === null) {
            return;
        }

        if ($equipo->ultimo_mantenimiento === null || $equipo->ultimo_mantenimiento->lt($mantenimiento->fecha_ejecucion)) {
            $equipo->ultimo_mantenimiento = $mantenimiento->fecha_ejecucion;
            $equipo->save();
        }
    }

    /**
     * Trae al formulario la rutina definida en la ficha del equipo, que es la
     * plantilla de la que parte cada mantenimiento preventivo.
     */
    private function heredarRutinaDelEquipo(): void
    {
        $equipo = $this->equipoSeleccionado;

        $this->subtareas = array_fill_keys(array_keys(Equipo::SUBTAREAS), false);
        $this->accesorios_estado = array_fill_keys(array_keys(Equipo::ACCESORIOS), '');

        if ($equipo === null) {
            return;
        }

        $this->subtareas = array_merge($this->subtareas, array_map(
            static fn ($valor): bool => (bool) $valor,
            $equipo->subtareas ?? [],
        ));

        if ($this->prioridad === '' && $equipo->prioridad) {
            $this->prioridad = $equipo->prioridad;
        }
    }

    /**
     * Campos cuyo nombre coincide en el formulario y en la tabla.
     *
     * @return list<string>
     */
    private function camposDirectos(): array
    {
        return [
            'prioridad', 'fecha_programada', 'fecha_ejecucion', 'tecnico',
            'motivo', 'descripcion', 'repuestos', 'observaciones',
        ];
    }

    private function reiniciarFormulario(): void
    {
        $this->reset([
            'mantenimientoId', 'equipo_id', 'buscarEquipo', 'tipo', 'estado', 'prioridad',
            'fecha_programada', 'fecha_ejecucion', 'tecnico', 'motivo',
            'descripcion', 'repuestos', 'observaciones', 'costo',
        ]);

        $this->fecha_programada = Date::today()->format('Y-m-d');
        $this->subtareas = array_fill_keys(array_keys(Equipo::SUBTAREAS), false);
        $this->accesorios_estado = array_fill_keys(array_keys(Equipo::ACCESORIOS), '');

        $this->formularioSucio = false;
        $this->confirmarCierreFormulario = false;
    }

    /** @return array<string, mixed> */
    private function reglas(): array
    {
        return [
            'equipo_id' => ['required', 'exists:equipos,id'],
            'tipo' => ['required', Rule::in(array_keys(Mantenimiento::TIPOS))],
            'estado' => ['required', Rule::in(array_keys(Mantenimiento::ESTADOS))],
            'prioridad' => [Rule::in(array_merge([''], Mantenimiento::PRIORIDADES))],
            'fecha_programada' => ['required', 'date'],
            // Una orden ejecutada tiene que decir cuándo se ejecutó.
            'fecha_ejecucion' => [$this->estado === 'ejecutado' ? 'required' : 'nullable', 'date'],
            'tecnico' => ['nullable', 'string', 'max:255'],
            // El correctivo nace de una falla: sin ella la orden no se entiende.
            'motivo' => [$this->tipo === 'correctivo' ? 'required' : 'nullable', 'string', 'max:2000'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'repuestos' => ['nullable', 'string', 'max:2000'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'costo' => ['nullable', 'numeric', 'min:0'],
            'subtareas' => ['array'],
            'subtareas.*' => ['boolean'],
            'accesorios_estado' => ['array'],
            'accesorios_estado.*' => [Rule::in(array_merge([''], array_keys(Equipo::ESTADOS_ACCESORIO)))],
        ];
    }

    /** @return array<string, string> */
    private function etiquetas(): array
    {
        return [
            'equipo_id' => 'equipo',
            'tipo' => 'tipo de mantenimiento',
            'estado' => 'estado de la orden',
            'fecha_programada' => 'fecha programada',
            'fecha_ejecucion' => 'fecha de ejecución',
            'tecnico' => 'técnico responsable',
            'motivo' => 'falla o motivo reportado',
            'descripcion' => 'trabajo a realizar',
            'repuestos' => 'repuestos utilizados',
            'costo' => 'costo del servicio',
        ];
    }
}; ?>

<section class="eq-root w-full space-y-6">
    {{-- ───────────────── Encabezado ───────────────── --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <nav class="mb-1 flex items-center gap-1.5 text-[11.5px] font-medium text-zinc-400">
                <span class="text-zinc-500 dark:text-zinc-400">Mantenimientos</span>
            </nav>

            <h1 class="text-2xl font-bold tracking-tight text-carbon dark:text-white">Asignación de mantenimientos</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Órdenes preventivas y correctivas asignadas a los equipos del inventario.
            </p>
        </div>

        <button type="button" class="eq-btn eq-btn-accent" wire:click="abrirCreacion">
            <flux:icon name="plus" variant="mini" class="size-4" />
            Asignar mantenimiento
        </button>
    </div>

    {{-- ───────────────── Indicadores ───────────────── --}}
    @php
        $tarjetas = [
            ['tipo' => 'total', 'etiqueta' => 'Asignados', 'valor' => $this->resumen['total'], 'color' => 'text-carbon dark:text-white'],
            ['tipo' => 'preventivos', 'etiqueta' => 'Preventivos', 'valor' => $this->resumen['preventivos'], 'color' => 'text-signal'],
            ['tipo' => 'correctivos', 'etiqueta' => 'Correctivos', 'valor' => $this->resumen['correctivos'], 'color' => 'text-amber-600 dark:text-amber-500'],
            ['tipo' => 'pendientes', 'etiqueta' => 'Pendientes', 'valor' => $this->resumen['pendientes'], 'color' => 'text-lima-700 dark:text-lima'],
            ['tipo' => 'vencidos', 'etiqueta' => 'Vencidos', 'valor' => $this->resumen['vencidos'], 'color' => 'text-rose-600 dark:text-rose-400'],
            ['tipo' => 'ejecutados', 'etiqueta' => 'Ejecutados', 'valor' => $this->resumen['ejecutados'], 'color' => 'text-emerald-600 dark:text-emerald-400'],
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        @foreach ($tarjetas as $tarjeta)
            <button
                type="button"
                wire:click="verListado('{{ $tarjeta['tipo'] }}')"
                title="Ver el listado de mantenimientos {{ mb_strtolower($tarjeta['etiqueta']) }}"
                class="eq-panel flex w-full cursor-pointer flex-col items-center justify-center gap-1 px-4 py-5 text-center transition duration-300 outline-none hover:-translate-y-1 hover:shadow-lg focus-visible:ring-4 focus-visible:ring-lima/30"
            >
                <p class="text-3xl leading-none font-bold {{ $tarjeta['color'] }}">{{ $tarjeta['valor'] }}</p>
                <p class="text-[11.5px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $tarjeta['etiqueta'] }}</p>
            </button>
        @endforeach
    </div>

    {{-- ───────────────── Procedencia del panel ─────────────────
         Al llegar desde un indicador, el listado aparece ya acotado. Sin este
         aviso el usuario no sabría por qué ve menos órdenes de las que espera. --}}
    @if (array_key_exists($filtroBandeja, static::BANDEJAS))
        <div class="eq-panel flex flex-wrap items-center justify-between gap-3 border-signal/30 bg-signal/5 px-5 py-3 dark:border-signal/25 dark:bg-signal/10">
            <p class="text-[13px] text-carbon dark:text-zinc-200">
                Mostrando sólo: <span class="font-semibold">{{ static::BANDEJAS[$filtroBandeja] }}</span>
            </p>

            <button type="button" class="eq-enlace" wire:click="$set('filtroBandeja', '')">
                Ver todas las órdenes
            </button>
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
                placeholder="Buscar por equipo, serie, empresa, técnico, falla o trabajo realizado…"
                wire:model.live.debounce.400ms="buscar"
            >
            <div wire:loading.delay wire:target="buscar" class="absolute top-1/2 right-4 -translate-y-1/2">
                <flux:icon name="arrow-path" variant="mini" class="size-4 animate-spin text-lima" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="eq-label" for="f-tipo">Tipo de mantenimiento</label>
                <select id="f-tipo" class="eq-select" wire:model.live="filtroTipo">
                    <option value="">Ambos tipos</option>
                    @foreach (\App\Models\Mantenimiento::TIPOS as $clave => $etiqueta)
                        <option value="{{ $clave }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="eq-label" for="f-estado">Estado</label>
                <select id="f-estado" class="eq-select" wire:model.live="filtroEstado">
                    <option value="">Todos los estados</option>
                    @foreach (\App\Models\Mantenimiento::ESTADOS as $clave => $etiqueta)
                        <option value="{{ $clave }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="eq-label" for="f-empresa">Empresa</label>
                <select id="f-empresa" class="eq-select" wire:model.live="filtroEmpresa">
                    <option value="">Todas las empresas</option>
                    @foreach ($this->empresas as $empresa)
                        <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="eq-label" for="f-tecnico">Técnico responsable</label>
                <select id="f-tecnico" class="eq-select" wire:model.live="filtroTecnico">
                    <option value="">Todos los técnicos</option>
                    @foreach ($this->tecnicos as $tecnico)
                        <option value="{{ $tecnico }}">{{ $tecnico }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="eq-label" for="f-prioridad">Prioridad</label>
                <select id="f-prioridad" class="eq-select" wire:model.live="filtroPrioridad">
                    <option value="">Todas</option>
                    @foreach (\App\Models\Mantenimiento::PRIORIDADES as $prioridad)
                        <option value="{{ $prioridad }}">{{ $prioridad }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="eq-label" for="f-desde">Programado desde</label>
                <input id="f-desde" type="date" class="eq-input" wire:model.live="filtroDesde">
            </div>

            <div>
                <label class="eq-label" for="f-hasta">Programado hasta</label>
                <input id="f-hasta" type="date" class="eq-input" wire:model.live="filtroHasta">
            </div>

            <div>
                <label class="eq-label" for="f-por-pagina">Resultados por página</label>
                <select id="f-por-pagina" class="eq-select" wire:model.live="porPagina">
                    @foreach ([10, 25, 50, 100] as $cantidad)
                        <option value="{{ $cantidad }}">{{ $cantidad }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <label class="mt-4 flex max-w-md cursor-pointer items-center gap-3 rounded-xl border border-zinc-200 bg-white px-3.5 py-2.5 transition hover:border-lima dark:border-zinc-700 dark:bg-zinc-900">
            <input type="checkbox" class="size-4 rounded accent-lima" wire:model.live="soloVencidos">
            <span class="text-sm font-medium text-carbon dark:text-zinc-200">Sólo mantenimientos vencidos</span>
        </label>
    </div>

    {{-- ───────────────── Tabla ───────────────── --}}
    <div class="eq-panel overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
            <p class="flex items-center gap-2 text-[13px] font-semibold text-carbon dark:text-zinc-200">
                Preventivos y correctivos
                <span class="eq-chip bg-lima-soft text-lima-700 dark:bg-lima/10 dark:text-lima">
                    {{ $this->mantenimientos->total() }} {{ $this->mantenimientos->total() === 1 ? 'resultado' : 'resultados' }}
                </span>
            </p>

            <p class="text-[12px] text-zinc-500 dark:text-zinc-400">Pulse una fila para abrir la orden completa.</p>
        </div>

        <div wire:loading.delay.class="opacity-50" class="overflow-x-auto transition-opacity">
            <table class="w-full min-w-4xl text-left text-[13px]">
                <thead class="bg-zinc-50/80 text-[11px] font-bold tracking-wide text-zinc-500 uppercase dark:bg-zinc-800/60 dark:text-zinc-400">
                    <tr>
                        @php
                            $columnas = [
                                ['clave' => 'id', 'titulo' => 'Orden', 'ordenable' => true],
                                ['clave' => 'tipo', 'titulo' => 'Tipo', 'ordenable' => true],
                                ['clave' => null, 'titulo' => 'Equipo', 'ordenable' => false],
                                ['clave' => null, 'titulo' => 'Empresa / Área', 'ordenable' => false],
                                ['clave' => 'fecha_programada', 'titulo' => 'Programado', 'ordenable' => true],
                                ['clave' => 'estado', 'titulo' => 'Estado', 'ordenable' => true],
                                ['clave' => null, 'titulo' => 'Técnico', 'ordenable' => false],
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
                    @forelse ($this->mantenimientos as $mantenimiento)
                        <tr
                            wire:key="mantenimiento-{{ $mantenimiento->id }}"
                            tabindex="0"
                            wire:click="verMantenimiento({{ $mantenimiento->id }})"
                            wire:keydown.enter="verMantenimiento({{ $mantenimiento->id }})"
                            title="Ver la orden {{ $mantenimiento->codigo() }}"
                            class="cursor-pointer transition duration-150 outline-none hover:bg-lima-soft/40 focus-visible:bg-lima-soft/60 dark:hover:bg-zinc-800/50 dark:focus-visible:bg-zinc-800/70"
                        >
                            <td class="px-4 py-3 align-top">
                                <p class="font-mono text-[12.5px] font-semibold text-carbon dark:text-zinc-100">{{ $mantenimiento->codigo() }}</p>
                                @if ($mantenimiento->prioridad)
                                    <p class="text-[11.5px] text-zinc-500 dark:text-zinc-400">Prioridad {{ mb_strtolower($mantenimiento->prioridad) }}</p>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top">
                                <span class="eq-chip {{ $this::COLORES_TIPO[$mantenimiento->tipo] ?? '' }}">
                                    <flux:icon name="{{ $mantenimiento->tipo === 'correctivo' ? 'exclamation-triangle' : 'calendar-days' }}" variant="micro" class="size-3" />
                                    {{ $mantenimiento->tipoEtiqueta() }}
                                </span>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-zinc-200 bg-white text-[11px] font-bold text-carbon shadow-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                        @if ($mantenimiento->equipo?->fotoUrl())
                                            <img src="{{ $mantenimiento->equipo->fotoUrl() }}" alt="Foto de {{ $mantenimiento->equipo->descripcion }}" class="size-full object-cover">
                                        @else
                                            {{ $mantenimiento->equipo?->iniciales() ?? '—' }}
                                        @endif
                                    </span>

                                    <div class="min-w-0">
                                        <p class="font-semibold text-carbon dark:text-zinc-100">{{ $mantenimiento->equipo?->descripcion ?? 'Equipo retirado' }}</p>
                                        <p class="text-[12px] text-zinc-500 dark:text-zinc-400">
                                            {{ $mantenimiento->equipo?->marca?->nombre ?? '—' }}
                                            @if ($mantenimiento->equipo?->modelo) · {{ $mantenimiento->equipo->modelo->nombre }} @endif
                                        </p>
                                        @if ($mantenimiento->equipo?->numero_serie)
                                            <p class="font-mono text-[11.5px] text-zinc-400">S/N {{ $mantenimiento->equipo->numero_serie }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <p class="font-medium text-carbon dark:text-zinc-200">{{ $mantenimiento->empresa?->nombre ?? 'Sin empresa' }}</p>
                                @if ($mantenimiento->equipo?->area)
                                    <p class="flex items-center gap-1 text-[12px] text-zinc-500 dark:text-zinc-400">
                                        <flux:icon name="map-pin" variant="micro" class="size-3 text-lima" />
                                        {{ $mantenimiento->equipo->area->nombre }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top">
                                <p class="font-medium text-carbon dark:text-zinc-200">{{ $mantenimiento->fecha_programada->format('d/m/Y') }}</p>

                                @if ($mantenimiento->estaVencido())
                                    @php $atraso = abs($mantenimiento->diasRestantes()); @endphp
                                    <span class="eq-chip mt-1 bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400">
                                        <flux:icon name="bell-alert" variant="micro" class="size-3" />
                                        Vencido {{ $atraso }} {{ $atraso === 1 ? 'día' : 'días' }}
                                    </span>
                                @elseif ($mantenimiento->estaAbierto())
                                    @php $faltan = $mantenimiento->diasRestantes(); @endphp
                                    <p class="text-[11.5px] text-zinc-500 dark:text-zinc-400">
                                        {{ $faltan === 0 ? 'Programado para hoy' : 'Faltan '.$faltan.' '.($faltan === 1 ? 'día' : 'días') }}
                                    </p>
                                @elseif ($mantenimiento->fecha_ejecucion)
                                    <p class="text-[11.5px] text-zinc-500 dark:text-zinc-400">
                                        Ejecutado {{ $mantenimiento->fecha_ejecucion->format('d/m/Y') }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top">
                                <span class="eq-chip {{ $this::COLORES_ESTADO[$mantenimiento->estado] ?? '' }}">
                                    {{ $mantenimiento->estadoEtiqueta() }}
                                </span>
                            </td>

                            <td class="px-4 py-3 align-top">
                                @if ($mantenimiento->tecnico)
                                    <p class="flex items-center gap-1 font-medium text-carbon dark:text-zinc-200">
                                        <flux:icon name="user" variant="micro" class="size-3.5 text-zinc-400" />
                                        {{ $mantenimiento->tecnico }}
                                    </p>
                                @else
                                    <span class="text-zinc-400">Sin asignar</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="flex items-center gap-1">
                                    @if ($mantenimiento->estaAbierto())
                                        <button
                                            type="button"
                                            class="eq-icon-btn hover:!bg-emerald-50 hover:!text-emerald-600 dark:hover:!bg-emerald-500/10 dark:hover:!text-emerald-400"
                                            wire:click.stop="marcarEjecutado({{ $mantenimiento->id }})"
                                            title="Marcar {{ $mantenimiento->codigo() }} como ejecutado hoy"
                                        >
                                            <flux:icon name="check-circle" variant="mini" class="size-4" />
                                        </button>
                                    @endif

                                    {{-- Una orden ejecutada ya puede reportarse; el enlace no debe
                                         abrir además la ficha de la fila que lo contiene. --}}
                                    @if ($mantenimiento->estado === 'ejecutado')
                                        @php $reporteEmitido = $mantenimiento->reporte; @endphp

                                        <a
                                            x-data
                                            x-on:click.stop
                                            href="{{ route('mantenimientos.reporte', [$mantenimiento, 'imprimir' => 1]) }}"
                                            target="_blank"
                                            rel="noopener"
                                            @class([
                                                'eq-icon-btn hover:!bg-lima-soft hover:!text-lima-700 dark:hover:!bg-lima/10 dark:hover:!text-lima',
                                                '!text-lima-700 dark:!text-lima' => $reporteEmitido,
                                            ])
                                            title="{{ $reporteEmitido
                                                ? 'Reporte '.$reporteEmitido->codigo().' ya generado el '.$reporteEmitido->ultima_generacion->format('d/m/Y').'; se vuelve a emitir'
                                                : 'Generar el reporte de '.$mantenimiento->codigo() }}"
                                        >
                                            <flux:icon name="document-arrow-down" variant="mini" class="size-4" />
                                        </a>
                                    @endif

                                    <button type="button" class="eq-icon-btn" wire:click.stop="editar({{ $mantenimiento->id }})" title="Editar {{ $mantenimiento->codigo() }}">
                                        <flux:icon name="pencil-square" variant="mini" class="size-4" />
                                    </button>

                                    <button
                                        type="button"
                                        class="eq-icon-btn hover:!bg-rose-50 hover:!text-rose-600 dark:hover:!bg-rose-500/10 dark:hover:!text-rose-400"
                                        wire:click.stop="confirmarEliminacion({{ $mantenimiento->id }})"
                                        title="Eliminar {{ $mantenimiento->codigo() }}"
                                    >
                                        <flux:icon name="trash" variant="mini" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                                    <span class="flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon name="wrench-screwdriver" class="size-7 text-zinc-400" />
                                    </span>
                                    <p class="text-[15px] font-semibold text-carbon dark:text-zinc-200">
                                        {{ $this->hayFiltrosActivos ? 'Ningún mantenimiento coincide con los filtros' : 'Todavía no hay mantenimientos asignados' }}
                                    </p>
                                    <p class="text-[13px] text-zinc-500 dark:text-zinc-400">
                                        {{ $this->hayFiltrosActivos ? 'Ajuste o limpie los filtros para ver más resultados.' : 'Asigne el primer preventivo o correctivo a un equipo del inventario.' }}
                                    </p>
                                    @if ($this->hayFiltrosActivos)
                                        <button type="button" class="eq-btn eq-btn-ghost" wire:click="limpiarFiltros">Limpiar filtros</button>
                                    @else
                                        <button type="button" class="eq-btn eq-btn-accent" wire:click="abrirCreacion">
                                            <flux:icon name="plus" variant="mini" class="size-4" /> Asignar mantenimiento
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->mantenimientos->hasPages())
            <div class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-800">
                {{ $this->mantenimientos->links() }}
            </div>
        @endif
    </div>

    {{-- Los modales se teletransportan al <body>: dentro del contenedor de la
         página el velo `fixed` no llegaba a cubrir toda la pantalla. --}}
    @teleport('body')
        @include('pages.mantenimientos.partials.modal-formulario', [
            'equiposDisponibles' => $this->equiposParaFormulario,
            'equipo' => $this->equipoSeleccionado,
            'tecnicosSugeridos' => $this->tecnicos,
            'topeEquipos' => $this::TOPE_EQUIPOS,
        ])
    @endteleport

    @teleport('body')
        @include('pages.mantenimientos.partials.modal-detalle', [
            'mantenimiento' => $this->mantenimientoDetalle,
            'coloresTipo' => $this::COLORES_TIPO,
            'coloresEstado' => $this::COLORES_ESTADO,
        ])
    @endteleport

    @teleport('body')
        @include('pages.mantenimientos.partials.modal-listado', [
            'listados' => $this::LISTADOS,
            'mantenimientosListados' => $this->listadoMantenimientos,
            'totalListado' => $this->listadoTotal,
            'topeListado' => $this::TOPE_LISTADO,
            'coloresTipo' => $this::COLORES_TIPO,
            'coloresEstado' => $this::COLORES_ESTADO,
        ])
    @endteleport

    @teleport('body')
        @include('pages.mantenimientos.partials.modal-eliminar', ['mantenimiento' => $this->mantenimientoEliminable])
    @endteleport
</section>
