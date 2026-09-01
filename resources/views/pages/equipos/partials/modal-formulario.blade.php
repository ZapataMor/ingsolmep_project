{{-- Asistente por fases para registrar o editar un equipo. --}}
@php
    $totalPasos = count($pasosDefinicion);
    $definicionActual = $pasosDefinicion[$paso] ?? $pasosDefinicion[1];
@endphp

<div
    x-cloak
    x-show="$wire.mostrarFormulario"
    x-transition.opacity.duration.200ms
    {{-- El bloqueo del scroll del cuerpo lo lleva `modal-detalle`, que observa los tres modales a la vez. --}}
    x-on:keydown.escape.window="$wire.mostrarFormulario && $wire.cerrarFormulario()"
    class="fixed inset-0 z-50 overflow-y-auto bg-carbon-deep/70 p-3 backdrop-blur-sm sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="eq-modal-titulo"
>
    <div
        x-show="$wire.mostrarFormulario"
        x-transition:enter="transition duration-250 ease-out"
        x-transition:enter-start="opacity-0 translate-y-6 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        class="mx-auto my-2 w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-zinc-900 dark:ring-white/10"
    >
        {{-- Cabecera --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-carbon to-carbon-deep px-6 py-5 sm:px-8">
            <div class="pointer-events-none absolute inset-0 opacity-[0.07]" style="background-image: repeating-linear-gradient(to right, #fff 0 1px, transparent 1px 22px), repeating-linear-gradient(to bottom, #fff 0 1px, transparent 1px 22px);"></div>

            <div class="relative flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-semibold tracking-[0.14em] text-lima uppercase">
                        {{ $equipoId ? 'Edición de equipo' : 'Nuevo registro' }}
                    </p>
                    <h2 id="eq-modal-titulo" class="mt-1 text-xl font-bold text-white">
                        {{ $equipoId ? 'Editar equipo del inventario' : 'Registrar equipo biomédico' }}
                    </h2>
                    <p class="mt-1 text-[12.5px] text-zinc-400">
                        Fase {{ $paso }} de {{ $totalPasos }} · {{ $definicionActual['titulo'] }}
                    </p>
                </div>

                <button type="button" class="eq-icon-btn !text-zinc-400 hover:!bg-white/10 hover:!text-white" wire:click="cerrarFormulario" title="Cerrar">
                    <flux:icon name="x-mark" variant="mini" class="size-5" />
                </button>
            </div>

            {{-- Línea de tiempo de fases --}}
            <ol class="relative mt-6 flex items-center">
                @foreach ($pasosDefinicion as $numero => $definicion)
                    @php
                        $completado = $numero < $paso;
                        $actual = $numero === $paso;
                        $alcanzable = $numero <= $pasoMaximo;
                    @endphp

                    <li class="flex flex-1 items-center last:flex-none">
                        <button
                            type="button"
                            wire:click="irAPaso({{ $numero }})"
                            @disabled(! $alcanzable)
                            class="group flex shrink-0 items-center gap-2.5 rounded-xl px-1 py-1 text-left transition duration-200 disabled:cursor-not-allowed enabled:cursor-pointer enabled:hover:scale-[1.03]"
                            title="{{ $definicion['titulo'] }}"
                        >
                            <span @class([
                                'eq-step-dot',
                                'border-lima bg-lima text-carbon-deep shadow-lg shadow-lima/30' => $completado,
                                'border-lima bg-carbon-deep text-lima ring-4 ring-lima/25' => $actual,
                                'border-white/20 bg-white/5 text-zinc-500' => ! $completado && ! $actual,
                            ])>
                                @if ($completado)
                                    <flux:icon name="check" variant="mini" class="size-4" />
                                @else
                                    <flux:icon name="{{ $definicion['icono'] }}" variant="mini" class="size-4" />
                                @endif
                            </span>

                            <span @class([
                                'hidden text-[12px] font-semibold whitespace-nowrap lg:block',
                                'text-lima' => $actual,
                                'text-zinc-300' => $completado,
                                'text-zinc-500' => ! $completado && ! $actual,
                            ])>{{ $definicion['titulo'] }}</span>
                        </button>

                        @unless ($loop->last)
                            <span @class([
                                'mx-2 h-0.5 flex-1 rounded-full transition-all duration-500',
                                'bg-lima' => $completado,
                                'bg-white/10' => ! $completado,
                            ])></span>
                        @endunless
                    </li>
                @endforeach
            </ol>
        </div>

        {{-- Cuerpo --}}
        <form wire:submit="guardar">
            <div class="max-h-[58vh] overflow-y-auto px-6 py-6 sm:px-8">
                @if ($errors->any())
                    <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-[13px] text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300" role="alert">
                        <flux:icon name="exclamation-triangle" variant="mini" class="mt-0.5 size-4 shrink-0" />
                        <div>
                            <p class="font-semibold">Revise los datos de esta fase</p>
                            <ul class="mt-1 list-inside list-disc space-y-0.5">
                                @foreach ($errors->all() as $mensaje)
                                    <li>{{ $mensaje }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <div wire:key="paso-{{ $paso }}">
                    @if ($paso === 1)
                        @include('pages.equipos.partials.paso-general')
                    @elseif ($paso === 2)
                        @include('pages.equipos.partials.paso-tecnicas')
                    @elseif ($paso === 3)
                        @include('pages.equipos.partials.paso-subtareas')
                    @elseif ($paso === 4)
                        @include('pages.equipos.partials.paso-accesorios')
                    @else
                        @include('pages.equipos.partials.paso-observaciones')
                    @endif
                </div>
            </div>

            {{-- Pie --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 bg-zinc-50/80 px-6 py-4 sm:px-8 dark:border-zinc-800 dark:bg-zinc-800/40">
                <div class="flex items-center gap-1.5">
                    @foreach ($pasosDefinicion as $numero => $definicion)
                        <span @class([
                            'h-1.5 rounded-full transition-all duration-300',
                            'w-7 bg-lima' => $numero === $paso,
                            'w-1.5 bg-lima/50' => $numero < $paso,
                            'w-1.5 bg-zinc-300 dark:bg-zinc-700' => $numero > $paso,
                        ])></span>
                    @endforeach
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" class="eq-btn eq-btn-ghost" wire:click="cerrarFormulario">Cancelar</button>

                    @if ($paso > 1)
                        <button type="button" class="eq-btn eq-btn-ghost" wire:click="anterior">
                            <flux:icon name="chevron-left" variant="micro" class="size-3.5" />
                            Anterior
                        </button>
                    @endif

                    @if ($paso < $totalPasos)
                        <button type="button" class="eq-btn eq-btn-primary" wire:click="siguiente">
                            Siguiente
                            <flux:icon name="chevron-right" variant="micro" class="size-3.5" />
                        </button>
                    @else
                        <button type="submit" class="eq-btn eq-btn-accent" wire:loading.attr="disabled" wire:target="guardar">
                            <flux:icon name="check" variant="mini" class="size-4" wire:loading.remove wire:target="guardar" />
                            <flux:icon name="arrow-path" variant="mini" class="size-4 animate-spin" wire:loading wire:target="guardar" />
                            {{ $equipoId ? 'Guardar cambios' : 'Guardar equipo' }}
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
