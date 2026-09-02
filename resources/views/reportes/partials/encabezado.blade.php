{{-- Banda de identificación del documento. Al imprimir se fija en el margen
     superior de la página, de modo que se repite en todas las hojas. --}}
<header class="rep-encabezado">
    <div class="rep-marca">
        @if ($logo)
            <img class="rep-marca__logo" src="{{ $logo }}" alt="{{ $prestador['nombre'] }}">
        @endif

        <div class="rep-marca__datos rep-quiebre">
            <p class="rep-marca__nombre">{{ $prestador['nombre'] }}</p>

            @if (filled($prestador['nit']))
                <p class="rep-marca__linea">NIT {{ $prestador['nit'] }}</p>
            @endif

            @if (filled($prestador['lema']))
                <p class="rep-marca__linea">{{ $prestador['lema'] }}</p>
            @endif

            @if (filled($contacto))
                <p class="rep-marca__linea">{{ $contacto }}</p>
            @endif
        </div>
    </div>

    <div class="rep-sello">
        <span class="rep-sello__tipo">Orden de trabajo · {{ $mantenimiento->tipoEtiqueta() }}</span>
        <p class="rep-sello__codigo">{{ $mantenimiento->codigo() }}</p>
        <p class="rep-sello__fecha">Ejecutado el {{ $fechaEjecucion }}</p>
    </div>
</header>
