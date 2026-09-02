{{-- Ficha de sólo lectura de la orden de mantenimiento seleccionada. --}}
{{-- La raíz del teletransporte existe siempre: Livewire descarta un
     `@teleport` cuyo cuerpo nace vacío y ya no lo vuelve a poblar. --}}
<div class="contents">
    @if ($mantenimiento)
        @php
            $equipoOrden = $mantenimiento->equipo;

            $programacion = [
                'Tipo' => $mantenimiento->tipoEtiqueta(),
                'Fecha programada' => $mantenimiento->fecha_programada->format('d/m/Y'),
                'Fecha de ejecución' => $mantenimiento->fecha_ejecucion?->format('d/m/Y'),
                'Técnico responsable' => $mantenimiento->tecnico,
                'Prioridad' => $mantenimiento->prioridad,
                'Costo del servicio' => $mantenimiento->costo !== null ? '$ '.number_format((float) $mantenimiento->costo, 2, ',', '.') : null,
            ];

            $datosEquipo = [
                'Equipo' => $equipoOrden?->descripcion,
                'Marca y modelo' => trim(($equipoOrden?->marca?->nombre ?? '').' '.($equipoOrden?->modelo?->nombre ?? '')),
                'Número de serie' => $equipoOrden?->numero_serie,
                'Empresa' => $mantenimiento->empresa?->nombre,
                'Área o servicio' => $equipoOrden?->area?->nombre,
                'Último mantenimiento del equipo' => $equipoOrden?->ultimo_mantenimiento?->format('d/m/Y'),
            ];

            $subtareasMarcadas = collect($mantenimiento->subtareas ?? [])
                ->filter()
                ->keys()
                ->map(fn (string $clave): string => \App\Models\Equipo::SUBTAREAS[$clave] ?? $clave);

            $accesoriosEvaluados = collect($mantenimiento->accesorios_estado ?? [])
                ->filter(fn ($estado): bool => (string) $estado !== '');
        @endphp

        <div
            x-data
            x-on:keydown.escape.window="$wire.cerrarDetalle()"
            x-on:click.self="$wire.cerrarDetalle()"
            class="eq-modal fixed inset-0 z-50 overflow-y-auto bg-carbon-deep/70 p-3 backdrop-blur-sm sm:p-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="mt-detalle-titulo"
        >
            <div class="eq-modal-panel mx-auto my-2 w-full max-w-3xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-zinc-900 dark:ring-white/10">
                {{-- Cabecera --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-carbon to-carbon-deep px-6 py-5 sm:px-8">
                    <div class="pointer-events-none absolute inset-0 opacity-[0.07]" style="background-image: repeating-linear-gradient(to right, #fff 0 1px, transparent 1px 22px), repeating-linear-gradient(to bottom, #fff 0 1px, transparent 1px 22px);"></div>

                    <div class="relative flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-4">
                            <span class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white text-sm font-bold text-carbon ring-1 ring-white/15">
                                @if ($equipoOrden?->fotoUrl())
                                    <img src="{{ $equipoOrden->fotoUrl() }}" alt="Foto de {{ $equipoOrden->descripcion }}" class="size-full object-cover">
                                @else
                                    {{ $equipoOrden?->iniciales() ?? '—' }}
                                @endif
                            </span>

                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold tracking-[0.14em] text-lima uppercase">
                                    Orden {{ $mantenimiento->codigo() }} · {{ $mantenimiento->tipoEtiqueta() }}
                                </p>
                                <h2 id="mt-detalle-titulo" class="mt-1 truncate text-xl font-bold text-white">
                                    {{ $equipoOrden?->descripcion ?? 'Equipo retirado del inventario' }}
                                </h2>
                                <p class="mt-1 text-[12.5px] text-zinc-400">
                                    {{ $mantenimiento->empresa?->nombre ?? 'Sin empresa' }} ·
                                    Programado {{ $mantenimiento->fecha_programada->format('d/m/Y') }}
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-3">
                            <span class="eq-chip {{ $coloresEstado[$mantenimiento->estado] ?? '' }}">
                                {{ $mantenimiento->estadoEtiqueta() }}
                            </span>

                            <button type="button" class="eq-icon-btn !text-zinc-400 hover:!bg-white/10 hover:!text-white" wire:click="cerrarDetalle" title="Cerrar">
                                <flux:icon name="x-mark" variant="mini" class="size-5" />
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Cuerpo --}}
                <div class="max-h-[60vh] space-y-6 overflow-y-auto px-6 py-6 sm:px-8">
                    @if ($mantenimiento->estaVencido())
                        @php $atraso = abs($mantenimiento->diasRestantes()); @endphp
                        <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-[13px] text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300" role="alert">
                            <flux:icon name="bell-alert" variant="mini" class="mt-0.5 size-4 shrink-0" />
                            <p>
                                Esta orden está vencida: la fecha programada pasó hace
                                <strong>{{ $atraso }} {{ $atraso === 1 ? 'día' : 'días' }}</strong> y todavía sigue {{ mb_strtolower($mantenimiento->estadoEtiqueta()) }}.
                            </p>
                        </div>
                    @endif

                    <section>
                        <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="calendar-days" class="size-4" /> Programación
                        </p>

                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                            @foreach ($programacion as $etiqueta => $valor)
                                <div>
                                    <dt class="text-[11px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $etiqueta }}</dt>
                                    <dd class="mt-0.5 text-[13.5px] font-medium text-carbon dark:text-zinc-100">{{ filled($valor) ? $valor : '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>

                    <section>
                        <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="cpu-chip" class="size-4" /> Equipo intervenido
                        </p>

                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                            @foreach ($datosEquipo as $etiqueta => $valor)
                                <div>
                                    <dt class="text-[11px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $etiqueta }}</dt>
                                    <dd class="mt-0.5 text-[13.5px] font-medium text-carbon dark:text-zinc-100">{{ filled($valor) ? $valor : '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>

                    <section>
                        <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="document-check" class="size-4" /> Detalle del trabajo
                        </p>

                        @php
                            $textos = [
                                ($mantenimiento->tipo === 'correctivo' ? 'Falla reportada' : 'Motivo de la programación') => $mantenimiento->motivo,
                                'Trabajo a realizar o ejecutado' => $mantenimiento->descripcion,
                                'Repuestos utilizados' => $mantenimiento->repuestos,
                                'Observaciones' => $mantenimiento->observaciones,
                            ];
                        @endphp

                        <div class="space-y-3">
                            @foreach ($textos as $etiqueta => $texto)
                                <div>
                                    <p class="text-[11px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $etiqueta }}</p>
                                    <p class="mt-0.5 text-[13.5px] leading-relaxed whitespace-pre-line text-carbon dark:text-zinc-100">
                                        {{ filled($texto) ? $texto : '—' }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    @if ($mantenimiento->tipo === 'preventivo')
                        <section>
                            <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                                <flux:icon name="wrench-screwdriver" class="size-4" />
                                Rutina de subtareas
                                <span class="text-zinc-400 normal-case">({{ $subtareasMarcadas->count() }} de {{ count(\App\Models\Equipo::SUBTAREAS) }})</span>
                            </p>

                            <div class="flex flex-wrap gap-2">
                                @forelse ($subtareasMarcadas as $subtarea)
                                    <span class="eq-chip bg-lima-soft text-lima-700 dark:bg-lima/10 dark:text-lima">
                                        <flux:icon name="check" variant="micro" class="size-3" />
                                        {{ $subtarea }}
                                    </span>
                                @empty
                                    <span class="text-[13px] text-zinc-500 dark:text-zinc-400">Esta orden no tiene subtareas marcadas.</span>
                                @endforelse
                            </div>
                        </section>
                    @endif

                    <section>
                        <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="squares-2x2" class="size-4" /> Estado de los accesorios
                        </p>

                        <div class="flex flex-wrap gap-2">
                            @forelse ($accesoriosEvaluados as $clave => $estadoAccesorio)
                                <span @class([
                                    'eq-chip',
                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400' => $estadoAccesorio === 'B',
                                    'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400' => $estadoAccesorio === 'R',
                                    'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400' => $estadoAccesorio === 'M',
                                ])>
                                    {{ \App\Models\Equipo::ACCESORIOS[$clave] ?? $clave }}
                                    <span class="font-bold">· {{ \App\Models\Equipo::ESTADOS_ACCESORIO[$estadoAccesorio] ?? $estadoAccesorio }}</span>
                                </span>
                            @empty
                                <span class="text-[13px] text-zinc-500 dark:text-zinc-400">No se evaluaron accesorios en esta orden.</span>
                            @endforelse
                        </div>
                    </section>
                </div>

                {{-- Pie --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 bg-zinc-50/80 px-6 py-4 sm:px-8 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <p class="text-[12px] text-zinc-500 dark:text-zinc-400">Vista de sólo lectura.</p>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="eq-btn eq-btn-ghost" wire:click="cerrarDetalle">Cerrar</button>

                        <button
                            type="button"
                            class="eq-btn eq-btn-ghost hover:!border-rose-200 hover:!bg-rose-50 hover:!text-rose-600 dark:hover:!border-rose-500/30 dark:hover:!bg-rose-500/10 dark:hover:!text-rose-400"
                            wire:click="confirmarEliminacion({{ $mantenimiento->id }})"
                        >
                            <flux:icon name="trash" variant="mini" class="size-4" />
                            Eliminar
                        </button>

                        @if ($mantenimiento->estaAbierto())
                            <button type="button" class="eq-btn eq-btn-ghost" wire:click="marcarEjecutado({{ $mantenimiento->id }})">
                                <flux:icon name="check-circle" variant="mini" class="size-4 text-emerald-500" />
                                Marcar ejecutado
                            </button>
                        @endif

                        {{-- El reporte certifica un trabajo hecho: sólo existe con la orden ejecutada. --}}
                        @if ($mantenimiento->estado === 'ejecutado')
                            <a
                                href="{{ route('mantenimientos.reporte', $mantenimiento) }}"
                                target="_blank"
                                rel="noopener"
                                class="eq-btn eq-btn-accent"
                                title="Abrir el reporte de {{ $mantenimiento->codigo() }} en una pestaña nueva"
                            >
                                <flux:icon name="document-arrow-down" variant="mini" class="size-4" />
                                Generar reporte
                            </a>
                        @endif

                        <button type="button" class="eq-btn eq-btn-primary" wire:click="editar({{ $mantenimiento->id }})">
                            <flux:icon name="pencil-square" variant="mini" class="size-4" />
                            Editar orden
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
