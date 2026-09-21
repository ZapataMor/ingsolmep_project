{{-- Ficha de sólo lectura de la cuenta seleccionada en la tabla. --}}
{{-- La raíz del teletransporte existe siempre: Livewire descarta un
     `@teleport` cuyo cuerpo nace vacío y ya no lo vuelve a poblar. --}}
<div class="contents">
    @if ($usuario)
        <div
            x-data
            x-on:keydown.escape.window="$wire.cerrarDetalle()"
            x-on:click.self="$wire.cerrarDetalle()"
            class="eq-modal fixed inset-0 z-50 overflow-y-auto bg-carbon-deep/70 p-3 backdrop-blur-sm sm:p-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="us-detalle-titulo"
        >
            <div class="eq-modal-panel mx-auto my-2 w-full max-w-2xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-zinc-900 dark:ring-white/10">
                @php
                    $identificacion = [
                        'Nombre de usuario' => '@'.$usuario->username,
                        'Correo electrónico' => $usuario->email,
                        'Teléfono' => $usuario->telefono,
                        'Fecha de creación' => $usuario->created_at?->format('d/m/Y'),
                    ];
                @endphp

                {{-- Cabecera --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-carbon to-carbon-deep px-6 py-5 sm:px-8">
                    <div class="pointer-events-none absolute inset-0 opacity-[0.07]" style="background-image: repeating-linear-gradient(to right, #fff 0 1px, transparent 1px 22px), repeating-linear-gradient(to bottom, #fff 0 1px, transparent 1px 22px);"></div>

                    <div class="relative flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-4">
                            <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-white text-sm font-bold text-carbon ring-1 ring-white/15">
                                {{ $usuario->initials() }}
                            </span>

                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold tracking-[0.14em] text-lima uppercase">
                                    {{ $usuario->rolEtiqueta() }} · #{{ $usuario->id }}
                                </p>
                                <h2 id="us-detalle-titulo" class="mt-1 truncate text-xl font-bold text-white">{{ $usuario->name }}</h2>
                                <p class="mt-1 text-[12.5px] text-zinc-400">
                                    {{ $usuario->empresa?->nombre ?: 'Sin institución vinculada' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-3">
                            <span @class([
                                'eq-chip',
                                'bg-emerald-500/15 text-emerald-400' => $usuario->activo,
                                'bg-rose-500/15 text-rose-400' => ! $usuario->activo,
                            ])>
                                <span @class(['size-1.5 rounded-full', 'bg-emerald-500' => $usuario->activo, 'bg-rose-500' => ! $usuario->activo])></span>
                                {{ $usuario->activo ? 'Con acceso' : 'Sin acceso' }}
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
                            <flux:icon name="{{ \App\Models\User::ICONO_ROLES[$usuario->rol] ?? 'user' }}" class="size-4" /> Qué puede hacer
                        </p>

                        <p class="text-[13.5px] leading-relaxed text-carbon dark:text-zinc-200">
                            {{ \App\Models\User::DESCRIPCION_ROLES[$usuario->rol] ?? '—' }}
                        </p>
                    </section>

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

                    @if ($usuario->esInstitucion() && $usuario->empresa)
                        <section>
                            <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                                <flux:icon name="building-office-2" class="size-4" /> Institución
                            </p>

                            <div class="rounded-xl border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                                <p class="text-[13.5px] font-semibold text-carbon dark:text-zinc-100">{{ $usuario->empresa->nombre }}</p>
                                <p class="text-[12px] text-zinc-500 dark:text-zinc-400">
                                    NIT {{ $usuario->empresa->nit ?: '—' }} · {{ $usuario->empresa->ciudad ?: 'Sin ciudad registrada' }}
                                </p>
                            </div>
                        </section>
                    @endif

                    @if ($usuario->esTecnico())
                        <section>
                            <p class="mb-3 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                                <flux:icon name="wrench-screwdriver" class="size-4" /> Carga de trabajo
                            </p>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-xl border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                                    <p class="text-2xl font-bold text-carbon dark:text-white">{{ $usuario->mantenimientos_count }}</p>
                                    <p class="text-[11.5px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Órdenes asignadas</p>
                                </div>

                                <div class="rounded-xl border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                                    <p class="text-2xl font-bold text-lima-700 dark:text-lima">{{ $usuario->mantenimientos_abiertos_count }}</p>
                                    <p class="text-[11.5px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Todavía sin cerrar</p>
                                </div>
                            </div>

                            @if ($usuario->mantenimientos_count > 0)
                                <a href="{{ route('mantenimientos.index', ['tecnico' => $usuario->name]) }}" class="eq-enlace mt-3 inline-block" wire:navigate>
                                    Ver sus órdenes en el módulo de mantenimientos
                                </a>
                            @endif
                        </section>
                    @endif
                </div>

                {{-- Pie --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 bg-zinc-50/80 px-6 py-4 sm:px-8 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <p class="text-[12px] text-zinc-500 dark:text-zinc-400">Vista de sólo lectura.</p>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="eq-btn eq-btn-ghost" wire:click="cerrarDetalle">Cerrar</button>

                        @if ($usuario->id !== auth()->id())
                            <button
                                type="button"
                                class="eq-btn eq-btn-ghost hover:!border-rose-200 hover:!bg-rose-50 hover:!text-rose-600 dark:hover:!border-rose-500/30 dark:hover:!bg-rose-500/10 dark:hover:!text-rose-400"
                                wire:click="confirmarEliminacion({{ $usuario->id }})"
                            >
                                <flux:icon name="trash" variant="mini" class="size-4" />
                                Eliminar
                            </button>
                        @endif

                        <button type="button" class="eq-btn eq-btn-primary" wire:click="editar({{ $usuario->id }})">
                            <flux:icon name="pencil-square" variant="mini" class="size-4" />
                            Editar usuario
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
