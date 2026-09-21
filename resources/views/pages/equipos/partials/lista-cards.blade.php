{{-- Vista en tarjetas: pensada para reconocer el equipo de un vistazo. Muestra
     sólo lo que sirve para identificarlo y para saber si urge —equipo, marca y
     modelo, empresa, estado y proximidad del mantenimiento—; el resto de la
     hoja de vida está en la ficha que abre la propia tarjeta. --}}
<div wire:loading.delay.class="opacity-50" class="transition-opacity">
    @if ($this->equipos->isNotEmpty())
        <div class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->equipos as $equipo)
                <article
                    wire:key="equipo-card-{{ $equipo->id }}"
                    role="button"
                    tabindex="0"
                    wire:click="verEquipo({{ $equipo->id }})"
                    wire:keydown.enter="verEquipo({{ $equipo->id }})"
                    title="Ver la ficha de {{ $equipo->descripcion }}"
                    class="eq-card"
                >
                    {{-- Identidad del equipo --}}
                    <div class="flex items-start gap-3 border-b border-zinc-100 p-4 dark:border-zinc-800">
                        <span class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-carbon to-carbon-deep text-[13px] font-bold text-white shadow-sm">
                            @if ($equipo->fotoUrl())
                                <img src="{{ $equipo->fotoUrl() }}" alt="{{ $equipo->descripcion }}" class="size-full object-cover">
                            @else
                                {{ $equipo->iniciales() }}
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[14px] font-semibold text-carbon dark:text-zinc-100" title="{{ $equipo->descripcion }}">
                                {{ $equipo->descripcion }}
                            </p>
                            <p class="mt-0.5 truncate text-[12px] text-zinc-500 dark:text-zinc-400">
                                {{ $equipo->marca?->nombre ?? 'Sin marca' }} · {{ $equipo->modelo?->nombre ?? 'Sin modelo' }}
                            </p>
                        </div>
                    </div>

                    {{-- Empresa y proximidad del mantenimiento --}}
                    <div class="space-y-3 p-4 text-[13px]">
                        <div class="min-w-0">
                            <p class="eq-card-dato">Empresa</p>
                            <p class="truncate font-medium text-carbon dark:text-zinc-200" title="{{ $equipo->empresa?->nombre }}">
                                {{ $equipo->empresa?->nombre ?? 'Sin asignar' }}
                            </p>
                        </div>

                        <x-semaforo-mantenimiento :equipo="$equipo" />
                    </div>

                    {{-- Estado: el chip sigue siendo el interruptor de servicio --}}
                    <div class="mt-auto flex items-center justify-between gap-3 border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <button
                            type="button"
                            @can('gestionar-equipos')
                                wire:click.stop="alternarActivo({{ $equipo->id }})"
                                title="Cambiar estado"
                            @else
                                disabled
                            @endcan
                            @class([
                                'eq-chip transition duration-200',
                                'cursor-pointer hover:scale-110 hover:shadow-md' => auth()->user()->can('gestionar-equipos'),
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

                        <span class="inline-flex items-center gap-1 text-[12px] font-semibold text-zinc-400 dark:text-zinc-500">
                            Ver ficha
                            <flux:icon name="arrow-right" variant="micro" class="size-3.5" />
                        </span>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="px-4 py-16 text-center">
            @include('pages.equipos.partials.lista-vacia')
        </div>
    @endif
</div>
