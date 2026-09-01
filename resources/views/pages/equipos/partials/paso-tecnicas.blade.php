{{-- Fase 2: características técnicas de funcionamiento. --}}
@php
    $sinCorriente = $suministro_electrico === 'na';
    $campos = [
        ['voltaje', 'Voltaje', 'V'],
        ['amperaje', 'Amperaje', 'A'],
        ['frecuencia', 'Frecuencia', 'Hz'],
        ['corriente', 'Corriente', 'A'],
        ['potencia', 'Potencia', 'W'],
        ['voltios', 'Voltios', 'VA'],
    ];
    $camposFisicos = [
        ['temperatura', 'Temperatura', '°C'],
        ['presion', 'Presión', 'PSI'],
        ['peso', 'Peso', 'kg'],
        ['velocidad', 'Velocidad', 'rpm'],
    ];
@endphp

<div class="space-y-6">
    <div>
        <label class="eq-label">Suministro eléctrico</label>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach (\App\Models\Equipo::SUMINISTROS as $clave => $etiqueta)
                <label class="relative cursor-pointer">
                    <input type="radio" class="peer sr-only" value="{{ $clave }}" wire:model.live="suministro_electrico">
                    <span class="flex items-center justify-center rounded-xl border border-zinc-200 bg-white px-3.5 py-3 text-center text-[13px] font-medium text-carbon shadow-xs transition duration-200 hover:scale-[1.02] hover:shadow-md peer-checked:border-lima peer-checked:bg-lima-soft peer-checked:font-semibold peer-checked:text-lima-700 peer-checked:shadow-md dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:peer-checked:bg-lima/10 dark:peer-checked:text-lima">
                        {{ $etiqueta }}
                    </span>
                </label>
            @endforeach
        </div>
    </div>

    <div @class(['transition duration-200', 'pointer-events-none opacity-40' => $sinCorriente])>
        <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
            <flux:icon name="bolt" class="size-4" /> Parámetros eléctricos
        </p>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($campos as [$campo, $etiqueta, $unidad])
                <div>
                    <label class="eq-label" for="eq-{{ $campo }}">{{ $etiqueta }}</label>
                    <div class="relative">
                        <input id="eq-{{ $campo }}" type="text" class="eq-input pr-12" wire:model="{{ $campo }}" placeholder="0" autocomplete="off" @disabled($sinCorriente)>
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-[11px] font-semibold text-zinc-400">{{ $unidad }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div>
        <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
            <flux:icon name="beaker" class="size-4" /> Parámetros físicos y de operación
        </p>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($camposFisicos as [$campo, $etiqueta, $unidad])
                <div>
                    <label class="eq-label" for="eq-{{ $campo }}">{{ $etiqueta }}</label>
                    <div class="relative">
                        <input id="eq-{{ $campo }}" type="text" class="eq-input pr-12" wire:model="{{ $campo }}" placeholder="0" autocomplete="off">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-[11px] font-semibold text-zinc-400">{{ $unidad }}</span>
                    </div>
                </div>
            @endforeach

            <div class="sm:col-span-2 lg:col-span-1">
                <label class="eq-label" for="eq-tecnologia">Tecnología predominante</label>
                <input id="eq-tecnologia" type="text" class="eq-input" wire:model="tecnologia_predominante" placeholder="Ej: Electrónica, mecánica, neumática" autocomplete="off">
            </div>
        </div>
    </div>
</div>
