{{-- Listado de sólo lectura que abre cada tarjeta de indicadores. --}}
{{-- La raíz del teletransporte existe siempre: Livewire descarta un
     `@teleport` cuyo cuerpo nace vacío y ya no lo vuelve a poblar. --}}
<div class="contents">
    @php $definicion = $listados[$listadoVisto] ?? null; @endphp

    @if ($definicion)
        <div
            x-data
            x-on:keydown.escape.window="$wire.cerrarListado()"
            x-on:click.self="$wire.cerrarListado()"
            class="eq-modal fixed inset-0 z-50 overflow-y-auto bg-carbon-deep/70 p-3 backdrop-blur-sm sm:p-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="mt-listado-titulo"
        >
            <div class="eq-modal-panel mx-auto my-2 w-full max-w-3xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-zinc-900 dark:ring-white/10">
                {{-- Cabecera --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-carbon to-carbon-deep px-6 py-5 sm:px-8">
                    <div class="pointer-events-none absolute inset-0 opacity-[0.07]" style="background-image: repeating-linear-gradient(to right, #fff 0 1px, transparent 1px 22px), repeating-linear-gradient(to bottom, #fff 0 1px, transparent 1px 22px);"></div>

                    <div class="relative flex items-start justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-lima ring-1 ring-white/15">
                                <flux:icon name="{{ $definicion['icono'] }}" class="size-6" />
                            </span>

                            <div>
                                <h2 id="mt-listado-titulo" class="text-xl font-bold text-white">{{ $definicion['titulo'] }}</h2>
                                <p class="mt-1 text-[12.5px] text-zinc-400">{{ $definicion['descripcion'] }}</p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-3">
                            <span class="eq-chip bg-lima/15 text-lima">{{ $totalListado }} {{ $totalListado === 1 ? 'orden' : 'órdenes' }}</span>

                            <button type="button" class="eq-icon-btn !text-zinc-400 hover:!bg-white/10 hover:!text-white" wire:click="cerrarListado" title="Cerrar">
                                <flux:icon name="x-mark" variant="mini" class="size-5" />
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Cuerpo --}}
                <div class="max-h-[60vh] overflow-y-auto">
                    @forelse ($mantenimientosListados as $registro)
                        <button
                            type="button"
                            wire:key="listado-{{ $registro->id }}"
                            wire:click="verMantenimiento({{ $registro->id }})"
                            title="Ver la orden {{ $registro->codigo() }}"
                            class="flex w-full cursor-pointer items-center gap-4 border-b border-zinc-100 px-6 py-3 text-left transition last:border-0 hover:bg-lima-soft/40 sm:px-8 dark:border-zinc-800 dark:hover:bg-zinc-800/50"
                        >
                            <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-zinc-200 bg-white text-[11px] font-bold text-carbon shadow-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                @if ($registro->equipo?->fotoUrl())
                                    <img src="{{ $registro->equipo->fotoUrl() }}" alt="Foto de {{ $registro->equipo->descripcion }}" class="size-full object-cover">
                                @else
                                    {{ $registro->equipo?->iniciales() ?? '—' }}
                                @endif
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13.5px] font-semibold text-carbon dark:text-zinc-100">
                                    {{ $registro->equipo?->descripcion ?? 'Equipo retirado' }}
                                </p>
                                <p class="truncate text-[12px] text-zinc-500 dark:text-zinc-400">
                                    {{ $registro->codigo() }} · {{ $registro->empresa?->nombre ?? 'Sin empresa' }}
                                </p>
                            </div>

                            <div class="hidden min-w-0 sm:block sm:w-40">
                                <p class="truncate text-[12.5px] font-medium text-carbon dark:text-zinc-200">
                                    {{ $registro->fecha_programada->format('d/m/Y') }}
                                </p>
                                <p class="truncate text-[11.5px] text-zinc-500 dark:text-zinc-400">
                                    {{ $registro->tecnico ?: 'Sin técnico asignado' }}
                                </p>
                            </div>

                            <span class="eq-chip shrink-0 {{ $coloresTipo[$registro->tipo] ?? '' }}">{{ $registro->tipoEtiqueta() }}</span>

                            <span class="eq-chip shrink-0 {{ $coloresEstado[$registro->estado] ?? '' }}">{{ $registro->estadoEtiqueta() }}</span>
                        </button>
                    @empty
                        <div class="flex flex-col items-center gap-3 px-6 py-16 text-center">
                            <span class="flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                                <flux:icon name="wrench-screwdriver" class="size-7 text-zinc-400" />
                            </span>
                            <p class="text-[15px] font-semibold text-carbon dark:text-zinc-200">No hay mantenimientos en esta categoría</p>
                        </div>
                    @endforelse
                </div>

                {{-- Pie --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 bg-zinc-50/80 px-6 py-4 sm:px-8 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <p class="text-[12px] text-zinc-500 dark:text-zinc-400">
                        @if ($totalListado > $topeListado)
                            Mostrando las primeras {{ $topeListado }} de {{ $totalListado }}. Use los filtros de la tabla para ver el resto.
                        @else
                            Pulse una orden para abrir su ficha.
                        @endif
                    </p>

                    <button type="button" class="eq-btn eq-btn-ghost" wire:click="cerrarListado">Cerrar</button>
                </div>
            </div>
        </div>
    @endif
</div>
