{{-- Órdenes por mes, últimos doce, preventivos frente a correctivos.

     La gráfica es SVG generado en el servidor y no una librería: son doce
     puntos con dos series, sin animación ni degradados, y así no se descarga
     ni un kilobyte de JavaScript para dibujarlos. --}}

@php
    $serie = $this->ordenesPorMes;

    // Geometría del lienzo. El SVG escala con el contenedor; estas unidades son
    // sólo el sistema de coordenadas interno.
    $ancho = 470;
    $alto = 176;
    $margenIzq = 26;
    $margenDer = 8;
    $margenSup = 10;
    $margenInf = 24;

    $util = $ancho - $margenIzq - $margenDer;
    $utilAlto = $alto - $margenSup - $margenInf;

    $tope = max(1, collect($serie)->flatMap(fn ($mes) => [$mes['preventivo'], $mes['correctivo']])->max());
    // Se redondea hacia arriba a una decena para que el eje tenga marcas limpias.
    $tope = (int) (ceil($tope / 10) * 10) ?: 10;

    $x = fn (int $indice): float => round($margenIzq + $indice * ($util / max(1, count($serie) - 1)), 2);
    $y = fn (int $valor): float => round($margenSup + (1 - $valor / $tope) * $utilAlto, 2);

    $trazo = fn (string $clave): string => collect($serie)
        ->map(fn ($mes, $indice) => $x($indice).','.$y($mes[$clave]))
        ->implode(' ');

    $totalPreventivos = collect($serie)->sum('preventivo');
    $totalCorrectivos = collect($serie)->sum('correctivo');
@endphp

<div class="eq-panel px-5 py-4">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-[13px] font-bold tracking-wide text-carbon uppercase dark:text-zinc-200">
            Órdenes por mes
        </h2>

        <div class="flex items-center gap-4 text-[11.5px] text-zinc-500 dark:text-zinc-400">
            <span class="flex items-center gap-1.5">
                <span class="h-0.5 w-4 bg-signal"></span>
                <span class="tabular-nums">Preventivos ({{ $totalPreventivos }})</span>
            </span>
            <span class="flex items-center gap-1.5">
                <span class="h-0.5 w-4 bg-amber-500"></span>
                <span class="tabular-nums">Correctivos ({{ $totalCorrectivos }})</span>
            </span>
        </div>
    </div>

    <svg viewBox="0 0 {{ $ancho }} {{ $alto }}" class="h-auto w-full" role="img"
         aria-label="Órdenes preventivas y correctivas por mes durante los últimos doce meses">
        {{-- Marcas del eje vertical: tres líneas finas, sin retícula completa. --}}
        @foreach ([0, 0.5, 1] as $fraccion)
            @php
                $lineaY = round($margenSup + $fraccion * $utilAlto, 2);
                $valor = (int) round($tope * (1 - $fraccion));
            @endphp

            <line
                x1="{{ $margenIzq }}" y1="{{ $lineaY }}"
                x2="{{ $ancho - $margenDer }}" y2="{{ $lineaY }}"
                class="stroke-zinc-200 dark:stroke-zinc-800" stroke-width="1"
            />
            <text
                x="{{ $margenIzq - 6 }}" y="{{ $lineaY + 3 }}"
                text-anchor="end" font-size="9"
                class="fill-zinc-400 tabular-nums dark:fill-zinc-500"
            >{{ $valor }}</text>
        @endforeach

        {{-- Series. El preventivo va detrás porque es la línea de fondo del
             negocio; el correctivo, que es el que hay que ver bajar, va encima. --}}
        <polyline points="{{ $trazo('preventivo') }}" fill="none" stroke-width="1.75"
                  stroke-linejoin="round" stroke-linecap="round" class="stroke-signal" />
        <polyline points="{{ $trazo('correctivo') }}" fill="none" stroke-width="1.75"
                  stroke-linejoin="round" stroke-linecap="round" class="stroke-amber-500" />

        @foreach ($serie as $indice => $mes)
            <circle cx="{{ $x($indice) }}" cy="{{ $y($mes['preventivo']) }}" r="2" class="fill-signal" />
            <circle cx="{{ $x($indice) }}" cy="{{ $y($mes['correctivo']) }}" r="2" class="fill-amber-500" />

            {{-- Un mes de cada dos en el eje: doce rótulos no caben legibles. --}}
            @if ($indice % 2 === 1 || $indice === count($serie) - 1)
                <text
                    x="{{ $x($indice) }}" y="{{ $alto - 8 }}"
                    text-anchor="middle" font-size="9"
                    class="fill-zinc-400 dark:fill-zinc-500"
                >{{ $mes['etiqueta'] }}</text>
            @endif

            <title>{{ $mes['mes'] }}: {{ $mes['preventivo'] }} preventivos, {{ $mes['correctivo'] }} correctivos</title>
        @endforeach
    </svg>
</div>
