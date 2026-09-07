<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Empresa;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Cuentas de acceso al sistema.
 *
 * Aquí se decide lo único que separa a un usuario de otro: su rol y, cuando es
 * de institución, cuál. Ese par es lo que después recorta todo lo que ve
 * —inventario, órdenes, reportes, panel— sin que ninguna pantalla tenga que
 * preguntarlo. La ruta sólo la alcanza el administrador.
 */
new #[Title('Usuarios')] class extends Component {
    use PasswordValidationRules, ProfileValidationRules, WithPagination;

    // ------------------------------------------------------------------
    // Filtros del listado
    // ------------------------------------------------------------------

    #[Url(as: 'q')]
    public string $buscar = '';

    #[Url(as: 'rol')]
    public string $filtroRol = '';

    /** Llega puesto desde la ficha de una empresa, para ver sus accesos. */
    #[Url(as: 'empresa')]
    public string $filtroEmpresa = '';

    #[Url(as: 'activo')]
    public string $filtroActivo = '';

    public int $porPagina = 10;

    #[Url(as: 'orden')]
    public string $ordenarPor = 'name';

    #[Url(as: 'dir')]
    public string $ordenDireccion = 'asc';

    // ------------------------------------------------------------------
    // Formulario
    // ------------------------------------------------------------------

    public bool $mostrarFormulario = false;

    /** Hay datos escritos en el formulario que todavía no se han guardado. */
    public bool $formularioSucio = false;

    /** Muestra el aviso previo a cerrar el formulario con cambios pendientes. */
    public bool $confirmarCierreFormulario = false;

    public ?int $usuarioId = null;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $telefono = '';

    public string $rol = 'tecnico';

    public string $empresa_id = '';

    public bool $activo = true;

    public string $password = '';

    public string $password_confirmation = '';

    public ?int $usuarioAEliminar = null;

    // ------------------------------------------------------------------
    // Vistas de sólo lectura
    // ------------------------------------------------------------------

    public ?int $usuarioVisto = null;

    // ------------------------------------------------------------------
    // Datos derivados
    // ------------------------------------------------------------------

    /** @return LengthAwarePaginator<int, User> */
    #[Computed]
    public function usuarios(): LengthAwarePaginator
    {
        $ordenables = ['name', 'username', 'email', 'rol', 'activo', 'created_at'];
        $columna = in_array($this->ordenarPor, $ordenables, true) ? $this->ordenarPor : 'name';

        return $this->consultaFiltrada()
            ->with('empresa')
            ->withCount('mantenimientos')
            ->orderBy($columna, $this->ordenDireccion === 'asc' ? 'asc' : 'desc')
            ->paginate($this->porPagina);
    }

    /** @return array<string, int> */
    #[Computed]
    public function resumen(): array
    {
        return [
            'total' => User::count(),
            'administrador' => User::delRol('administrador')->count(),
            'institucion' => User::delRol('institucion')->count(),
            'tecnico' => User::delRol('tecnico')->count(),
            'inactivos' => User::where('activo', false)->count(),
        ];
    }

    /**
     * Instituciones a las que se puede vincular una cuenta.
     *
     * @return Collection<int, Empresa>
     */
    #[Computed]
    public function empresas(): Collection
    {
        return Empresa::orderBy('nombre')->get();
    }

    /** Usuario abierto en la ficha. */
    #[Computed]
    public function usuarioDetalle(): ?User
    {
        if ($this->usuarioVisto === null) {
            return null;
        }

        return User::query()
            ->with('empresa')
            ->withCount(['mantenimientos', 'mantenimientos as mantenimientos_abiertos_count' => fn (Builder $q) => $q->abiertos()])
            ->find($this->usuarioVisto);
    }

    /** Usuario señalado para eliminar, con el trabajo que arrastra. */
    #[Computed]
    public function usuarioEliminable(): ?User
    {
        if ($this->usuarioAEliminar === null) {
            return null;
        }

        return User::withCount('mantenimientos')->find($this->usuarioAEliminar);
    }

    #[Computed]
    public function hayFiltrosActivos(): bool
    {
        return $this->buscar !== ''
            || $this->filtroRol !== ''
            || $this->filtroEmpresa !== ''
            || $this->filtroActivo !== '';
    }

    /** Nombre de la institución por la que se está filtrando, si hay una. */
    #[Computed]
    public function empresaFiltrada(): ?Empresa
    {
        return $this->filtroEmpresa === '' ? null : Empresa::find($this->filtroEmpresa);
    }

    // ------------------------------------------------------------------
    // Listado
    // ------------------------------------------------------------------

    public function updated(string $propiedad, mixed $valor = null): void
    {
        // Sólo el usuario de institución cuelga de una empresa; al cambiar de
        // rol el vínculo anterior deja de tener sentido.
        if ($propiedad === 'rol' && $this->rol !== 'institucion') {
            $this->empresa_id = '';
        }

        if (str_starts_with($propiedad, 'filtro') || in_array($propiedad, ['buscar', 'porPagina'], true)) {
            $this->resetPage();

            return;
        }

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

    public function filtrarPorRol(string $rol): void
    {
        $this->filtroRol = array_key_exists($rol, User::ROLES) ? $rol : '';
        $this->filtroActivo = '';

        $this->resetPage();
    }

    public function verInactivos(): void
    {
        $this->filtroRol = '';
        $this->filtroActivo = '0';

        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['buscar', 'filtroRol', 'filtroEmpresa', 'filtroActivo']);

        $this->resetPage();
    }

    /**
     * Da o quita el acceso sin borrar la cuenta, que es lo que conserva el
     * nombre del técnico en las órdenes que ya ejecutó.
     */
    public function alternarActivo(int $id): void
    {
        $usuario = User::findOrFail($id);

        if ($this->esYo($usuario)) {
            Flux::toast(variant: 'warning', text: 'No puedes desactivar tu propia cuenta.');

            return;
        }

        $usuario->activo = ! $usuario->activo;
        $usuario->save();

        unset($this->usuarios, $this->resumen);

        Flux::toast(
            variant: $usuario->activo ? 'success' : 'warning',
            text: $usuario->activo
                ? 'La cuenta de «'.$usuario->name.'» vuelve a tener acceso.'
                : 'La cuenta de «'.$usuario->name.'» quedó sin acceso.',
        );
    }

    // ------------------------------------------------------------------
    // Vistas de sólo lectura
    // ------------------------------------------------------------------

    public function verUsuario(int $id): void
    {
        $this->usuarioVisto = $id;
    }

    public function cerrarDetalle(): void
    {
        $this->usuarioVisto = null;
    }

    // ------------------------------------------------------------------
    // Formulario
    // ------------------------------------------------------------------

    /**
     * El rol y la empresa pueden venir dados desde donde se pulsó: la ficha de
     * una institución crea directamente el acceso de esa institución.
     */
    public function abrirCreacion(string $rol = 'tecnico', ?int $empresaId = null): void
    {
        $this->resetValidation();
        $this->reiniciarFormulario();

        if (array_key_exists($rol, User::ROLES)) {
            $this->rol = $rol;
        }

        if ($this->rol === 'institucion') {
            $this->empresa_id = (string) ($empresaId ?? $this->filtroEmpresa);
        }

        $this->mostrarFormulario = true;
    }

    public function editar(int $id): void
    {
        $usuario = User::findOrFail($id);

        $this->usuarioVisto = null;

        $this->resetValidation();
        $this->reiniciarFormulario();

        $this->usuarioId = $usuario->id;
        $this->name = $usuario->name;
        $this->username = (string) $usuario->username;
        $this->email = $usuario->email;
        $this->telefono = (string) $usuario->telefono;
        $this->rol = $usuario->rol;
        $this->empresa_id = (string) ($usuario->empresa_id ?? '');
        $this->activo = (bool) $usuario->activo;

        $this->mostrarFormulario = true;
    }

    public function guardar(): void
    {
        $this->validate($this->reglas(), [], $this->etiquetas());

        $usuario = $this->usuarioId ? User::findOrFail($this->usuarioId) : new User;

        // Nadie se quita a sí mismo el acceso ni el rol de administrador desde
        // esta pantalla: sería la última vez que la abre.
        $activo = $this->esYo($usuario) ? true : $this->activo;
        $rol = $this->esYo($usuario) ? $usuario->rol : $this->rol;

        $usuario->fill([
            'name' => trim($this->name),
            'username' => trim($this->username),
            'email' => trim($this->email),
            'telefono' => trim($this->telefono) ?: null,
            'rol' => $rol,
            'empresa_id' => $rol === 'institucion' ? (int) $this->empresa_id : null,
            'activo' => $activo,
        ]);

        if ($this->password !== '') {
            $usuario->password = $this->password;
        }

        // La cuenta la abre el administrador en persona: no tiene sentido
        // mandarla a verificar un correo que él mismo acaba de escribir.
        $usuario->email_verified_at ??= Date::now();

        $usuario->save();

        $creado = $this->usuarioId === null;

        $this->cerrarFormulario();

        unset($this->usuarios, $this->resumen);

        Flux::toast(
            variant: 'success',
            text: $creado
                ? 'Usuario «'.$usuario->name.'» creado como '.mb_strtolower($usuario->rolEtiqueta()).'.'
                : 'Usuario «'.$usuario->name.'» actualizado correctamente.',
        );
    }

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
        $this->usuarioVisto = null;

        $this->usuarioAEliminar = $id;
    }

    public function eliminar(): void
    {
        if ($this->usuarioAEliminar === null) {
            return;
        }

        $usuario = User::findOrFail($this->usuarioAEliminar);

        if ($this->esYo($usuario)) {
            $this->usuarioAEliminar = null;

            Flux::toast(variant: 'warning', text: 'No puedes eliminar tu propia cuenta.');

            return;
        }

        $nombre = $usuario->name;
        $usuario->delete();

        $this->usuarioAEliminar = null;

        unset($this->usuarios, $this->resumen);

        Flux::toast(variant: 'success', text: 'Usuario «'.$nombre.'» eliminado.');
    }

    // ------------------------------------------------------------------
    // Apoyo
    // ------------------------------------------------------------------

    /**
     * Consulta del listado con los filtros de la pantalla ya aplicados.
     *
     * @return Builder<User>
     */
    private function consultaFiltrada(): Builder
    {
        return User::query()
            ->when($this->buscar !== '', function (Builder $consulta): void {
                $termino = '%'.$this->buscar.'%';

                $consulta->where(function (Builder $grupo) use ($termino): void {
                    $grupo->where('name', 'like', $termino)
                        ->orWhere('username', 'like', $termino)
                        ->orWhere('email', 'like', $termino)
                        ->orWhere('telefono', 'like', $termino)
                        ->orWhereHas('empresa', fn (Builder $e) => $e->where('nombre', 'like', $termino));
                });
            })
            ->when($this->filtroRol !== '', fn (Builder $q) => $q->where('rol', $this->filtroRol))
            ->when($this->filtroEmpresa !== '', fn (Builder $q) => $q->where('empresa_id', $this->filtroEmpresa))
            ->when($this->filtroActivo !== '', fn (Builder $q) => $q->where('activo', $this->filtroActivo === '1'));
    }

    /** El usuario en pantalla es el que está usando el sistema ahora mismo. */
    private function esYo(User $usuario): bool
    {
        return $usuario->exists && $usuario->id === Auth::id();
    }

    private function reiniciarFormulario(): void
    {
        $this->reset([
            'usuarioId', 'name', 'username', 'email', 'telefono',
            'rol', 'empresa_id', 'activo', 'password', 'password_confirmation',
        ]);

        $this->formularioSucio = false;
        $this->confirmarCierreFormulario = false;
    }

    /** @return array<string, mixed> */
    private function reglas(): array
    {
        return [
            'name' => $this->nameRules(),
            'username' => $this->usernameRules($this->usuarioId),
            'email' => $this->emailRules($this->usuarioId),
            'telefono' => ['nullable', 'string', 'max:60'],
            'rol' => ['required', Rule::in(array_keys(User::ROLES))],
            // El usuario de institución sin institución no vería nada: la
            // pantalla quedaría en blanco y el error aparecería mucho después.
            'empresa_id' => [
                $this->rol === 'institucion' ? 'required' : 'nullable',
                'exists:empresas,id',
            ],
            'activo' => ['boolean'],
            // Al crear hace falta contraseña; al editar, sólo si se va a cambiar.
            'password' => $this->usuarioId === null
                ? $this->passwordRules()
                : ['nullable', 'string', 'confirmed'],
        ];
    }

    /** @return array<string, string> */
    private function etiquetas(): array
    {
        return [
            'name' => 'nombre completo',
            'username' => 'nombre de usuario',
            'email' => 'correo electrónico',
            'telefono' => 'teléfono',
            'rol' => 'tipo de usuario',
            'empresa_id' => 'institución',
            'password' => 'contraseña',
        ];
    }
}; ?>

<section class="eq-root w-full space-y-6">
    {{-- ───────────────── Encabezado ───────────────── --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <nav class="mb-1 flex items-center gap-1.5 text-[11.5px] font-medium text-zinc-400">
                <span class="text-zinc-500 dark:text-zinc-400">Usuarios</span>
            </nav>

            <h1 class="text-2xl font-bold tracking-tight text-carbon dark:text-white">Cuentas de acceso</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Quién entra al sistema y qué parte de él le corresponde ver.
            </p>
        </div>

        <button type="button" class="eq-btn eq-btn-accent" wire:click="abrirCreacion">
            <flux:icon name="plus" variant="mini" class="size-4" />
            Añadir usuario
        </button>
    </div>

    {{-- ───────────────── Indicadores ───────────────── --}}
    @php
        $tarjetas = [
            ['rol' => 'administrador', 'etiqueta' => 'Administradores', 'valor' => $this->resumen['administrador'], 'color' => 'text-signal'],
            ['rol' => 'institucion', 'etiqueta' => 'Instituciones', 'valor' => $this->resumen['institucion'], 'color' => 'text-lima-700 dark:text-lima'],
            ['rol' => 'tecnico', 'etiqueta' => 'Técnicos', 'valor' => $this->resumen['tecnico'], 'color' => 'text-amber-600 dark:text-amber-500'],
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($tarjetas as $tarjeta)
            <button
                type="button"
                wire:click="filtrarPorRol('{{ $tarjeta['rol'] }}')"
                title="{{ \App\Models\User::DESCRIPCION_ROLES[$tarjeta['rol']] }}"
                @class([
                    'eq-panel flex w-full cursor-pointer flex-col items-center justify-center gap-1 px-4 py-5 text-center transition duration-300 outline-none hover:-translate-y-1 hover:shadow-lg focus-visible:ring-4 focus-visible:ring-lima/30',
                    'ring-2 ring-lima' => $filtroRol === $tarjeta['rol'],
                ])
            >
                <p class="text-3xl leading-none font-bold {{ $tarjeta['color'] }}">{{ $tarjeta['valor'] }}</p>
                <p class="text-[11.5px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $tarjeta['etiqueta'] }}</p>
            </button>
        @endforeach

        <button
            type="button"
            wire:click="verInactivos"
            title="Cuentas sin acceso al sistema"
            @class([
                'eq-panel flex w-full cursor-pointer flex-col items-center justify-center gap-1 px-4 py-5 text-center transition duration-300 outline-none hover:-translate-y-1 hover:shadow-lg focus-visible:ring-4 focus-visible:ring-lima/30',
                'ring-2 ring-lima' => $filtroActivo === '0',
            ])
        >
            <p class="text-3xl leading-none font-bold text-rose-600 dark:text-rose-400">{{ $this->resumen['inactivos'] }}</p>
            <p class="text-[11.5px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Sin acceso</p>
        </button>
    </div>

    {{-- ───────────────── Procedencia: accesos de una institución ───────────────── --}}
    @if ($this->empresaFiltrada)
        <div class="eq-panel flex flex-wrap items-center justify-between gap-3 border-signal/30 bg-signal/5 px-5 py-3 dark:border-signal/25 dark:bg-signal/10">
            <p class="text-[13px] text-carbon dark:text-zinc-200">
                Mostrando sólo las cuentas de: <span class="font-semibold">{{ $this->empresaFiltrada->nombre }}</span>
            </p>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="eq-btn eq-btn-ghost !px-3 !py-1.5 !text-[12px]" wire:click="abrirCreacion('institucion', {{ $this->empresaFiltrada->id }})">
                    <flux:icon name="plus" variant="micro" class="size-3.5" />
                    Crear acceso para esta institución
                </button>

                <button type="button" class="eq-enlace" wire:click="$set('filtroEmpresa', '')">
                    Ver todos los usuarios
                </button>
            </div>
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
                placeholder="Buscar por nombre, usuario, correo, teléfono o institución…"
                wire:model.live.debounce.400ms="buscar"
            >
            <div wire:loading.delay wire:target="buscar" class="absolute top-1/2 right-4 -translate-y-1/2">
                <flux:icon name="arrow-path" variant="mini" class="size-4 animate-spin text-lima" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="eq-label" for="f-rol">Tipo de usuario</label>
                <select id="f-rol" class="eq-select" wire:model.live="filtroRol">
                    <option value="">Todos</option>
                    @foreach (\App\Models\User::ROLES as $clave => $etiqueta)
                        <option value="{{ $clave }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="eq-label" for="f-empresa">Institución</label>
                <select id="f-empresa" class="eq-select" wire:model.live="filtroEmpresa">
                    <option value="">Todas</option>
                    @foreach ($this->empresas as $empresa)
                        <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="eq-label" for="f-activo">Acceso</label>
                <select id="f-activo" class="eq-select" wire:model.live="filtroActivo">
                    <option value="">Todos</option>
                    <option value="1">Con acceso</option>
                    <option value="0">Sin acceso</option>
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
                Lista de usuarios
                <span class="eq-chip bg-lima-soft text-lima-700 dark:bg-lima/10 dark:text-lima">
                    {{ $this->usuarios->total() }} {{ $this->usuarios->total() === 1 ? 'resultado' : 'resultados' }}
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
                                ['clave' => 'name', 'titulo' => 'Usuario', 'ordenable' => true],
                                ['clave' => 'rol', 'titulo' => 'Tipo', 'ordenable' => true],
                                ['clave' => null, 'titulo' => 'Institución', 'ordenable' => false],
                                ['clave' => null, 'titulo' => 'Contacto', 'ordenable' => false],
                                ['clave' => null, 'titulo' => 'Órdenes', 'ordenable' => false],
                                ['clave' => 'activo', 'titulo' => 'Acceso', 'ordenable' => true],
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
                    @forelse ($this->usuarios as $usuario)
                        <tr
                            wire:key="usuario-{{ $usuario->id }}"
                            tabindex="0"
                            wire:click="verUsuario({{ $usuario->id }})"
                            wire:keydown.enter="verUsuario({{ $usuario->id }})"
                            title="Ver la ficha de {{ $usuario->name }}"
                            class="cursor-pointer transition duration-150 outline-none hover:bg-lima-soft/40 focus-visible:bg-lima-soft/60 dark:hover:bg-zinc-800/50 dark:focus-visible:bg-zinc-800/70"
                        >
                            <td class="px-4 py-3 align-top">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-[12px] font-bold text-carbon shadow-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                        {{ $usuario->initials() }}
                                    </span>

                                    <div class="min-w-0">
                                        <p class="font-semibold text-carbon dark:text-zinc-100">
                                            {{ $usuario->name }}
                                            @if ($usuario->id === auth()->id())
                                                <span class="eq-chip ml-1 bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">Tú</span>
                                            @endif
                                        </p>
                                        <p class="font-mono text-[12px] text-zinc-500 dark:text-zinc-400">{{ '@'.$usuario->username }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <span class="eq-chip bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                    <flux:icon name="{{ \App\Models\User::ICONO_ROLES[$usuario->rol] ?? 'user' }}" variant="micro" class="size-3" />
                                    {{ $usuario->rolEtiqueta() }}
                                </span>
                            </td>

                            <td class="px-4 py-3 align-top">
                                @if ($usuario->empresa)
                                    <p class="font-medium text-carbon dark:text-zinc-200">{{ $usuario->empresa->nombre }}</p>
                                    @if ($usuario->empresa->ciudad)
                                        <p class="text-[12px] text-zinc-500 dark:text-zinc-400">{{ $usuario->empresa->ciudad }}</p>
                                    @endif
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top">
                                <p class="text-[12.5px] text-carbon dark:text-zinc-200">{{ $usuario->email }}</p>
                                @if ($usuario->telefono)
                                    <p class="text-[12px] text-zinc-500 dark:text-zinc-400">{{ $usuario->telefono }}</p>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top">
                                @if ($usuario->esTecnico())
                                    <p class="font-medium text-carbon dark:text-zinc-200">{{ $usuario->mantenimientos_count }}</p>
                                    <p class="text-[12px] text-zinc-500 dark:text-zinc-400">asignadas</p>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top">
                                <button
                                    type="button"
                                    wire:click.stop="alternarActivo({{ $usuario->id }})"
                                    title="Cambiar el acceso de {{ $usuario->name }}"
                                    @class([
                                        'eq-chip cursor-pointer transition duration-200 hover:scale-110 hover:shadow-md',
                                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400' => $usuario->activo,
                                        'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400' => ! $usuario->activo,
                                    ])
                                >
                                    <span @class([
                                        'size-1.5 rounded-full',
                                        'bg-emerald-500' => $usuario->activo,
                                        'bg-rose-500' => ! $usuario->activo,
                                    ])></span>
                                    {{ $usuario->activo ? 'Con acceso' : 'Sin acceso' }}
                                </button>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="flex items-center gap-1">
                                    <button type="button" class="eq-icon-btn" wire:click.stop="editar({{ $usuario->id }})" title="Editar {{ $usuario->name }}">
                                        <flux:icon name="pencil-square" variant="mini" class="size-4" />
                                    </button>

                                    @if ($usuario->id !== auth()->id())
                                        <button
                                            type="button"
                                            class="eq-icon-btn hover:!bg-rose-50 hover:!text-rose-600 dark:hover:!bg-rose-500/10 dark:hover:!text-rose-400"
                                            wire:click.stop="confirmarEliminacion({{ $usuario->id }})"
                                            title="Eliminar {{ $usuario->name }}"
                                        >
                                            <flux:icon name="trash" variant="mini" class="size-4" />
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                                    <span class="flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon name="users" class="size-7 text-zinc-400" />
                                    </span>
                                    <p class="text-[15px] font-semibold text-carbon dark:text-zinc-200">
                                        {{ $this->hayFiltrosActivos ? 'Ningún usuario coincide con los filtros' : 'Todavía no hay usuarios registrados' }}
                                    </p>
                                    <p class="text-[13px] text-zinc-500 dark:text-zinc-400">
                                        {{ $this->hayFiltrosActivos ? 'Ajuste o limpie los filtros para ver más resultados.' : 'Cree la cuenta del primer técnico o de la primera institución.' }}
                                    </p>
                                    @if ($this->hayFiltrosActivos)
                                        <button type="button" class="eq-btn eq-btn-ghost" wire:click="limpiarFiltros">Limpiar filtros</button>
                                    @else
                                        <button type="button" class="eq-btn eq-btn-accent" wire:click="abrirCreacion">
                                            <flux:icon name="plus" variant="mini" class="size-4" /> Añadir usuario
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->usuarios->hasPages())
            <div class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-800">
                {{ $this->usuarios->links() }}
            </div>
        @endif
    </div>

    {{-- Los modales se teletransportan al <body>: dentro del contenedor de la
         página el velo `fixed` no llegaba a cubrir toda la pantalla. --}}
    @teleport('body')
        @include('pages.usuarios.partials.modal-formulario', ['empresasDisponibles' => $this->empresas])
    @endteleport

    @teleport('body')
        @include('pages.usuarios.partials.modal-detalle', ['usuario' => $this->usuarioDetalle])
    @endteleport

    @teleport('body')
        @include('pages.usuarios.partials.modal-eliminar', ['usuario' => $this->usuarioEliminable])
    @endteleport
</section>
