<?php

namespace Database\Seeders;

use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Órdenes de mantenimiento de demostración, todas ejecutadas, con su reporte
 * ya emitido. Cubren los cuatro escenarios que interesan al revisar el
 * documento: preventivo y correctivo, cada uno con información breve y con
 * información extensa.
 */
class MantenimientosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = User::where('username', 'prueba.ingsolmep')->first() ?? User::first();

        foreach ($this->ordenes() as $datos) {
            $equipo = Equipo::where('numero_serie', $datos['serie'])->first();

            if ($equipo === null) {
                $this->command->warn('Sin equipo con serie '.$datos['serie'].': se omite la orden.');

                continue;
            }

            $mantenimiento = Mantenimiento::firstOrCreate(
                [
                    'equipo_id' => $equipo->id,
                    'tipo' => $datos['tipo'],
                    'fecha_programada' => $datos['fecha_programada'],
                ],
                array_merge($datos['orden'], [
                    'empresa_id' => $equipo->empresa_id,
                    'estado' => 'ejecutado',
                    'subtareas' => $datos['tipo'] === 'preventivo' ? $this->rutina($datos['subtareas'] ?? []) : null,
                ]),
            );

            // Una orden cerrada manda sobre la fecha del último mantenimiento
            // del equipo, igual que al guardarla desde la pantalla.
            if ($equipo->ultimo_mantenimiento === null || $equipo->ultimo_mantenimiento->lt($mantenimiento->fecha_ejecucion)) {
                $equipo->ultimo_mantenimiento = $mantenimiento->fecha_ejecucion;
                $equipo->save();
            }

            // Volver a sembrar no debe inflar el contador de emisiones.
            if ($mantenimiento->reporte()->doesntExist()) {
                Reporte::registrar($mantenimiento, $usuario);
            }
        }
    }

    /**
     * Rutina del equipo con las subtareas indicadas marcadas como ejecutadas.
     *
     * @param  list<string>  $hechas
     * @return array<string, bool>
     */
    private function rutina(array $hechas): array
    {
        return array_merge(
            array_fill_keys(array_keys(Equipo::SUBTAREAS), false),
            array_fill_keys($hechas, true),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function ordenes(): array
    {
        return [
            // ── 1 · Preventivo con información breve ──────────────────────
            [
                'serie' => '2018041600425',
                'tipo' => 'preventivo',
                'fecha_programada' => '2026-06-18',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'revision_panel_control'],
                'orden' => [
                    'prioridad' => 'Baja',
                    'fecha_ejecucion' => '2026-06-18',
                    'tecnico' => 'Luis Carlos Castellar Fuentes',
                    'motivo' => 'Rutina semestral programada.',
                    'descripcion' => 'Limpieza general, verificación de nivelación y contraste de pesaje con patrón de 20 kg.',
                    'observaciones' => 'Equipo funciona correctamente.',
                    'accesorios_estado' => ['cable_ac' => 'B', 'bateria' => 'B'],
                ],
            ],

            // ── 2 · Preventivo con información extensa ────────────────────
            [
                'serie' => 'HM2019C1044',
                'tipo' => 'preventivo',
                'fecha_programada' => '2026-08-05',
                'subtareas' => [
                    'prueba_funcionamiento', 'desarmado_limpieza', 'prueba_fugas', 'revision_alarma',
                    'revision_conectores', 'ajuste_sistema_electronico', 'ajuste_sistema_electrico',
                    'limpieza_tarjetas', 'ajuste_extractores', 'limpieza_ajuste_mecanicos',
                    'revision_panel_control', 'limpieza_filtros', 'revision_sistema_neumatico',
                    'cambio_accesorios',
                ],
                'orden' => [
                    'prioridad' => 'Alta',
                    'fecha_ejecucion' => '2026-08-07',
                    'tecnico' => 'Luis Carlos Castellar Fuentes',
                    'motivo' => 'Rutina semestral programada dentro del plan anual de mantenimiento preventivo del parque de equipos de soporte vital de la unidad de cuidados intensivos. La programación se adelantó dos semanas respecto al cronograma original por solicitud de la coordinación médica, que requería el equipo disponible para el pico de ocupación previsto en agosto.',
                    'descripcion' => self::TRABAJO_PREVENTIVO,
                    'repuestos' => "1 × Filtro HEPA de línea de paciente (ref. HM-FH-7720), vida útil 5.000 horas\n1 × Diafragma de silicona para válvula espiratoria (ref. HM-DS-1145)\n2 × Filtro de admisión de aire lavable (ref. HM-FA-0308)\n1 × Celda de oxígeno galvánica (ref. HM-CO2-9981), instalada con fecha de vencimiento 08/2028\n4 × Empaque tórico de silicona grado médico (ref. HM-OR-2214)\n1 × Juego de escobillas para motor del compresor interno (ref. HM-EC-5560)",
                    'observaciones' => self::OBSERVACIONES_PREVENTIVO,
                    'costo' => 3785400.00,
                    'accesorios_estado' => [
                        'cable_ac' => 'B', 'cable_ecg' => 'B', 'sensor_spo2' => 'R', 'manguera_nibp' => 'B',
                        'brazalete' => 'R', 'control' => 'B', 'chupas_precordiales' => 'M', 'pieza_mano' => 'B',
                        'transductor' => 'B', 'sensor_temperatura' => 'R', 'sensor_oxigeno' => 'B',
                        'manguera_oxigeno' => 'B', 'manguera_aire' => 'B', 'pala_ekg' => 'M', 'bateria' => 'R',
                    ],
                ],
            ],

            // ── 3 · Correctivo con información breve ──────────────────────
            [
                'serie' => 'MCH-2017-0455',
                'tipo' => 'correctivo',
                'fecha_programada' => '2026-07-02',
                'orden' => [
                    'prioridad' => 'Media',
                    'fecha_ejecucion' => '2026-07-02',
                    'tecnico' => 'Andrés Felipe Mendoza',
                    'motivo' => 'La lámpara no enciende.',
                    'descripcion' => 'Se reemplazó el interruptor de encendido y se verificó continuidad del cable de alimentación.',
                    'repuestos' => '1 × Interruptor basculante 250 V (ref. GEN-SW-0012)',
                    'costo' => 85000.00,
                    'accesorios_estado' => ['cable_ac' => 'B'],
                ],
            ],

            // ── 4 · Correctivo con información extensa ────────────────────
            [
                'serie' => 'CF-82340117',
                'tipo' => 'correctivo',
                'fecha_programada' => '2026-08-23',
                'orden' => [
                    'prioridad' => 'Alta',
                    'fecha_ejecucion' => '2026-08-26',
                    'tecnico' => 'Luis Carlos Castellar Fuentes',
                    'motivo' => self::FALLA_CORRECTIVO,
                    'descripcion' => self::TRABAJO_CORRECTIVO,
                    'repuestos' => "1 × Cable de sensor de SpO2 con conector DB9, longitud 3 m (ref. MR-CB-0092)\n1 × Conector DB9 hembra para chasis, contactos dorados (ref. MR-CN-0044)\n1 × Frasco de laca protectora para circuito impreso, 100 ml (ref. GEN-LAC-0100)\n2 × Empaque tórico de sellado del panel frontal (ref. MR-OR-1180)\n1 × Estaño con núcleo de resina sin plomo, 20 g (insumo de taller)",
                    'observaciones' => self::OBSERVACIONES_CORRECTIVO,
                    'costo' => 1284500.50,
                    'accesorios_estado' => [
                        'cable_ac' => 'B', 'cable_ecg' => 'R', 'sensor_spo2' => 'B', 'manguera_nibp' => 'B',
                        'brazalete' => 'R', 'sensor_temperatura' => 'B', 'chupas_precordiales' => 'M',
                        'pala_ekg' => 'R', 'bateria' => 'M',
                    ],
                ],
            ],
        ];
    }

    private const TRABAJO_PREVENTIVO = <<<'TXT'
    Se ejecutó el protocolo completo de mantenimiento preventivo semestral definido por el fabricante en el manual de servicio, revisión 7.2, complementado con las verificaciones adicionales que exige la resolución vigente para equipos de soporte vital clasificados en riesgo III.

    Desmontaje y limpieza: se retiró la carcasa posterior y el panel lateral izquierdo. Se aspiró el polvo acumulado en el compartimiento del compresor interno y se limpiaron las tarjetas electrónicas de control y de potencia con aire comprimido a baja presión, seguido de limpiador dieléctrico en los conectores del bus principal y del módulo de sensores de flujo. Se lavaron y secaron los dos filtros de admisión de aire y se reemplazó el filtro HEPA de la línea de paciente por haber cumplido su vida útil de 5.000 horas.

    Sistema neumático: se ejecutó prueba de fugas del circuito con presión de 60 cmH2O sostenida durante sesenta segundos, con una caída registrada de 1,2 cmH2O, valor dentro del límite de 3 cmH2O declarado por el fabricante. Se revisaron las válvulas proporcionales de oxígeno y aire, se ajustó el asiento de la válvula espiratoria y se reemplazó el diafragma de silicona por presentar deformación permanente en el borde de sellado.

    Sistema eléctrico y electrónico: se midió la resistencia de tierra de protección en 0,08 ohmios y la corriente de fuga del chasis en 38 µA en condición normal y 96 µA en condición de primer defecto, conforme a la norma IEC 60601-1, ambos por debajo de los límites permitidos. Se verificó la conmutación automática a batería interna con corte de red simulado: el equipo sostuvo la ventilación durante 47 minutos con la batería al 100 %, por encima del mínimo de 30 minutos exigido para traslados intrahospitalarios.

    Calibración y pruebas funcionales: se calibraron los sensores de flujo inspiratorio y espiratorio y la celda de oxígeno contra el analizador de gases Fluke VT650 con número de serie ANALIZADOR-VT650-2019-00114455. Se contrastaron volumen corriente, presión pico, PEEP y FiO2 en los modos de volumen control, presión control y presión soporte, con desviaciones máximas del 2,8 % frente a los valores de referencia, dentro de la tolerancia del 5 % declarada. Se verificó el disparo de todas las alarmas sonoras y visuales, incluidas desconexión de paciente, presión alta, volumen minuto bajo y falla de suministro de gases.

    Entrega: el equipo se probó durante dos horas con pulmón de prueba antes de devolverlo al servicio y se dejó constancia de la intervención en la bitácora de la unidad y en la hoja de vida del activo.
    TXT;

    private const OBSERVACIONES_PREVENTIVO = <<<'TXT'
    La batería interna conserva el 88 % de su capacidad nominal y sigue dentro de especificación, pero acumula 1.840 ciclos de carga de los 2.000 declarados por el fabricante: se recomienda presupuestar su reemplazo para el primer trimestre del próximo año antes de que comprometa la autonomía en traslados.

    El compresor interno presenta un nivel de ruido de 62 dB, tres decibeles por encima de la medición del mantenimiento anterior. No compromete el funcionamiento ni supera el límite de 65 dB, pero conviene vigilarlo en la próxima rutina porque suele anticipar desgaste en los rodamientos del motor.

    Se observó que el brazo articulado de soporte del circuito presenta juego en la rótula superior. No hace parte del alcance de este mantenimiento por ser un accesorio mecánico no crítico, pero se dejó reportado a la coordinación de la unidad para que se gestione su ajuste.
    TXT;

    private const FALLA_CORRECTIVO = <<<'TXT'
    El personal de enfermería del turno nocturno reporta que desde el 19 de agosto la pantalla del monitor se apaga de forma intermitente, con episodios de entre tres y quince segundos que se repiten varias veces por hora y sin patrón horario identificable. Durante esos episodios el equipo mantiene el registro de las curvas y no reinicia, pero la pantalla queda completamente negra y no responde al panel táctil.

    De manera simultánea se reporta que la alarma de saturación de oxígeno se dispara sin causa clínica aparente, con lecturas que caen bruscamente a valores por debajo de 80 % y se recuperan solas en pocos segundos, mientras el oxímetro de pulso portátil usado como contraste muestra saturaciones estables por encima de 95 %.

    El evento obligó a trasladar al paciente del cubículo 7 a un monitor de respaldo en dos ocasiones durante la noche del 22 de agosto. La coordinación de la unidad reportó el caso como incidente de seguridad del paciente y solicitó la revisión con carácter prioritario, por tratarse de un equipo de monitoreo en área crítica.
    TXT;

    private const TRABAJO_CORRECTIVO = <<<'TXT'
    Diagnóstico: se reprodujo la falla de pantalla en banco tras cuarenta minutos de operación continua. Con el equipo abierto se verificó que el apagado coincidía con la manipulación del arnés de video, lo que orientó la revisión al conector LVDS entre la tarjeta principal y el panel. La inspección con lupa mostró dos pines con fractura por fatiga en la soldadura y evidencia de oxidación en el alojamiento del conector, compatible con la exposición del equipo a la limpieza con soluciones no aprobadas por el fabricante.

    Respecto de la alarma de SpO2, se contrastó el canal contra el simulador de paciente Fluke ProSim 8 con número de serie SIMULADOR-PROSIM8-2020-99887766554433221100. El simulador reprodujo las caídas espurias únicamente al flexionar el cable del sensor cerca del conector DB9, confirmando conductor interno partido en el cable y descartando falla del módulo de oximetría de la tarjeta.

    Intervención: se resoldó el conector LVDS con estación de aire caliente a 320 °C, se limpió el alojamiento con limpiador dieléctrico y se aplicó laca protectora en la zona reparada. Se reemplazó por completo el cable del sensor de SpO2 y se sustituyó el conector DB9 del extremo del equipo, que presentaba holgura mecánica en dos de sus pines.

    Verificación: se ejecutaron cuatro horas de prueba de operación continua sin que se repitiera el apagado de pantalla. Se contrastaron nuevamente todos los canales contra el simulador —ECG en las siete derivaciones, presión no invasiva, saturación, temperatura y capnografía— con desviaciones máximas del 1,9 % frente a los valores de referencia. Se verificó el disparo correcto de las alarmas de saturación baja, frecuencia cardíaca alta y baja, y desconexión de sensor.

    Seguridad eléctrica: se midió corriente de fuga del chasis en 41 µA en condición normal y 104 µA en condición de primer defecto, y resistencia de tierra de protección en 0,11 ohmios, todos conforme a IEC 60601-1 y dentro de los límites permitidos. El equipo se entregó al servicio en condiciones operativas plenas.
    TXT;

    private const OBSERVACIONES_CORRECTIVO = <<<'TXT'
    La causa raíz de la oxidación del conector LVDS es la limpieza del equipo con hipoclorito de sodio, que no está autorizada por el fabricante. Se recomienda socializar con el personal de enfermería y de servicios generales el procedimiento de limpieza del manual, que admite únicamente alcohol isopropílico al 70 % o las toallas desinfectantes aprobadas por la marca; de mantenerse la práctica actual la falla reaparecerá en otros conectores del equipo.

    La batería interna conserva el 61 % de su capacidad nominal y ya no garantiza los treinta minutos de autonomía exigidos para traslados intrahospitalarios. No se reemplazó en esta intervención por no estar dentro del alcance del correctivo reportado; se recomienda programar su cambio dentro de los próximos noventa días.

    Se recomienda además revisar los otros tres monitores de la misma marca y modelo instalados en la unidad, porque comparten el mismo protocolo de limpieza y es probable que presenten el mismo deterioro en fase temprana.
    TXT;
}
