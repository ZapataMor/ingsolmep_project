{{-- Estado vacío, compartido por la vista en tarjetas y la de tabla. --}}
<div class="mx-auto flex max-w-sm flex-col items-center gap-3">
    <span class="flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
        <flux:icon name="cpu-chip" class="size-7 text-zinc-400" />
    </span>
    <p class="text-[15px] font-semibold text-carbon dark:text-zinc-200">
        {{ $this->hayFiltrosActivos ? 'Ningún equipo coincide con los filtros' : 'Todavía no hay equipos registrados' }}
    </p>
    <p class="text-[13px] text-zinc-500 dark:text-zinc-400">
        {{ $this->hayFiltrosActivos ? 'Ajuste o limpie los filtros para ver más resultados.' : 'Registre el primer equipo del inventario para empezar.' }}
    </p>
    @if ($this->hayFiltrosActivos)
        <button type="button" class="eq-btn eq-btn-ghost" wire:click="limpiarFiltros">Limpiar filtros</button>
    @else
        <button type="button" class="eq-btn eq-btn-accent" wire:click="abrirCreacion">
            <flux:icon name="plus" variant="mini" class="size-4" /> Añadir equipo
        </button>
    @endif
</div>
