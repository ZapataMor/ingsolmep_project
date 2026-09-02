{{-- Confirmación para retirar el reporte del listado. --}}
{{-- La raíz del teletransporte existe siempre: Livewire descarta un
     `@teleport` cuyo cuerpo nace vacío y ya no lo vuelve a poblar. --}}
<div class="contents">
    @if ($reporte)
        <div
            x-data
            x-on:keydown.escape.window="$wire.set('reporteAEliminar', null)"
            x-on:click.self="$wire.set('reporteAEliminar', null)"
            class="eq-modal fixed inset-0 z-60 flex items-center justify-center bg-carbon-deep/70 p-4 backdrop-blur-sm"
            role="alertdialog"
            aria-modal="true"
            aria-labelledby="rp-eliminar-titulo"
        >
            <div class="eq-modal-panel w-full max-w-md overflow-hidden rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-black/5 dark:bg-zinc-900 dark:ring-white/10">
                <div class="flex items-start gap-4">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400">
                        <flux:icon name="trash" class="size-5" />
                    </span>

                    <div>
                        <h2 id="rp-eliminar-titulo" class="text-[16px] font-bold text-carbon dark:text-white">
                            Retirar el reporte {{ $reporte->codigo() }}
                        </h2>
                        <p class="mt-1.5 text-[13px] leading-relaxed text-zinc-500 dark:text-zinc-400">
                            El reporte {{ mb_strtolower($reporte->tipoEtiqueta()) }} de
                            «{{ $reporte->mantenimiento?->equipo?->descripcion ?? 'equipo retirado' }}» saldrá del listado
                            de reportes generados. La orden de mantenimiento no se toca.
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex items-start gap-3 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-[12.5px] text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-300">
                    <flux:icon name="information-circle" variant="mini" class="mt-0.5 size-4 shrink-0" />
                    <p>
                        El documento no se pierde: se vuelve a componer desde la orden, y al abrirlo de nuevo
                        el reporte reaparecerá en este listado.
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="eq-btn eq-btn-ghost" wire:click="$set('reporteAEliminar', null)">Cancelar</button>
                    <button type="button" class="eq-btn eq-btn-danger" wire:click="eliminar">
                        <flux:icon name="trash" variant="mini" class="size-4" />
                        Sí, retirar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
