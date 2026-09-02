{{-- Cumplimiento del mes por institución, de menor a mayor.

     La que tiene problemas queda siempre arriba. Barras en HTML y no en SVG:
     necesitan rótulo, cifra y enlace propios en cada fila, y el navegador ya
     sabe maquetar eso mejor que un lienzo. --}}

@php
    $instituciones = $this->cumplimientoPorInstitucion;
    $peores = array_slice($instituciones, 0, 8);
    $meta = $this->meta;
@endphp

<div class="eq-panel px-5 py-4">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-[13px] font-bold tracking-wide text-carbon uppercase dark:text-zinc-200">
            Cumplimiento por institución
        </h2>

        @if (count($instituciones) > count($peores))
            <a href="{{ route('empresas.index') }}" wire:navigate class="eq-enlace">
                Ver las {{ count($instituciones) }}
            </a>
        @endif
    </div>

    @if ($peores === [])
        <p class="py-8 text-center text-[13px] text-zinc-500 dark:text-zinc-400">
            Ninguna institución tiene órdenes programadas este mes.
        </p>
    @else
        <div class="space-y-2">
            @foreach ($peores as $institucion)
                <a
                    href="{{ route('mantenimientos.index', ['empresa' => $institucion['id'], 'bandeja' => 'mes']) }}"
                    wire:navigate
                    class="group block"
                >
                    <div class="mb-1 flex items-baseline justify-between gap-3">
                        <span class="min-w-0 truncate text-[12.5px] text-carbon group-hover:underline dark:text-zinc-200">
                            {{ $institucion['nombre'] }}
                        </span>
                        <span class="shrink-0 text-[12px] tabular-nums text-zinc-500 dark:text-zinc-400">
                            {{ $institucion['ejecutadas'] }}/{{ $institucion['programadas'] }}
                            · <span class="font-semibold text-carbon dark:text-zinc-200">{{ $institucion['porcentaje'] }} %</span>
                        </span>
                    </div>

                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div
                            @class([
                                'h-full rounded-full',
                                'bg-emerald-500' => $institucion['porcentaje'] >= $meta,
                                'bg-amber-500' => $institucion['porcentaje'] < $meta && $institucion['porcentaje'] >= $meta - 15,
                                'bg-rose-500' => $institucion['porcentaje'] < $meta - 15,
                            ])
                            style="width: {{ max(1, $institucion['porcentaje']) }}%"
                        ></div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
