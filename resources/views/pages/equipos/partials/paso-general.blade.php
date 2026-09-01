{{-- Fase 1: información general y asignación del equipo. --}}
<div class="grid gap-5 md:grid-cols-3">
    <div>
        <label class="eq-label" for="eq-descripcion">Nombre del equipo <span class="eq-req">*</span></label>
        <input id="eq-descripcion" type="text" class="eq-input" wire:model="descripcion" placeholder="Ej: Monitor de signos vitales" autocomplete="off">
        <span class="eq-hint">Tipo genérico del equipo.</span>
        @error('descripcion') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
    </div>

    <div>
        <div class="mb-1.5 flex items-baseline justify-between gap-2">
            <label class="eq-label !mb-0" for="eq-marca">Marca <span class="eq-req">*</span></label>
            <button type="button" class="eq-enlace" wire:click="alternarMarcaNueva">
                @if ($marcaNueva) Elegir de la lista @else + Agregar marca nueva @endif
            </button>
        </div>

        @if ($marcaNueva)
            <input id="eq-marca" type="text" class="eq-input" wire:model.live.debounce.500ms="marcaNombre" placeholder="Ej: PHILIPS" autocomplete="off">
            <span class="eq-hint">Se registrará al guardar el equipo.</span>
        @else
            <select id="eq-marca" class="eq-select" wire:model.live="marcaNombre">
                <option value="">Seleccione marca</option>
                @foreach ($marcasSugeridas as $marca)
                    <option value="{{ $marca->nombre }}">{{ $marca->nombre }}</option>
                @endforeach
            </select>
            <span class="eq-hint">Marcas ya registradas en el inventario.</span>
        @endif

        @error('marcaNombre') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
    </div>

    <div>
        <div class="mb-1.5 flex items-baseline justify-between gap-2">
            <label class="eq-label !mb-0" for="eq-modelo">Modelo <span class="eq-req">*</span></label>
            <button type="button" class="eq-enlace" wire:click="alternarModeloNuevo" @disabled($marcaNueva || $marcaNombre === '')>
                @if ($modeloNuevo) Elegir de la lista @else + Agregar modelo nuevo @endif
            </button>
        </div>

        @if ($modeloNuevo)
            <input id="eq-modelo" type="text" class="eq-input" wire:model="modeloNombre" placeholder="Ej: SE-1" autocomplete="off" @disabled($marcaNombre === '')>
        @else
            <select id="eq-modelo" class="eq-select" wire:model="modeloNombre" @disabled($marcaNombre === '')>
                <option value="">@if ($marcaNombre === '') Seleccione primero la marca @else Seleccione modelo @endif</option>
                @foreach ($modelosSugeridos as $modelo)
                    <option value="{{ $modelo->nombre }}">{{ $modelo->nombre }}</option>
                @endforeach
            </select>
        @endif

        <span class="eq-hint">@if ($marcaNombre === '') Se habilita al elegir la marca. @else Modelos de {{ $marcaNombre }}. @endif</span>

        @error('modeloNombre') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
    </div>

    <div>
        <label class="eq-label" for="eq-invima">Registro INVIMA</label>
        <input id="eq-invima" type="text" class="eq-input" wire:model="registro_invima" placeholder="Ej: 2021DM-0012345" autocomplete="off">
        @error('registro_invima') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
    </div>

    <div>
        <label class="eq-label" for="eq-riesgo">Clasificación por riesgo <span class="eq-req">*</span></label>
        <select id="eq-riesgo" class="eq-select" wire:model="clasificacion_riesgo">
            <option value="">Seleccione</option>
            @foreach (\App\Models\Equipo::RIESGOS as $clave => $etiqueta)
                <option value="{{ $clave }}">{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('clasificacion_riesgo') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
    </div>

    <div>
        <label class="eq-label" for="eq-especialidad">Clasificación por especialidad <span class="eq-req">*</span></label>
        <input id="eq-especialidad" type="text" class="eq-input" wire:model="clasificacion_especialidad" placeholder="Ej: Cardiología, Imagenología, UCI" autocomplete="off">
        @error('clasificacion_especialidad') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
    </div>

    <div>
        <label class="eq-label" for="eq-fabricante">Fabricante</label>
        <input id="eq-fabricante" type="text" class="eq-input" wire:model="fabricante" placeholder="Ej: GE Healthcare, MINDRAY" autocomplete="off">
    </div>

    <div>
        <label class="eq-label" for="eq-pais">País de origen</label>
        <input id="eq-pais" type="text" class="eq-input" wire:model="pais_origen" placeholder="Ej: Estados Unidos, Alemania" autocomplete="off">
    </div>

    <div>
        <label class="eq-label" for="eq-tel">Teléfono del fabricante</label>
        <input id="eq-tel" type="text" class="eq-input" wire:model="telefono_fabricante" placeholder="Ej: +1 800 123 456" autocomplete="off">
    </div>

    <div>
        <label class="eq-label" for="eq-adquisicion">Tipo de adquisición</label>
        <select id="eq-adquisicion" class="eq-select" wire:model="tipo_adquisicion">
            <option value="">Seleccione</option>
            @foreach (\App\Models\Equipo::TIPOS_ADQUISICION as $tipo)
                <option value="{{ $tipo }}">{{ $tipo }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="eq-label" for="eq-garantia">Vencimiento de la garantía</label>
        <input id="eq-garantia" type="date" class="eq-input" wire:model="garantia_vence">
        <span class="eq-hint">Fecha en que termina la garantía del fabricante.</span>
        @error('garantia_vence') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
    </div>

    <div>
        <label class="eq-label" for="eq-prioridad">Prioridad</label>
        <select id="eq-prioridad" class="eq-select" wire:model="prioridad">
            <option value="">Seleccione</option>
            @foreach (\App\Models\Equipo::PRIORIDADES as $nivel)
                <option value="{{ $nivel }}">{{ $nivel }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="eq-label" for="eq-serie">Número de serie</label>
        <input id="eq-serie" type="text" class="eq-input" wire:model="numero_serie" placeholder="Ej: SE13A0307C4697" autocomplete="off">
        @error('numero_serie') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
    </div>

    <div class="md:col-span-3">
        <div class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
            <p class="mb-4 flex items-center gap-2 text-[12px] font-bold tracking-wide text-signal uppercase">
                <flux:icon name="building-office-2" class="size-4" /> Asignación (opcional)
            </p>

            <div class="grid gap-5 md:grid-cols-3">
                <div>
                    <label class="eq-label" for="eq-empresa">Empresa / IPS</label>
                    <select id="eq-empresa" class="eq-select" wire:model.live="empresa_id">
                        <option value="">Sin asignar (inventario maestro)</option>
                        @foreach ($empresasDisponibles as $empresa)
                            <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <div class="mb-1.5 flex items-baseline justify-between gap-2">
                        <label class="eq-label !mb-0" for="eq-area">Área / servicio</label>
                        <button type="button" class="eq-enlace" wire:click="alternarAreaNueva" @disabled(! $empresa_id)>
                            @if ($areaNueva) Elegir de la lista @else + Agregar área nueva @endif
                        </button>
                    </div>

                    @if ($areaNueva)
                        <input id="eq-area" type="text" class="eq-input" wire:model="areaNombre" placeholder="Ej: Consultorio médico #1" autocomplete="off" @disabled(! $empresa_id)>
                    @else
                        <select id="eq-area" class="eq-select" wire:model="areaNombre" @disabled(! $empresa_id)>
                            <option value="">@if ($empresa_id) Sin área asignada @else Seleccione primero la empresa @endif</option>
                            @foreach ($areasSugeridas as $area)
                                <option value="{{ $area->nombre }}">{{ $area->nombre }}</option>
                            @endforeach
                        </select>
                    @endif

                    <span class="eq-hint">@if ($empresa_id) Áreas registradas para esta empresa. @else Seleccione primero una empresa. @endif</span>
                </div>

                <div>
                    <label class="eq-label">Estado del equipo</label>
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-zinc-200 bg-white px-3.5 py-2.5 transition hover:border-lima dark:border-zinc-700 dark:bg-zinc-900">
                        <input type="checkbox" class="size-4 rounded accent-lima" wire:model.live="activo">
                        <span class="text-sm font-medium text-carbon dark:text-zinc-200">
                            @if ($activo) Activo / en servicio @else Fuera de servicio @endif
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="md:col-span-3">
        <label class="eq-label" for="eq-obs-tec">Observaciones técnicas <span class="eq-req">*</span></label>
        <textarea id="eq-obs-tec" class="eq-textarea" wire:model="observaciones_tecnicas" placeholder="Descripción técnica del equipo, especificaciones, características especiales..."></textarea>
        @error('observaciones_tecnicas') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
    </div>

    <div class="md:col-span-3">
        <label class="eq-label" for="eq-obs-gen">Observaciones generales</label>
        <textarea id="eq-obs-gen" class="eq-textarea" wire:model="observaciones_generales" placeholder="Observaciones adicionales, recomendaciones, notas importantes..."></textarea>
    </div>

    <div class="md:col-span-3">
        <label class="eq-label">Foto del equipo</label>
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">
                @if ($foto)
                    <img src="{{ $foto->temporaryUrl() }}" alt="Vista previa" class="size-full object-cover">
                @elseif ($fotoActual)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($fotoActual) }}" alt="Foto del equipo" class="size-full object-cover">
                @else
                    <flux:icon name="photo" class="size-7 text-zinc-400" />
                @endif
            </div>

            <div class="min-w-56 flex-1">
                <input type="file" class="eq-input file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-lima file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white" wire:model="foto" accept="image/png,image/jpeg,image/gif,image/webp">
                <span class="eq-hint">Formatos aceptados: JPG, PNG, GIF, WEBP (máx. 2 MB).</span>
                <div wire:loading wire:target="foto" class="eq-hint !text-signal">Cargando imagen…</div>
                @error('foto') <span class="eq-hint !text-rose-500">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
</div>
