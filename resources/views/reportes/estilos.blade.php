{{-- Hoja de estilos del documento. Va incrustada y sin Tailwind: el reporte se
     imprime desde el navegador y no debe depender de la compilación de la
     aplicación ni del tema claro/oscuro de la pantalla. --}}
<style>
@verbatim
/* ── Fundamentos ─────────────────────────────────────────────────── */

:root {
    --rep-tinta: #23262a;
    --rep-tinta-media: #55585c;
    --rep-tinta-suave: #7b8085;
    --rep-linea: #e2e6e1;
    --rep-linea-fuerte: #c9cfc7;
    --rep-fondo: #f7f8f6;
    --rep-acento: #8cc63f;
    --rep-acento-fuerte: #5a8f2b;
    --rep-acento-suave: #f1f8e5;

    --rep-hoja-ancho: 210mm;
    --rep-hoja-margen: 13mm;
}

/* El correctivo nace de una falla: se distingue del preventivo con un acento
   ámbar, para que las dos órdenes no se confundan de un vistazo. */
[data-tipo="correctivo"] {
    --rep-acento: #e0921e;
    --rep-acento-fuerte: #b45309;
    --rep-acento-suave: #fdf4e5;
}

*,
*::before,
*::after {
    box-sizing: border-box;
}

html {
    -webkit-text-size-adjust: 100%;
}

body {
    margin: 0;
    background: #e9ebe8;
    color: var(--rep-tinta);
    font-family: "Segoe UI", ui-sans-serif, system-ui, Roboto, "Helvetica Neue", Arial, sans-serif;
    font-size: 11px;
    line-height: 1.5;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

p,
h1,
h2,
h3,
dl,
dd,
dt,
figure {
    margin: 0;
}

/* Cualquier valor puede llegar largo (series, correos, descripciones de
   catálogo): se parte antes que desbordar la caja que lo contiene. */
.rep-quiebre {
    min-width: 0;
    overflow-wrap: anywhere;
    word-break: break-word;
    hyphens: auto;
}

/* ── Barra de acciones (sólo pantalla) ───────────────────────────── */

.rep-barra {
    position: sticky;
    top: 0;
    z-index: 10;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 20px;
    background: #23262a;
    color: #fff;
    box-shadow: 0 6px 18px rgb(0 0 0 / 18%);
}

.rep-barra__info {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 10px;
    min-width: 0;
}

.rep-barra__codigo {
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.04em;
}

.rep-barra__ayuda {
    font-size: 11.5px;
    color: #b6bbb3;
}

.rep-barra__acciones {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.rep-boton {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 15px;
    border: 1px solid transparent;
    border-radius: 10px;
    font: inherit;
    font-size: 12.5px;
    font-weight: 600;
    text-decoration: none;
    color: #fff;
    cursor: pointer;
    transition: transform 150ms ease, background-color 150ms ease;
}

.rep-boton:hover {
    transform: translateY(-1px);
}

.rep-boton--principal {
    background: var(--rep-acento);
    color: #17240a;
}

.rep-boton--fantasma {
    background: transparent;
    border-color: #4a4e52;
    color: #e6e8e4;
}

.rep-boton--fantasma:hover {
    background: #32363a;
}

/* ── Hoja ────────────────────────────────────────────────────────── */

.rep-hoja {
    width: var(--rep-hoja-ancho);
    max-width: 100%;
    min-height: 297mm;
    margin: 22px auto 40px;
    padding: var(--rep-hoja-margen);
    background: #fff;
    box-shadow: 0 10px 40px rgb(0 0 0 / 12%);
}

/* Armazón de una sola columna. Al imprimir, el navegador repite `thead` y
   `tfoot` en cada página y reserva su espacio, que es lo que un encabezado
   `position: fixed` no consigue: ése se recorta contra el margen de la hoja. */
.rep-armazon {
    width: 100%;
    border-collapse: collapse;
}

.rep-armazon > thead {
    display: table-header-group;
}

.rep-armazon > tfoot {
    display: table-footer-group;
}

.rep-armazon td {
    padding: 0;
    vertical-align: top;
}

.rep-armazon__cabecera {
    padding-bottom: 4mm !important;
}

/* ── Encabezado ──────────────────────────────────────────────────── */

/* Rejilla de dos columnas y no `flex`: dentro de una celda de tabla el ancho
   disponible se resuelve de otra forma y la identificación de la orden se caía
   a una segunda línea al imprimir. */
.rep-encabezado {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: start;
    gap: 6mm;
    padding-bottom: 3mm;
    border-bottom: 2px solid var(--rep-acento);
}

.rep-marca {
    display: flex;
    align-items: center;
    gap: 4mm;
    min-width: 0;
}

.rep-marca__logo {
    height: 14mm;
    width: auto;
    max-width: 58mm;
    object-fit: contain;
}

.rep-marca__datos {
    min-width: 0;
    border-left: 1px solid var(--rep-linea);
    padding-left: 4mm;
}

.rep-marca__nombre {
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.02em;
    color: var(--rep-tinta);
}

.rep-marca__linea {
    font-size: 9px;
    color: var(--rep-tinta-suave);
    max-width: 68mm;
}

.rep-sello {
    justify-self: end;
    text-align: right;
    min-width: 0;
}

.rep-sello__tipo {
    display: inline-block;
    padding: 1.2mm 3mm;
    border-radius: 999px;
    background: var(--rep-acento-suave);
    color: var(--rep-acento-fuerte);
    font-size: 8.5px;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.rep-sello__codigo {
    margin-top: 1.5mm;
    font-size: 18px;
    font-weight: 800;
    letter-spacing: 0.02em;
    line-height: 1.1;
    color: var(--rep-tinta);
}

.rep-sello__fecha {
    font-size: 9px;
    color: var(--rep-tinta-suave);
}

/* ── Título del documento ────────────────────────────────────────── */

.rep-titular {
    margin-top: 6mm;
}

.rep-titular__nombre {
    font-size: 17px;
    font-weight: 800;
    letter-spacing: -0.01em;
    line-height: 1.25;
}

.rep-titular__sub {
    margin-top: 1mm;
    font-size: 10.5px;
    color: var(--rep-tinta-media);
}

.rep-etiquetas {
    display: flex;
    flex-wrap: wrap;
    gap: 2mm;
    margin-top: 3mm;
}

.rep-etiqueta {
    display: inline-flex;
    align-items: baseline;
    gap: 5px;
    padding: 1.2mm 3mm;
    border: 1px solid var(--rep-linea);
    border-radius: 999px;
    background: var(--rep-fondo);
    font-size: 9.5px;
    break-inside: avoid;
}

.rep-etiqueta__k {
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--rep-tinta-suave);
}

.rep-etiqueta__v {
    font-weight: 700;
    color: var(--rep-tinta);
}

.rep-etiqueta--ok {
    background: #eef8f0;
    border-color: #c9e7d1;
}

.rep-etiqueta--ok .rep-etiqueta__v {
    color: #217a45;
}

/* ── Secciones ───────────────────────────────────────────────────── */

.rep-seccion {
    margin-top: 7mm;
}

.rep-seccion__titulo {
    display: flex;
    align-items: center;
    gap: 3mm;
    margin-bottom: 3.5mm;
    padding-bottom: 1.5mm;
    border-bottom: 1.5px solid var(--rep-acento);
    /* Un título nunca queda solo al final de una página. */
    break-after: avoid;
    page-break-after: avoid;
}

.rep-seccion__numero {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 6mm;
    height: 6mm;
    flex: none;
    border-radius: 2mm;
    background: var(--rep-acento);
    color: #fff;
    font-size: 9px;
    font-weight: 800;
}

.rep-seccion__nombre {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--rep-acento-fuerte);
}

.rep-seccion__conteo {
    margin-left: auto;
    font-size: 9px;
    font-weight: 600;
    color: var(--rep-tinta-suave);
    letter-spacing: 0.04em;
    white-space: nowrap;
}

/* ── Rejilla de datos ────────────────────────────────────────────── */

.rep-datos {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(52mm, 1fr));
    gap: 3.5mm 5mm;
}

.rep-dato {
    min-width: 0;
    padding-left: 2.5mm;
    border-left: 2px solid var(--rep-linea-fuerte);
    break-inside: avoid;
    page-break-inside: avoid;
}

.rep-dato--ancho {
    grid-column: 1 / -1;
}

.rep-dato__k {
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    color: var(--rep-tinta-suave);
}

.rep-dato__v {
    font-size: 11px;
    font-weight: 600;
    line-height: 1.45;
    color: var(--rep-tinta);
}

.rep-dato__v--vacio {
    font-weight: 500;
    color: var(--rep-tinta-suave);
}

/* ── Bloques de texto largo ──────────────────────────────────────── */

.rep-textos {
    display: grid;
    gap: 4mm;
}

.rep-texto__k {
    margin-bottom: 1.2mm;
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    color: var(--rep-tinta-suave);
    break-after: avoid;
    page-break-after: avoid;
}

.rep-texto__v {
    padding: 2.5mm 3mm;
    border: 1px solid var(--rep-linea);
    border-left: 2.5px solid var(--rep-acento);
    border-radius: 1.5mm;
    background: var(--rep-fondo);
    font-size: 10.5px;
    line-height: 1.6;
    /* El texto viene de un textarea: se respetan los saltos escritos y se
       reparte en varias páginas si hace falta, sin recortarse. */
    white-space: pre-line;
    text-align: justify;
}

.rep-texto__v--vacio {
    color: var(--rep-tinta-suave);
    font-style: italic;
    text-align: left;
}

/* ── Listas de verificación ──────────────────────────────────────── */

.rep-lista {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(62mm, 1fr));
    gap: 2mm 3mm;
}

.rep-item {
    display: flex;
    align-items: flex-start;
    gap: 2.5mm;
    padding: 1.6mm 2.5mm;
    border: 1px solid var(--rep-linea);
    border-radius: 1.5mm;
    background: #fff;
    font-size: 10px;
    line-height: 1.35;
    break-inside: avoid;
    page-break-inside: avoid;
}

.rep-item--hecho {
    border-color: var(--rep-acento);
    background: var(--rep-acento-suave);
}

.rep-item__casilla {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 3.4mm;
    height: 3.4mm;
    margin-top: 0.3mm;
    flex: none;
    border: 1px solid var(--rep-linea-fuerte);
    border-radius: 0.8mm;
    background: #fff;
    font-size: 8px;
    font-weight: 800;
    line-height: 1;
    color: transparent;
}

.rep-item--hecho .rep-item__casilla {
    border-color: var(--rep-acento-fuerte);
    background: var(--rep-acento-fuerte);
    color: #fff;
}

.rep-item__nombre {
    min-width: 0;
    font-weight: 600;
}

.rep-item:not(.rep-item--hecho) .rep-item__nombre {
    font-weight: 500;
    color: var(--rep-tinta-suave);
}

/* ── Tablas ──────────────────────────────────────────────────────── */

.rep-tabla-marco {
    overflow-x: auto;
}

.rep-tabla {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 10px;
}

/* La cabecera se repite en cada página que ocupe la tabla. */
.rep-tabla thead {
    display: table-header-group;
}

.rep-tabla tr {
    break-inside: avoid;
    page-break-inside: avoid;
}

.rep-tabla th,
.rep-tabla td {
    border: 1px solid var(--rep-linea);
    padding: 1.8mm 2.5mm;
    text-align: left;
    vertical-align: middle;
}

.rep-tabla th {
    background: var(--rep-fondo);
    font-size: 8px;
    font-weight: 800;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    color: var(--rep-tinta-suave);
}

.rep-tabla__centro {
    width: 15mm;
    text-align: center;
}

.rep-tabla__marca {
    font-weight: 800;
    color: var(--rep-acento-fuerte);
}

.rep-tabla__nombre {
    font-weight: 600;
}

.rep-tabla tbody tr:nth-child(even) td {
    background: #fbfcfa;
}

/* ── Vacíos ──────────────────────────────────────────────────────── */

.rep-vacio {
    padding: 3mm;
    border: 1px dashed var(--rep-linea-fuerte);
    border-radius: 1.5mm;
    color: var(--rep-tinta-suave);
    font-size: 10px;
    font-style: italic;
    text-align: center;
}

/* ── Firmas ──────────────────────────────────────────────────────── */

.rep-firmas {
    /* Cuanto más compacto el bloque, más veces cabe al pie de la última página
       con contenido en lugar de arrastrar una hoja nueva casi vacía. */
    margin-top: 6mm;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(50mm, 1fr));
    gap: 6mm;
    break-inside: avoid;
    page-break-inside: avoid;
}

.rep-firma {
    text-align: center;
    break-inside: avoid;
    page-break-inside: avoid;
}

.rep-firma__espacio {
    height: 12mm;
}

.rep-firma__linea {
    border-top: 1px solid var(--rep-tinta-media);
    padding-top: 1.5mm;
}

.rep-firma__nombre {
    /* Sin nombre registrado la línea conserva su altura y queda lista para
       firmarse a mano. */
    min-height: 1.35em;
    font-size: 10.5px;
    font-weight: 700;
    line-height: 1.35;
}

.rep-firma__cargo {
    font-size: 9px;
    color: var(--rep-tinta-suave);
}

.rep-firma__rol {
    margin-top: 0.8mm;
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--rep-acento-fuerte);
}

/* ── Pie ─────────────────────────────────────────────────────────── */

.rep-pie {
    margin-top: 8mm;
    padding-top: 2mm;
    border-top: 1px solid var(--rep-linea);
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    justify-content: space-between;
    gap: 3mm;
    font-size: 8.5px;
    color: var(--rep-tinta-suave);
}

.rep-pie__sello {
    font-weight: 700;
    color: var(--rep-tinta-media);
}

/* ── Impresión ───────────────────────────────────────────────────── */

@media print {
    @page {
        size: A4 portrait;
        margin: 12mm;
    }

    body {
        background: #fff;
    }

    .rep-barra {
        display: none;
    }

    .rep-hoja {
        width: auto;
        min-height: 0;
        margin: 0;
        padding: 0;
        box-shadow: none;
    }

    .rep-titular {
        margin-top: 0;
    }

    /* Un desbordamiento con barra en pantalla se convierte en contenido
       completo sobre el papel. */
    .rep-tabla-marco {
        overflow-x: visible;
    }
}

/* ── Pantallas estrechas ─────────────────────────────────────────── */

@media screen and (max-width: 820px) {
    .rep-hoja {
        width: 100%;
        margin: 12px 0 24px;
        padding: 18px;
    }

    /* Sin ancho para las dos columnas, la identificación de la orden pasa
       debajo de la marca en lugar de estrujarse contra ella. */
    .rep-encabezado {
        grid-template-columns: minmax(0, 1fr);
    }

    .rep-sello {
        justify-self: start;
        text-align: left;
    }
}
@endverbatim
</style>
