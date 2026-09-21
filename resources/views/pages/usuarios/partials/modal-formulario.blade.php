{{-- Formulario de creación y edición de una cuenta de acceso. --}}
{{-- La raíz del teletransporte existe siempre: Livewire descarta un
     `@teleport` cuyo cuerpo nace vacío y ya no lo vuelve a poblar. --}}
<div class="contents">
    @if ($mostrarFormulario)
        @php
            $esMiCuenta = $usuarioId !== null && $usuarioId === auth()->id();
        @endphp

        <div
            x-data
            x-on:keydown.escape.window="$wire.confirmarCierreFormulario || $wire.intentarCerrarFormulario()"
            x-on:click.self="$wire.intentarCerrarFormulario()"
            class="eq-modal fixed inset-0 z-50 overflow-y-auto bg-carbon-deep/70 p-3 backdrop-blur-sm sm:p-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="us-modal-titulo"
        >
            <div class="eq-modal-panel mx-auto my-2 w-full max-w-3xl overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-zinc-900 dark:ring-white/10">
            {{-- Cabecera --}}
            <div class="relative overflow-hidden bg-gradient-to-br from-carbon to-carbon-deep px-6 py-5 sm:px-8">
                <div class="pointer-events-none absolute inset-0 opacity-[0.07]" style="background-image: repeating-linear-gradient(to right, #fff 0 1px, transparent 1px 22px), repeating-linear-gradient(to bottom, #fff 0 1px, transparent 1px 22px);"></div>

                <div class="relative flex items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-lima ring-1 ring-white/15">
                            <flux:icon name="{{ \App\Models\User::ICONO_ROLES[$rol] ?? 'user' }}" class="size-6" />
                        </span>

                        <div>
                            <p class="text-[11px] font-semibold tracking-[0.14em] text-lima uppercase">
                                {{ $usuarioId ? 'Edición de usuario' : 'Nuevo usuario' }}
                            </p>
                            <h2 id="us-modal-titulo" class="mt-1 text-xl font-bold text-white">
                                {{ $usuarioId ? 'Editar cuenta de acceso' : 'Crear cuenta de acceso' }}
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

                    {{-- Tipo de usuario --}}
                    <section>
                        <p class="mb-4 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="identification" class="size-4" /> Tipo de usuario
                        </p>

                        @if ($esMiCuenta)
                            <div class="mb-4 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-[12.5px] text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                                <flux:icon name="exclamation-triangle" variant="mini" class="mt-0.5 size-4 shrink-0" />
                                <p>Estás editando tu propia cuenta: el tipo de usuario y el acceso no se pueden cambiar desde aquí.</p>
                            </div>
                        @endif

                        <div class="grid gap-3 sm:grid-cols-3">
                            @foreach (\App\Models\User::ROLES as $clave => $etiqueta)
                                <label @class([
                                    'flex flex-col gap-1.5 rounded-xl border px-4 py-3 transition',
                                    'cursor-pointer hover:border-lima' => ! $esMiCuenta,
                                    'cursor-not-allowed opacity-60' => $esMiCuenta,
                                    'border-lima bg-lima-soft/40 dark:bg-lima/10' => $rol === $clave,
                                    'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' => $rol !== $clave,
                                ])>
                                    <span class="flex items-center gap-2">
                                        <input
                                            type="radio"
                                            class="size-4 accent-lima"
                                            value="{{ $clave }}"
                                            wire:model.live="rol"
                                            @disabled($esMiCuenta)
                                        >
                                        <flux:icon name="{{ \App\Models\User::ICONO_ROLES[$clave] }}" variant="mini" class="size-4 text-lima" />
                                        <span class="text-[13.5px] font-semibold text-carbon dark:text-zinc-100">{{ $etiqueta }}</span>
                                    </span>

                                    <span class="text-[11.5px] leading-snug text-zinc-500 dark:text-zinc-400">
                                        {{ \App\Models\User::DESCRIPCION_ROLES[$clave] }}
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        @error('rol') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror

                        @if ($rol === 'institucion')
                            <div class="mt-5 max-w-md">
                                <label class="eq-label" for="us-empresa">Institución <span class="eq-req">*</span></label>
                                <select id="us-empresa" class="eq-select" wire:model.live="empresa_id">
                                    <option value="">Seleccione la institución…</option>
                                    @foreach ($empresasDisponibles as $empresa)
                                        <option value="{{ $empresa->id }}">{{ $empresa->nombre }}{{ $empresa->ciudad ? ' — '.$empresa->ciudad : '' }}</option>
                                    @endforeach
                                </select>
                                <span class="eq-hint">Determina qué equipos, órdenes y reportes verá al entrar: sólo los de esta IPS.</span>
                                @error('empresa_id') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </section>

                    {{-- Datos de la persona --}}
                    <section>
                        <p class="mb-4 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="user" class="size-4" /> Datos de la persona
                        </p>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="eq-label" for="us-name">Nombre completo <span class="eq-req">*</span></label>
                                <input id="us-name" type="text" class="eq-input" wire:model="name" placeholder="Ej: Luis Carlos Castellar Fuentes" autocomplete="off">
                                <span class="eq-hint">Es el nombre que sale impreso en los reportes que firme.</span>
                                @error('name') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="eq-label" for="us-username">Nombre de usuario <span class="eq-req">*</span></label>
                                <input id="us-username" type="text" class="eq-input" wire:model="username" placeholder="Ej: luis.castellar" autocomplete="off">
                                <span class="eq-hint">Con esto entra al sistema. Sólo letras, números, punto, guion y guion bajo.</span>
                                @error('username') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="eq-label" for="us-email">Correo electrónico <span class="eq-req">*</span></label>
                                <input id="us-email" type="email" class="eq-input" wire:model="email" placeholder="correo@ejemplo.com" autocomplete="off">
                                @error('email') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="eq-label" for="us-telefono">Teléfono</label>
                                <input id="us-telefono" type="text" class="eq-input" wire:model="telefono" placeholder="Ej: 3206415286" autocomplete="off">
                                @error('telefono') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </section>

                    {{-- Contraseña --}}
                    <section>
                        <p class="mb-4 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="key" class="size-4" /> Contraseña
                        </p>

                        @if ($usuarioId)
                            <p class="mb-4 text-[12.5px] text-zinc-500 dark:text-zinc-400">
                                Deje ambos campos en blanco para conservar la contraseña actual.
                            </p>
                        @endif

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="eq-label" for="us-password">
                                    {{ $usuarioId ? 'Nueva contraseña' : 'Contraseña' }}
                                    @unless ($usuarioId) <span class="eq-req">*</span> @endunless
                                </label>
                                <input id="us-password" type="password" class="eq-input" wire:model="password" autocomplete="new-password">
                                @error('password') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="eq-label" for="us-password-confirmation">
                                    Confirmar contraseña
                                    @unless ($usuarioId) <span class="eq-req">*</span> @endunless
                                </label>
                                <input id="us-password-confirmation" type="password" class="eq-input" wire:model="password_confirmation" autocomplete="new-password">
                            </div>
                        </div>
                    </section>

                    {{-- Acceso --}}
                    <section>
                        <p class="mb-4 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                            <flux:icon name="lock-closed" class="size-4" /> Acceso al sistema
                        </p>

                        <label @class([
                            'flex max-w-md items-center gap-3 rounded-xl border border-zinc-200 bg-white px-3.5 py-2.5 transition dark:border-zinc-700 dark:bg-zinc-900',
                            'cursor-pointer hover:border-lima' => ! $esMiCuenta,
                            'cursor-not-allowed opacity-60' => $esMiCuenta,
                        ])>
                            <input type="checkbox" class="size-4 rounded accent-lima" wire:model.live="activo" @disabled($esMiCuenta)>
                            <span class="text-sm font-medium text-carbon dark:text-zinc-200">
                                @if ($activo) Puede entrar al sistema @else Sin acceso — la cuenta queda bloqueada @endif
                            </span>
                        </label>

                        <span class="eq-hint">
                            Quitar el acceso no borra la cuenta: el nombre sigue apareciendo en las órdenes y los reportes ya emitidos.
                        </span>
                    </section>
                </div>

                {{-- Pie --}}
                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-zinc-200 bg-zinc-50/80 px-6 py-4 sm:px-8 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <button type="button" class="eq-btn eq-btn-ghost" wire:click="intentarCerrarFormulario">Cancelar</button>

                    <button type="submit" class="eq-btn eq-btn-accent" wire:loading.attr="disabled" wire:target="guardar">
                        <flux:icon name="check" variant="mini" class="size-4" wire:loading.remove wire:target="guardar" />
                        <flux:icon name="arrow-path" variant="mini" class="size-4 animate-spin" wire:loading wire:target="guardar" />
                        {{ $usuarioId ? 'Guardar cambios' : 'Crear usuario' }}
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
                aria-labelledby="us-aviso-cierre-titulo"
            >
                <div class="eq-modal-panel w-full max-w-md overflow-hidden rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-black/5 dark:bg-zinc-900 dark:ring-white/10">
                    <div class="flex items-start gap-4">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
                            <flux:icon name="exclamation-triangle" class="size-5" />
                        </span>

                        <div>
                            <h2 id="us-aviso-cierre-titulo" class="text-[16px] font-bold text-carbon dark:text-white">¿Cerrar sin guardar?</h2>
                            <p class="mt-1.5 text-[13px] leading-relaxed text-zinc-500 dark:text-zinc-400">
                                Hay información digitada en {{ $usuarioId ? 'la edición de esta cuenta' : 'la nueva cuenta' }} que todavía no se ha guardado.
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
