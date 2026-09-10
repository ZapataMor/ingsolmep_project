{{-- Ficha de sólo lectura del equipo seleccionado en la tabla. --}}
<div
    x-cloak
    x-show="$wire.equipoVisto !== null"
    x-transition.opacity.duration.200ms
    x-effect="document.body.style.overflow = ($wire.equipoVisto !== null || $wire.mostrarFormulario || $wire.listadoVisto !== '') ? 'hidden' : ''"
    x-on:keydown.escape.window="$wire.equipoVisto !== null && $wire.cerrarDetalle()"
    x-on:click.self="$wire.cerrarDetalle()"
    class="fixed inset-0 z-50 overflow-y-auto bg-carbon-deep/70 p-3 backdrop-blur-sm sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="eq-detalle-titulo"
>
    <div
        x-show="$wire.equipoVisto !== null"
        x-transition:enter="transition duration-250 ease-out"
        x-transition:enter-start="opacity-0 translate-y-6 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        class="mx-auto my-2 w-full max-w-4xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-zinc-900 dark:ring-white/10"
    >
        @if ($equipo)
            @php
                $tecnicas = array_filter([
                    'Voltaje' => $equipo->voltaje,
                    'Amperaje' => $equipo->amperaje,
                    'Frecuencia' => $equipo->frecuencia,
                    'Corriente' => $equipo->corriente,
                    'Potencia' => $equipo->potencia,
                    'Voltios' => $equipo->voltios,
                    'Temperatura' => $equipo->temperatura,
                    'Presión' => $equipo->presion,
                    'Peso' => $equipo->peso,
                    'Velocidad' => $equipo->velocidad,
                    'Tecnología' => $equipo->tecnologia_predominante,
                ], fn ($valor) => filled($valor));

                $subtareasMarcadas = collect($equipo->subtareas ?? [])
                    ->filter()
                    ->keys()
                    ->map(fn (string $clave) => \App\Models\Equipo::SUBTAREAS[$clave] ?? $clave);

                $accesorios = collect($equipo->accesorios_estado ?? [])->filter();

                $colorEstado = ['B' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400', 'R' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400', 'M' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400'];

                $ficha = [
                    'Empresa / IPS' => $equipo->empresa?->nombre,
                    'Área / servicio' => $equipo->area?->nombre,
                    'Número de serie' => $equipo->numero_serie,
                    'Registro INVIMA' => $equipo->registro_invima,
                    'Clasificación por riesgo' => $equipo->clasificacion_riesgo ? (\App\Models\Equipo::RIESGOS[$equipo->clasificacion_riesgo] ?? $equipo->clasificacion_riesgo) : null,
                    'Especialidad' => $equipo->clasificacion_especialidad,
                    'Fabricante' => $equipo->fabricante,
                    'País de origen' => $equipo->pais_origen,
                    'Teléfono del fabricante' => $equipo->telefono_fabricante,
                    'Tipo de adquisición' => $equipo->tipo_adquisicion,
                    'Garantía' => $equipo->garantia_vence?->format('d/m/Y'),
                    'Último mantenimiento' => $equipo->ultimo_mantenimiento?->format('d/m/Y'),
                    'Prioridad' => $equipo->prioridad,
                    'Fecha de registro' => $equipo->created_at?->format('d/m/Y'),
                ];

                $coloresOrden = [
                    'programado' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
                    'en_proceso' => 'bg-lima-soft text-lima-700 dark:bg-lima/15 dark:text-lima',
                    'ejecutado' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
                    'cancelado' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400',
                ];

                $textos = array_filter([
                    'Observaciones técnicas' => $equipo->observaciones_tecnicas,
                    'Observaciones generales' => $equipo->observaciones_generales,
                    'Nota de mantenimiento' => $equipo->mantenimiento,
                    'Componentes y accesorios incluidos' => $equipo->componentes,
                    'Texto por defecto para órdenes de trabajo' => $equipo->observaciones_ot,
                ], fn ($valor) => filled($valor));
            @endphp

            {{-- Cabecera --}}
            <div class="relative overflow-hidden bg-gradient-to-br from-carbon to-carbon-deep px-6 py-5 sm:px-8">
                <div class="pointer-events-none absolute inset-0 opacity-[0.07]" style="background-image: repeating-linear-gradient(to right, #fff 0 1px, transparent 1px 22px), repeating-linear-gradient(to bottom, #fff 0 1px, transparent 1px 22px);"></div>

                <div class="relative flex items-start justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-4">
                        <span class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white/10 text-sm font-bold text-white ring-1 ring-white/15">
                            @if ($equipo->fotoUrl())
                                <img src="{{ $equipo->fotoUrl() }}" alt="{{ $equipo->descripcion }}" class="size-full object-cover">
                            @else
                                {{ $equipo->iniciales() }}
                            @endif
                        </span>

                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold tracking-[0.14em] text-lima uppercase">Ficha del equipo · #{{ $equipo->id }}</p>
                            <h2 id="eq-detalle-titulo" class="mt-1 truncate text-xl font-bold text-white">{{ $equipo->descripcion }}</h2>
                            <p class="mt-1 text-[12.5px] text-zinc-400">
                                {{ $equipo->marca?->nombre ?? 'Sin marca' }} · {{ $equipo->modelo?->nombre ?? 'Sin modelo' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                        <span @class([
                            'eq-chip',
                            'bg-emerald-500/15 text-emerald-400' => $equipo->activo,
                            'bg-rose-500/15 text-rose-400' => ! $equipo->activo,
                        ])>
                            <span @class(['size-1.5 rounded-full', 'bg-emerald-500' => $equipo->activo, 'bg-rose-500' => ! $equipo->activo])></span>
                            {{ $equipo->activo ? 'Activo' : 'Fuera de servicio' }}
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

                            @if ($clave === 'mantenimientos')
                                <span @class([
                                    'rounded-full px-1.5 py-0.5 text-[10.5px] font-bold',
                                    'bg-lima-soft text-lima-700 dark:bg-lima/15 dark:text-lima' => $activa,
                                    'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400' => ! $activa,
                                ])>{{ $totalHistorial }}</span>
                            @endif
                        </button>
                    @endforeach
                </nav>
            </div>

            {{-- Cuerpo. Sólo se pinta la pestaña abierta: el historial además ni
                 se consulta mientras la suya esté cerrada. --}}
            <div class="max-h-[60vh] space-y-6 overflow-y-auto px-6 py-6 sm:px-8">
                @if ($pestanaActiva === 'informacion')
                <section>
                    <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                        <flux:icon name="calendar-days" class="size-4" /> Próximo mantenimiento
                    </p>

                    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                        <x-semaforo-mantenimiento :equipo="$equipo" :titulo="false" :detalle="true" />
                    </div>
                </section>

                <section>
                    <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                        <flux:icon name="clipboard-document-list" class="size-4" /> Información general
                    </p>

                    <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($ficha as $etiqueta => $valor)
                            <div>
                                <dt class="text-[11px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $etiqueta }}</dt>
                                <dd class="mt-0.5 text-[13.5px] font-medium text-carbon dark:text-zinc-100">{{ filled($valor) ? $valor : '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                <section>
                    <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                        <flux:icon name="bolt" class="size-4" /> Características técnicas
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <span class="eq-chip bg-signal/10 text-signal-600 dark:text-signal">
                            {{ \App\Models\Equipo::SUMINISTROS[$equipo->suministro_electrico] ?? 'Suministro sin definir' }}
                        </span>

                        @forelse ($tecnicas as $etiqueta => $valor)
                            <span class="eq-chip bg-zinc-100 text-carbon dark:bg-zinc-800 dark:text-zinc-200">
                                {{ $etiqueta }}: <span class="font-bold">{{ $valor }}</span>
                            </span>
                        @empty
                            <span class="text-[13px] text-zinc-500 dark:text-zinc-400">Sin parámetros registrados.</span>
                        @endforelse
                    </div>
                </section>

                <section>
                    <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                        <flux:icon name="wrench-screwdriver" class="size-4" />
                        Subtareas de mantenimiento
                        <span class="text-zinc-400 normal-case">({{ $subtareasMarcadas->count() }} de {{ count(\App\Models\Equipo::SUBTAREAS) }})</span>
                    </p>

                    <div class="flex flex-wrap gap-2">
                        @forelse ($subtareasMarcadas as $subtarea)
                            <span class="eq-chip bg-lima-soft text-lima-700 dark:bg-lima/10 dark:text-lima">
                                <flux:icon name="check" variant="micro" class="size-3" />
                                {{ $subtarea }}
                            </span>
                        @empty
                            <span class="text-[13px] text-zinc-500 dark:text-zinc-400">Sin subtareas marcadas por defecto.</span>
                        @endforelse
                    </div>
                </section>

                <section>
                    <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                        <flux:icon name="squares-2x2" class="size-4" /> Estado de accesorios
                    </p>

                    <div class="flex flex-wrap gap-2">
                        @forelse ($accesorios as $clave => $estado)
                            <span class="eq-chip {{ $colorEstado[$estado] ?? 'bg-zinc-100 text-carbon dark:bg-zinc-800 dark:text-zinc-200' }}">
                                {{ \App\Models\Equipo::ACCESORIOS[$clave] ?? $clave }}:
                                <span class="font-bold">{{ \App\Models\Equipo::ESTADOS_ACCESORIO[$estado] ?? $estado }}</span>
                            </span>
                        @empty
                            <span class="text-[13px] text-zinc-500 dark:text-zinc-400">Sin accesorios evaluados.</span>
                        @endforelse
                    </div>
                </section>

                @if ($textos !== [])
                    <section class="space-y-4">
                        <p class="flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="document-check" class="size-4" /> Observaciones
                        </p>

                        @foreach ($textos as $etiqueta => $texto)
                            <div class="rounded-xl border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-800 dark:bg-zinc-800/40">
                                <p class="text-[11px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $etiqueta }}</p>
                                <p class="mt-1 text-[13.5px] leading-relaxed whitespace-pre-line text-carbon dark:text-zinc-100">{{ $texto }}</p>
                            </div>
                        @endforeach
                    </section>
                @endif
                @endif

                @if ($pestanaActiva === 'mantenimientos')
                {{-- Historial: lo que se le ha hecho al equipo, de lo más
                     reciente a lo más antiguo. Se recorta al tope y se remite al
                     módulo de mantenimientos, que es el que tiene filtros. --}}
                <section>
                    <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                        <flux:icon name="wrench-screwdriver" class="size-4" />
                        Historial de mantenimientos
                        <span class="text-zinc-400 normal-case">
                            ({{ $totalHistorial }} {{ $totalHistorial === 1 ? 'orden' : 'órdenes' }})
                        </span>
                    </p>

                    <ol class="space-y-2">
                        @forelse ($historial as $orden)
                            <li
                                wire:key="ficha-orden-{{ $orden->id }}"
                                class="rounded-xl border border-zinc-200 px-4 py-3 dark:border-zinc-700"
                            >
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-[12px] font-bold text-carbon dark:text-zinc-100">{{ $orden->codigo() }}</span>

                                    <span @class([
                                        'eq-chip',
                                        'bg-signal/10 text-signal-600 dark:bg-signal/15 dark:text-signal' => $orden->tipo === 'preventivo',
                                        'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400' => $orden->tipo === 'correctivo',
                                    ])>{{ $orden->tipoEtiqueta() }}</span>

                                    <span class="eq-chip {{ $coloresOrden[$orden->estado] ?? '' }}">{{ $orden->estadoEtiqueta() }}</span>

                                    @if ($orden->estaVencido())
                                        <span class="eq-chip bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400">
                                            <flux:icon name="bell-alert" variant="micro" class="size-3" />
                                            Vencida
                                        </span>
                                    @endif

                                    <span class="ml-auto text-[12px] text-zinc-500 dark:text-zinc-400">
                                        {{ $orden->fecha_ejecucion?->format('d/m/Y') ?? $orden->fecha_programada->format('d/m/Y') }}
                                    </span>
                                </div>

                                <p class="mt-1.5 text-[12px] text-zinc-500 dark:text-zinc-400">
                                    Programada {{ $orden->fecha_programada->format('d/m/Y') }}
                                    · {{ $orden->fecha_ejecucion ? 'ejecutada '.$orden->fecha_ejecucion->format('d/m/Y') : 'sin ejecutar' }}
                                    · {{ $orden->responsable?->name ?: ($orden->tecnico ?: 'Sin técnico asignado') }}
                                </p>

                                @if (filled($orden->descripcion) || filled($orden->motivo))
                                    <p class="mt-1.5 text-[13px] leading-relaxed text-carbon dark:text-zinc-200">
                                        {{ $orden->descripcion ?: $orden->motivo }}
                                    </p>
                                @endif

                                @if ($orden->presenta_novedad)
                                    <p class="mt-2 flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2 text-[12.5px] text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                                        <flux:icon name="exclamation-triangle" variant="micro" class="mt-0.5 size-3.5 shrink-0" />
                                        <span>{{ $orden->novedad ?: 'Se reportó una novedad sin detallar.' }}</span>
                                    </p>
                                @endif
                            </li>
                        @empty
                            <li class="text-[13px] text-zinc-500 dark:text-zinc-400">
                                A este equipo todavía no se le ha registrado ningún mantenimiento.
                            </li>
                        @endforelse
                    </ol>

                    @if ($totalHistorial > $topeHistorial)
                        <p class="mt-3 text-[12px] text-zinc-500 dark:text-zinc-400">
                            Se muestran las {{ $topeHistorial }} órdenes más recientes de {{ $totalHistorial }}.
                        </p>
                    @endif
                </section>
                @endif
            </div>

            {{-- Pie --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 bg-zinc-50/80 px-6 py-4 sm:px-8 dark:border-zinc-800 dark:bg-zinc-800/40">
                <p class="text-[12px] text-zinc-500 dark:text-zinc-400">Vista de sólo lectura.</p>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" class="eq-btn eq-btn-ghost" wire:click="cerrarDetalle">Cerrar</button>

                    @can('gestionar-equipos')
                        <button
                            type="button"
                            class="eq-btn eq-btn-ghost hover:!border-rose-200 hover:!bg-rose-50 hover:!text-rose-600 dark:hover:!border-rose-500/30 dark:hover:!bg-rose-500/10 dark:hover:!text-rose-400"
                            wire:click="confirmarEliminacion({{ $equipo->id }})"
                        >
                            <flux:icon name="trash" variant="mini" class="size-4" />
                            Eliminar
                        </button>

                        <button type="button" class="eq-btn eq-btn-primary" wire:click="editar({{ $equipo->id }})">
                            <flux:icon name="pencil-square" variant="mini" class="size-4" />
                            Editar equipo
                        </button>
                    @endcan
                </div>
            </div>
        @endif
    </div>
</div>
