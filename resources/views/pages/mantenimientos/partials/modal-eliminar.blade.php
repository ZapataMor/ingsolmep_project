{{-- Confirmación de eliminación (borrado lógico). --}}
{{-- La raíz del teletransporte existe siempre: Livewire descarta un
     `@teleport` cuyo cuerpo nace vacío y ya no lo vuelve a poblar. --}}
<div class="contents">
    @if ($mantenimiento)
        <div
            x-data
            x-on:keydown.escape.window="$wire.set('mantenimientoAEliminar', null)"
            x-on:click.self="$wire.set('mantenimientoAEliminar', null)"
            class="eq-modal fixed inset-0 z-60 flex items-center justify-center bg-carbon-deep/70 p-4 backdrop-blur-sm"
            role="alertdialog"
            aria-modal="true"
            aria-labelledby="mt-eliminar-titulo"
        >
            <div class="eq-modal-panel w-full max-w-md overflow-hidden rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-black/5 dark:bg-zinc-900 dark:ring-white/10">
                <div class="flex items-start gap-4">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400">
                        <flux:icon name="trash" class="size-5" />
                    </span>

                    <div>
                        <h2 id="mt-eliminar-titulo" class="text-[16px] font-bold text-carbon dark:text-white">
                            Eliminar la orden {{ $mantenimiento->codigo() }}
                        </h2>
                        <p class="mt-1.5 text-[13px] leading-relaxed text-zinc-500 dark:text-zinc-400">
                            El mantenimiento {{ mb_strtolower($mantenimiento->tipoEtiqueta()) }} de
                            «{{ $mantenimiento->equipo?->descripcion ?? 'equipo retirado' }}» saldrá del listado.
                            El dato se conserva en la base de datos, de modo que el historial del equipo no se pierde.
                        </p>
                    </div>
                </div>

                @if ($mantenimiento->estado === 'ejecutado')
                    <div class="mt-4 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-[12.5px] text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                        <flux:icon name="exclamation-triangle" variant="mini" class="mt-0.5 size-4 shrink-0" />
                        <p>
                            Esta orden ya está ejecutada. La fecha del último mantenimiento registrada en la ficha
                            del equipo no cambia al eliminarla.
                        </p>
                    </div>
                @endif

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="eq-btn eq-btn-ghost" wire:click="$set('mantenimientoAEliminar', null)">Cancelar</button>
                    <button type="button" class="eq-btn eq-btn-danger" wire:click="eliminar">
                        <flux:icon name="trash" variant="mini" class="size-4" />
                        Sí, eliminar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
