{{-- Confirmación de eliminación (borrado lógico). --}}
<div
    x-cloak
    x-show="$wire.equipoAEliminar !== null"
    x-transition.opacity.duration.200ms
    x-on:keydown.escape.window="$wire.equipoAEliminar !== null && $wire.set('equipoAEliminar', null)"
    class="fixed inset-0 z-50 flex items-center justify-center bg-carbon-deep/70 p-4 backdrop-blur-sm"
    role="dialog"
    aria-modal="true"
>
    <div
        x-show="$wire.equipoAEliminar !== null"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="w-full max-w-md overflow-hidden rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-black/5 dark:bg-zinc-900 dark:ring-white/10"
    >
        <div class="flex items-start gap-4">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400">
                <flux:icon name="trash" class="size-5" />
            </span>

            <div>
                <h2 class="text-[16px] font-bold text-carbon dark:text-white">Eliminar equipo</h2>
                <p class="mt-1.5 text-[13px] leading-relaxed text-zinc-500 dark:text-zinc-400">
                    El equipo saldrá del inventario activo. El registro se conserva en la base de datos,
                    de modo que su historial de mantenimientos no se pierde.
                </p>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="eq-btn eq-btn-ghost" wire:click="$set('equipoAEliminar', null)">Cancelar</button>
            <button type="button" class="eq-btn eq-btn-danger" wire:click="eliminar">
                <flux:icon name="trash" variant="mini" class="size-4" />
                Sí, eliminar
            </button>
        </div>
    </div>
</div>
