{{-- Bloques de redacción libre (falla reportada, trabajo ejecutado, repuestos).
     Cada bloque respeta los saltos de línea escritos por el técnico y se
     reparte entre páginas si el texto es extenso, sin recortarse.

     @param int                        $numero
     @param string                     $titulo
     @param array<string, string|null> $textos  Etiqueta => texto.
--}}
<section class="rep-seccion">
    <h2 class="rep-seccion__titulo">
        <span class="rep-seccion__numero">{{ str_pad((string) $numero, 2, '0', STR_PAD_LEFT) }}</span>
        <span class="rep-seccion__nombre">{{ $titulo }}</span>
    </h2>

    <div class="rep-textos">
        @foreach ($textos as $etiqueta => $texto)
            <div>
                <p class="rep-texto__k rep-quiebre">{{ $etiqueta }}</p>
                <div @class(['rep-texto__v', 'rep-quiebre', 'rep-texto__v--vacio' => blank($texto)])>{{ filled($texto) ? $texto : 'Sin información registrada para este apartado.' }}</div>
            </div>
        @endforeach
    </div>
</section>
