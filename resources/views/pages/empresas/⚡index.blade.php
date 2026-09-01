<?php

use App\Models\Empresa;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Title('Empresas')] class extends Component {
    use WithFileUploads, WithPagination;

    // ------------------------------------------------------------------
    // Filtros del listado
    // ------------------------------------------------------------------

    public string $buscar = '';

    public string $filtroCiudad = '';

    public string $filtroActivo = '';

    public int $porPagina = 10;

    public string $ordenarPor = 'nombre';

    public string $ordenDireccion = 'asc';

    // ------------------------------------------------------------------
    // Formulario
    // ------------------------------------------------------------------

    public bool $mostrarFormulario = false;

    /** Hay datos escritos en el formulario que todavía no se han guardado. */
    public bool $formularioSucio = false;

    /** Muestra el aviso previo a cerrar el formulario con cambios pendientes. */
    public bool $confirmarCierreFormulario = false;

    public ?int $empresaId = null;

    public string $nombre = '';

    public string $nit = '';

    public string $email = '';

    public string $ciudad = '';

    public string $direccion = '';

    public string $telefono = '';

    public string $celular = '';

    public string $whatsapp = '';

    public bool $activo = true;

    public $logo = null;

    public ?string $logoActual = null;

    /** El logo guardado se borrará al confirmar el formulario. */
    public bool $logoRetirado = false;

    public ?int $empresaAEliminar = null;

    // ------------------------------------------------------------------
    // Vistas de sólo lectura
    // ------------------------------------------------------------------

    public ?int $empresaVista = null;

    public string $listadoVisto = '';

    /**
     * Listados de sólo lectura que abren las tarjetas de indicadores.
     *
     * @var array<string, array{titulo: string, descripcion: string, icono: string}>
     */
    public const LISTADOS = [
        'total' => [
            'titulo' => 'Empresas registradas',
            'descripcion' => 'Todas las IPS, clínicas y clientes del sistema.',
            'icono' => 'building-office-2',
        ],
        'activas' => [
            'titulo' => 'Empresas activas',
            'descripcion' => 'Clientes con servicio vigente.',
            'icono' => 'check-badge',
        ],
        'inactivas' => [
            'titulo' => 'Empresas inactivas',
            'descripcion' => 'Clientes sin servicio vigente, conservados por su historial.',
            'icono' => 'no-symbol',
        ],
        'sinEquipos' => [
            'titulo' => 'Empresas sin equipos',
            'descripcion' => 'Clientes a los que todavía no se les ha levantado inventario.',
            'icono' => 'rectangle-stack',
        ],
    ];

    /** Máximo de filas que muestra el modal de listado antes de recortar. */
    public const TOPE_LISTADO = 100;

    /** Tamaño máximo del logo, en kilobytes. */
    public const MAX_LOGO_KB = 5120;

    // ------------------------------------------------------------------
    // Datos derivados
    // ------------------------------------------------------------------

    /** @return LengthAwarePaginator<int, Empresa> */
    #[Computed]
    public function empresas(): LengthAwarePaginator
    {
        $ordenables = ['nombre', 'nit', 'ciudad', 'activo', 'created_at'];
        $columna = in_array($this->ordenarPor, $ordenables, true) ? $this->ordenarPor : 'nombre';

        return Empresa::query()
            ->withCount(['equipos', 'areas'])
            ->when($this->buscar !== '', function (Builder $consulta): void {
                $termino = '%'.$this->buscar.'%';

                $consulta->where(function (Builder $grupo) use ($termino): void {
                    $grupo->where('nombre', 'like', $termino)
                        ->orWhere('nit', 'like', $termino)
                        ->orWhere('email', 'like', $termino)
                        ->orWhere('ciudad', 'like', $termino)
                        ->orWhere('direccion', 'like', $termino)
                        ->orWhere('celular', 'like', $termino)
                        ->orWhere('telefono', 'like', $termino);
                });
            })
            ->when($this->filtroCiudad !== '', fn (Builder $q) => $q->where('ciudad', $this->filtroCiudad))
            ->when($this->filtroActivo !== '', fn (Builder $q) => $q->where('activo', $this->filtroActivo === '1'))
            ->orderBy($columna, $this->ordenDireccion === 'asc' ? 'asc' : 'desc')
            ->paginate($this->porPagina);
    }

    /** @return array<string, int> */
    #[Computed]
    public function resumen(): array
    {
        return [
            'total' => Empresa::count(),
            'activas' => Empresa::where('activo', true)->count(),
            'inactivas' => Empresa::where('activo', false)->count(),
            'sinEquipos' => Empresa::whereDoesntHave('equipos')->count(),
        ];
    }

    /**
     * Ciudades ya registradas, para el desplegable de filtro.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function ciudades(): Collection
    {
        return Empresa::query()
            ->whereNotNull('ciudad')
            ->where('ciudad', '<>', '')
            ->distinct()
            ->orderBy('ciudad')
            ->pluck('ciudad');
    }

    /** Empresa abierta en la ficha, con sus áreas y conteos cargados. */
    #[Computed]
    public function empresaDetalle(): ?Empresa
    {
        if ($this->empresaVista === null) {
            return null;
        }

        return Empresa::query()
            ->withCount('equipos')
            ->with(['areas' => fn ($consulta) => $consulta->withCount('equipos')->orderBy('nombre')])
            ->find($this->empresaVista);
    }

    /** Empresa señalada para eliminar, con el conteo de lo que arrastra. */
    #[Computed]
    public function empresaEliminable(): ?Empresa
    {
        if ($this->empresaAEliminar === null) {
            return null;
        }

        return Empresa::withCount(['equipos', 'areas'])->find($this->empresaAEliminar);
    }

    /**
     * Empresas que muestra el modal de una tarjeta de indicadores.
     *
     * @return Collection<int, Empresa>
     */
    #[Computed]
    public function listadoEmpresas(): Collection
    {
        if ($this->listadoVisto === '') {
            return collect();
        }

        return $this->consultaDelListado()
            ->withCount('equipos')
            ->orderBy('nombre')
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
            || $this->filtroCiudad !== ''
            || $this->filtroActivo !== '';
    }

    // ------------------------------------------------------------------
    // Listado
    // ------------------------------------------------------------------

    public function updated(string $propiedad, mixed $valor = null): void
    {
        if (str_starts_with($propiedad, 'filtro') || in_array($propiedad, ['buscar', 'porPagina'], true)) {
            $this->resetPage();

            return;
        }

        // Cualquier campo tocado con el formulario abierto cuenta como cambio
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

    public function limpiarFiltros(): void
    {
        $this->reset(['buscar', 'filtroCiudad', 'filtroActivo']);

        $this->resetPage();
    }

    public function alternarActivo(int $id): void
    {
        $empresa = Empresa::findOrFail($id);
        $empresa->activo = ! $empresa->activo;
        $empresa->save();

        unset($this->empresas, $this->resumen);

        Flux::toast(
            variant: $empresa->activo ? 'success' : 'warning',
            text: $empresa->activo ? 'Empresa marcada como activa.' : 'Empresa marcada como inactiva.',
        );
    }

    // ------------------------------------------------------------------
    // Vistas de sólo lectura
    // ------------------------------------------------------------------

    public function verEmpresa(int $id): void
    {
        $this->empresaVista = $id;
    }

    public function cerrarDetalle(): void
    {
        $this->empresaVista = null;
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

    public function abrirCreacion(): void
    {
        $this->resetValidation();
        $this->reiniciarFormulario();
        $this->mostrarFormulario = true;
    }

    public function editar(int $id): void
    {
        $empresa = Empresa::findOrFail($id);

        // Editar desde la ficha o desde un listado los reemplaza por el formulario.
        $this->empresaVista = null;
        $this->listadoVisto = '';

        $this->resetValidation();
        $this->reiniciarFormulario();

        $this->empresaId = $empresa->id;
        $this->logoActual = $empresa->logo_path;
        $this->activo = (bool) $empresa->activo;

        foreach ($this->camposDirectos() as $campo) {
            $this->{$campo} = (string) ($empresa->{$campo} ?? '');
        }

        $this->mostrarFormulario = true;
    }

    public function retirarLogo(): void
    {
        $this->logo = null;
        $this->logoRetirado = true;
        $this->formularioSucio = true;
        $this->resetValidation('logo');
    }

    public function guardar(): void
    {
        $this->validate($this->reglas(), [], $this->etiquetas());

        $datos = ['activo' => $this->activo];

        foreach ($this->camposDirectos() as $campo) {
            $datos[$campo] = trim((string) $this->{$campo}) ?: null;
        }

        // El número de WhatsApp se guarda sólo con dígitos, tal como lo espera wa.me.
        $datos['whatsapp'] = $datos['whatsapp'] ? preg_replace('/\D/', '', $datos['whatsapp']) : null;

        $empresa = $this->empresaId ? Empresa::findOrFail($this->empresaId) : new Empresa;
        $logoPrevio = $empresa->logo_path;

        if ($this->logo) {
            $datos['logo_path'] = $this->logo->store('empresas', 'public');
        } elseif ($this->logoRetirado) {
            $datos['logo_path'] = null;
        }

        $empresa->fill($datos)->save();

        // Al reemplazar o retirar el logo se borra el anterior para no dejar huérfanos.
        if ($logoPrevio && $logoPrevio !== $empresa->logo_path) {
            Storage::disk('public')->delete($logoPrevio);
        }

        $creada = $this->empresaId === null;

        $this->cerrarFormulario();

        unset($this->empresas, $this->resumen, $this->ciudades);

        Flux::toast(
            variant: 'success',
            text: $creada
                ? 'Empresa «'.$empresa->nombre.'» registrada correctamente.'
                : 'Empresa «'.$empresa->nombre.'» actualizada correctamente.',
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
        $this->empresaVista = null;

        $this->empresaAEliminar = $id;
    }

    public function eliminar(): void
    {
        if ($this->empresaAEliminar === null) {
            return;
        }

        $empresa = Empresa::findOrFail($this->empresaAEliminar);
        $nombre = $empresa->nombre;
        $empresa->delete();

        $this->empresaAEliminar = null;

        unset($this->empresas, $this->resumen, $this->ciudades);

        Flux::toast(variant: 'success', text: 'Empresa «'.$nombre.'» eliminada.');
    }

    // ------------------------------------------------------------------
    // Apoyo
    // ------------------------------------------------------------------

    /**
     * Consulta base del listado que abre cada tarjeta de indicadores.
     *
     * @return Builder<Empresa>
     */
    private function consultaDelListado(): Builder
    {
        return match ($this->listadoVisto) {
            'activas' => Empresa::query()->where('activo', true),
            'inactivas' => Empresa::query()->where('activo', false),
            'sinEquipos' => Empresa::query()->whereDoesntHave('equipos'),
            default => Empresa::query(),
        };
    }

    /**
     * Campos cuyo nombre coincide en el formulario y en la tabla.
     *
     * @return list<string>
     */
    private function camposDirectos(): array
    {
        return ['nombre', 'nit', 'email', 'ciudad', 'direccion', 'telefono', 'celular', 'whatsapp'];
    }

    private function reiniciarFormulario(): void
    {
        $this->reset([
            'empresaId', 'nombre', 'nit', 'email', 'ciudad', 'direccion',
            'telefono', 'celular', 'whatsapp', 'activo', 'logo', 'logoActual', 'logoRetirado',
        ]);

        $this->formularioSucio = false;
        $this->confirmarCierreFormulario = false;
    }

    /** @return array<string, mixed> */
    private function reglas(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            // El NIT no se exige único: una misma razón social puede tener varias sedes.
            'nit' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255'],
            'ciudad' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:60'],
            'celular' => ['required', 'string', 'max:60'],
            'whatsapp' => ['nullable', 'string', 'regex:/^\+?[\d\s-]{7,20}$/'],
            'activo' => ['boolean'],
            'logo' => ['nullable', 'image', 'max:'.self::MAX_LOGO_KB],
        ];
    }

    /** @return array<string, string> */
    private function etiquetas(): array
    {
        return [
            'nombre' => 'nombre de la empresa',
            'nit' => 'NIT',
            'email' => 'correo electrónico',
            'whatsapp' => 'número de WhatsApp',
            'logo' => 'logo de la empresa',
        ];
    }
}; ?>

<section class="eq-root w-full space-y-6">
    {{-- ───────────────── Encabezado ───────────────── --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <nav class="mb-1 flex items-center gap-1.5 text-[11.5px] font-medium text-zinc-400">
                <span class="text-zinc-500 dark:text-zinc-400">Empresas</span>
            </nav>

            <h1 class="text-2xl font-bold tracking-tight text-carbon dark:text-white">Registro de empresas</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                IPS, clínicas y clientes a los que INGSOLMEP S.A.S. presta servicio.
            </p>
        </div>

        <button type="button" class="eq-btn eq-btn-accent" wire:click="abrirCreacion">
            <flux:icon name="plus" variant="mini" class="size-4" />
            Añadir empresa
        </button>
    </div>

    {{-- ───────────────── Indicadores ───────────────── --}}
    @php
        $tarjetas = [
            ['tipo' => 'total', 'etiqueta' => 'Empresas registradas', 'valor' => $this->resumen['total'], 'color' => 'text-signal'],
            ['tipo' => 'activas', 'etiqueta' => 'Activas', 'valor' => $this->resumen['activas'], 'color' => 'text-lima-700 dark:text-lima'],
            ['tipo' => 'inactivas', 'etiqueta' => 'Inactivas', 'valor' => $this->resumen['inactivas'], 'color' => 'text-rose-600 dark:text-rose-400'],
            ['tipo' => 'sinEquipos', 'etiqueta' => 'Sin equipos', 'valor' => $this->resumen['sinEquipos'], 'color' => 'text-amber-600 dark:text-amber-500'],
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
                placeholder="Buscar por nombre, NIT, correo, ciudad, dirección o teléfono…"
                wire:model.live.debounce.400ms="buscar"
            >
            <div wire:loading.delay wire:target="buscar" class="absolute top-1/2 right-4 -translate-y-1/2">
                <flux:icon name="arrow-path" variant="mini" class="size-4 animate-spin text-lima" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="eq-label" for="f-ciudad">Ciudad</label>
                <select id="f-ciudad" class="eq-select" wire:model.live="filtroCiudad">
                    <option value="">Todas</option>
                    @foreach ($this->ciudades as $ciudad)
                        <option value="{{ $ciudad }}">{{ $ciudad }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="eq-label" for="f-activo">Estado</label>
                <select id="f-activo" class="eq-select" wire:model.live="filtroActivo">
                    <option value="">Todos los estados</option>
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
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
    </div>

    {{-- ───────────────── Tabla ───────────────── --}}
    <div class="eq-panel overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
            <p class="flex items-center gap-2 text-[13px] font-semibold text-carbon dark:text-zinc-200">
                Lista de empresas
                <span class="eq-chip bg-lima-soft text-lima-700 dark:bg-lima/10 dark:text-lima">
                    {{ $this->empresas->total() }} {{ $this->empresas->total() === 1 ? 'resultado' : 'resultados' }}
                </span>
            </p>

            <p class="text-[12px] text-zinc-500 dark:text-zinc-400">Pulse una fila para abrir la ficha completa.</p>
        </div>

        <div wire:loading.delay.class="opacity-50" class="overflow-x-auto transition-opacity">
            <table class="w-full min-w-4xl text-left text-[13px]">
                <thead class="bg-zinc-50/80 text-[11px] font-bold tracking-wide text-zinc-500 uppercase dark:bg-zinc-800/60 dark:text-zinc-400">
                    <tr>
                        @php
                            $columnas = [
                                ['clave' => 'nombre', 'titulo' => 'Empresa', 'ordenable' => true],
                                ['clave' => 'nit', 'titulo' => 'NIT', 'ordenable' => true],
                                ['clave' => 'ciudad', 'titulo' => 'Ciudad', 'ordenable' => true],
                                ['clave' => null, 'titulo' => 'Contacto', 'ordenable' => false],
                                ['clave' => null, 'titulo' => 'Inventario', 'ordenable' => false],
                                ['clave' => 'activo', 'titulo' => 'Estado', 'ordenable' => true],
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
                    @forelse ($this->empresas as $empresa)
                        <tr
                            wire:key="empresa-{{ $empresa->id }}"
                            tabindex="0"
                            wire:click="verEmpresa({{ $empresa->id }})"
                            wire:keydown.enter="verEmpresa({{ $empresa->id }})"
                            title="Ver la ficha de {{ $empresa->nombre }}"
                            class="cursor-pointer transition duration-150 outline-none hover:bg-lima-soft/40 focus-visible:bg-lima-soft/60 dark:hover:bg-zinc-800/50 dark:focus-visible:bg-zinc-800/70"
                        >
                            <td class="px-4 py-3 align-top">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-zinc-200 bg-white text-[11px] font-bold text-carbon shadow-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                        @if ($empresa->logoUrl())
                                            <img src="{{ $empresa->logoUrl() }}" alt="Logo de {{ $empresa->nombre }}" class="size-full object-contain p-1">
                                        @else
                                            {{ $empresa->iniciales() }}
                                        @endif
                                    </span>

                                    <div class="min-w-0">
                                        <p class="font-semibold text-carbon dark:text-zinc-100">{{ $empresa->nombre }}</p>
                                        @if ($empresa->email)
                                            <p class="flex items-center gap-1 text-[12px] text-zinc-500 dark:text-zinc-400">
                                                <flux:icon name="envelope" variant="micro" class="size-3" />
                                                {{ $empresa->email }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3 align-top font-mono text-[12px] text-zinc-600 dark:text-zinc-300">{{ $empresa->nit ?: '—' }}</td>

                            <td class="px-4 py-3 align-top">
                                @if ($empresa->ciudad)
                                    <p class="flex items-center gap-1 font-medium text-carbon dark:text-zinc-200">
                                        <flux:icon name="map-pin" variant="micro" class="size-3.5 text-lima" />
                                        {{ $empresa->ciudad }}
                                    </p>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif

                                @if ($empresa->direccion)
                                    <p class="text-[12px] text-zinc-500 dark:text-zinc-400">{{ $empresa->direccion }}</p>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top">
                                @if ($empresa->celular)
                                    <p class="flex items-center gap-1 font-medium text-carbon dark:text-zinc-200">
                                        <flux:icon name="phone" variant="micro" class="size-3.5" />
                                        {{ $empresa->celular }}
                                    </p>
                                @endif

                                @if ($empresa->telefono)
                                    <p class="text-[12px] text-zinc-500 dark:text-zinc-400">Fijo: {{ $empresa->telefono }}</p>
                                @endif

                                @if ($empresa->whatsappUrl())
                                    <a
                                        href="{{ $empresa->whatsappUrl() }}"
                                        target="_blank"
                                        rel="noopener"
                                        x-data
                                        x-on:click.stop
                                        class="eq-chip mt-1 bg-emerald-100 text-emerald-700 transition hover:scale-105 dark:bg-emerald-500/15 dark:text-emerald-400"
                                        title="Escribir por WhatsApp al {{ $empresa->whatsapp }}"
                                    >
                                        <flux:icon name="chat-bubble-left-right" variant="micro" class="size-3" />
                                        WhatsApp
                                    </a>
                                @endif

                                @if (! $empresa->celular && ! $empresa->telefono && ! $empresa->whatsapp)
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top">
                                <p class="font-medium text-carbon dark:text-zinc-200">
                                    {{ $empresa->equipos_count }} {{ $empresa->equipos_count === 1 ? 'equipo' : 'equipos' }}
                                </p>
                                <p class="text-[12px] text-zinc-500 dark:text-zinc-400">
                                    {{ $empresa->areas_count }} {{ $empresa->areas_count === 1 ? 'área' : 'áreas' }}
                                </p>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <button
                                    type="button"
                                    wire:click.stop="alternarActivo({{ $empresa->id }})"
                                    title="Cambiar estado"
                                    @class([
                                        'eq-chip cursor-pointer transition duration-200 hover:scale-110 hover:shadow-md',
                                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400' => $empresa->activo,
                                        'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400' => ! $empresa->activo,
                                    ])
                                >
                                    <span @class([
                                        'size-1.5 rounded-full',
                                        'bg-emerald-500' => $empresa->activo,
                                        'bg-rose-500' => ! $empresa->activo,
                                    ])></span>
                                    {{ $empresa->activo ? 'Activo' : 'Inactivo' }}
                                </button>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="flex items-center gap-1">
                                    <button type="button" class="eq-icon-btn" wire:click.stop="editar({{ $empresa->id }})" title="Editar {{ $empresa->nombre }}">
                                        <flux:icon name="pencil-square" variant="mini" class="size-4" />
                                    </button>

                                    <button
                                        type="button"
                                        class="eq-icon-btn hover:!bg-rose-50 hover:!text-rose-600 dark:hover:!bg-rose-500/10 dark:hover:!text-rose-400"
                                        wire:click.stop="confirmarEliminacion({{ $empresa->id }})"
                                        title="Eliminar {{ $empresa->nombre }}"
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
                                        <flux:icon name="building-office-2" class="size-7 text-zinc-400" />
                                    </span>
                                    <p class="text-[15px] font-semibold text-carbon dark:text-zinc-200">
                                        {{ $this->hayFiltrosActivos ? 'Ninguna empresa coincide con los filtros' : 'Todavía no hay empresas registradas' }}
                                    </p>
                                    <p class="text-[13px] text-zinc-500 dark:text-zinc-400">
                                        {{ $this->hayFiltrosActivos ? 'Ajuste o limpie los filtros para ver más resultados.' : 'Registre la primera IPS o clínica para poder asignarle equipos.' }}
                                    </p>
                                    @if ($this->hayFiltrosActivos)
                                        <button type="button" class="eq-btn eq-btn-ghost" wire:click="limpiarFiltros">Limpiar filtros</button>
                                    @else
                                        <button type="button" class="eq-btn eq-btn-accent" wire:click="abrirCreacion">
                                            <flux:icon name="plus" variant="mini" class="size-4" /> Añadir empresa
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->empresas->hasPages())
            <div class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-800">
                {{ $this->empresas->links() }}
            </div>
        @endif
    </div>

    {{-- Los modales se teletransportan al <body>: dentro del contenedor de la
         página el velo `fixed` no llegaba a cubrir toda la pantalla. --}}
    @teleport('body')
        @include('pages.empresas.partials.modal-formulario', [
            'maxLogoKb' => $this::MAX_LOGO_KB,
            'ciudadesSugeridas' => $this->ciudades,
        ])
    @endteleport

    @teleport('body')
        @include('pages.empresas.partials.modal-detalle', ['empresa' => $this->empresaDetalle])
    @endteleport

    @teleport('body')
        @include('pages.empresas.partials.modal-listado', [
            'listados' => $this::LISTADOS,
            'empresasListadas' => $this->listadoEmpresas,
            'totalListado' => $this->listadoTotal,
            'topeListado' => $this::TOPE_LISTADO,
        ])
    @endteleport

    @teleport('body')
        @include('pages.empresas.partials.modal-eliminar', ['empresa' => $this->empresaEliminable])
    @endteleport
</section>
