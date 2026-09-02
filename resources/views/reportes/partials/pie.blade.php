{{-- Pie del documento. Al imprimir se fija en el margen inferior y acompaña a
     cada página con el código de la orden, para que ninguna hoja suelta quede
     sin identificar. --}}
<footer class="rep-pie">
    <p class="rep-quiebre">
        <span class="rep-pie__sello">{{ $prestador['nombre'] }}</span>
        @if (filled($prestador['nit'])) · NIT {{ $prestador['nit'] }} @endif
        · Orden {{ $mantenimiento->codigo() }}
    </p>

    <p class="rep-quiebre">Documento generado el {{ $generadoEn }} · Firmado digitalmente</p>
</footer>
