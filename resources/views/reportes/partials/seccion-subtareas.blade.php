{{-- Rutina del preventivo. Se lista la plantilla completa con la casilla
     marcada o vacía: el reporte sirve entonces como constancia de lo que se
     hizo y de lo que no, no sólo como resumen de lo ejecutado.

     @param int                    $numero
     @param array<string, bool>    $subtareas  Clave de la subtarea => ejecutada.
     @param int                    $hechas
--}}
<section class="rep-seccion">
    <h2 class="rep-seccion__titulo">
        <span class="rep-seccion__numero">{{ str_pad((string) $numero, 2, '0', STR_PAD_LEFT) }}</span>
        <span class="rep-seccion__nombre">Rutina de subtareas</span>
        <span class="rep-seccion__conteo">{{ $hechas }} de {{ count($subtareas) }} ejecutadas</span>
    </h2>

    @if ($subtareas === [])
        <p class="rep-vacio">Esta orden no tiene una rutina de subtareas definida.</p>
    @else
        <div class="rep-lista">
            @foreach ($subtareas as $etiqueta => $ejecutada)
                <div @class(['rep-item', 'rep-item--hecho' => $ejecutada])>
                    <span class="rep-item__casilla" aria-hidden="true">✓</span>
                    <span class="rep-item__nombre rep-quiebre">{{ $etiqueta }}</span>
                </div>
            @endforeach
        </div>
    @endif
</section>
