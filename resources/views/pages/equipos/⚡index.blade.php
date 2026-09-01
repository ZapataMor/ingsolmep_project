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

    public string $ordenarPor = 'created_at';

    public string $ordenDireccion = 'desc';

    // ------------------------------------------------------------------
    // Asistente de registro
    // ------------------------------------------------------------------

    public bool $mostrarFormulario = false;

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

    public string $prioridad = '';

    public string $observaciones_tecnicas = '';

    public string $observaciones_generales = '';

    public string $mantenimiento = '';

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
        $ordenables = ['id', 'descripcion', 'numero_serie', 'activo', 'created_at'];
        $columna = in_array($this->ordenarPor, $ordenables, true) ? $this->ordenarPor : 'created_at';

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

        if (str_starts_with($propiedad, 'filtro') || in_array($propiedad, ['buscar', 'porPagina'], true)) {
            $this->resetPage();
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

    public function cerrarFormulario(): void
    {
        $this->mostrarFormulario = false;
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
     * Campos cuyo nombre coincide en el formulario y en la tabla.
     *
     * @return list<string>
     */
    private function camposDirectos(): array
    {
        return [
            'descripcion', 'numero_serie', 'registro_invima', 'clasificacion_riesgo',
            'clasificacion_especialidad', 'fabricante', 'pais_origen', 'telefono_fabricante',
            'tipo_adquisicion', 'prioridad', 'observaciones_tecnicas', 'observaciones_generales',
            'mantenimiento', 'voltaje', 'amperaje', 'frecuencia', 'corriente', 'potencia',
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
            'tipo_adquisicion', 'prioridad', 'observaciones_tecnicas', 'observaciones_generales',
            'areaNueva', 'marcaNueva', 'modeloNuevo',
            'mantenimiento', 'activo', 'foto', 'fotoActual', 'suministro_electrico',
            'voltaje', 'amperaje', 'frecuencia', 'corriente', 'potencia', 'voltios',
            'temperatura', 'presion', 'peso', 'velocidad', 'tecnologia_predominante',
            'componentes', 'observaciones_ot', 'paso', 'pasoMaximo',
        ]);

        $this->subtareas = array_fill_keys(array_keys(Equipo::SUBTAREAS), false);
        $this->accesorios_estado = array_fill_keys(array_keys(Equipo::ACCESORIOS), '');
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
                <a href="{{ route('dashboard') }}" wire:navigate class="transition hover:text-lima">Escritorio</a>
                <flux:icon name="chevron-right" variant="micro" class="size-3" />
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
            ['etiqueta' => 'Equipos registrados', 'valor' => $this->resumen['total'], 'icono' => 'cpu-chip', 'color' => 'from-signal to-signal-600', 'sombra' => 'shadow-signal/25'],
            ['etiqueta' => 'En servicio', 'valor' => $this->resumen['activos'], 'icono' => 'check-badge', 'color' => 'from-lima to-lima-700', 'sombra' => 'shadow-lima/25'],
            ['etiqueta' => 'Fuera de servicio', 'valor' => $this->resumen['fueraDeServicio'], 'icono' => 'bolt-slash', 'color' => 'from-rose-500 to-rose-600', 'sombra' => 'shadow-rose-500/25'],
            ['etiqueta' => 'Sin asignar', 'valor' => $this->resumen['sinAsignar'], 'icono' => 'rectangle-stack', 'color' => 'from-amber-400 to-amber-600', 'sombra' => 'shadow-amber-500/25'],
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($tarjetas as $tarjeta)
            <div class="eq-panel flex items-center gap-4 p-4 transition duration-300 hover:-translate-y-1 hover:shadow-lg">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br {{ $tarjeta['color'] }} text-white shadow-lg {{ $tarjeta['sombra'] }}">
                    <flux:icon name="{{ $tarjeta['icono'] }}" class="size-6" />
                </span>

                <div>
                    <p class="text-[11.5px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $tarjeta['etiqueta'] }}</p>
                    <p class="text-2xl font-bold text-carbon dark:text-white">{{ $tarjeta['valor'] }}</p>
                </div>
            </div>
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

    {{-- ───────────────── Tabla ───────────────── --}}
    <div class="eq-panel overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
            <p class="flex items-center gap-2 text-[13px] font-semibold text-carbon dark:text-zinc-200">
                Lista de equipos
                <span class="eq-chip bg-lima-soft text-lima-700 dark:bg-lima/10 dark:text-lima">
                    {{ $this->equipos->total() }} {{ $this->equipos->total() === 1 ? 'resultado' : 'resultados' }}
                </span>
            </p>

            <div class="flex items-center gap-2">
                <label class="text-[12px] text-zinc-500 dark:text-zinc-400" for="f-por-pagina">Mostrar</label>
                <select id="f-por-pagina" class="eq-select !w-auto !py-1.5 !text-[12px]" wire:model.live="porPagina">
                    @foreach ([10, 25, 50, 100] as $cantidad)
                        <option value="{{ $cantidad }}">{{ $cantidad }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div wire:loading.delay.class="opacity-50" class="overflow-x-auto transition-opacity">
            <table class="w-full min-w-5xl text-left text-[13px]">
                <thead class="bg-zinc-50/80 text-[11px] font-bold tracking-wide text-zinc-500 uppercase dark:bg-zinc-800/60 dark:text-zinc-400">
                    <tr>
                        @php
                            $columnas = [
                                ['clave' => 'id', 'titulo' => '#', 'ordenable' => true],
                                ['clave' => 'descripcion', 'titulo' => 'Equipo', 'ordenable' => true],
                                ['clave' => null, 'titulo' => 'Empresa / Área', 'ordenable' => false],
                                ['clave' => null, 'titulo' => 'Marca / Modelo', 'ordenable' => false],
                                ['clave' => 'numero_serie', 'titulo' => 'N.º de serie', 'ordenable' => true],
                                ['clave' => null, 'titulo' => 'Observaciones técnicas', 'ordenable' => false],
                                ['clave' => null, 'titulo' => 'Mantenimiento', 'ordenable' => false],
                                ['clave' => 'activo', 'titulo' => 'Estado', 'ordenable' => true],
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

                        <th scope="col" class="px-4 py-3 text-right font-bold whitespace-nowrap">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->equipos as $equipo)
                        <tr wire:key="equipo-{{ $equipo->id }}" class="transition duration-150 hover:bg-lima-soft/40 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3 align-top font-mono text-[12px] text-zinc-400">{{ $equipo->id }}</td>

                            <td class="px-4 py-3 align-top">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-carbon to-carbon-deep text-[11px] font-bold text-white shadow-sm">
                                        @if ($equipo->fotoUrl())
                                            <img src="{{ $equipo->fotoUrl() }}" alt="{{ $equipo->descripcion }}" class="size-full object-cover">
                                        @else
                                            {{ $equipo->iniciales() }}
                                        @endif
                                    </span>

                                    <div class="min-w-0">
                                        <p class="font-semibold text-carbon dark:text-zinc-100">{{ $equipo->descripcion }}</p>
                                        @if ($equipo->clasificacion_riesgo)
                                            <span class="eq-chip mt-0.5 bg-signal/10 text-signal-600 dark:text-signal">Riesgo {{ $equipo->clasificacion_riesgo }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <p class="font-medium text-carbon dark:text-zinc-200">{{ $equipo->empresa?->nombre ?? '—' }}</p>
                                <p class="text-[12px] text-zinc-500 dark:text-zinc-400">{{ $equipo->area?->nombre ?? 'Sin área' }}</p>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <p class="font-medium text-carbon dark:text-zinc-200">{{ $equipo->marca?->nombre ?? '—' }}</p>
                                <p class="text-[12px] text-zinc-500 dark:text-zinc-400">{{ $equipo->modelo?->nombre ?? '—' }}</p>
                            </td>

                            <td class="px-4 py-3 align-top font-mono text-[12px] text-zinc-600 dark:text-zinc-300">{{ $equipo->numero_serie ?: '—' }}</td>

                            <td class="max-w-56 px-4 py-3 align-top text-zinc-600 dark:text-zinc-300">
                                <span class="line-clamp-2" title="{{ $equipo->observaciones_tecnicas }}">{{ $equipo->observaciones_tecnicas ?: '—' }}</span>
                            </td>

                            <td class="max-w-56 px-4 py-3 align-top text-zinc-600 dark:text-zinc-300">
                                <span class="line-clamp-2" title="{{ $equipo->mantenimiento }}">{{ $equipo->mantenimiento ?: '—' }}</span>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <button
                                    type="button"
                                    wire:click="alternarActivo({{ $equipo->id }})"
                                    title="Cambiar estado"
                                    @class([
                                        'eq-chip cursor-pointer transition duration-200 hover:scale-110 hover:shadow-md',
                                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400' => $equipo->activo,
                                        'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400' => ! $equipo->activo,
                                    ])
                                >
                                    <span @class([
                                        'size-1.5 rounded-full',
                                        'bg-emerald-500' => $equipo->activo,
                                        'bg-rose-500' => ! $equipo->activo,
                                    ])></span>
                                    {{ $equipo->activo ? 'Activo' : 'Fuera de servicio' }}
                                </button>
                            </td>

                            <td class="px-4 py-3 text-right align-top whitespace-nowrap">
                                <button type="button" class="eq-icon-btn" title="Editar" wire:click="editar({{ $equipo->id }})">
                                    <flux:icon name="pencil-square" variant="mini" class="size-4" />
                                </button>

                                <button type="button" class="eq-icon-btn hover:!bg-rose-50 hover:!text-rose-600 dark:hover:!bg-rose-500/10" title="Eliminar" wire:click="confirmarEliminacion({{ $equipo->id }})">
                                    <flux:icon name="trash" variant="mini" class="size-4" />
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                                    <span class="flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon name="cpu-chip" class="size-7 text-zinc-400" />
                                    </span>
                                    <p class="text-[15px] font-semibold text-carbon dark:text-zinc-200">
                                        {{ $this->hayFiltrosActivos ? 'Ningún equipo coincide con los filtros' : 'Todavía no hay equipos registrados' }}
                                    </p>
                                    <p class="text-[13px] text-zinc-500 dark:text-zinc-400">
                                        {{ $this->hayFiltrosActivos ? 'Ajuste o limpie los filtros para ver más resultados.' : 'Registre el primer equipo del inventario para empezar.' }}
                                    </p>
                                    @if ($this->hayFiltrosActivos)
                                        <button type="button" class="eq-btn eq-btn-ghost" wire:click="limpiarFiltros">Limpiar filtros</button>
                                    @else
                                        <button type="button" class="eq-btn eq-btn-accent" wire:click="abrirCreacion">
                                            <flux:icon name="plus" variant="mini" class="size-4" /> Añadir equipo
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->equipos->hasPages())
            <div class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-800">
                {{ $this->equipos->links() }}
            </div>
        @endif
    </div>

    @include('pages.equipos.partials.modal-formulario', [
        'pasosDefinicion' => $this::PASOS,
        'marcasSugeridas' => $this->marcas,
        'modelosSugeridos' => $this->modelosDeMarca,
        'empresasDisponibles' => $this->empresas,
        'areasSugeridas' => $this->areasDeEmpresa,
        'nombreEmpresa' => $this->nombreEmpresaSeleccionada,
    ])
    @include('pages.equipos.partials.modal-eliminar')
</section>
