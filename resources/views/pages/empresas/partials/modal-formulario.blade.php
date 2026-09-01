{{-- Formulario de registro y edición de una empresa. --}}
{{-- La raíz del teletransporte existe siempre: Livewire descarta un
     `@teleport` cuyo cuerpo nace vacío y ya no lo vuelve a poblar. --}}
<div class="contents">
    @if ($mostrarFormulario)
        <div
            x-data
            x-on:keydown.escape.window="$wire.confirmarCierreFormulario || $wire.intentarCerrarFormulario()"
            x-on:click.self="$wire.intentarCerrarFormulario()"
            class="eq-modal fixed inset-0 z-50 overflow-y-auto bg-carbon-deep/70 p-3 backdrop-blur-sm sm:p-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="em-modal-titulo"
        >
            <div class="eq-modal-panel mx-auto my-2 w-full max-w-4xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-zinc-900 dark:ring-white/10">
            {{-- Cabecera --}}
            <div class="relative overflow-hidden bg-gradient-to-br from-carbon to-carbon-deep px-6 py-5 sm:px-8">
                <div class="pointer-events-none absolute inset-0 opacity-[0.07]" style="background-image: repeating-linear-gradient(to right, #fff 0 1px, transparent 1px 22px), repeating-linear-gradient(to bottom, #fff 0 1px, transparent 1px 22px);"></div>

                <div class="relative flex items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-lima ring-1 ring-white/15">
                            <flux:icon name="building-office-2" class="size-6" />
                        </span>

                        <div>
                            <p class="text-[11px] font-semibold tracking-[0.14em] text-lima uppercase">
                                {{ $empresaId ? 'Edición de empresa' : 'Nueva empresa' }}
                            </p>
                            <h2 id="em-modal-titulo" class="mt-1 text-xl font-bold text-white">
                                {{ $empresaId ? 'Editar datos de la empresa' : 'Registrar empresa o IPS' }}
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

                    {{-- Identificación --}}
                    <section>
                        <p class="mb-4 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="information-circle" class="size-4" /> Información de la empresa
                        </p>

                        <div class="mb-5">
                            <label class="eq-label">Logo de la empresa</label>

                            <div class="flex flex-wrap items-center gap-4">
                                <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                                    @if ($logo)
                                        <img src="{{ $logo->temporaryUrl() }}" alt="Vista previa del logo" class="size-full object-contain p-1.5">
                                    @elseif ($logoActual && ! $logoRetirado)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($logoActual) }}" alt="Logo actual" class="size-full object-contain p-1.5">
                                    @else
                                        <flux:icon name="photo" class="size-7 text-zinc-400" />
                                    @endif
                                </div>

                                <div class="min-w-56 flex-1">
                                    <input
                                        id="em-logo"
                                        type="file"
                                        class="eq-input file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-lima file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white"
                                        wire:model="logo"
                                        accept="image/png,image/jpeg,image/gif,image/webp"
                                    >
                                    <span class="eq-hint">Formatos: JPG, PNG, GIF, WEBP · Tamaño máximo: {{ (int) ($maxLogoKb / 1024) }} MB.</span>

                                    <div wire:loading wire:target="logo" class="eq-hint !text-signal">Cargando imagen…</div>

                                    @if (($logoActual && ! $logoRetirado) || $logo)
                                        <button type="button" class="eq-enlace mt-1" wire:click="retirarLogo">Quitar logo</button>
                                    @endif

                                    @error('logo') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="eq-label" for="em-nombre">Nombre de la empresa <span class="eq-req">*</span></label>
                                <input id="em-nombre" type="text" class="eq-input" wire:model="nombre" placeholder="Ej: I.P.S.I. ANASU AINWAA" autocomplete="off">
                                <span class="eq-hint">Razón social o nombre de la sede.</span>
                                @error('nombre') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="eq-label" for="em-nit">NIT <span class="eq-req">*</span></label>
                                <input id="em-nit" type="text" class="eq-input" wire:model="nit" placeholder="Ej: 900203322-3" autocomplete="off">
                                <span class="eq-hint">Varias sedes pueden compartir el mismo NIT.</span>
                                @error('nit') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="eq-label" for="em-email">Correo electrónico <span class="eq-req">*</span></label>
                                <input id="em-email" type="email" class="eq-input" wire:model="email" placeholder="correo@empresa.com" autocomplete="off">
                                @error('email') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="eq-label" for="em-ciudad">Ciudad</label>
                                <input id="em-ciudad" type="text" class="eq-input" wire:model="ciudad" placeholder="Ej: MAICAO - LA GUAJIRA" autocomplete="off" list="em-ciudades">
                                <datalist id="em-ciudades">
                                    @foreach ($ciudadesSugeridas as $sugerencia)
                                        <option value="{{ $sugerencia }}"></option>
                                    @endforeach
                                </datalist>
                                @error('ciudad') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="eq-label" for="em-direccion">Dirección</label>
                                <input id="em-direccion" type="text" class="eq-input" wire:model="direccion" placeholder="Ej: Calle 12 # 8-45, barrio Centro" autocomplete="off">
                                @error('direccion') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </section>

                    {{-- Contacto --}}
                    <section>
                        <p class="mb-4 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="phone" class="size-4" /> Datos de contacto
                        </p>

                        <div class="grid gap-5 md:grid-cols-3">
                            <div>
                                <label class="eq-label" for="em-celular">Celular <span class="eq-req">*</span></label>
                                <input id="em-celular" type="text" class="eq-input" wire:model="celular" placeholder="Ej: 3206415286" autocomplete="off">
                                @error('celular') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="eq-label" for="em-telefono">Teléfono fijo</label>
                                <input id="em-telefono" type="text" class="eq-input" wire:model="telefono" placeholder="Ej: 605 7290000" autocomplete="off">
                                @error('telefono') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="eq-label" for="em-whatsapp">WhatsApp</label>
                                <input id="em-whatsapp" type="text" class="eq-input" wire:model="whatsapp" placeholder="573001234567" autocomplete="off">
                                <span class="eq-hint">Código de país + número (Ej: 573001234567).</span>
                                @error('whatsapp') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </section>

                    {{-- Estado --}}
                    <section>
                        <p class="mb-4 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="check-badge" class="size-4" /> Estado del cliente
                        </p>

                        <label class="flex max-w-md cursor-pointer items-center gap-3 rounded-xl border border-zinc-200 bg-white px-3.5 py-2.5 transition hover:border-lima dark:border-zinc-700 dark:bg-zinc-900">
                            <input type="checkbox" class="size-4 rounded accent-lima" wire:model.live="activo">
                            <span class="text-sm font-medium text-carbon dark:text-zinc-200">
                                @if ($activo) Activa — con servicio vigente @else Inactiva — sin servicio vigente @endif
                            </span>
                        </label>

                        <span class="eq-hint">Las empresas inactivas se conservan con todo su historial de equipos.</span>
                    </section>
                </div>

                {{-- Pie --}}
                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-zinc-200 bg-zinc-50/80 px-6 py-4 sm:px-8 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <button type="button" class="eq-btn eq-btn-ghost" wire:click="intentarCerrarFormulario">Cancelar</button>

                    <button type="submit" class="eq-btn eq-btn-accent" wire:loading.attr="disabled" wire:target="guardar">
                        <flux:icon name="check" variant="mini" class="size-4" wire:loading.remove wire:target="guardar" />
                        <flux:icon name="arrow-path" variant="mini" class="size-4 animate-spin" wire:loading wire:target="guardar" />
                        {{ $empresaId ? 'Guardar cambios' : 'Guardar empresa' }}
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
                aria-labelledby="em-aviso-cierre-titulo"
            >
                <div class="eq-modal-panel w-full max-w-md overflow-hidden rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-black/5 dark:bg-zinc-900 dark:ring-white/10">
                    <div class="flex items-start gap-4">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
                            <flux:icon name="exclamation-triangle" class="size-5" />
                        </span>

                        <div>
                            <h2 id="em-aviso-cierre-titulo" class="text-[16px] font-bold text-carbon dark:text-white">¿Cerrar sin guardar?</h2>
                            <p class="mt-1.5 text-[13px] leading-relaxed text-zinc-500 dark:text-zinc-400">
                                Hay información digitada en {{ $empresaId ? 'la edición de esta empresa' : 'el registro de esta empresa' }} que todavía no se ha guardado.
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
