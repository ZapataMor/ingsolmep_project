{{-- Fase 5: observaciones por defecto para órdenes de trabajo y confirmación. --}}
<div class="space-y-6">
    <div>
        <label class="eq-label" for="eq-obs-ot">Texto por defecto para órdenes de trabajo</label>
        <textarea id="eq-obs-ot" class="eq-textarea min-h-28" wire:model="observaciones_ot" placeholder="Texto que aparecerá automáticamente en las órdenes de trabajo de este equipo..."></textarea>
        <span class="eq-hint">Se precargará al crear una OT para este equipo.</span>
    </div>

    <div>
        <label class="eq-label" for="eq-mantenimiento">Nota de mantenimiento</label>
        <textarea id="eq-mantenimiento" class="eq-textarea min-h-24" wire:model="mantenimiento" placeholder="Ej: Deshabilitado en diciembre 2025 después de actualización de inventario."></textarea>
        <span class="eq-hint">Es la columna «Mantenimiento» del listado.</span>
    </div>

    <div class="rounded-2xl border border-lima/40 bg-lima-soft/70 p-5 dark:border-lima/25 dark:bg-lima/5">
        <p class="mb-4 flex items-center gap-2 text-[12px] font-bold tracking-wide text-lima-700 uppercase dark:text-lima">
            <flux:icon name="clipboard-document-check" class="size-4" /> Resumen del registro
        </p>

        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
            @php
                $resumen = [
                    'Equipo' => $descripcion ?: '—',
                    'Marca / modelo' => trim(($marcaNombre ?: '—').' / '.($modeloNombre ?: '—')),
                    'Serie' => $numero_serie ?: '—',
                    'Riesgo' => $clasificacion_riesgo ?: '—',
                    'Especialidad' => $clasificacion_especialidad ?: '—',
                    'Empresa' => $nombreEmpresa ?: 'Sin asignar',
                    'Área' => $areaNombre ?: '—',
                    'Suministro' => \App\Models\Equipo::SUMINISTROS[$suministro_electrico] ?? '—',
                    'Subtareas marcadas' => collect($subtareas)->filter()->count().' de '.count(\App\Models\Equipo::SUBTAREAS),
                    'Accesorios evaluados' => collect($accesorios_estado)->filter()->count().' de '.count(\App\Models\Equipo::ACCESORIOS),
                    'Estado' => $activo ? 'Activo' : 'Fuera de servicio',
                ];
            @endphp

            @foreach ($resumen as $etiqueta => $valor)
                <div>
                    <dt class="text-[11px] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $etiqueta }}</dt>
                    <dd class="mt-0.5 text-[13.5px] font-medium text-carbon dark:text-zinc-100">{{ $valor }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</div>
