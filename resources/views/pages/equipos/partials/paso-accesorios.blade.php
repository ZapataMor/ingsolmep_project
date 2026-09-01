{{-- Fase 4: estado por defecto de los accesorios y lista de componentes. --}}
@php
    $opciones = [
        '' => ['etiqueta' => '—', 'activo' => 'peer-checked:border-zinc-400 peer-checked:bg-zinc-200 peer-checked:text-carbon dark:peer-checked:bg-zinc-700 dark:peer-checked:text-white'],
        'B' => ['etiqueta' => 'B', 'activo' => 'peer-checked:border-emerald-500 peer-checked:bg-emerald-500 peer-checked:text-white'],
        'R' => ['etiqueta' => 'R', 'activo' => 'peer-checked:border-amber-500 peer-checked:bg-amber-500 peer-checked:text-white'],
        'M' => ['etiqueta' => 'M', 'activo' => 'peer-checked:border-rose-500 peer-checked:bg-rose-500 peer-checked:text-white'],
    ];
@endphp

<div class="space-y-6">
    <div>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <p class="flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                <flux:icon name="squares-2x2" class="size-4" /> Estado de accesorios (por defecto)
            </p>
            <div class="flex items-center gap-3 text-[11px] font-semibold text-zinc-500 dark:text-zinc-400">
                <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-emerald-500"></span> Bueno</span>
                <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-amber-500"></span> Regular</span>
                <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-rose-500"></span> Malo</span>
            </div>
        </div>

        <div class="grid gap-2.5 lg:grid-cols-2">
            @foreach (\App\Models\Equipo::ACCESORIOS as $clave => $etiqueta)
                <div class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-2.5 shadow-xs transition duration-200 hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-[13px] font-medium text-carbon dark:text-zinc-200">{{ $etiqueta }}</span>

                    <div class="flex shrink-0 items-center gap-1">
                        @foreach ($opciones as $valor => $opcion)
                            <label class="cursor-pointer">
                                <input type="radio" class="peer sr-only" value="{{ $valor }}" wire:model="accesorios_estado.{{ $clave }}">
                                <span class="flex size-8 items-center justify-center rounded-lg border border-zinc-200 bg-zinc-50 text-[12px] font-bold text-zinc-500 transition duration-200 hover:scale-110 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400 {{ $opcion['activo'] }}">
                                    {{ $opcion['etiqueta'] }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div>
        <label class="eq-label" for="eq-componentes">Componentes y/o accesorios incluidos</label>
        <textarea id="eq-componentes" class="eq-textarea min-h-32" wire:model="componentes" placeholder="Enumere los componentes, accesorios, cables, sensores y piezas incluidas con el equipo..."></textarea>
        <span class="eq-hint">Ejemplo: Cable ECG 5 derivaciones, brazalete NIBP adulto, sensor SpO2, manual de usuario.</span>
    </div>
</div>
