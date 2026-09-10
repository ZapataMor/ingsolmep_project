{{-- Vista en tabla: densa y ordenable, para comparar el inventario. Lleva los
     mismos datos que la tarjeta —equipo, marca y modelo, empresa, estado y
     proximidad del mantenimiento—; la hoja de vida completa está en la ficha
     que abre cada fila. --}}
<div wire:loading.delay.class="opacity-50" class="overflow-x-auto transition-opacity">
    <table class="w-full min-w-3xl text-left text-[13px]">
        <thead class="bg-zinc-50/80 text-[11px] font-bold tracking-wide text-zinc-500 uppercase dark:bg-zinc-800/60 dark:text-zinc-400">
            <tr>
                @php
                    $columnas = [
                        ['clave' => 'descripcion', 'titulo' => 'Equipo', 'ordenable' => true],
                        ['clave' => null, 'titulo' => 'Marca / Modelo', 'ordenable' => false],
                        ['clave' => null, 'titulo' => 'Empresa', 'ordenable' => false],
                        ['clave' => null, 'titulo' => 'Próximo mantenimiento', 'ordenable' => false],
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
            </tr>
        </thead>

        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($this->equipos as $equipo)
                <tr
                    wire:key="equipo-{{ $equipo->id }}"
                    tabindex="0"
                    wire:click="verEquipo({{ $equipo->id }})"
                    wire:keydown.enter="verEquipo({{ $equipo->id }})"
                    title="Ver la ficha de {{ $equipo->descripcion }}"
                    class="cursor-pointer transition duration-150 outline-none hover:bg-lima-soft/40 focus-visible:bg-lima-soft/60 dark:hover:bg-zinc-800/50 dark:focus-visible:bg-zinc-800/70"
                >
                    <td class="px-4 py-3 align-top">
                        <div class="flex items-center gap-3">
                            <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-carbon to-carbon-deep text-[11px] font-bold text-white shadow-sm">
                                @if ($equipo->fotoUrl())
                                    <img src="{{ $equipo->fotoUrl() }}" alt="{{ $equipo->descripcion }}" class="size-full object-cover">
                                @else
                                    {{ $equipo->iniciales() }}
                                @endif
                            </span>

                            <p class="min-w-0 font-semibold text-carbon dark:text-zinc-100">{{ $equipo->descripcion }}</p>
                        </div>
                    </td>

                    <td class="px-4 py-3 align-top">
                        <p class="font-medium text-carbon dark:text-zinc-200">{{ $equipo->marca?->nombre ?? '—' }}</p>
                        <p class="text-[12px] text-zinc-500 dark:text-zinc-400">{{ $equipo->modelo?->nombre ?? '—' }}</p>
                    </td>

                    <td class="px-4 py-3 align-top">
                        <p class="font-medium text-carbon dark:text-zinc-200">{{ $equipo->empresa?->nombre ?? 'Sin asignar' }}</p>
                    </td>

                    <td class="px-4 py-3 align-top">
                        <x-semaforo-mantenimiento :equipo="$equipo" :titulo="false" class="w-44" />
                    </td>

                    <td class="px-4 py-3 align-top">
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
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-16 text-center">
                        @include('pages.equipos.partials.lista-vacia')
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
