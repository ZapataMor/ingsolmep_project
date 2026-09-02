{{-- Asignación y edición de un mantenimiento preventivo o correctivo. --}}
{{-- La raíz del teletransporte existe siempre: Livewire descarta un
     `@teleport` cuyo cuerpo nace vacío y ya no lo vuelve a poblar. --}}
<div class="contents">
    @if ($mostrarFormulario)
        @php $esCorrectivo = $tipo === 'correctivo'; @endphp

        <div
            x-data
            x-on:keydown.escape.window="$wire.confirmarCierreFormulario || $wire.intentarCerrarFormulario()"
            x-on:click.self="$wire.intentarCerrarFormulario()"
            class="eq-modal fixed inset-0 z-50 overflow-y-auto bg-carbon-deep/70 p-3 backdrop-blur-sm sm:p-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="mt-modal-titulo"
        >
            <div class="eq-modal-panel mx-auto my-2 w-full max-w-4xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-zinc-900 dark:ring-white/10">
                {{-- Cabecera --}}
                <div class="relative overflow-hidden bg-gradient-to-br from-carbon to-carbon-deep px-6 py-5 sm:px-8">
                    <div class="pointer-events-none absolute inset-0 opacity-[0.07]" style="background-image: repeating-linear-gradient(to right, #fff 0 1px, transparent 1px 22px), repeating-linear-gradient(to bottom, #fff 0 1px, transparent 1px 22px);"></div>

                    <div class="relative flex items-start justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-lima ring-1 ring-white/15">
                                <flux:icon name="wrench-screwdriver" class="size-6" />
                            </span>

                            <div>
                                <p class="text-[11px] font-semibold tracking-[0.14em] text-lima uppercase">
                                    {{ $mantenimientoId ? 'Edición de la orden' : 'Nueva asignación' }}
                                </p>
                                <h2 id="mt-modal-titulo" class="mt-1 text-xl font-bold text-white">
                                    {{ $mantenimientoId ? 'Editar mantenimiento' : 'Asignar mantenimiento a un equipo' }}
                                </h2>
                                <p class="mt-1 text-[12.5px] text-zinc-400">
                                    Los campos marcados con <span class="text-rose-400">*</span> son obligatorios.
                                </p>
                            </div>
                        </div>

                        <button type="button" class="eq-icon-btn !text-zinc-400 hover:!bg-white/10 hover:!text-white" wire:click="intentarCerrarFormulario" title="Cerrar">
                            <flux:icon name="x-mark" variant="mini" class="size-5" />
                        </button>
                    </div>
                </div>

                {{-- Cuerpo --}}
                <form wire:submit="guardar">
                    <div class="max-h-[62vh] space-y-6 overflow-y-auto px-6 py-6 sm:px-8">
                        @if ($errors->any())
                            <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-[13px] text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300" role="alert">
                                <flux:icon name="exclamation-triangle" variant="mini" class="mt-0.5 size-4 shrink-0" />
                                <div>
                                    <p class="font-semibold">Revise los datos del formulario</p>
                                    <ul class="mt-1 list-inside list-disc space-y-0.5">
                                        @foreach ($errors->all() as $mensaje)
                                            <li>{{ $mensaje }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        {{-- Tipo de mantenimiento --}}
                        <section>
                            <p class="mb-4 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                                <flux:icon name="squares-2x2" class="size-4" /> Tipo de mantenimiento
                            </p>

                            <div class="grid gap-3 sm:grid-cols-2">
                                @php
                                    $descripcionTipo = [
                                        'preventivo' => 'Rutina programada: revisión, limpieza y ajuste del equipo.',
                                        'correctivo' => 'Intervención abierta por una falla reportada del equipo.',
                                    ];
                                @endphp

                                @foreach (\App\Models\Mantenimiento::TIPOS as $clave => $etiqueta)
                                    <label @class([
                                        'flex cursor-pointer items-start gap-3 rounded-xl border px-4 py-3 transition',
                                        'border-lima bg-lima-soft/60 shadow-sm dark:border-lima dark:bg-lima/10' => $tipo === $clave,
                                        'border-zinc-200 bg-white hover:border-lima dark:border-zinc-700 dark:bg-zinc-900' => $tipo !== $clave,
                                    ])>
                                        <input type="radio" class="mt-0.5 size-4 accent-lima" value="{{ $clave }}" wire:model.live="tipo">
                                        <span>
                                            <span class="block text-sm font-semibold text-carbon dark:text-zinc-100">{{ $etiqueta }}</span>
                                            <span class="mt-0.5 block text-[12px] text-zinc-500 dark:text-zinc-400">{{ $descripcionTipo[$clave] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>

                            @error('tipo') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                        </section>

                        {{-- Equipo --}}
                        <section>
                            <p class="mb-4 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                                <flux:icon name="cpu-chip" class="size-4" /> Equipo intervenido
                            </p>

                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label class="eq-label" for="mt-buscar-equipo">Buscar equipo</label>
                                    <input
                                        id="mt-buscar-equipo"
                                        type="search"
                                        class="eq-input"
                                        placeholder="Nombre, serie, marca o empresa…"
                                        wire:model.live.debounce.400ms="buscarEquipo"
                                        autocomplete="off"
                                    >
                                    <span class="eq-hint">El desplegable muestra hasta {{ $topeEquipos }} equipos: use el buscador para acotarlo.</span>
                                </div>

                                <div>
                                    <label class="eq-label" for="mt-equipo">Equipo <span class="eq-req">*</span></label>
                                    <select id="mt-equipo" class="eq-select" wire:model.live="equipo_id">
                                        <option value="">Seleccione un equipo…</option>
                                        @foreach ($equiposDisponibles as $opcion)
                                            <option value="{{ $opcion->id }}">
                                                {{ $opcion->descripcion }}
                                                @if ($opcion->numero_serie) · S/N {{ $opcion->numero_serie }} @endif
                                                @if ($opcion->empresa) — {{ $opcion->empresa->nombre }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('equipo_id') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            @if ($equipo)
                                <div class="mt-4 flex flex-wrap items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50/70 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-800/40">
                                    <span class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-zinc-200 bg-white text-[11px] font-bold text-carbon dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                                        @if ($equipo->fotoUrl())
                                            <img src="{{ $equipo->fotoUrl() }}" alt="Foto de {{ $equipo->descripcion }}" class="size-full object-cover">
                                        @else
                                            {{ $equipo->iniciales() }}
                                        @endif
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <p class="text-[13.5px] font-semibold text-carbon dark:text-zinc-100">{{ $equipo->descripcion }}</p>
                                        <p class="text-[12px] text-zinc-500 dark:text-zinc-400">
                                            {{ $equipo->marca?->nombre ?? 'Sin marca' }}
                                            @if ($equipo->modelo) · {{ $equipo->modelo->nombre }} @endif
                                            @if ($equipo->numero_serie) · S/N {{ $equipo->numero_serie }} @endif
                                        </p>
                                        <p class="text-[12px] text-zinc-500 dark:text-zinc-400">
                                            {{ $equipo->empresa?->nombre ?? 'Sin empresa asignada' }}
                                            @if ($equipo->area) · {{ $equipo->area->nombre }} @endif
                                        </p>
                                    </div>

                                    @if ($equipo->ultimo_mantenimiento)
                                        <span class="eq-chip bg-lima-soft text-lima-700 dark:bg-lima/10 dark:text-lima">
                                            <flux:icon name="clock" variant="micro" class="size-3" />
                                            Último: {{ $equipo->ultimo_mantenimiento->format('d/m/Y') }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </section>

                        {{-- Programación --}}
                        <section>
                            <p class="mb-4 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                                <flux:icon name="calendar-days" class="size-4" /> Programación y responsable
                            </p>

                            <div class="grid gap-5 md:grid-cols-3">
                                <div>
                                    <label class="eq-label" for="mt-fecha-programada">Fecha programada <span class="eq-req">*</span></label>
                                    <input id="mt-fecha-programada" type="date" class="eq-input" wire:model="fecha_programada">
                                    @error('fecha_programada') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="eq-label" for="mt-estado">Estado de la orden <span class="eq-req">*</span></label>
                                    <select id="mt-estado" class="eq-select" wire:model.live="estado">
                                        @foreach (\App\Models\Mantenimiento::ESTADOS as $clave => $etiqueta)
                                            <option value="{{ $clave }}">{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                    @error('estado') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="eq-label" for="mt-fecha-ejecucion">
                                        Fecha de ejecución
                                        @if ($estado === 'ejecutado') <span class="eq-req">*</span> @endif
                                    </label>
                                    <input id="mt-fecha-ejecucion" type="date" class="eq-input" wire:model="fecha_ejecucion">
                                    <span class="eq-hint">Se registra al cerrar la orden como ejecutada.</span>
                                    @error('fecha_ejecucion') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="eq-label" for="mt-tecnico">Técnico responsable</label>
                                    <input id="mt-tecnico" type="text" class="eq-input" wire:model="tecnico" placeholder="Ej: Luis Zapata" autocomplete="off" list="mt-tecnicos">
                                    <datalist id="mt-tecnicos">
                                        @foreach ($tecnicosSugeridos as $sugerencia)
                                            <option value="{{ $sugerencia }}"></option>
                                        @endforeach
                                    </datalist>
                                    @error('tecnico') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="eq-label" for="mt-prioridad">Prioridad</label>
                                    <select id="mt-prioridad" class="eq-select" wire:model="prioridad">
                                        <option value="">Sin definir</option>
                                        @foreach (\App\Models\Mantenimiento::PRIORIDADES as $opcion)
                                            <option value="{{ $opcion }}">{{ $opcion }}</option>
                                        @endforeach
                                    </select>
                                    @error('prioridad') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="eq-label" for="mt-costo">Costo del servicio</label>
                                    <input id="mt-costo" type="number" step="0.01" min="0" class="eq-input" wire:model="costo" placeholder="Ej: 250000" autocomplete="off">
                                    <span class="eq-hint">Valor en pesos, incluidos repuestos.</span>
                                    @error('costo') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </section>

                        {{-- Detalle del trabajo --}}
                        <section>
                            <p class="mb-4 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                                <flux:icon name="document-check" class="size-4" /> Detalle del trabajo
                            </p>

                            <div class="space-y-5">
                                <div>
                                    <label class="eq-label" for="mt-motivo">
                                        {{ $esCorrectivo ? 'Falla reportada' : 'Motivo de la programación' }}
                                        @if ($esCorrectivo) <span class="eq-req">*</span> @endif
                                    </label>
                                    <textarea
                                        id="mt-motivo"
                                        class="eq-textarea"
                                        wire:model="motivo"
                                        placeholder="{{ $esCorrectivo ? 'Ej: el equipo no enciende y presenta olor a quemado en la fuente.' : 'Ej: rutina trimestral según cronograma del cliente.' }}"
                                    ></textarea>
                                    @error('motivo') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="eq-label" for="mt-descripcion">Trabajo a realizar o ejecutado</label>
                                    <textarea id="mt-descripcion" class="eq-textarea" wire:model="descripcion" placeholder="Actividades previstas o ejecutadas sobre el equipo."></textarea>
                                    @error('descripcion') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid gap-5 md:grid-cols-2">
                                    <div>
                                        <label class="eq-label" for="mt-repuestos">Repuestos utilizados</label>
                                        <textarea id="mt-repuestos" class="eq-textarea" wire:model="repuestos" placeholder="Ej: fusible 5A, batería 12V 7Ah."></textarea>
                                        @error('repuestos') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="eq-label" for="mt-observaciones">Observaciones</label>
                                        <textarea id="mt-observaciones" class="eq-textarea" wire:model="observaciones" placeholder="Recomendaciones o pendientes para la próxima visita."></textarea>
                                        @error('observaciones') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Novedad de la rutina preventiva. Es lo que el
                                     panel persigue: un hallazgo anotado aquí y
                                     sin correctivo después queda señalado en la
                                     bandeja de atención hasta que se atienda. --}}
                                @if (! $esCorrectivo)
                                    <div class="mt-5">
                                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-zinc-200 bg-white px-3.5 py-2.5 transition hover:border-lima dark:border-zinc-700 dark:bg-zinc-900">
                                            <input type="checkbox" class="size-4 rounded accent-lima" wire:model.live="presenta_novedad">
                                            <span class="text-sm font-medium text-carbon dark:text-zinc-200">
                                                El equipo presenta una novedad que requiere seguimiento
                                            </span>
                                        </label>

                                        @if ($presenta_novedad)
                                            <div class="mt-3">
                                                <label class="eq-label" for="mt-novedad">Novedad encontrada <span class="eq-req">*</span></label>
                                                <textarea id="mt-novedad" class="eq-textarea" wire:model="novedad" placeholder="Ej: cable de alimentación con el aislamiento agrietado."></textarea>
                                                <span class="eq-hint">Quedará pendiente en el panel hasta que se abra el correctivo que la atienda.</span>
                                                @error('novedad') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </section>

                        {{-- Rutina de subtareas: propia del preventivo --}}
                        @if (! $esCorrectivo)
                            <section>
                                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                    <p class="flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                                        <flux:icon name="wrench-screwdriver" class="size-4" /> Rutina de subtareas
                                    </p>

                                    <div class="flex items-center gap-3">
                                        <button type="button" class="eq-enlace" wire:click="marcarTodasLasSubtareas">Marcar todas</button>
                                        <button type="button" class="eq-enlace" wire:click="limpiarSubtareas">Limpiar</button>
                                    </div>
                                </div>

                                <p class="eq-hint !mt-0 mb-3">
                                    Al elegir el equipo se hereda la rutina registrada en su ficha. Marque lo que cubre esta orden.
                                </p>

                                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach (\App\Models\Equipo::SUBTAREAS as $clave => $etiqueta)
                                        <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-[12.5px] transition hover:border-lima dark:border-zinc-700 dark:bg-zinc-900">
                                            <input type="checkbox" class="size-4 rounded accent-lima" wire:model="subtareas.{{ $clave }}">
                                            <span class="text-carbon dark:text-zinc-200">{{ $etiqueta }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        {{-- Estado de accesorios --}}
                        <section>
                            <p class="mb-4 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                                <flux:icon name="squares-2x2" class="size-4" /> Estado de los accesorios
                            </p>

                            <p class="eq-hint !mt-0 mb-3">Deje en blanco los accesorios que no apliquen al equipo.</p>

                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach (\App\Models\Equipo::ACCESORIOS as $clave => $etiqueta)
                                    <div class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                                        <span class="text-[12.5px] text-carbon dark:text-zinc-200">{{ $etiqueta }}</span>

                                        <select class="eq-select !w-28 !px-2.5 !py-1.5 !text-[12px]" wire:model="accesorios_estado.{{ $clave }}" aria-label="Estado de {{ $etiqueta }}">
                                            <option value="">N/A</option>
                                            @foreach (\App\Models\Equipo::ESTADOS_ACCESORIO as $codigo => $nombre)
                                                <option value="{{ $codigo }}">{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>

                            @error('accesorios_estado.*') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                        </section>
                    </div>

                    {{-- Pie --}}
                    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-zinc-200 bg-zinc-50/80 px-6 py-4 sm:px-8 dark:border-zinc-800 dark:bg-zinc-800/40">
                        <button type="button" class="eq-btn eq-btn-ghost" wire:click="intentarCerrarFormulario">Cancelar</button>

                        <button type="submit" class="eq-btn eq-btn-accent" wire:loading.attr="disabled" wire:target="guardar">
                            <flux:icon name="check" variant="mini" class="size-4" wire:loading.remove wire:target="guardar" />
                            <flux:icon name="arrow-path" variant="mini" class="size-4 animate-spin" wire:loading wire:target="guardar" />
                            {{ $mantenimientoId ? 'Guardar cambios' : 'Asignar mantenimiento' }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- Aviso al cerrar con datos escritos sin guardar --}}
            @if ($confirmarCierreFormulario)
                <div
                    x-data
                    x-on:keydown.escape.window="$wire.continuarEditando()"
                    x-on:click.self="$wire.continuarEditando()"
                    class="eq-modal fixed inset-0 z-60 flex items-center justify-center bg-carbon-deep/70 p-4 backdrop-blur-sm"
                    role="alertdialog"
                    aria-modal="true"
                    aria-labelledby="mt-aviso-cierre-titulo"
                >
                    <div class="eq-modal-panel w-full max-w-md overflow-hidden rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-black/5 dark:bg-zinc-900 dark:ring-white/10">
                        <div class="flex items-start gap-4">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
                                <flux:icon name="exclamation-triangle" class="size-5" />
                            </span>

                            <div>
                                <h2 id="mt-aviso-cierre-titulo" class="text-[16px] font-bold text-carbon dark:text-white">¿Cerrar sin guardar?</h2>
                                <p class="mt-1.5 text-[13px] leading-relaxed text-zinc-500 dark:text-zinc-400">
                                    Hay información digitada en {{ $mantenimientoId ? 'la edición de esta orden' : 'esta asignación' }} que todavía no se ha guardado.
                                    Si cierra ahora, se perderá.
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-wrap items-center justify-end gap-2">
                            <button type="button" class="eq-btn eq-btn-ghost" wire:click="continuarEditando">Seguir editando</button>
                            <button type="button" class="eq-btn eq-btn-danger" wire:click="cerrarFormulario">Cerrar y descartar</button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
