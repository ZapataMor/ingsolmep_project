{{-- Equipos con más correctivos en los últimos doce meses.

     No es un listado de mantenimiento: es una lista de candidatos a reposición,
     la recomendación que INGSOLMEP le puede llevar a su cliente. --}}

@php
    $candidatos = $this->equiposConMasCorrectivos;
@endphp

<div class="eq-panel overflow-hidden">
    <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-zinc-200 px-5 py-3 dark:border-zinc-800">
        <h2 class="text-[13px] font-bold tracking-wide text-carbon uppercase dark:text-zinc-200">
            Equipos con más correctivos
        </h2>
        <p class="text-[11.5px] text-zinc-500 dark:text-zinc-400">
            Últimos 12 meses · candidatos a reposición
        </p>
    </div>

    @if ($candidatos->isEmpty())
        <p class="px-5 py-6 text-center text-[13px] text-zinc-500 dark:text-zinc-400">
            Ningún equipo ha necesitado correctivos en el último año.
        </p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-[13px]">
                <thead class="border-b border-zinc-200 dark:border-zinc-800">
                    <tr class="text-[10.5px] font-bold tracking-wide text-zinc-400 uppercase dark:text-zinc-500">
                        <th scope="col" class="px-5 py-2 font-bold">Equipo</th>
                        <th scope="col" class="px-5 py-2 font-bold">Institución</th>
                        <th scope="col" class="px-5 py-2 font-bold">Área</th>
                        <th scope="col" class="px-5 py-2 text-right font-bold">Correctivos</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($candidatos as $equipo)
                        <tr class="border-b border-zinc-100 last:border-b-0 hover:bg-zinc-50 dark:border-zinc-800/70 dark:hover:bg-zinc-800/50">
                            <td class="max-w-56 px-5 py-2">
                                <a
                                    href="{{ route('mantenimientos.index', ['q' => $equipo->numero_serie ?: $equipo->descripcion, 'tipo' => 'correctivo']) }}"
                                    wire:navigate
                                    class="block truncate font-medium text-carbon hover:underline dark:text-zinc-100"
                                >{{ $equipo->descripcion }}</a>

                                @if ($equipo->numero_serie)
                                    <span class="block truncate text-[11px] tabular-nums text-zinc-400 dark:text-zinc-500">
                                        {{ $equipo->numero_serie }}
                                    </span>
                                @endif
                            </td>

                            <td class="max-w-48 truncate px-5 py-2 text-zinc-600 dark:text-zinc-300">
                                {{ $equipo->empresa ?? '—' }}
                            </td>

                            <td class="max-w-40 truncate px-5 py-2 text-zinc-600 dark:text-zinc-300">
                                {{ $equipo->area ?? '—' }}
                            </td>

                            <td class="px-5 py-2 text-right text-[14px] font-bold tabular-nums text-carbon dark:text-white">
                                {{ $equipo->correctivos }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
