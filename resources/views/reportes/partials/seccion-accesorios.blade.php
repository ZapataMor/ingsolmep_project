{{-- Estado de los accesorios evaluados, en la tabla B/R/M del formato de la
     empresa. La cabecera se repite si la tabla pasa a la página siguiente.

     @param int                       $numero
     @param array<string, string>     $accesorios  Nombre del accesorio => B|R|M.
     @param array<string, string>     $estados     Clave del estado => etiqueta.
--}}
<section class="rep-seccion">
    <h2 class="rep-seccion__titulo">
        <span class="rep-seccion__numero">{{ str_pad((string) $numero, 2, '0', STR_PAD_LEFT) }}</span>
        <span class="rep-seccion__nombre">Estado de accesorios</span>
        <span class="rep-seccion__conteo">{{ count($accesorios) }} {{ count($accesorios) === 1 ? 'evaluado' : 'evaluados' }}</span>
    </h2>

    @if ($accesorios === [])
        <p class="rep-vacio">No se evaluaron accesorios en esta intervención.</p>
    @else
        <div class="rep-tabla-marco">
            <table class="rep-tabla">
                <thead>
                    <tr>
                        <th scope="col">Accesorio</th>
                        @foreach ($estados as $inicial => $etiqueta)
                            <th scope="col" class="rep-tabla__centro" title="{{ $etiqueta }}">{{ $inicial }} · {{ $etiqueta }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach ($accesorios as $nombre => $estado)
                        <tr>
                            <td class="rep-tabla__nombre rep-quiebre">{{ $nombre }}</td>
                            @foreach (array_keys($estados) as $inicial)
                                <td class="rep-tabla__centro">
                                    @if ($estado === $inicial)
                                        <span class="rep-tabla__marca">X</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
