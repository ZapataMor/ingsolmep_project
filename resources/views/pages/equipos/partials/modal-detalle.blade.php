{{-- Ficha de sólo lectura del equipo seleccionado en la tabla. --}}
<div
    x-cloak
    x-show="$wire.equipoVisto !== null"
    x-transition.opacity.duration.200ms
    x-effect="document.body.style.overflow = ($wire.equipoVisto !== null || $wire.mostrarFormulario || $wire.listadoVisto !== '') ? 'hidden' : ''"
    x-on:keydown.escape.window="$wire.equipoVisto !== null && $wire.cerrarDetalle()"
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
                    'Prioridad' => $equipo->prioridad,
                    'Fecha de registro' => $equipo->created_at?->format('d/m/Y'),
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

            {{-- Cuerpo --}}
            <div class="max-h-[60vh] space-y-6 overflow-y-auto px-6 py-6 sm:px-8">
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
            </div>

            {{-- Pie --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 bg-zinc-50/80 px-6 py-4 sm:px-8 dark:border-zinc-800 dark:bg-zinc-800/40">
                <p class="text-[12px] text-zinc-500 dark:text-zinc-400">Vista de sólo lectura.</p>

                <div class="flex items-center gap-2">
                    <button type="button" class="eq-btn eq-btn-ghost" wire:click="cerrarDetalle">Cerrar</button>
                    <button type="button" class="eq-btn eq-btn-primary" wire:click="editar({{ $equipo->id }})">
                        <flux:icon name="pencil-square" variant="mini" class="size-4" />
                        Editar equipo
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
