@php
    use App\Models\Equipo;

    $equipo = $mantenimiento->equipo;
    $empresa = $mantenimiento->empresa ?? $equipo?->empresa;
    $esCorrectivo = $mantenimiento->tipo === 'correctivo';

    $prestador = config('reportes.prestador');
    $firmante = config('reportes.firmante');

    $logo = filled($prestador['logo']) && file_exists(public_path($prestador['logo']))
        ? asset($prestador['logo'])
        : null;

    $contactoPrestador = collect([$prestador['ciudad'], $prestador['telefono'], $prestador['email']])
        ->filter()
        ->implode(' · ');

    $fechaEjecucion = $mantenimiento->fecha_ejecucion?->format('d/m/Y') ?? '—';
    $generadoEn = now()->format('d/m/Y \a \l\a\s H:i');

    $dinero = static fn ($valor): ?string => $valor === null
        ? null
        : '$ '.number_format((float) $valor, 2, ',', '.');

    // La plantilla del equipo manda: la rutina se muestra completa, marcando
    // qué se ejecutó, y no sólo el listado de lo hecho.
    $subtareasOrden = $mantenimiento->subtareas ?? [];

    $subtareas = collect(Equipo::SUBTAREAS)
        ->filter(fn (string $etiqueta, string $clave): bool => array_key_exists($clave, $subtareasOrden))
        ->mapWithKeys(fn (string $etiqueta, string $clave): array => [
            $etiqueta => (bool) $subtareasOrden[$clave],
        ])
        ->all();

    $subtareasHechas = count(array_filter($subtareas));

    $accesorios = collect($mantenimiento->accesorios_estado ?? [])
        ->filter(fn ($estado): bool => (string) $estado !== '')
        ->mapWithKeys(fn (string $estado, string $clave): array => [
            Equipo::ACCESORIOS[$clave] ?? $clave => $estado,
        ])
        ->all();

    $seccion = 0;
@endphp

<!DOCTYPE html>
<html lang="es" data-tipo="{{ $mantenimiento->tipo }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $mantenimiento->codigo() }} · Reporte de mantenimiento {{ mb_strtolower($mantenimiento->tipoEtiqueta()) }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    @include('reportes.estilos')
</head>

<body>
    {{-- Barra de trabajo: no se imprime, sólo acompaña la vista en pantalla. --}}
    <div class="rep-barra">
        <div class="rep-barra__info">
            <span class="rep-barra__codigo">{{ $mantenimiento->codigo() }}</span>
            <span class="rep-barra__ayuda">
                En el diálogo de impresión elija «Guardar como PDF», active «Gráficos de fondo»
                y desactive «Encabezados y pies de página».
            </span>
        </div>

        <div class="rep-barra__acciones">
            <a class="rep-boton rep-boton--fantasma" href="{{ route('mantenimientos.index') }}">Volver a mantenimientos</a>
            <button type="button" class="rep-boton rep-boton--principal" onclick="window.print()">Imprimir o guardar en PDF</button>
        </div>
    </div>

    <article class="rep-hoja">
        {{-- El armazón es una tabla de una sola columna a propósito: es la única
             estructura que el navegador repite en todas las páginas al imprimir,
             de modo que el encabezado y el pie acompañan a cada hoja sin
             superponerse al contenido. --}}
        <table class="rep-armazon">
            <thead>
                <tr>
                    <td class="rep-armazon__cabecera">
                        @include('reportes.partials.encabezado', [
                            'prestador' => $prestador,
                            'logo' => $logo,
                            'contacto' => $contactoPrestador,
                            'fechaEjecucion' => $fechaEjecucion,
                        ])
                    </td>
                </tr>
            </thead>

            <tfoot>
                <tr>
                    <td class="rep-armazon__pie">
                        @include('reportes.partials.pie', [
                            'prestador' => $prestador,
                            'generadoEn' => $generadoEn,
                        ])
                    </td>
                </tr>
            </tfoot>

            <tbody>
                <tr>
                    <td>
                        {{-- ───────── Titular ───────── --}}
                        <div class="rep-titular">
                            <h1 class="rep-titular__nombre rep-quiebre">
                                Reporte de mantenimiento {{ mb_strtolower($mantenimiento->tipoEtiqueta()) }}
                            </h1>

                            <p class="rep-titular__sub rep-quiebre">
                                {{ $equipo?->descripcion ?? 'Equipo retirado del inventario' }}
                                @if ($empresa) · {{ $empresa->nombre }} @endif
                            </p>

                            <div class="rep-etiquetas">
                                <span class="rep-etiqueta rep-etiqueta--ok">
                                    <span class="rep-etiqueta__k">Estado</span>
                                    <span class="rep-etiqueta__v">{{ $mantenimiento->estadoEtiqueta() }}</span>
                                </span>

                                <span class="rep-etiqueta">
                                    <span class="rep-etiqueta__k">Programado</span>
                                    <span class="rep-etiqueta__v">{{ $mantenimiento->fecha_programada->format('d/m/Y') }}</span>
                                </span>

                                <span class="rep-etiqueta">
                                    <span class="rep-etiqueta__k">Ejecutado</span>
                                    <span class="rep-etiqueta__v">{{ $fechaEjecucion }}</span>
                                </span>

                                @if (filled($mantenimiento->tecnico))
                                    <span class="rep-etiqueta">
                                        <span class="rep-etiqueta__k">Técnico</span>
                                        <span class="rep-etiqueta__v rep-quiebre">{{ $mantenimiento->tecnico }}</span>
                                    </span>
                                @endif

                                @if (filled($mantenimiento->prioridad))
                                    <span class="rep-etiqueta">
                                        <span class="rep-etiqueta__k">Prioridad</span>
                                        <span class="rep-etiqueta__v">{{ $mantenimiento->prioridad }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- ───────── Activo intervenido ───────── --}}
                        @include('reportes.partials.seccion-datos', [
                            'numero' => ++$seccion,
                            'titulo' => 'Activo intervenido',
                            'datos' => [
                                'Descripción' => $equipo?->descripcion,
                                'Marca' => $equipo?->marca?->nombre,
                                'Modelo' => $equipo?->modelo?->nombre,
                                'Número de serie' => $equipo?->numero_serie,
                                'Registro INVIMA' => $equipo?->registro_invima,
                                'Fabricante' => $equipo?->fabricante,
                                'Clasificación por riesgo' => $equipo?->clasificacion_riesgo
                                    ? (Equipo::RIESGOS[$equipo->clasificacion_riesgo] ?? $equipo->clasificacion_riesgo)
                                    : null,
                                'Clasificación por especialidad' => $equipo?->clasificacion_especialidad,
                                'Tipo de adquisición' => $equipo?->tipo_adquisicion,
                                'Último mantenimiento' => $equipo?->ultimo_mantenimiento?->format('d/m/Y'),
                            ],
                            'anchos' => ['Descripción'],
                        ])

                        {{-- ───────── Ubicación ───────── --}}
                        @include('reportes.partials.seccion-datos', [
                            'numero' => ++$seccion,
                            'titulo' => 'Ubicación del activo',
                            'datos' => [
                                'Institución' => $empresa?->nombre,
                                'NIT' => $empresa?->nit,
                                'Ciudad' => $empresa?->ciudad,
                                'Dirección' => $empresa?->direccion,
                                'Área o servicio' => $equipo?->area?->nombre,
                                'Contacto' => collect([$empresa?->telefono, $empresa?->celular])->filter()->implode(' · ') ?: null,
                            ],
                            'anchos' => ['Dirección'],
                        ])

                        {{-- ───────── Datos de la orden ───────── --}}
                        @include('reportes.partials.seccion-datos', [
                            'numero' => ++$seccion,
                            'titulo' => 'Datos de la orden',
                            'datos' => [
                                'Orden' => $mantenimiento->codigo(),
                                'Tipo de mantenimiento' => $mantenimiento->tipoEtiqueta(),
                                'Estado' => $mantenimiento->estadoEtiqueta(),
                                'Prioridad' => $mantenimiento->prioridad,
                                'Fecha programada' => $mantenimiento->fecha_programada->format('d/m/Y'),
                                'Fecha de ejecución' => $fechaEjecucion,
                                'Técnico responsable' => $mantenimiento->tecnico,
                                'Costo del servicio' => $dinero($mantenimiento->costo),
                            ],
                        ])

                        {{-- ───────── Detalle de la intervención ───────── --}}
                        @include('reportes.partials.seccion-textos', [
                            'numero' => ++$seccion,
                            'titulo' => 'Detalle de la intervención',
                            'textos' => [
                                ($esCorrectivo ? 'Falla reportada' : 'Motivo de la programación') => $mantenimiento->motivo,
                                'Trabajo ejecutado' => $mantenimiento->descripcion,
                                'Repuestos utilizados' => $mantenimiento->repuestos,
                            ],
                        ])

                        {{-- La rutina de subtareas es propia del preventivo. --}}
                        @if (! $esCorrectivo)
                            @include('reportes.partials.seccion-subtareas', [
                                'numero' => ++$seccion,
                                'subtareas' => $subtareas,
                                'hechas' => $subtareasHechas,
                            ])
                        @endif

                        {{-- ───────── Accesorios ───────── --}}
                        @include('reportes.partials.seccion-accesorios', [
                            'numero' => ++$seccion,
                            'accesorios' => $accesorios,
                            'estados' => Equipo::ESTADOS_ACCESORIO,
                        ])

                        {{-- ───────── Observaciones ───────── --}}
                        @include('reportes.partials.seccion-textos', [
                            'numero' => ++$seccion,
                            'titulo' => 'Observaciones',
                            'textos' => ['Observaciones del servicio' => $mantenimiento->observaciones],
                        ])

                        {{-- ───────── Firmas ───────── --}}
                        @include('reportes.partials.firmas', [
                            'numero' => ++$seccion,
                            'firmas' => [
                                [
                                    'nombre' => $mantenimiento->tecnico,
                                    'cargo' => 'Técnico ejecutor',
                                    'rol' => 'Elaboró',
                                ],
                                [
                                    'nombre' => $firmante['nombre'],
                                    'cargo' => $firmante['cargo'],
                                    'rol' => 'Revisó y avaló',
                                ],
                                [
                                    'nombre' => null,
                                    'cargo' => $empresa?->nombre,
                                    'rol' => 'Recibido a satisfacción',
                                ],
                            ],
                        ])
                    </td>
                </tr>
            </tbody>
        </table>
    </article>

    <script>
        // Con `?imprimir=1` el documento abre directamente el diálogo de
        // impresión: es el atajo del icono de la tabla, mientras que el botón
        // de la ficha abre el reporte para revisarlo antes de guardarlo.
        if (new URLSearchParams(window.location.search).has('imprimir')) {
            window.addEventListener('load', function () {
                window.print();
            });
        }
    </script>
</body>
</html>
