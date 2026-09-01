{{-- Ficha de sólo lectura de la empresa seleccionada en la tabla. --}}
{{-- La raíz del teletransporte existe siempre: Livewire descarta un
     `@teleport` cuyo cuerpo nace vacío y ya no lo vuelve a poblar. --}}
<div class="contents">
    @if ($empresa)
        <div
            x-data
            x-on:keydown.escape.window="$wire.cerrarDetalle()"
            x-on:click.self="$wire.cerrarDetalle()"
            class="eq-modal fixed inset-0 z-50 overflow-y-auto bg-carbon-deep/70 p-3 backdrop-blur-sm sm:p-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="em-detalle-titulo"
        >
            <div class="eq-modal-panel mx-auto my-2 w-full max-w-3xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-zinc-900 dark:ring-white/10">
                @php
                    $identificacion = [
                        'NIT' => $empresa->nit,
                        'Ciudad' => $empresa->ciudad,
                        'Dirección' => $empresa->direccion,
                        'Fecha de registro' => $empresa->created_at?->format('d/m/Y'),
                    ];

                    $contacto = [
                        'Correo electrónico' => $empresa->email,
                        'Celular' => $empresa->celular,
                        'Teléfono fijo' => $empresa->telefono,
                        'WhatsApp' => $empresa->whatsapp,
                    ];
                @endphp

                {{-- Cabecera --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-carbon to-carbon-deep px-6 py-5 sm:px-8">
                    <div class="pointer-events-none absolute inset-0 opacity-[0.07]" style="background-image: repeating-linear-gradient(to right, #fff 0 1px, transparent 1px 22px), repeating-linear-gradient(to bottom, #fff 0 1px, transparent 1px 22px);"></div>

                    <div class="relative flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-4">
                            <span class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white text-sm font-bold text-carbon ring-1 ring-white/15">
                                @if ($empresa->logoUrl())
                                    <img src="{{ $empresa->logoUrl() }}" alt="Logo de {{ $empresa->nombre }}" class="size-full object-contain p-1.5">
                                @else
                                    {{ $empresa->iniciales() }}
                                @endif
                            </span>

                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold tracking-[0.14em] text-lima uppercase">Ficha de la empresa · #{{ $empresa->id }}</p>
                                <h2 id="em-detalle-titulo" class="mt-1 truncate text-xl font-bold text-white">{{ $empresa->nombre }}</h2>
                                <p class="mt-1 text-[12.5px] text-zinc-400">
                                    NIT {{ $empresa->nit ?: '—' }} · {{ $empresa->ciudad ?: 'Sin ciudad registrada' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-3">
                            <span @class([
                                'eq-chip',
                                'bg-emerald-500/15 text-emerald-400' => $empresa->activo,
                                'bg-rose-500/15 text-rose-400' => ! $empresa->activo,
                            ])>
                                <span @class(['size-1.5 rounded-full', 'bg-emerald-500' => $empresa->activo, 'bg-rose-500' => ! $empresa->activo])></span>
                                {{ $empresa->activo ? 'Activo' : 'Inactivo' }}
                            </span>

                            <button type="button" class="eq-icon-btn !text-zinc-400 hover:!bg-white/10 hover:!text-white" wire:click="cerrarDetalle" title="Cerrar">
                                <flux:icon name="x-mark" variant="mini" class="size-5" />
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Cuerpo --}}
                <div class="max-h-[60vh] space-y-6 overflow-y-auto px-6 py-6 sm:px-8">
                    <section>
                        <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="information-circle" class="size-4" /> Identificación
                        </p>

                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                            @foreach ($identificacion as $etiqueta => $valor)
                                <div>
                                    <dt class="text-[11px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $etiqueta }}</dt>
                                    <dd class="mt-0.5 text-[13.5px] font-medium text-carbon dark:text-zinc-100">{{ filled($valor) ? $valor : '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>

                    <section>
                        <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="phone" class="size-4" /> Contacto
                        </p>

                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                            @foreach ($contacto as $etiqueta => $valor)
                                <div>
                                    <dt class="text-[11px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $etiqueta }}</dt>
                                    <dd class="mt-0.5 text-[13.5px] font-medium text-carbon dark:text-zinc-100">{{ filled($valor) ? $valor : '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @if ($empresa->email)
                                <a href="mailto:{{ $empresa->email }}" class="eq-btn eq-btn-ghost !px-3 !py-1.5 !text-[12px]">
                                    <flux:icon name="envelope" variant="micro" class="size-3.5" />
                                    Escribir correo
                                </a>
                            @endif

                            @if ($empresa->whatsappUrl())
                                <a href="{{ $empresa->whatsappUrl() }}" target="_blank" rel="noopener" class="eq-btn eq-btn-ghost !px-3 !py-1.5 !text-[12px]">
                                    <flux:icon name="chat-bubble-left-right" variant="micro" class="size-3.5 text-emerald-500" />
                                    Abrir WhatsApp
                                </a>
                            @endif

                            @if ($empresa->celular)
                                <a href="tel:{{ $empresa->celular }}" class="eq-btn eq-btn-ghost !px-3 !py-1.5 !text-[12px]">
                                    <flux:icon name="phone" variant="micro" class="size-3.5" />
                                    Llamar
                                </a>
                            @endif
                        </div>
                    </section>

                    <section>
                        <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="cpu-chip" class="size-4" />
                            Inventario asignado
                            <span class="text-zinc-400 normal-case">
                                ({{ $empresa->equipos_count }} {{ $empresa->equipos_count === 1 ? 'equipo' : 'equipos' }})
                            </span>
                        </p>

                        <div class="flex flex-wrap gap-2">
                            @forelse ($empresa->areas as $area)
                                <span class="eq-chip bg-lima-soft text-lima-700 dark:bg-lima/10 dark:text-lima">
                                    <flux:icon name="map-pin" variant="micro" class="size-3" />
                                    {{ $area->nombre }}
                                    <span class="font-bold">· {{ $area->equipos_count }}</span>
                                </span>
                            @empty
                                <span class="text-[13px] text-zinc-500 dark:text-zinc-400">
                                    Todavía no hay áreas ni servicios registrados. Se crean al asignar equipos a esta empresa.
                                </span>
                            @endforelse
                        </div>
                    </section>
                </div>

                {{-- Pie --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 bg-zinc-50/80 px-6 py-4 sm:px-8 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <p class="text-[12px] text-zinc-500 dark:text-zinc-400">Vista de sólo lectura.</p>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="eq-btn eq-btn-ghost" wire:click="cerrarDetalle">Cerrar</button>

                        <button
                            type="button"
                            class="eq-btn eq-btn-ghost hover:!border-rose-200 hover:!bg-rose-50 hover:!text-rose-600 dark:hover:!border-rose-500/30 dark:hover:!bg-rose-500/10 dark:hover:!text-rose-400"
                            wire:click="confirmarEliminacion({{ $empresa->id }})"
                        >
                            <flux:icon name="trash" variant="mini" class="size-4" />
                            Eliminar
                        </button>

                        <button type="button" class="eq-btn eq-btn-primary" wire:click="editar({{ $empresa->id }})">
                            <flux:icon name="pencil-square" variant="mini" class="size-4" />
                            Editar empresa
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
