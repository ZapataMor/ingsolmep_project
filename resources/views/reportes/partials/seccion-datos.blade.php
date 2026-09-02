{{-- Rejilla de pares etiqueta/valor. Las columnas se reparten solas según el
     ancho disponible y cada dato conserva su etiqueta encima, así que un valor
     largo crece hacia abajo sin descuadrar a los demás.

     @param int                          $numero  Orden de la sección.
     @param string                       $titulo
     @param array<string, string|null>   $datos   Etiqueta => valor.
     @param list<string>                 $anchos  Etiquetas que ocupan la fila completa.
--}}
@php
    $anchos ??= [];
@endphp

<section class="rep-seccion">
    <h2 class="rep-seccion__titulo">
        <span class="rep-seccion__numero">{{ str_pad((string) $numero, 2, '0', STR_PAD_LEFT) }}</span>
        <span class="rep-seccion__nombre">{{ $titulo }}</span>
    </h2>

    <dl class="rep-datos">
        @foreach ($datos as $etiqueta => $valor)
            <div @class(['rep-dato', 'rep-dato--ancho' => in_array($etiqueta, $anchos, true)])>
                <dt class="rep-dato__k rep-quiebre">{{ $etiqueta }}</dt>
                <dd @class(['rep-dato__v', 'rep-quiebre', 'rep-dato__v--vacio' => blank($valor)])>
                    {{ filled($valor) ? $valor : 'No registrado' }}
                </dd>
            </div>
        @endforeach
    </dl>
</section>
