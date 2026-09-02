<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Prestador del servicio
    |---------------------------------------------------------------------------
    |
    | Datos de INGSOLMEP que encabezan cada reporte. Viven en configuración y no
    | en la vista para que un cambio de NIT, de lema o de logo no obligue a
    | tocar la plantilla del documento.
    |
    */

    'prestador' => [
        'nombre' => env('REPORTE_PRESTADOR_NOMBRE', 'INGSOLMEP S.A.S.'),
        'nit' => env('REPORTE_PRESTADOR_NIT', '901.616.249-1'),
        'lema' => env('REPORTE_PRESTADOR_LEMA', 'Ingeniería y soluciones en equipos médicos y sistemas de potencia'),
        'ciudad' => env('REPORTE_PRESTADOR_CIUDAD', 'Riohacha — La Guajira'),
        'telefono' => env('REPORTE_PRESTADOR_TELEFONO'),
        'email' => env('REPORTE_PRESTADOR_EMAIL'),

        // Ruta del logo dentro de `public/`.
        'logo' => env('REPORTE_PRESTADOR_LOGO', 'images/logo.png'),
    ],

    /*
    |---------------------------------------------------------------------------
    | Firma de ingeniería
    |---------------------------------------------------------------------------
    |
    | Quien avala técnicamente la orden. El técnico que la ejecutó sale de la
    | propia orden; este bloque es fijo para toda la empresa.
    |
    */

    'firmante' => [
        'nombre' => env('REPORTE_FIRMANTE_NOMBRE', 'Mario Luis Ramírez Ospino'),
        'cargo' => env('REPORTE_FIRMANTE_CARGO', 'Ingeniero electrónico'),
    ],

];
