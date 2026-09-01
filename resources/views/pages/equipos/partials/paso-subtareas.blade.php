{{-- Fase 3: subtareas de mantenimiento marcadas por defecto para este equipo. --}}
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-signal/10 px-4 py-3">
        <p class="flex items-center gap-2 text-[13px] text-carbon dark:text-zinc-200">
            <flux:icon name="wrench-screwdriver" class="size-4 shrink-0 text-signal" />
            Rutina que se cargará por defecto en cada orden de trabajo de este equipo.
        </p>

        <div class="flex items-center gap-2">
            <button type="button" class="eq-btn eq-btn-ghost !px-3 !py-1.5 !text-[12px]" wire:click="marcarTodasLasSubtareas">
                Marcar todas
            </button>
            <button type="button" class="eq-btn eq-btn-ghost !px-3 !py-1.5 !text-[12px]" wire:click="limpiarSubtareas">
                Limpiar
            </button>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach (\App\Models\Equipo::SUBTAREAS as $clave => $etiqueta)
            <label class="relative cursor-pointer">
                <input type="checkbox" class="peer sr-only" wire:model.live="subtareas.{{ $clave }}">

                <span class="flex h-full items-center rounded-xl border border-zinc-200 bg-white py-3 pr-10 pl-4 text-[13px] font-medium text-carbon shadow-xs transition duration-200 hover:scale-[1.02] hover:shadow-md peer-checked:border-lima peer-checked:bg-lima-soft peer-checked:text-lima-700 peer-checked:shadow-md dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:peer-checked:bg-lima/10 dark:peer-checked:text-lima">
                    {{ $etiqueta }}
                </span>

                <span class="pointer-events-none absolute top-1/2 right-3 size-5 -translate-y-1/2 rounded-full border-2 border-zinc-300 peer-checked:hidden dark:border-zinc-600"></span>

                <span class="pointer-events-none absolute top-1/2 right-3 hidden size-5 -translate-y-1/2 items-center justify-center rounded-full bg-lima text-white peer-checked:flex">
                    <flux:icon name="check" variant="micro" class="size-3.5" />
                </span>
            </label>
        @endforeach
    </div>

    <p class="text-[12px] text-zinc-500 dark:text-zinc-400">
        Seleccionadas: <span class="font-bold text-lima-700 dark:text-lima">{{ collect($subtareas)->filter()->count() }}</span> de {{ count(\App\Models\Equipo::SUBTAREAS) }}
    </p>
</div>
