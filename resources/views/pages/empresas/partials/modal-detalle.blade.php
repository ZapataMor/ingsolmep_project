{{-- Ficha de sólo lectura de la empresa seleccionada en la tabla. --}}
{{-- La raíz del teletransporte existe siempre: Livewire descarta un
     `@teleport` cuyo cuerpo nace vacío y ya no lo vuelve a poblar. --}}
<div class="contents">
    @if ($empresa)
        <div
            x-data
            x-on:keydown.escape.window="$wire.cerrarDetalle()"
            x-on:click.self="$wire.cerrarDetalle()"
            class="eq-modal fixed inset-0 z-50 overflow-y-auto bg-carbon-deep/70 p-3 backdrop-blur-sm sm:p-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="em-detalle-titulo"
        >
            <div class="eq-modal-panel mx-auto my-2 w-full max-w-3xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-zinc-900 dark:ring-white/10">
                @php
                    $identificacion = [
                        'NIT' => $empresa->nit,
                        'Ciudad' => $empresa->ciudad,
                        'Dirección' => $empresa->direccion,
                        'Fecha de registro' => $empresa->created_at?->format('d/m/Y'),
                    ];

                    $contacto = [
                        'Correo electrónico' => $empresa->email,
                        'Celular' => $empresa->celular,
                        'Teléfono fijo' => $empresa->telefono,
                        'WhatsApp' => $empresa->whatsapp,
                    ];

                    $estadosOperativos = \App\Models\Equipo::ESTADOS_OPERATIVOS;

                    // Cada pestaña lleva el tamaño de lo que hay dentro, para
                    // no obligar a entrar sólo a comprobar si está vacía.
                    $totalesPorPestana = [
                        'equipos' => $empresa->equipos_count,
                        'usuarios' => $empresa->usuarios->count(),
                    ];
                @endphp

                {{-- Cabecera --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-carbon to-carbon-deep px-6 py-5 sm:px-8">
                    <div class="pointer-events-none absolute inset-0 opacity-[0.07]" style="background-image: repeating-linear-gradient(to right, #fff 0 1px, transparent 1px 22px), repeating-linear-gradient(to bottom, #fff 0 1px, transparent 1px 22px);"></div>

                    <div class="relative flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-4">
                            <span class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white text-sm font-bold text-carbon ring-1 ring-white/15">
                                @if ($empresa->logoUrl())
                                    <img src="{{ $empresa->logoUrl() }}" alt="Logo de {{ $empresa->nombre }}" class="size-full object-contain p-1.5">
                                @else
                                    {{ $empresa->iniciales() }}
                                @endif
                            </span>

                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold tracking-[0.14em] text-lima uppercase">Ficha de la empresa · #{{ $empresa->id }}</p>
                                <h2 id="em-detalle-titulo" class="mt-1 truncate text-xl font-bold text-white">{{ $empresa->nombre }}</h2>
                                <p class="mt-1 text-[12.5px] text-zinc-400">
                                    NIT {{ $empresa->nit ?: '—' }} · {{ $empresa->ciudad ?: 'Sin ciudad registrada' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-3">
                            <span @class([
                                'eq-chip',
                                'bg-emerald-500/15 text-emerald-400' => $empresa->activo,
                                'bg-rose-500/15 text-rose-400' => ! $empresa->activo,
                            ])>
                                <span @class(['size-1.5 rounded-full', 'bg-emerald-500' => $empresa->activo, 'bg-rose-500' => ! $empresa->activo])></span>
                                {{ $empresa->activo ? 'Activo' : 'Inactivo' }}
                            </span>

                            <button type="button" class="eq-icon-btn !text-zinc-400 hover:!bg-white/10 hover:!text-white" wire:click="cerrarDetalle" title="Cerrar">
                                <flux:icon name="x-mark" variant="mini" class="size-5" />
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Pestañas --}}
                <div class="border-b border-zinc-200 px-6 sm:px-8 dark:border-zinc-800">
                    <nav class="flex flex-wrap gap-x-4 sm:gap-x-6" aria-label="Secciones de la ficha">
                        @foreach ($pestanas as $clave => $definicion)
                            @php $activa = $clave === $pestanaActiva; @endphp

                            <button
                                type="button"
                                wire:click="verPestanaFicha('{{ $clave }}')"
                                aria-current="{{ $activa ? 'page' : 'false' }}"
                                @class([
                                    'eq-tab',
                                    'eq-tab-activa' => $activa,
                                    'eq-tab-inactiva' => ! $activa,
                                ])
                            >
                                <flux:icon name="{{ $definicion['icono'] }}" variant="mini" @class(['size-4', 'text-lima' => $activa]) />
                                {{ $definicion['titulo'] }}

                                @isset ($totalesPorPestana[$clave])
                                    <span @class([
                                        'rounded-full px-1.5 py-0.5 text-[10.5px] font-bold',
                                        'bg-lima-soft text-lima-700 dark:bg-lima/15 dark:text-lima' => $activa,
                                        'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400' => ! $activa,
                                    ])>{{ $totalesPorPestana[$clave] }}</span>
                                @endisset
                            </button>
                        @endforeach
                    </nav>
                </div>

                {{-- Cuerpo. Sólo se pinta la pestaña abierta: el inventario
                     además ni se consulta mientras la suya esté cerrada. --}}
                <div class="max-h-[60vh] space-y-6 overflow-y-auto px-6 py-6 sm:px-8">
                    @if ($pestanaActiva === 'informacion')
                    <section>
                        <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="information-circle" class="size-4" /> Identificación
                        </p>

                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                            @foreach ($identificacion as $etiqueta => $valor)
                                <div>
                                    <dt class="text-[11px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $etiqueta }}</dt>
                                    <dd class="mt-0.5 text-[13.5px] font-medium text-carbon dark:text-zinc-100">{{ filled($valor) ? $valor : '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>

                    <section>
                        <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="phone" class="size-4" /> Contacto
                        </p>

                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                            @foreach ($contacto as $etiqueta => $valor)
                                <div>
                                    <dt class="text-[11px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $etiqueta }}</dt>
                                    <dd class="mt-0.5 text-[13.5px] font-medium text-carbon dark:text-zinc-100">{{ filled($valor) ? $valor : '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @if ($empresa->email)
                                <a href="mailto:{{ $empresa->email }}" class="eq-btn eq-btn-ghost !px-3 !py-1.5 !text-[12px]">
                                    <flux:icon name="envelope" variant="micro" class="size-3.5" />
                                    Escribir correo
                                </a>
                            @endif

                            @if ($empresa->whatsappUrl())
                                <a href="{{ $empresa->whatsappUrl() }}" target="_blank" rel="noopener" class="eq-btn eq-btn-ghost !px-3 !py-1.5 !text-[12px]">
                                    <flux:icon name="chat-bubble-left-right" variant="micro" class="size-3.5 text-emerald-500" />
                                    Abrir WhatsApp
                                </a>
                            @endif

                            @if ($empresa->celular)
                                <a href="tel:{{ $empresa->celular }}" class="eq-btn eq-btn-ghost !px-3 !py-1.5 !text-[12px]">
                                    <flux:icon name="phone" variant="micro" class="size-3.5" />
                                    Llamar
                                </a>
                            @endif
                        </div>
                    </section>
                    @endif

                    @if ($pestanaActiva === 'equipos')
                    {{-- Dónde está repartido el inventario, antes de lo que es. --}}
                    <section>
                        <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="map-pin" class="size-4" />
                            Áreas y servicios
                            <span class="text-zinc-400 normal-case">
                                ({{ $empresa->areas->count() }} {{ $empresa->areas->count() === 1 ? 'área' : 'áreas' }})
                            </span>
                        </p>

                        <div class="flex flex-wrap gap-2">
                            @forelse ($empresa->areas as $area)
                                <span class="eq-chip bg-lima-soft text-lima-700 dark:bg-lima/10 dark:text-lima">
                                    <flux:icon name="map-pin" variant="micro" class="size-3" />
                                    {{ $area->nombre }}
                                    <span class="font-bold">· {{ $area->equipos_count }}</span>
                                </span>
                            @empty
                                <span class="text-[13px] text-zinc-500 dark:text-zinc-400">
                                    Todavía no hay áreas ni servicios registrados. Se crean al asignar equipos a esta empresa.
                                </span>
                            @endforelse
                        </div>
                    </section>

                    {{-- El inventario en sí. Las áreas de arriba dicen dónde
                         está repartido; esto dice qué es. Se recorta al tope y
                         se remite al módulo de equipos, que es el que tiene
                         filtros y paginación. --}}
                    <section>
                        <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="rectangle-stack" class="size-4" />
                            Equipos asignados
                            <span class="text-zinc-400 normal-case">
                                ({{ $empresa->equipos_count }} {{ $empresa->equipos_count === 1 ? 'equipo' : 'equipos' }})
                            </span>
                        </p>

                        <div class="space-y-2">
                            @forelse ($equipos as $equipo)
                                <div
                                    wire:key="ficha-equipo-{{ $equipo->id }}"
                                    class="flex flex-wrap items-center gap-3 rounded-xl border border-zinc-200 px-4 py-2.5 dark:border-zinc-700"
                                >
                                    <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-carbon to-carbon-deep text-[11px] font-bold text-white shadow-sm">
                                        @if ($equipo->fotoUrl())
                                            <img src="{{ $equipo->fotoUrl() }}" alt="{{ $equipo->descripcion }}" class="size-full object-cover">
                                        @else
                                            {{ $equipo->iniciales() }}
                                        @endif
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[13.5px] font-semibold text-carbon dark:text-zinc-100">{{ $equipo->descripcion }}</p>
                                        <p class="truncate text-[12px] text-zinc-500 dark:text-zinc-400">
                                            <span class="font-mono">{{ $equipo->numero_serie ?: 'Sin serie' }}</span>
                                            · {{ $equipo->marca?->nombre ?? 'Sin marca' }}
                                            {{ $equipo->modelo?->nombre ? '/ '.$equipo->modelo->nombre : '' }}
                                            · {{ $equipo->area?->nombre ?? 'Sin área' }}
                                        </p>
                                    </div>

                                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                                        @if ($equipo->clasificacion_riesgo)
                                            <span class="eq-chip bg-signal/10 text-signal-600 dark:text-signal">Riesgo {{ $equipo->clasificacion_riesgo }}</span>
                                        @endif

                                        <span @class([
                                            'eq-chip',
                                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400' => $equipo->estado_operativo === 'operativo',
                                            'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400' => $equipo->estado_operativo === 'fuera_servicio',
                                            'bg-zinc-100 text-zinc-600 dark:bg-zinc-700/40 dark:text-zinc-300' => $equipo->estado_operativo === 'dado_baja',
                                        ])>
                                            {{ $estadosOperativos[$equipo->estado_operativo] ?? $equipo->estado_operativo }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-[13px] text-zinc-500 dark:text-zinc-400">
                                    Esta institución todavía no tiene equipos asignados en el inventario.
                                </p>
                            @endforelse
                        </div>

                        @if ($empresa->equipos_count > $topeEquipos)
                            <p class="mt-3 text-[12px] text-zinc-500 dark:text-zinc-400">
                                Se muestran los primeros {{ $topeEquipos }} de {{ $empresa->equipos_count }} equipos.
                            </p>
                        @endif

                        @if ($empresa->equipos_count > 0)
                            <a href="{{ route('equipos.index', ['empresa' => $empresa->id]) }}" class="eq-enlace mt-3 inline-block" wire:navigate>
                                Ver el inventario completo en el módulo de equipos
                            </a>
                        @endif
                    </section>
                    @endif

                    @if ($pestanaActiva === 'usuarios')
                    {{-- Quién entra al sistema por esta IPS. Se ve desde aquí,
                         que es donde nace la pregunta; se administra en el
                         módulo de usuarios, que es donde viven las tres clases
                         de cuenta. --}}
                    <section>
                        <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="users" class="size-4" />
                            Usuarios de la institución
                            <span class="text-zinc-400 normal-case">
                                ({{ $empresa->usuarios->count() }} {{ $empresa->usuarios->count() === 1 ? 'cuenta' : 'cuentas' }})
                            </span>
                        </p>

                        <div class="space-y-2">
                            @forelse ($empresa->usuarios as $usuario)
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-zinc-200 px-4 py-2.5 dark:border-zinc-700">
                                    <div class="min-w-0">
                                        <p class="text-[13.5px] font-semibold text-carbon dark:text-zinc-100">{{ $usuario->name }}</p>
                                        <p class="font-mono text-[12px] text-zinc-500 dark:text-zinc-400">{{ '@'.$usuario->username }} · {{ $usuario->email }}</p>
                                    </div>

                                    <span @class([
                                        'eq-chip',
                                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400' => $usuario->activo,
                                        'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400' => ! $usuario->activo,
                                    ])>
                                        {{ $usuario->activo ? 'Con acceso' : 'Sin acceso' }}
                                    </span>
                                </div>
                            @empty
                                <p class="text-[13px] text-zinc-500 dark:text-zinc-400">
                                    Esta institución todavía no tiene cuentas para consultar sus equipos y mantenimientos.
                                </p>
                            @endforelse
                        </div>

                        <a href="{{ route('usuarios.index', ['empresa' => $empresa->id]) }}" class="eq-enlace mt-3 inline-block" wire:navigate>
                            {{ $empresa->usuarios->isEmpty() ? 'Crear el acceso de esta institución' : 'Administrar sus usuarios' }}
                        </a>
                    </section>
                    @endif
                </div>

                {{-- Pie --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 bg-zinc-50/80 px-6 py-4 sm:px-8 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <p class="text-[12px] text-zinc-500 dark:text-zinc-400">Vista de sólo lectura.</p>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="eq-btn eq-btn-ghost" wire:click="cerrarDetalle">Cerrar</button>

                        <button
                            type="button"
                            class="eq-btn eq-btn-ghost hover:!border-rose-200 hover:!bg-rose-50 hover:!text-rose-600 dark:hover:!border-rose-500/30 dark:hover:!bg-rose-500/10 dark:hover:!text-rose-400"
                            wire:click="confirmarEliminacion({{ $empresa->id }})"
                        >
                            <flux:icon name="trash" variant="mini" class="size-4" />
                            Eliminar
                        </button>

                        <button type="button" class="eq-btn eq-btn-primary" wire:click="editar({{ $empresa->id }})">
                            <flux:icon name="pencil-square" variant="mini" class="size-4" />
                            Editar empresa
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
