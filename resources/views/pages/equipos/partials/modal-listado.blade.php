{{-- Listado de sólo lectura que abre cada tarjeta de indicadores. --}}
<div
    x-cloak
    x-show="$wire.listadoVisto !== ''"
    x-transition.opacity.duration.200ms
    x-on:keydown.escape.window="$wire.listadoVisto !== '' && $wire.cerrarListado()"
    class="fixed inset-0 z-50 overflow-y-auto bg-carbon-deep/70 p-3 backdrop-blur-sm sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="eq-listado-titulo"
>
    <div
        x-show="$wire.listadoVisto !== ''"
        x-transition:enter="transition duration-250 ease-out"
        x-transition:enter-start="opacity-0 translate-y-6 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        class="mx-auto my-2 w-full max-w-4xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-zinc-900 dark:ring-white/10"
    >
        @php $definicion = $listados[$listadoVisto] ?? null; @endphp

        @if ($definicion)
            {{-- Cabecera --}}
            <div class="relative overflow-hidden bg-gradient-to-br from-carbon to-carbon-deep px-6 py-5 sm:px-8">
                <div class="pointer-events-none absolute inset-0 opacity-[0.07]" style="background-image: repeating-linear-gradient(to right, #fff 0 1px, transparent 1px 22px), repeating-linear-gradient(to bottom, #fff 0 1px, transparent 1px 22px);"></div>

                <div class="relative flex items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-lima ring-1 ring-white/15">
                            <flux:icon name="{{ $definicion['icono'] }}" class="size-6" />
                        </span>

                        <div>
                            <h2 id="eq-listado-titulo" class="text-xl font-bold text-white">{{ $definicion['titulo'] }}</h2>
                            <p class="mt-1 text-[12.5px] text-zinc-400">{{ $definicion['descripcion'] }}</p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                        <span class="eq-chip bg-lima/15 text-lima">{{ $totalListado }} {{ $totalListado === 1 ? 'equipo' : 'equipos' }}</span>

                        <button type="button" class="eq-icon-btn !text-zinc-400 hover:!bg-white/10 hover:!text-white" wire:click="cerrarListado" title="Cerrar">
                            <flux:icon name="x-mark" variant="mini" class="size-5" />
                        </button>
                    </div>
                </div>
            </div>

            {{-- Cuerpo --}}
            <div class="max-h-[60vh] overflow-y-auto">
                @forelse ($equiposListados as $registro)
                    <div wire:key="listado-{{ $registro->id }}" class="flex items-center gap-4 border-b border-zinc-100 px-6 py-3 last:border-0 sm:px-8 dark:border-zinc-800">
                        <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-carbon to-carbon-deep text-[11px] font-bold text-white shadow-sm">
                            @if ($registro->fotoUrl())
                                <img src="{{ $registro->fotoUrl() }}" alt="{{ $registro->descripcion }}" class="size-full object-cover">
                            @else
                                {{ $registro->iniciales() }}
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13.5px] font-semibold text-carbon dark:text-zinc-100">{{ $registro->descripcion }}</p>
                            <p class="truncate text-[12px] text-zinc-500 dark:text-zinc-400">
                                {{ $registro->marca?->nombre ?? 'Sin marca' }} · {{ $registro->modelo?->nombre ?? 'Sin modelo' }}
                                @if ($registro->numero_serie)
                                    · <span class="font-mono">{{ $registro->numero_serie }}</span>
                                @endif
                            </p>
                        </div>

                        <div class="hidden min-w-0 sm:block sm:w-56">
                            <p class="truncate text-[12.5px] font-medium text-carbon dark:text-zinc-200">{{ $registro->empresa?->nombre ?? 'Sin asignar' }}</p>
                            <p class="truncate text-[11.5px] text-zinc-500 dark:text-zinc-400">{{ $registro->area?->nombre ?? 'Sin área' }}</p>
                        </div>

                        <span @class([
                            'eq-chip shrink-0',
                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400' => $registro->activo,
                            'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400' => ! $registro->activo,
                        ])>
                            <span @class(['size-1.5 rounded-full', 'bg-emerald-500' => $registro->activo, 'bg-rose-500' => ! $registro->activo])></span>
                            {{ $registro->activo ? 'Activo' : 'Fuera de servicio' }}
                        </span>
                    </div>
                @empty
                    <div class="flex flex-col items-center gap-3 px-6 py-16 text-center">
                        <span class="flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon name="cpu-chip" class="size-7 text-zinc-400" />
                        </span>
                        <p class="text-[15px] font-semibold text-carbon dark:text-zinc-200">No hay equipos en esta categoría</p>
                    </div>
                @endforelse
            </div>

            {{-- Pie --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 bg-zinc-50/80 px-6 py-4 sm:px-8 dark:border-zinc-800 dark:bg-zinc-800/40">
                <p class="text-[12px] text-zinc-500 dark:text-zinc-400">
                    @if ($totalListado > $topeListado)
                        Mostrando los primeros {{ $topeListado }} de {{ $totalListado }}. Use los filtros de la tabla para ver el resto.
                    @else
                        Vista de sólo lectura.
                    @endif
                </p>

                <button type="button" class="eq-btn eq-btn-ghost" wire:click="cerrarListado">Cerrar</button>
            </div>
        @endif
    </div>
</div>
