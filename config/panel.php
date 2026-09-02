<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Metas del panel
    |---------------------------------------------------------------------------
    |
    | Valores de referencia contra los que se lee cada indicador. Un porcentaje
    | suelto no dice nada: «87 %» sólo significa algo al lado de la meta y del
    | mes anterior. Viven en configuración porque son un acuerdo comercial con
    | el cliente, no una constante del código.
    |
    */

    'meta_cumplimiento' => (int) env('PANEL_META_CUMPLIMIENTO', 95),

    /*
    |---------------------------------------------------------------------------
    | Umbrales de la bandeja de atención
    |---------------------------------------------------------------------------
    |
    | Días a partir de los cuales cada situación pasa de normal a atendible.
    |
    */

    'umbrales' => [
        // Un correctivo abierto más de esto lleva demasiado tiempo sin cerrarse.
        'correctivo_estancado' => (int) env('PANEL_DIAS_CORRECTIVO_ESTANCADO', 15),

        // Ventana en la que una garantía por vencer todavía se puede aprovechar.
        'garantia_por_vencer' => (int) env('PANEL_DIAS_GARANTIA', 60),

        // Tiempo sin mantenimiento que deja a un equipo fuera de la rutina.
        'sin_mantenimiento' => (int) env('PANEL_DIAS_SIN_MANTENIMIENTO', 180),

        // Día del mes desde el que tiene sentido reclamar un cronograma que no
        // ha arrancado: antes de esa fecha aún no hay retraso que reportar.
        'dia_corte_cronograma' => (int) env('PANEL_DIA_CORTE_CRONOGRAMA', 15),
    ],

    /*
    |---------------------------------------------------------------------------
    | Caché
    |---------------------------------------------------------------------------
    |
    | Segundos que vive cada bloque calculado. Las órdenes vencidas caducan
    | mucho antes: son lo único de la pantalla que exige actuar hoy y cinco
    | minutos de retraso en ese número es un mal negocio.
    |
    */

    'cache' => [
        'segundos' => (int) env('PANEL_CACHE_SEGUNDOS', 300),
        'segundos_vencidas' => (int) env('PANEL_CACHE_SEGUNDOS_VENCIDAS', 60),
    ],

];
