@props([
    'sidebar' => false,
])

{{-- Marca denominativa de INGSOLMEP con la paleta de la pantalla de acceso:
     lima #8CC63F en «INGS», carbón #3A3B3D en el resto y gris medio en el sufijo.
     Con la barra lateral colapsada sólo se muestra el ícono de la marca. --}}
<a {{ $attributes->class('eq-brand') }}>
    <img src="{{ asset('images/icono-180.png') }}" alt="" class="eq-brand-badge" aria-hidden="true">

    <span class="eq-brand-full">
        <span class="eq-brand-name"><span class="eq-brand-mark">INGS</span>OLMEP</span>
        <span class="eq-brand-suffix">S.A.S.</span>
    </span>
</a>
