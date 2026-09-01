<?php

use App\Models\Area;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\Modelo;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Title('Equipos')] class extends Component {
    use WithFileUploads, WithPagination;

    /**
     * Fases del asistente de registro, en orden.
     *
     * @var array<int, array{titulo: string, icono: string}>
     */
    public const PASOS = [
        1 => ['titulo' => 'Información general', 'icono' => 'clipboard-document-list'],
        2 => ['titulo' => 'Características técnicas', 'icono' => 'bolt'],
        3 => ['titulo' => 'Subtareas', 'icono' => 'wrench-screwdriver'],
        4 => ['titulo' => 'Accesorios', 'icono' => 'squares-2x2'],
        5 => ['titulo' => 'Observaciones', 'icono' => 'document-check'],
    ];

    /**
     * Formas de ver el listado: tarjetas para explorar, tabla para comparar.
     *
     * @var array<string, array{titulo: string, icono: string}>
     */
    public const VISTAS = [
        'cards' => ['titulo' => 'Tarjetas', 'icono' => 'squares-2x2'],
        'tabla' => ['titulo' => 'Tabla', 'icono' => 'table-cells'],
    ];

    /**
     * Columnas por las que se puede ordenar el listado.
     *
     * @var array<string, string>
     */
    public const ORDENABLES = [
        'created_at' => 'Más recientes',
        'descripcion' => 'Equipo',
        'numero_serie' => 'N.º de serie',
        'activo' => 'Estado',
    ];

    // ------------------------------------------------------------------
    // Filtros del listado
    // ------------------------------------------------------------------

    public string $buscar = '';

    public string $filtroEmpresa = '';

    public string $filtroArea = '';

    public string $filtroMarca = '';

    public string $filtroModelo = '';

    public string $filtroSerie = '';

    public string $filtroRiesgo = '';

    public string $filtroActivo = '';

    public int $porPagina = 10;

    /** Vista del listado; se recuerda entre visitas para no reelegirla cada vez. */
    #[Session(key: 'equipos.vista')]
    public string $vista = 'cards';

    public string $ordenarPor = 'created_at';

    public string $ordenDireccion = 'desc';

    // ------------------------------------------------------------------
    // Asistente de registro
    // ------------------------------------------------------------------

    public bool $mostrarFormulario = false;

    /** Hay datos escritos en el asistente que todavía no se han guardado. */
    public bool $formularioSucio = false;

    /** Muestra el aviso previo a cerrar el asistente con cambios pendientes. */
    public bool $confirmarCierreFormulario = false;

    public int $paso = 1;

    public int $pasoMaximo = 1;

    public ?int $equipoId = null;

    public string $empresa_id = '';

    public string $areaNombre = '';

    public string $marcaNombre = '';

    public string $modeloNombre = '';

    // Cada desplegable de catálogo puede cambiarse por un campo de texto para
    // dar de alta un valor que todavía no existe.
    public bool $areaNueva = false;

    public bool $marcaNueva = false;

    public bool $modeloNuevo = false;

    public string $descripcion = '';

    public string $numero_serie = '';

    public string $registro_invima = '';

    public string $clasificacion_riesgo = '';

    public string $clasificacion_especialidad = '';

    public string $fabricante = '';

    public string $pais_origen = '';

    public string $telefono_fabricante = '';

    public string $tipo_adquisicion = '';

    public string $garantia_vence = '';

    public string $prioridad = '';

    public string $observaciones_tecnicas = '';

    public string $observaciones_generales = '';

    public string $mantenimiento = '';

    public string $ultimo_mantenimiento = '';

    public bool $activo = true;

    public $foto = null;

    public ?string $fotoActual = null;

    public string $suministro_electrico = 'ac';

    public string $voltaje = '';

    public string $amperaje = '';

    public string $frecuencia = '';

    public string $corriente = '';

    public string $potencia = '';

    public string $voltios = '';

    public string $temperatura = '';

    public string $presion = '';

    public string $peso = '';

    public string $velocidad = '';

    public string $tecnologia_predominante = '';

    /** @var array<string, bool> */
    public array $subtareas = [];

    /** @var array<string, string> */
    public array $accesorios_estado = [];

    public string $componentes = '';

    public string $observaciones_ot = '';

    public ?int $equipoAEliminar = null;

    // ------------------------------------------------------------------
    // Vistas de sólo lectura
    // ------------------------------------------------------------------

    public ?int $equipoVisto = null;

    public string $listadoVisto = '';

    /**
     * Listados de sólo lectura que abren las tarjetas de indicadores.
     *
     * @var array<string, array{titulo: string, descripcion: string, icono: string}>
     */
    public const LISTADOS = [
        'total' => [
            'titulo' => 'Equipos registrados',
            'descripcion' => 'Todo el inventario, asignado o en bodega.',
            'icono' => 'cpu-chip',
        ],
        'activos' => [
            'titulo' => 'Equipos en servicio',
            'descripcion' => 'Equipos operativos y disponibles para uso clínico.',
            'icono' => 'check-badge',
        ],
        'fueraDeServicio' => [
            'titulo' => 'Equipos fuera de servicio',
            'descripcion' => 'Equipos dados de baja temporal o pendientes de intervención.',
            'icono' => 'bolt-slash',
        ],
        'sinAsignar' => [
            'titulo' => 'Equipos sin asignar',
            'descripcion' => 'Inventario maestro todavía sin empresa ni área.',
            'icono' => 'rectangle-stack',
        ],
    ];

    /** Máximo de filas que muestra el modal de listado antes de recortar. */
    public const TOPE_LISTADO = 100;

    public function mount(): void
    {
        $this->reiniciarFormulario();
    }

    // ------------------------------------------------------------------
    // Datos derivados
    // ------------------------------------------------------------------

    /** @return LengthAwarePaginator<int, Equipo> */
    #[Computed]
    public function equipos(): LengthAwarePaginator
    {
        $columna = array_key_exists($this->ordenarPor, self::ORDENABLES) ? $this->ordenarPor : 'created_at';

        return Equipo::query()
            ->with(['empresa', 'area', 'marca', 'modelo'])
            ->when($this->buscar !== '', function (Builder $consulta): void {
                $termino = '%'.$this->buscar.'%';

                $consulta->where(function (Builder $grupo) use ($termino): void {
                    $grupo->where('descripcion', 'like', $termino)
                        ->orWhere('numero_serie', 'like', $termino)
                        ->orWhere('registro_invima', 'like', $termino)
                        ->orWhere('clasificacion_especialidad', 'like', $termino)
                        ->orWhere('observaciones_tecnicas', 'like', $termino)
                        ->orWhereHas('marca', fn (Builder $m) => $m->where('nombre', 'like', $termino))
                        ->orWhereHas('modelo', fn (Builder $m) => $m->where('nombre', 'like', $termino))
                        ->orWhereHas('empresa', fn (Builder $m) => $m->where('nombre', 'like', $termino))
                        ->orWhereHas('area', fn (Builder $m) => $m->where('nombre', 'like', $termino));
                });
            })
            ->when($this->filtroEmpresa !== '', fn (Builder $q) => $q->where('empresa_id', $this->filtroEmpresa))
            ->when($this->filtroArea !== '', fn (Builder $q) => $q->where('area_id', $this->filtroArea))
            ->when($this->filtroMarca !== '', fn (Builder $q) => $q->where('marca_id', $this->filtroMarca))
            ->when($this->filtroModelo !== '', fn (Builder $q) => $q->where('modelo_id', $this->filtroModelo))
            ->when($this->filtroSerie !== '', fn (Builder $q) => $q->where('numero_serie', 'like', '%'.$this->filtroSerie.'%'))
            ->when($this->filtroRiesgo !== '', fn (Builder $q) => $q->where('clasificacion_riesgo', $this->filtroRiesgo))
            ->when($this->filtroActivo !== '', fn (Builder $q) => $q->where('activo', $this->filtroActivo === '1'))
            ->orderBy($columna, $this->ordenDireccion === 'asc' ? 'asc' : 'desc')
            ->paginate($this->porPagina);
    }

    /** @return array<string, int> */
    #[Computed]
    public function resumen(): array
    {
        return [
            'total' => Equipo::count(),
            'activos' => Equipo::where('activo', true)->count(),
            'fueraDeServicio' => Equipo::where('activo', false)->count(),
            'sinAsignar' => Equipo::whereNull('empresa_id')->count(),
        ];
    }

    /** @return Collection<int, Empresa> */
    #[Computed]
    public function empresas(): Collection
    {
        return Empresa::orderBy('nombre')->get();
    }

    /** @return Collection<int, Marca> */
    #[Computed]
    public function marcas(): Collection
    {
        return Marca::orderBy('nombre')->get();
    }

    /** Modelos disponibles para el filtro, acotados a la marca filtrada. */
    /** @return Collection<int, Modelo> */
    #[Computed]
    public function modelosFiltro(): Collection
    {
        return Modelo::query()
            ->when($this->filtroMarca !== '', fn (Builder $q) => $q->where('marca_id', $this->filtroMarca))
            ->orderBy('nombre')
            ->get();
    }

    /** @return Collection<int, Area> */
    #[Computed]
    public function areasFiltro(): Collection
    {
        if ($this->filtroEmpresa === '') {
            return collect();
        }

        return Area::where('empresa_id', $this->filtroEmpresa)->orderBy('nombre')->get();
    }

    /** Modelos sugeridos en el formulario, según la marca escrita. */
    /** @return Collection<int, Modelo> */
    #[Computed]
    public function modelosDeMarca(): Collection
    {
        $marca = Marca::where('nombre', trim($this->marcaNombre))->first();

        return $marca ? $marca->modelos()->orderBy('nombre')->get() : collect();
    }

    /** @return Collection<int, Area> */
    #[Computed]
    public function areasDeEmpresa(): Collection
    {
        if ($this->empresa_id === '') {
            return collect();
        }

        return Area::where('empresa_id', $this->empresa_id)->orderBy('nombre')->get();
    }

    #[Computed]
    public function nombreEmpresaSeleccionada(): ?string
    {
        if ($this->empresa_id === '') {
            return null;
        }

        return Empresa::find($this->empresa_id)?->nombre;
    }

    /** Equipo abierto en la vista de detalle, con sus relaciones cargadas. */
    #[Computed]
    public function equipoDetalle(): ?Equipo
    {
        if ($this->equipoVisto === null) {
            return null;
        }

        return Equipo::with(['empresa', 'area', 'marca', 'modelo'])->find($this->equipoVisto);
    }

    /**
     * Equipos que muestra el modal de una tarjeta de indicadores.
     *
     * @return Collection<int, Equipo>
     */
    #[Computed]
    public function listadoEquipos(): Collection
    {
        if ($this->listadoVisto === '') {
            return collect();
        }

        return $this->consultaDelListado()
            ->with(['empresa', 'area', 'marca', 'modelo'])
            ->orderBy('descripcion')
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
            || $this->filtroEmpresa !== ''
            || $this->filtroArea !== ''
            || $this->filtroMarca !== ''
            || $this->filtroModelo !== ''
            || $this->filtroSerie !== ''
            || $this->filtroRiesgo !== ''
            || $this->filtroActivo !== '';
    }

    // ------------------------------------------------------------------
    // Listado
    // ------------------------------------------------------------------

    public function updated(string $propiedad, mixed $valor = null): void
    {
        if ($propiedad === 'filtroEmpresa') {
            $this->filtroArea = '';
        }

        if ($propiedad === 'filtroMarca') {
            $this->filtroModelo = '';
        }

        if ($propiedad === 'empresa_id') {
            $this->areaNombre = '';
            $this->areaNueva = false;
        }

        // Al cambiar de marca en el desplegable, el modelo elegido deja de ser
        // válido. Escribiendo una marca nueva no se toca, porque su modelo
        // también se está escribiendo a mano.
        if ($propiedad === 'marcaNombre' && ! $this->marcaNueva) {
            $this->modeloNombre = '';
        }

        if (str_starts_with($propiedad, 'filtro') || in_array($propiedad, ['buscar', 'porPagina', 'ordenarPor'], true)) {
            $this->resetPage();

            return;
        }

        // Cualquier campo tocado con el asistente abierto cuenta como cambio
        // pendiente, para avisar antes de cerrarlo y perder lo escrito.
        if ($this->mostrarFormulario) {
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

    /** Alterna sólo la dirección: en tarjetas la columna la fija el desplegable. */
    public function alternarDireccion(): void
    {
        $this->ordenDireccion = $this->ordenDireccion === 'asc' ? 'desc' : 'asc';

        $this->resetPage();
    }

    public function cambiarVista(string $vista): void
    {
        $this->vista = array_key_exists($vista, self::VISTAS) ? $vista : 'cards';
    }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'buscar', 'filtroEmpresa', 'filtroArea', 'filtroMarca',
            'filtroModelo', 'filtroSerie', 'filtroRiesgo', 'filtroActivo',
        ]);

        $this->resetPage();
    }

    public function alternarActivo(int $id): void
    {
        $equipo = Equipo::findOrFail($id);
        $equipo->activo = ! $equipo->activo;
        $equipo->save();

        unset($this->equipos, $this->resumen);

        Flux::toast(
            variant: $equipo->activo ? 'success' : 'warning',
            text: $equipo->activo ? 'Equipo marcado como activo.' : 'Equipo marcado fuera de servicio.',
        );
    }

    // ------------------------------------------------------------------
    // Vistas de sólo lectura
    // ------------------------------------------------------------------

    public function verEquipo(int $id): void
    {
        $this->equipoVisto = $id;
    }

    public function cerrarDetalle(): void
    {
        $this->equipoVisto = null;
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
    // Asistente
    // ------------------------------------------------------------------

    public function abrirCreacion(): void
    {
        $this->reiniciarFormulario();
        $this->mostrarFormulario = true;
    }

    public function editar(int $id): void
    {
        $equipo = Equipo::with(['area', 'marca', 'modelo'])->findOrFail($id);

        // Editar desde la vista de detalle la reemplaza por el asistente.
        $this->equipoVisto = null;
        $this->listadoVisto = '';

        $this->reiniciarFormulario();

        $this->equipoId = $equipo->id;
        $this->empresa_id = (string) ($equipo->empresa_id ?? '');
        $this->areaNombre = $equipo->area?->nombre ?? '';
        $this->marcaNombre = $equipo->marca?->nombre ?? '';
        $this->modeloNombre = $equipo->modelo?->nombre ?? '';
        $this->fotoActual = $equipo->foto_path;

        foreach ($this->camposDirectos() as $campo) {
            $this->{$campo} = (string) ($equipo->{$campo} ?? '');
        }

        $this->garantia_vence = $equipo->garantia_vence?->format('Y-m-d') ?? '';
        $this->ultimo_mantenimiento = $equipo->ultimo_mantenimiento?->format('Y-m-d') ?? '';

        $this->activo = (bool) $equipo->activo;
        $this->suministro_electrico = $equipo->suministro_electrico ?: 'ac';
        $this->subtareas = array_merge($this->subtareas, array_map(
            static fn ($valor): bool => (bool) $valor,
            $equipo->subtareas ?? [],
        ));
        $this->accesorios_estado = array_merge($this->accesorios_estado, array_map(
            static fn ($valor): string => (string) $valor,
            $equipo->accesorios_estado ?? [],
        ));

        $this->paso = 1;
        $this->pasoMaximo = count(self::PASOS);
        $this->mostrarFormulario = true;
    }

    /**
     * Alterna entre elegir una marca del desplegable y escribir una nueva.
     * Una marca nueva no tiene modelos, así que su modelo también se escribe.
     */
    public function alternarMarcaNueva(): void
    {
        $this->marcaNueva = ! $this->marcaNueva;
        $this->marcaNombre = '';
        $this->modeloNombre = '';
        $this->modeloNuevo = $this->marcaNueva;
        $this->resetValidation(['marcaNombre', 'modeloNombre']);
    }

    public function alternarModeloNuevo(): void
    {
        $this->modeloNuevo = ! $this->modeloNuevo;
        $this->modeloNombre = '';
        $this->resetValidation('modeloNombre');
    }

    public function alternarAreaNueva(): void
    {
        $this->areaNueva = ! $this->areaNueva;
        $this->areaNombre = '';
        $this->resetValidation('areaNombre');
    }

    public function siguiente(): void
    {
        $this->validate($this->reglasDelPaso($this->paso), [], $this->etiquetas());

        if ($this->paso < count(self::PASOS)) {
            $this->paso++;
            $this->pasoMaximo = max($this->pasoMaximo, $this->paso);
        }
    }

    public function anterior(): void
    {
        if ($this->paso > 1) {
            $this->paso--;
        }
    }

    /**
     * Salta a una fase concreta. Hacia adelante sólo si las fases
     * intermedias ya están completas.
     */
    public function irAPaso(int $destino): void
    {
        if ($destino < 1 || $destino > count(self::PASOS) || $destino === $this->paso) {
            return;
        }

        if ($destino > $this->paso) {
            for ($intermedio = $this->paso; $intermedio < $destino; $intermedio++) {
                $this->validate($this->reglasDelPaso($intermedio), [], $this->etiquetas());
            }
        }

        $this->paso = $destino;
        $this->pasoMaximo = max($this->pasoMaximo, $destino);
    }

    public function guardar(): void
    {
        foreach (array_keys(self::PASOS) as $numero) {
            try {
                $this->validate($this->reglasDelPaso($numero), [], $this->etiquetas());
            } catch (ValidationException $excepcion) {
                $this->paso = $numero;

                throw $excepcion;
            }
        }

        $marca = Marca::firstOrCreate(['nombre' => trim($this->marcaNombre)]);
        $modelo = Modelo::firstOrCreate(['marca_id' => $marca->id, 'nombre' => trim($this->modeloNombre)]);

        $area = null;

        if ($this->empresa_id !== '' && trim($this->areaNombre) !== '') {
            $area = Area::firstOrCreate([
                'empresa_id' => (int) $this->empresa_id,
                'nombre' => trim($this->areaNombre),
            ]);
        }

        $datos = [
            'empresa_id' => $this->empresa_id !== '' ? (int) $this->empresa_id : null,
            'area_id' => $area?->id,
            'marca_id' => $marca->id,
            'modelo_id' => $modelo->id,
            'activo' => $this->activo,
            'suministro_electrico' => $this->suministro_electrico,
            'subtareas' => $this->subtareas,
            'accesorios_estado' => array_filter($this->accesorios_estado, static fn (string $estado): bool => $estado !== ''),
        ];

        foreach ($this->camposDirectos() as $campo) {
            $datos[$campo] = trim((string) $this->{$campo}) ?: null;
        }

        if ($this->foto) {
            $datos['foto_path'] = $this->foto->store('equipos', 'public');
        }

        $equipo = $this->equipoId ? Equipo::findOrFail($this->equipoId) : new Equipo;

        // Al reemplazar la foto se borra la anterior para no dejar huérfanos.
        $fotoPrevia = $equipo->foto_path;

        $equipo->fill($datos)->save();

        if ($this->foto && $fotoPrevia && $fotoPrevia !== $equipo->foto_path) {
            Storage::disk('public')->delete($fotoPrevia);
        }

        $creado = $this->equipoId === null;

        $this->cerrarFormulario();

        unset($this->equipos, $this->resumen, $this->marcas, $this->empresas);

        Flux::toast(
            variant: 'success',
            text: $creado
                ? 'Equipo «'.$equipo->descripcion.'» registrado correctamente.'
                : 'Equipo «'.$equipo->descripcion.'» actualizado correctamente.',
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

    public function marcarTodasLasSubtareas(): void
    {
        $this->subtareas = array_fill_keys(array_keys(Equipo::SUBTAREAS), true);
    }

    public function limpiarSubtareas(): void
    {
        $this->subtareas = array_fill_keys(array_keys(Equipo::SUBTAREAS), false);
    }

    // ------------------------------------------------------------------
    // Eliminación
    // ------------------------------------------------------------------

    public function confirmarEliminacion(int $id): void
    {
        // Eliminar desde la vista de detalle la reemplaza por la confirmación.
        $this->equipoVisto = null;

        $this->equipoAEliminar = $id;
    }

    public function eliminar(): void
    {
        if ($this->equipoAEliminar === null) {
            return;
        }

        $equipo = Equipo::findOrFail($this->equipoAEliminar);
        $descripcion = $equipo->descripcion;
        $equipo->delete();

        $this->equipoAEliminar = null;

        unset($this->equipos, $this->resumen);

        Flux::toast(variant: 'success', text: 'Equipo «'.$descripcion.'» eliminado.');
    }

    // ------------------------------------------------------------------
    // Apoyo
    // ------------------------------------------------------------------

    /**
     * Consulta base del listado que abre cada tarjeta de indicadores.
     *
     * @return Builder<Equipo>
     */
    private function consultaDelListado(): Builder
    {
        return match ($this->listadoVisto) {
            'activos' => Equipo::query()->where('activo', true),
            'fueraDeServicio' => Equipo::query()->where('activo', false),
            'sinAsignar' => Equipo::query()->whereNull('empresa_id'),
            default => Equipo::query(),
        };
    }

    /**
     * Campos cuyo nombre coincide en el formulario y en la tabla.
     *
     * @return list<string>
     */
    private function camposDirectos(): array
    {
        return [
            'descripcion', 'numero_serie', 'registro_invima', 'clasificacion_riesgo',
            'clasificacion_especialidad', 'fabricante', 'pais_origen', 'telefono_fabricante',
            'tipo_adquisicion', 'garantia_vence', 'prioridad', 'observaciones_tecnicas',
            'observaciones_generales', 'mantenimiento', 'ultimo_mantenimiento',
            'voltaje', 'amperaje', 'frecuencia', 'corriente', 'potencia',
            'voltios', 'temperatura', 'presion', 'peso', 'velocidad', 'tecnologia_predominante',
            'componentes', 'observaciones_ot',
        ];
    }

    private function reiniciarFormulario(): void
    {
        $this->reset([
            'equipoId', 'empresa_id', 'areaNombre', 'marcaNombre', 'modeloNombre',
            'descripcion', 'numero_serie', 'registro_invima', 'clasificacion_riesgo',
            'clasificacion_especialidad', 'fabricante', 'pais_origen', 'telefono_fabricante',
            'tipo_adquisicion', 'garantia_vence', 'prioridad', 'observaciones_tecnicas',
            'observaciones_generales', 'areaNueva', 'marcaNueva', 'modeloNuevo',
            'mantenimiento', 'ultimo_mantenimiento', 'activo', 'foto', 'fotoActual', 'suministro_electrico',
            'voltaje', 'amperaje', 'frecuencia', 'corriente', 'potencia', 'voltios',
            'temperatura', 'presion', 'peso', 'velocidad', 'tecnologia_predominante',
            'componentes', 'observaciones_ot', 'paso', 'pasoMaximo',
        ]);

        $this->subtareas = array_fill_keys(array_keys(Equipo::SUBTAREAS), false);
        $this->accesorios_estado = array_fill_keys(array_keys(Equipo::ACCESORIOS), '');

        $this->formularioSucio = false;
        $this->confirmarCierreFormulario = false;
    }

    /** @return array<string, mixed> */
    private function reglasDelPaso(int $paso): array
    {
        return match ($paso) {
            1 => [
                'descripcion' => ['required', 'string', 'max:255'],
                'marcaNombre' => ['required', 'string', 'max:255'],
                'modeloNombre' => ['required', 'string', 'max:255'],
                'registro_invima' => ['nullable', 'string', 'max:255'],
                'clasificacion_riesgo' => ['required', Rule::in(array_keys(Equipo::RIESGOS))],
                'clasificacion_especialidad' => ['required', 'string', 'max:255'],
                'fabricante' => ['nullable', 'string', 'max:255'],
                'pais_origen' => ['nullable', 'string', 'max:255'],
                'telefono_fabricante' => ['nullable', 'string', 'max:60'],
                'tipo_adquisicion' => [Rule::in(array_merge([''], Equipo::TIPOS_ADQUISICION))],
                'garantia_vence' => ['nullable', 'date'],
                'prioridad' => [Rule::in(array_merge([''], Equipo::PRIORIDADES))],
                'numero_serie' => ['nullable', 'string', 'max:255'],
                'empresa_id' => $this->empresa_id === '' ? ['nullable'] : ['exists:empresas,id'],
                'areaNombre' => ['nullable', 'string', 'max:255'],
                'observaciones_tecnicas' => ['required', 'string', 'max:2000'],
                'observaciones_generales' => ['nullable', 'string', 'max:2000'],
                'foto' => ['nullable', 'image', 'max:2048'],
            ],
            2 => [
                'suministro_electrico' => ['required', Rule::in(array_keys(Equipo::SUMINISTROS))],
                'voltaje' => ['nullable', 'string', 'max:40'],
                'amperaje' => ['nullable', 'string', 'max:40'],
                'frecuencia' => ['nullable', 'string', 'max:40'],
                'corriente' => ['nullable', 'string', 'max:40'],
                'potencia' => ['nullable', 'string', 'max:40'],
                'voltios' => ['nullable', 'string', 'max:40'],
                'temperatura' => ['nullable', 'string', 'max:40'],
                'presion' => ['nullable', 'string', 'max:40'],
                'peso' => ['nullable', 'string', 'max:40'],
                'velocidad' => ['nullable', 'string', 'max:40'],
                'tecnologia_predominante' => ['nullable', 'string', 'max:255'],
            ],
            3 => [
                'subtareas' => ['array'],
                'subtareas.*' => ['boolean'],
            ],
            4 => [
                'accesorios_estado' => ['array'],
                'accesorios_estado.*' => [Rule::in(array_merge([''], array_keys(Equipo::ESTADOS_ACCESORIO)))],
                'componentes' => ['nullable', 'string', 'max:2000'],
            ],
            5 => [
                'observaciones_ot' => ['nullable', 'string', 'max:2000'],
                'mantenimiento' => ['nullable', 'string', 'max:2000'],
                'ultimo_mantenimiento' => ['nullable', 'date'],
                'activo' => ['boolean'],
            ],
            default => [],
        };
    }

    /** @return array<string, string> */
    private function etiquetas(): array
    {
        return [
            'descripcion' => 'nombre del equipo',
            'marcaNombre' => 'marca',
            'modeloNombre' => 'modelo',
            'registro_invima' => 'registro INVIMA',
            'clasificacion_riesgo' => 'clasificación por riesgo',
            'clasificacion_especialidad' => 'clasificación por especialidad',
            'telefono_fabricante' => 'teléfono del fabricante',
            'tipo_adquisicion' => 'tipo de adquisición',
            'numero_serie' => 'número de serie',
            'empresa_id' => 'empresa',
            'areaNombre' => 'área',
            'garantia_vence' => 'fecha de vencimiento de la garantía',
            'ultimo_mantenimiento' => 'fecha del último mantenimiento',
            'observaciones_tecnicas' => 'observaciones técnicas',
            'observaciones_generales' => 'observaciones generales',
            'suministro_electrico' => 'suministro eléctrico',
            'tecnologia_predominante' => 'tecnología predominante',
            'observaciones_ot' => 'texto por defecto para OT',
        ];
    }
}; ?>

<section class="eq-root w-full space-y-6">
    {{-- ───────────────── Encabezado ───────────────── --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <nav class="mb-1 flex items-center gap-1.5 text-[11.5px] font-medium text-zinc-400">
                <span class="text-zinc-500 dark:text-zinc-400">Equipos</span>
            </nav>

            <h1 class="text-2xl font-bold tracking-tight text-carbon dark:text-white">Inventario de equipos</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Hoja de vida de los equipos biomédicos administrados por INGSOLMEP S.A.S.
            </p>
        </div>

        <button type="button" class="eq-btn eq-btn-accent" wire:click="abrirCreacion">
            <flux:icon name="plus" variant="mini" class="size-4" />
            Añadir equipo
        </button>
    </div>

    {{-- ───────────────── Indicadores ───────────────── --}}
    @php
        $tarjetas = [
            ['tipo' => 'total', 'etiqueta' => 'Equipos registrados', 'valor' => $this->resumen['total'], 'color' => 'text-signal'],
            ['tipo' => 'activos', 'etiqueta' => 'En servicio', 'valor' => $this->resumen['activos'], 'color' => 'text-lima-700 dark:text-lima'],
            ['tipo' => 'fueraDeServicio', 'etiqueta' => 'Fuera de servicio', 'valor' => $this->resumen['fueraDeServicio'], 'color' => 'text-rose-600 dark:text-rose-400'],
            ['tipo' => 'sinAsignar', 'etiqueta' => 'Sin asignar', 'valor' => $this->resumen['sinAsignar'], 'color' => 'text-amber-600 dark:text-amber-500'],
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($tarjetas as $tarjeta)
            <button
                type="button"
                wire:click="verListado('{{ $tarjeta['tipo'] }}')"
                title="Ver el listado de {{ mb_strtolower($tarjeta['etiqueta']) }}"
                class="eq-panel flex w-full cursor-pointer flex-col items-center justify-center gap-1 px-4 py-5 text-center transition duration-300 outline-none hover:-translate-y-1 hover:shadow-lg focus-visible:ring-4 focus-visible:ring-lima/30"
            >
                <p class="text-3xl leading-none font-bold {{ $tarjeta['color'] }}">{{ $tarjeta['valor'] }}</p>
                <p class="text-[11.5px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $tarjeta['etiqueta'] }}</p>
            </button>
        @endforeach
    </div>

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
                placeholder="Buscar por equipo, serie, marca, modelo, empresa, área o INVIMA…"
                wire:model.live.debounce.400ms="buscar"
            >
            <div wire:loading.delay wire:target="buscar" class="absolute top-1/2 right-4 -translate-y-1/2">
                <flux:icon name="arrow-path" variant="mini" class="size-4 animate-spin text-lima" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <div>
                <label class="eq-label" for="f-empresa">Empresa</label>
                <select id="f-empresa" class="eq-select" wire:model.live="filtroEmpresa">
                    <option value="">Todas</option>
                    @foreach ($this->empresas as $empresa)
                        <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="eq-label" for="f-area">Área</label>
                <select id="f-area" class="eq-select" wire:model.live="filtroArea" @disabled($filtroEmpresa === '')>
                    <option value="">{{ $filtroEmpresa === '' ? 'Elija una empresa' : 'Todas' }}</option>
                    @foreach ($this->areasFiltro as $area)
                        <option value="{{ $area->id }}">{{ $area->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="eq-label" for="f-marca">Marca</label>
                <select id="f-marca" class="eq-select" wire:model.live="filtroMarca">
                    <option value="">Todas</option>
                    @foreach ($this->marcas as $marca)
                        <option value="{{ $marca->id }}">{{ $marca->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="eq-label" for="f-modelo">Modelo</label>
                <select id="f-modelo" class="eq-select" wire:model.live="filtroModelo">
                    <option value="">Todos</option>
                    @foreach ($this->modelosFiltro as $modelo)
                        <option value="{{ $modelo->id }}">{{ $modelo->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="eq-label" for="f-serie">Número de serie</label>
                <input id="f-serie" type="text" class="eq-input" placeholder="Todos" wire:model.live.debounce.400ms="filtroSerie" autocomplete="off">
            </div>

            <div>
                <label class="eq-label" for="f-activo">Estado</label>
                <select id="f-activo" class="eq-select" wire:model.live="filtroActivo">
                    <option value="">Todos</option>
                    <option value="1">Activo</option>
                    <option value="0">Fuera de servicio</option>
                </select>
            </div>
        </div>
    </div>

    {{-- ───────────────── Listado ───────────────── --}}
    <div class="eq-panel overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
            <p class="flex items-center gap-2 text-[13px] font-semibold text-carbon dark:text-zinc-200">
                Lista de equipos
                <span class="eq-chip bg-lima-soft text-lima-700 dark:bg-lima/10 dark:text-lima">
                    {{ $this->equipos->total() }} {{ $this->equipos->total() === 1 ? 'resultado' : 'resultados' }}
                </span>
            </p>

            <div class="flex flex-wrap items-center gap-3">
                {{-- En tarjetas no hay cabecera que pulsar: el orden se elige aquí. --}}
                @if ($vista === 'cards')
                    <div class="flex items-center gap-2">
                        <label class="text-[12px] text-zinc-500 dark:text-zinc-400" for="f-ordenar">Ordenar</label>
                        <select id="f-ordenar" class="eq-select !w-auto !py-1.5 !text-[12px]" wire:model.live="ordenarPor">
                            @foreach ($this::ORDENABLES as $clave => $titulo)
                                <option value="{{ $clave }}">{{ $titulo }}</option>
                            @endforeach
                        </select>

                        <button
                            type="button"
                            wire:click="alternarDireccion"
                            title="{{ $ordenDireccion === 'asc' ? 'Orden ascendente' : 'Orden descendente' }}"
                            class="eq-icon-btn !size-8 border border-zinc-200 dark:border-zinc-700"
                        >
                            <flux:icon name="{{ $ordenDireccion === 'asc' ? 'bars-arrow-up' : 'bars-arrow-down' }}" variant="mini" class="size-4" />
                        </button>
                    </div>
                @endif

                <div class="flex items-center gap-2">
                    <label class="text-[12px] text-zinc-500 dark:text-zinc-400" for="f-por-pagina">Mostrar</label>
                    <select id="f-por-pagina" class="eq-select !w-auto !py-1.5 !text-[12px]" wire:model.live="porPagina">
                        @foreach ([10, 25, 50, 100] as $cantidad)
                            <option value="{{ $cantidad }}">{{ $cantidad }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tarjetas para explorar, tabla para comparar y ordenar. --}}
                <div class="flex items-center gap-0.5 rounded-xl border border-zinc-200 bg-zinc-50 p-0.5 dark:border-zinc-700 dark:bg-zinc-800/60" role="group" aria-label="Vista del listado">
                    @foreach ($this::VISTAS as $clave => $opcion)
                        <button
                            type="button"
                            wire:click="cambiarVista('{{ $clave }}')"
                            title="Ver en {{ mb_strtolower($opcion['titulo']) }}"
                            aria-pressed="{{ $vista === $clave ? 'true' : 'false' }}"
                            @class([
                                'inline-flex cursor-pointer items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[12px] font-semibold transition duration-200 outline-none focus-visible:ring-4 focus-visible:ring-lima/30',
                                'bg-white text-carbon shadow-sm dark:bg-zinc-900 dark:text-zinc-100' => $vista === $clave,
                                'text-zinc-500 hover:text-carbon dark:text-zinc-400 dark:hover:text-zinc-200' => $vista !== $clave,
                            ])
                        >
                            <flux:icon name="{{ $opcion['icono'] }}" variant="mini" class="size-4" />
                            <span class="hidden sm:inline">{{ $opcion['titulo'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        @include('pages.equipos.partials.lista-'.$vista)

        @if ($this->equipos->hasPages())
            <div class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-800">
                {{ $this->equipos->links() }}
            </div>
        @endif
    </div>

    {{-- Los modales se teletransportan al <body>: dentro del contenedor de la
         página el velo `fixed` no llegaba a cubrir toda la pantalla. --}}
    @teleport('body')
        @include('pages.equipos.partials.modal-formulario', [
            'pasosDefinicion' => $this::PASOS,
            'marcasSugeridas' => $this->marcas,
            'modelosSugeridos' => $this->modelosDeMarca,
            'empresasDisponibles' => $this->empresas,
            'areasSugeridas' => $this->areasDeEmpresa,
            'nombreEmpresa' => $this->nombreEmpresaSeleccionada,
        ])
    @endteleport

    @teleport('body')
        @include('pages.equipos.partials.modal-detalle', ['equipo' => $this->equipoDetalle])
    @endteleport

    @teleport('body')
        @include('pages.equipos.partials.modal-listado', [
            'listados' => $this::LISTADOS,
            'equiposListados' => $this->listadoEquipos,
            'totalListado' => $this->listadoTotal,
            'topeListado' => $this::TOPE_LISTADO,
        ])
    @endteleport

    @teleport('body')
        @include('pages.equipos.partials.modal-eliminar')
    @endteleport
</section>
