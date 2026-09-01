# Handoff: Pantalla de acceso INGSOLMEP

## Overview

Pantalla de inicio de sesión para el sistema de gestión de mantenimiento de equipos biomédicos de **INGSOLMEP S.A.S.** (Riohacha, La Guajira). Es la primera pantalla del sistema: recoge usuario y contraseña y transmite la identidad de marca. No hay registro, ni acceso con terceros, ni "recordarme".

Usuarios: ingenieros biomédicos, técnicos de campo y personal administrativo de IPS y clínicas.

## About the Design Files

Los archivos de este paquete son **referencias de diseño hechas en HTML** — prototipos que muestran la apariencia y el comportamiento previstos, **no código de producción para copiar directamente**.

La tarea es **recrear este diseño dentro del entorno existente de tu aplicativo** (React, Vue, Angular, Blade/Laravel, Django templates, lo que uses), aplicando los patrones, librerías de componentes y convenciones ya establecidos allí. Si el proyecto todavía no tiene entorno de frontend definido, elige el más apropiado e implementa el diseño ahí.

`Pantalla de acceso.dc.html` es un componente de un runtime de prototipado propio (`support.js`) que compila plantillas a React. **No integres `support.js` en tu aplicativo.** Úsalo solo para abrir el HTML en un navegador y ver el diseño funcionando. Toda la información necesaria para implementarlo está en este README.

### Cómo ver el prototipo
Abre `Pantalla de acceso.dc.html` en un navegador (requiere conexión para las fuentes de Google Fonts). El botón **Ingresar** simula la verificación durante 1700 ms y luego muestra el error de credenciales, para poder demostrar los tres estados.

## Fidelity

**Alta fidelidad (hifi).** Colores, tipografía, espaciado, geometría e interacciones son finales. Recrea la interfaz con precisión usando las librerías y patrones de tu base de código. Las medidas de este documento son las reales del prototipo.

---

## Design Tokens

### Colores

| Token | Hex | Uso |
|---|---|---|
| `verde-lima` | `#8CC63F` | Marca. Solo tres apariciones: `INGS` del wordmark, nodo de la señal, filete de foco en los campos. También el borde inferior del botón en carga y los anillos de foco. |
| `verde-oscuro` | `#5A8F2B` | Botón `:active`, color de enlaces (`a`) |
| `gris-carbon` | `#3A3B3D` | Texto principal, `OLMEP`, relleno del botón, borde de campo enfocado |
| `gris-medio` | `#58595B` | Subtítulo, texto secundario, pie, botón en estado de carga |
| `azul-senal` | `#29ABE2` | Trazo del electrocardiograma. **Único uso**; no aparece nunca dentro de la tarjeta en escritorio. |
| `neutro-base` | `#F2F4F1` | Fondo de la zona izquierda y relleno de los campos en reposo |
| `carbon-profundo` | `#23262A` | Fondo de la zona gráfica derecha |
| `blanco-tarjeta` | `#FFFFFF` | Fondo de la tarjeta, campos enfocados |
| `borde` | `#DCE0D8` | Borde de la tarjeta y de los campos en reposo |
| `regla-interna` | `#E7EAE4` | Filete divisor dentro de la tarjeta |
| `placeholder` | `#9DA29A` | Texto de marcador de posición |

Colores del estado de error (desaturados a propósito, fuera de la paleta de marca porque un estado de alerta lo exige):

| Token | Hex |
|---|---|
| `error-fondo` | `#FAF0EE` |
| `error-borde` | `#E3C8C3` |
| `error-texto` | `#8A2F26` |

**Regla de uso del verde lima:** es saturado. Concentrado en pocos lugares. No lo repartas.

### Tipografía

Familias: **IBM Plex Sans** (400, 500, 600) e **IBM Plex Mono** (400).

```
https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400&display=swap
```

| Elemento | Familia | Tamaño | Peso | Interlínea | Tracking |
|---|---|---|---|---|---|
| Wordmark `INGSOLMEP` | Plex Sans | 34 px | 600 | 1.05 | −0.02em |
| Subtítulo | Plex Sans | 14 px | 400 | 1.45 | — |
| Etiqueta de campo | Plex Sans | 13.5 px | 500 | — | — |
| Texto de campo / placeholder | Plex Sans | 15 px | 400 | — | — |
| Botón Ingresar | Plex Sans | 15 px | 600 | — | 0.012em |
| Botón Mostrar/Ocultar | Plex Sans | 12.5 px | 500 | — | — |
| Enlace ¿Olvidó su contraseña? | Plex Sans | 12.5 px | 400 | — | — |
| Mensaje de error | Plex Sans | 13 px | 400 | 1.5 | — |
| Pie de pantalla | Plex Mono | 11.5 px | 400 | — | 0.01em |

Sin etiquetas en mayúsculas espaciadas. Todas las etiquetas van en **mayúscula inicial**.

### Radios, bordes y sombras

- Radio de esquina: **2 px** en todo (tarjeta, campos, botón, banner de error). No hay nada más redondeado.
- **No hay sombras en ningún elemento.** La tarjeta se separa del fondo por un borde de 1 px y por el contraste con la zona oscura.
- Grosor de borde: 1 px, salvo el borde inferior de los campos que es 2 px.

### Espaciado

Escala usada: 8 / 10 / 18 / 20 / 22 / 26 / 28 / 30 / 42 / 46 px.

### Transiciones

`120 ms ease` sobre `border-color`, `background-color` y `color`. Nada más.

---

## Screens / Views

Hay una sola vista, con dos disposiciones (escritorio y móvil).

### Vista: Acceso — escritorio (ancho ≥ 881 px)

**Propósito:** el usuario escribe su usuario y contraseña y entra al sistema.

**Layout raíz**

```
position: relative;
min-height: 100vh;
background: #F2F4F1;
overflow: hidden;
```

Dos capas superpuestas: la zona gráfica (absoluta, al fondo) y el escenario del formulario (relativo, encima).

#### 1. Zona gráfica derecha (capa de fondo)

Contenedor absoluto `inset: 0`, recortado por la diagonal principal:

```css
clip-path: polygon(42% 0%, 100% 0%, 100% 100%, 24% 100%);
background-color: #23262A;
```

La diagonal entra por el borde superior al **42 %** del ancho y sale por el inferior al **24 %**. Se inclina hacia la izquierda a medida que baja. Estos porcentajes son deliberados: con ellos la diagonal cruza la tarjeta de login (entra por su borde superior cerca de la esquina derecha y sale por el borde inferior, alrededor del 62 % de su ancho). **Ese solapamiento es el gesto principal del diseño**; si cambias el ancho de la tarjeta o el margen izquierdo, reajusta los porcentajes para conservarlo.

El contenedor lleva `aria-hidden="true"`: es puramente decorativo.

Contiene exactamente **tres elementos gráficos**, ni uno más:

**Elemento 1 — Retícula de papel milimetrado.** Cuatro gradientes repetidos como `background-image` del mismo contenedor, en este orden (los finos primero, para que los gruesos queden encima):

```css
background-image:
  repeating-linear-gradient(to right,  rgba(255,255,255,0.028) 0 1px, transparent 1px 16px),
  repeating-linear-gradient(to bottom, rgba(255,255,255,0.028) 0 1px, transparent 1px 16px),
  repeating-linear-gradient(to right,  rgba(255,255,255,0.075) 0 1px, transparent 1px 80px),
  repeating-linear-gradient(to bottom, rgba(255,255,255,0.075) 0 1px, transparent 1px 80px);
```

Paso mayor 80 px al 7.5 % de blanco, subdivisión de 16 px al 2.8 %.

**Elemento 2 — El trazo de señal.** SVG absoluto `inset: 0`, `width/height: 100%`, `viewBox="0 0 1440 900"`, `preserveAspectRatio="xMidYMid slice"`. Tres piezas:

- *Eje vertical:* línea de `x=866, y=96` a `x=866, y=812`. `stroke: #8CC63F`, `stroke-opacity: 0.42`, `stroke-width: 1.2`.
- *Trazo base:* la ruta de electrocardiograma completa. `stroke: #29ABE2`, `stroke-opacity: 0.5`, `stroke-width: 2.4`, `stroke-linejoin/linecap: round`, `fill: none`, `pathLength="1000"`.

  ```
  M 60 470 H 300 c 14 0 18 -22 30 -22 c 12 0 16 22 30 22 H 404 l 10 12 l 12 -168
  l 14 210 l 12 -54 H 500 c 22 0 26 -40 52 -40 c 26 0 30 40 52 40 H 740
  c 14 0 18 -22 30 -22 c 12 0 16 22 30 22 H 844 l 10 12 l 12 -168 l 14 210 l 12 -54
  H 940 c 22 0 26 -40 52 -40 c 26 0 30 40 52 40 H 1180 c 14 0 18 -22 30 -22
  c 12 0 16 22 30 22 H 1284 l 10 12 l 12 -168 l 14 210 l 12 -54 H 1380
  c 22 0 26 -40 52 -40 c 26 0 30 40 52 40 H 1560
  ```

  Son tres ciclos cardíacos: onda P (curva suave), complejo QRS (el pico agudo `l 12 -168 / l 14 210`) y onda T. El quiebre vertical brusco del QRS es intencional: lee a la vez como pico de electrocardiograma y como el rayo eléctrico de la letra O del logo, que representa la parte de "sistema de potencia" del negocio. **No añadas un rayo como figura aparte.**

- *Nodo verde:* círculo en `cx=866, cy=314, r=5.5`, `fill: #8CC63F`. Cae exactamente sobre el pico R del segundo complejo QRS, donde el eje vertical cruza el trazo. Es el único punto de la pantalla donde el azul y el verde se tocan.

**Elemento 3 — Dos planos angulares.** Dos divs absolutos `inset: 0`, hijos de la zona gráfica, recortados por `clip-path`. Sus bordes son **paralelos a la diagonal principal** (mismo desplazamiento de −18 puntos porcentuales de arriba a abajo). No son aleatorios: si mueves la diagonal, muévelos igual.

```css
/* Plano A: banda ancha, blanco al 3.5% */
clip-path: polygon(74% 0%, 100% 0%, 100% 100%, 56% 100%);
background: rgba(255,255,255,0.035);

/* Plano B: cuña inferior derecha, lima al 8.5% */
clip-path: polygon(78% 62%, 100% 62%, 100% 100%, 71.2% 100%);
background: rgba(140,198,63,0.085);
```

#### 2. Escenario del formulario

```css
position: relative;
min-height: 100vh;
box-sizing: border-box;
display: flex;
align-items: center;      /* tarjeta centrada verticalmente */
padding: 64px 0 64px 8%;  /* margen izquierdo del 8% del ancho */
```

#### 3. Tarjeta de login (`<form>`)

```css
position: relative;
z-index: 1;               /* por encima de la zona gráfica */
width: 460px;
box-sizing: border-box;
background: #FFFFFF;
border: 1px solid #DCE0D8;
border-radius: 2px;
padding: 46px 46px 42px;
display: flex;
flex-direction: column;
```

El formulario lleva `novalidate` (la validación es del sistema, no del navegador).

Contenido, de arriba abajo:

| # | Elemento | Detalle |
|---|---|---|
| 1 | Wordmark | `<h1>` con dos `<span>`: `INGS` en `#8CC63F`, `OLMEP` en `#3A3B3D`. 34 px / 600 / −0.02em / line-height 1.05, margen 0. Es texto, **no una imagen del logo**. |
| 2 | Subtítulo | `<p>` "Sistema de Gestión de Equipos Médicos". 14 px, `#58595B`, `margin: 10px 0 0`. |
| 3 | Filete divisor | `<div>` de 1 px, `background: #E7EAE4`, `margin: 30px 0 28px`. |
| 4 | Banner de error | Solo cuando hay error. Ver "Error states". Ocupa `margin: 0 0 22px`. |
| 5 | Grupo de campos | `display: flex; flex-direction: column; gap: 20px`. |
| 6 | Botón Ingresar | `margin-top: 26px`. |
| 7 | Enlace ¿Olvidó su contraseña? | `margin-top: 18px`, alineado a la derecha (`justify-content: flex-end`). Opcional. |

**Campo Usuario**

Bloque `display: flex; flex-direction: column; gap: 8px`.

- `<label for="ing-user">` con el texto **Usuario** — es usuario, no correo electrónico.
- `<input id="ing-user" name="usuario" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="nombre.apellido">`

```css
/* reposo */
width: 100%; box-sizing: border-box; height: 48px; padding: 0 14px;
background: #F2F4F1;
border: 1px solid #DCE0D8;
border-bottom: 2px solid #DCE0D8;
border-radius: 2px;
font: 400 15px "IBM Plex Sans"; color: #3A3B3D;
outline: none;
transition: border-color .12s ease, background-color .12s ease;

/* :focus y :focus-visible */
border-color: #3A3B3D;
border-bottom-color: #8CC63F;   /* el filete lima es la señal de foco */
background: #FFFFFF;
```

Sin iconos dentro del campo.

**Campo Contraseña**

Mismo bloque, pero la fila de la etiqueta lleva dos cosas: `display: flex; align-items: baseline; justify-content: space-between; gap: 16px`.

- Izquierda: `<label for="ing-pass">` **Contraseña**.
- Derecha: `<button type="button">` que alterna entre **Mostrar** y **Ocultar**. Está **fuera** del campo, en la fila de la etiqueta, no superpuesto al input.

```css
/* botón Mostrar/Ocultar, reposo */
background: none; border: 0; padding: 2px 0;
font: 500 12.5px "IBM Plex Sans"; color: #58595B; cursor: pointer;
border-bottom: 1px solid transparent;
transition: color .12s ease;

/* :hover */  color: #3A3B3D; border-bottom-color: #8CC63F;
/* :focus-visible */ outline: 2px solid #8CC63F; outline-offset: 3px;
```

Atributos: `aria-pressed` con el booleano de visibilidad y `aria-controls="ing-pass"`.

- `<input id="ing-pass" name="contrasena" autocomplete="current-password" placeholder="••••••••">` con `type` alternando entre `password` y `text`. Estilos idénticos al campo Usuario.

**Botón Ingresar**

```css
width: 100%; height: 52px; border: 0; border-radius: 2px;
background: #3A3B3D; color: #FFFFFF;
font: 600 15px "IBM Plex Sans"; letter-spacing: .012em;
cursor: pointer;
transition: background-color .12s ease;

/* :hover  */ background: #2B2C2E;
/* :active */ background: #5A8F2B;   /* verde oscuro al presionar */
/* :focus-visible */ outline: 2px solid #8CC63F; outline-offset: 3px;
```

Es el **único** elemento con jerarquía de acción. Sin degradados.

**Enlace ¿Olvidó su contraseña?**

```css
font-size: 12.5px; color: #58595B; text-decoration: none;
border-bottom: 1px solid #DCE0D8; padding-bottom: 1px;
transition: color .12s ease, border-color .12s ease;
/* :hover */ color: #3A3B3D; border-bottom-color: #8CC63F;
```

#### 4. Pie de pantalla

Absoluto dentro del escenario: `left: 8%; bottom: 30px`. Texto exacto:

```
INGSOLMEP S.A.S. · Riohacha, La Guajira
```

IBM Plex Mono 11.5 px, `#58595B`, `letter-spacing: 0.01em`. Muy discreto, no compite con nada.

---

### Vista: Acceso — móvil (ancho ≤ 880 px)

En móvil **desaparecen la diagonal y toda la composición gráfica**. Queda la tarjeta a ancho completo sobre el neutro. La pantalla de acceso en un celular tiene que cargar y funcionar sin nada que estorbe.

Cambios respecto a escritorio:

- Zona gráfica: `display: none`.
- Escenario: `padding: 52px 24px 120px`, `align-items: flex-start`.
- Tarjeta: `width: 100%`, `max-width: 440px`, **sin borde**, `background: transparent`, `padding: 0`. Deja de ser una tarjeta y pasa a ser contenido directo sobre el neutro.
- Aparece un fragmento del trazo de señal sobre el wordmark: SVG `viewBox="0 0 220 40"`, renderizado a 132 × 34 px, `margin-bottom: 18px`, `stroke: #29ABE2`, `stroke-width: 2.4`, linejoin/linecap `round`, `aria-hidden="true"`. Ruta:

  ```
  M 0 26 H 58 l 7 8 l 8 -26 l 9 34 l 8 -16 H 118 c 12 0 14 -14 28 -14 c 14 0 16 14 28 14 H 220
  ```

  En escritorio este SVG está oculto (`display: none`).
- Campos: `height: 52px`. Botón: `height: 54px`. (Objetivos táctiles por encima de 44 px.)
- Pie: `left: 24px`, sigue anclado abajo.

---

## Interactions & Behavior

### Envío del formulario

1. `submit` → si ya está cargando, no hace nada.
2. Pasa a estado de carga y **limpia el error anterior**.
3. En el prototipo, un `setTimeout` de 1700 ms devuelve el error de credenciales. **En producción esto se reemplaza por la llamada real de autenticación**; el error se muestra con la respuesta del servidor y el éxito redirige al panel.
4. Limpia el temporizador al desmontar.

### Estados del botón

| Estado | Fondo | Texto | Otros |
|---|---|---|---|
| Reposo | `#3A3B3D` | Ingresar | — |
| Hover | `#2B2C2E` | Ingresar | — |
| Presionado | `#5A8F2B` | Ingresar | — |
| Foco de teclado | `#3A3B3D` | Ingresar | `outline: 2px solid #8CC63F; outline-offset: 3px` |
| Cargando | `#58595B` | **Verificando…** | `disabled`, `aria-busy="true"`, `cursor: default`, barra de progreso |

Barra de progreso indeterminada del estado de carga: `<span>` absoluto en el borde inferior del botón, `height: 2px`, `width: 34%`, `background: #8CC63F`, el botón con `overflow: hidden`.

```css
@keyframes ing-bar {
  0%   { transform: translateX(-110%); }
  100% { transform: translateX(300%); }
}
/* animation: ing-bar 1.15s linear infinite; */
```

Sin ruedita giratoria.

### Error de credenciales

Banner sobre el grupo de campos, con `role="alert"` para que lo anuncie el lector de pantalla. Se oculta mientras el botón está cargando.

```css
padding: 12px 14px;
background: #FAF0EE;
border: 1px solid #E3C8C3;
border-radius: 2px;
font-size: 13px; line-height: 1.5; color: #8A2F26;
text-wrap: pretty;
```

Texto exacto usado en el prototipo:

> Usuario o contraseña incorrectos. Le quedan 4 intentos antes del bloqueo temporal de la cuenta.

Dice qué pasó y qué sigue, sin disculparse ni ser vago. Conecta el contador de intentos con tu política real de bloqueo.

### Movimiento

Un solo gesto, continuo, sin animación de entrada: un segmento luminoso recorre el trazo del electrocardiograma en **14 s**, lineal e infinito.

Implementación: un `<use href="#ing-trace">` sobre el trazo base, con `stroke: #29ABE2`, `stroke-opacity: 1`, `stroke-width: 2.6`, `stroke-dasharray: 150 850` (posible porque la ruta declara `pathLength="1000"`), animando `stroke-dashoffset` de 1000 a 0.

```css
@keyframes ing-sweep { from { stroke-dashoffset: 1000; } to { stroke-dashoffset: 0; } }
```

**`prefers-reduced-motion`:** el recorrido se detiene y se oculta por completo (`animation: none; opacity: 0`), y la barra del botón deja de moverse y pasa a ancho completo al 45 % de opacidad.

Nada más se mueve en la pantalla.

---

## State Management

Tres variables. Nada más.

| Variable | Tipo | Inicial | Transiciones |
|---|---|---|---|
| `shown` | boolean | `false` | Alterna con el botón Mostrar/Ocultar. Controla `type` del campo contraseña. |
| `loading` | boolean | `false` | `true` al enviar; `false` cuando responde el servidor. |
| `error` | string | `""` | Se limpia al enviar; se llena con el mensaje del servidor al fallar. |

Los inputs son **no controlados** en el prototipo (sin `value` en el estado). En tu aplicativo, contrólalos o usa la librería de formularios que ya tengas.

Datos que necesita la implementación real: un endpoint de autenticación que reciba usuario y contraseña, devuelva sesión o error, e idealmente informe los intentos restantes antes del bloqueo para poder componer el mensaje.

### Parámetros configurables del prototipo

Tres opciones expuestas como props, por si quieres las variantes:

| Prop | Por defecto | Efecto |
|---|---|---|
| `splitWordmark` | `true` | `INGS` lima + `OLMEP` carbón. En `false`, todo el wordmark en `#3A3B3D`. |
| `signalMotion` | `true` | Activa el recorrido del trazo. |
| `showForgotLink` | `true` | Muestra el enlace de contraseña olvidada. |

---

## Accessibility

- Contraste mínimo AA en todo texto. `#58595B` sobre `#FFFFFF` = 6.4:1; blanco sobre `#3A3B3D` = 11.4:1; blanco sobre `#58595B` = 6.4:1; `#8A2F26` sobre `#FAF0EE` ≈ 7:1.
- Toda etiqueta asociada a su campo con `for` / `id`.
- El foco de teclado es visible por sí solo: borde carbón y filete lima en los campos, anillo lima en botones y enlaces. No dependas del `:focus` por clic.
- Orden de tabulación: Usuario → Mostrar/Ocultar → Contraseña → Ingresar → ¿Olvidó su contraseña?
- `role="alert"` en el error; `aria-busy` en el botón cargando; `aria-pressed` y `aria-controls` en Mostrar/Ocultar.
- Toda la composición gráfica lleva `aria-hidden="true"`.
- `prefers-reduced-motion` respetado.

---

## Assets

**Ninguno.** No hay imágenes, ni iconos, ni archivos de logo. El wordmark es texto, la composición gráfica es SVG y CSS inline. La única dependencia externa son las dos familias de Google Fonts.

Si prefieres autoalojar las fuentes, IBM Plex es de código abierto (licencia SIL Open Font License 1.1).

---

## Files

| Archivo | Qué es |
|---|---|
| `Pantalla de acceso.dc.html` | El prototipo. Referencia de diseño, no código de producción. |
| `support.js` | Runtime del prototipo. Necesario solo para abrir el HTML en el navegador. **No lo integres en tu aplicativo.** |
| `README.md` | Este documento. Autosuficiente: basta para implementar la pantalla sin abrir el HTML. |
