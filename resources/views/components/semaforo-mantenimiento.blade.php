{{-- Barra de proximidad al próximo mantenimiento del equipo.

     Dice de un vistazo lo que la fecha sola no dice: cuánto del intervalo ya se
     consumió. El color es el aviso —verde queda tiempo, amarillo se acerca,
     rojo ya no admite espera— y el ancho, la distancia recorrida. --}}
@props([
    'equipo',
    // La ficha ya tiene sus propios rótulos; la tabla, su cabecera.
    'titulo' => true,
    // Añade de dónde sale la fecha: una orden abierta o la rutina estimada.
    'detalle' => false,
])

@php
    $semaforo = $equipo->semaforoMantenimiento();

    $colores = [
        \App\Support\SemaforoMantenimiento::VERDE => ['barra' => 'bg-emerald-500', 'texto' => 'text-emerald-600 dark:text-emerald-400'],
        \App\Support\SemaforoMantenimiento::AMARILLO => ['barra' => 'bg-amber-500', 'texto' => 'text-amber-600 dark:text-amber-400'],
        \App\Support\SemaforoMantenimiento::ROJO => ['barra' => 'bg-rose-500', 'texto' => 'text-rose-600 dark:text-rose-400'],
    ];

    $color = $semaforo ? $colores[$semaforo->nivel()] : ['barra' => 'bg-zinc-300 dark:bg-zinc-600', 'texto' => 'text-zinc-500 dark:text-zinc-400'];
    $porcentaje = $semaforo?->porcentaje ?? 0;
    $etiqueta = $semaforo?->etiqueta() ?? 'Sin programar';
@endphp

<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <div class="flex items-baseline justify-between gap-2">
        @if ($titulo)
            <p class="eq-card-dato !mb-0">Próximo mantenimiento</p>
        @endif

        <span class="truncate text-[11.5px] font-bold {{ $color['texto'] }}">{{ $etiqueta }}</span>
    </div>

    <div
        class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"
        role="progressbar"
        aria-valuenow="{{ $porcentaje }}"
        aria-valuemin="0"
        aria-valuemax="100"
        aria-label="Avance hacia el próximo mantenimiento de {{ $equipo->descripcion }}: {{ $etiqueta }}"
    >
        <div class="h-full rounded-full {{ $color['barra'] }} transition-[width] duration-500" style="width: {{ $semaforo ? max($porcentaje, 3) : 0 }}%"></div>
    </div>

    <p class="mt-1 truncate text-[11px] text-zinc-500 dark:text-zinc-400">
        @if ($semaforo)
            {{ $semaforo->fecha->format('d/m/Y') }}@if ($detalle) · {{ $semaforo->origen() }}@elseif (! $semaforo->programado()) · estimado @endif
        @else
            Sin órdenes abiertas ni mantenimientos previos.
        @endif
    </p>
</div>
