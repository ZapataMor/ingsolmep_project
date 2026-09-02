{{-- Bloque de firmas. Se mantiene entero: si no cabe en la página en curso
     pasa completo a la siguiente, nunca partido entre dos hojas.

     @param list<array{nombre: string|null, cargo: string|null, rol: string}> $firmas
--}}
<section class="rep-seccion">
    <h2 class="rep-seccion__titulo">
        <span class="rep-seccion__numero">{{ str_pad((string) $numero, 2, '0', STR_PAD_LEFT) }}</span>
        <span class="rep-seccion__nombre">Constancia y firmas</span>
    </h2>

    <div class="rep-firmas">
        @foreach ($firmas as $firma)
            <div class="rep-firma">
                <div class="rep-firma__espacio"></div>

                <div class="rep-firma__linea">
                    {{-- Sin nombre la línea queda en blanco para firmarse a mano. --}}
                    <p class="rep-firma__nombre rep-quiebre">{{ $firma['nombre'] }}</p>

                    @if (filled($firma['cargo']))
                        <p class="rep-firma__cargo rep-quiebre">{{ $firma['cargo'] }}</p>
                    @endif

                    <p class="rep-firma__rol">{{ $firma['rol'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>
