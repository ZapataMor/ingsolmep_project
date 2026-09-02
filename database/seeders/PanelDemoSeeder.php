<?php

namespace Database\Seeders;

use App\Models\Equipo;
use App\Models\Mantenimiento;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

/**
 * Historia de doce meses de órdenes sobre el inventario existente.
 *
 * El panel principal no se puede revisar contra una base con cuatro órdenes
 * ejecutadas: la bandeja de atención sale en cero, las dos gráficas salen
 * vacías y no hay forma de comprobar que cada cifra coincide con el listado
 * que enlaza. Este seeder fabrica esa historia y deja sembrado al menos un
 * caso de cada grupo de la bandeja.
 *
 * Es determinista: la misma base de equipos produce siempre las mismas
 * órdenes, así los números del panel se pueden verificar a mano.
 */
class PanelDemoSeeder extends Seeder
{
    /** Técnicos entre los que se reparten las órdenes. */
    private const TECNICOS = [
        'Mario Ramírez', 'Yeison Cuadrado', 'Laura Peñaranda', 'Andrés Mejía',
    ];

    public function run(): void
    {
        $equipos = Equipo::query()->orderBy('id')->get();

        if ($equipos->isEmpty()) {
            $this->command->warn('Sin equipos en el inventario: no hay sobre qué generar órdenes.');

            return;
        }

        $hoy = Date::today();

        foreach ($equipos as $indice => $equipo) {
            // La semilla se deriva del identificador del equipo: cada uno
            // recibe siempre la misma historia, corra el seeder cuando corra.
            mt_srand($equipo->id * 7919);

            $this->historial($equipo, $hoy, $indice);
            $this->cronogramaDelMes($equipo, $hoy, $indice);
            $this->situacionesAbiertas($equipo, $hoy, $indice);
            $this->garantia($equipo, $hoy, $indice);
        }

        mt_srand();

        $this->refrescarUltimoMantenimiento();
        $this->marcarFueraDeServicio();

        $this->command->info('Historia de doce meses generada sobre '.$equipos->count().' equipos.');
    }

    /**
     * Preventivos trimestrales de los últimos doce meses, ya ejecutados. Uno de
     * cada cinco deja una novedad reportada por el técnico.
     */
    private function historial(Equipo $equipo, CarbonInterface $hoy, int $indice): void
    {
        // Unos pocos equipos quedan fuera de toda rutina reciente: son los que
        // alimentan «sin mantenimiento hace más de seis meses».
        $meses = $this->abandonado($indice) ? [12, 9] : [12, 9, 6, 3];

        foreach ($meses as $mesesAtras) {
            $programada = $hoy->copy()->subMonths($mesesAtras)->startOfMonth()->addDays(mt_rand(2, 24));
            $ejecucion = $programada->copy()->addDays(mt_rand(0, 4));

            // La última rutina de los equipos sin seguimiento lleva novedad
            // siempre: son los que sostienen la bandeja de atención.
            $conNovedad = mt_rand(1, 5) === 1 || ($mesesAtras === 3 && $indice % 4 === 0);

            $this->orden($equipo, [
                'tipo' => 'preventivo',
                'estado' => 'ejecutado',
                'fecha_programada' => $programada,
                'fecha_ejecucion' => $ejecucion,
                'presenta_novedad' => $conNovedad,
                'novedad' => $conNovedad ? $this->novedad() : null,
                'descripcion' => 'Rutina preventiva trimestral según protocolo del equipo.',
            ]);

            // El correctivo de seguimiento se abre casi siempre. En uno de cada
            // cuatro equipos no, y ese hueco es justo lo que el panel persigue.
            if ($conNovedad && $indice % 4 !== 0) {
                $this->orden($equipo, [
                    'tipo' => 'correctivo',
                    'estado' => 'ejecutado',
                    'prioridad' => 'Media',
                    'fecha_programada' => $ejecucion->copy()->addDays(mt_rand(3, 20)),
                    'fecha_ejecucion' => $ejecucion->copy()->addDays(mt_rand(21, 28)),
                    'motivo' => 'Novedad reportada en la rutina preventiva.',
                    'descripcion' => 'Atención de la novedad reportada en la rutina preventiva.',
                ]);
            }
        }

        // Correctivos sueltos por falla reportada, repartidos por el año.
        $sueltos = $this->abandonado($indice) ? 0 : mt_rand(0, 3);

        for ($numero = 0; $numero < $sueltos; $numero++) {
            $programada = $hoy->copy()->subDays(mt_rand(40, 330));

            $this->orden($equipo, [
                'tipo' => 'correctivo',
                'estado' => 'ejecutado',
                'prioridad' => ['Alta', 'Media', 'Baja'][mt_rand(0, 2)],
                'fecha_programada' => $programada,
                'fecha_ejecucion' => $programada->copy()->addDays(mt_rand(0, 6)),
                'motivo' => $this->falla(),
                'descripcion' => 'Diagnóstico, reemplazo de la parte afectada y prueba de funcionamiento.',
            ]);
        }
    }

    /**
     * Cronograma del mes en curso y del anterior, que son los dos que alimentan
     * el indicador de cumplimiento.
     */
    private function cronogramaDelMes(Equipo $equipo, CarbonInterface $hoy, int $indice): void
    {
        if ($this->abandonado($indice)) {
            return;
        }

        foreach ([1, 0] as $mesesAtras) {
            $mes = $hoy->copy()->subMonths($mesesAtras);

            // El mes en curso se programa alrededor del día de hoy y no a lo
            // largo de todo el mes: de otro modo, los primeros días el
            // cumplimiento saldría en cero porque nada ha vencido todavía.
            $tope = $mesesAtras === 1
                ? $mes->daysInMonth - 1
                : min($mes->daysInMonth - 1, $hoy->day + 4);

            $programada = $mes->copy()->startOfMonth()->addDays(mt_rand(0, max(0, $tope)));

            // Una institución entera se queda sin arrancar el mes en curso: es
            // el caso de «cronograma sin iniciar» de la bandeja.
            $institucionRezagada = $mesesAtras === 0
                && $equipo->empresa_id !== null
                && $equipo->empresa_id % 4 === 1;

            $ejecutada = ! $institucionRezagada
                && $programada->lte($hoy)
                && mt_rand(1, 100) <= ($mesesAtras === 1 ? 91 : 87);

            $this->orden($equipo, [
                'tipo' => 'preventivo',
                'estado' => $ejecutada ? 'ejecutado' : 'programado',
                'fecha_programada' => $programada,
                'fecha_ejecucion' => $ejecutada ? $programada->copy()->addDays(mt_rand(0, 3)) : null,
                'descripcion' => 'Rutina preventiva del cronograma mensual.',
            ]);
        }
    }

    /**
     * Órdenes que siguen abiertas hoy: las vencidas del indicador y los
     * correctivos estancados de la bandeja.
     */
    private function situacionesAbiertas(Equipo $equipo, CarbonInterface $hoy, int $indice): void
    {
        // Correctivo abierto hace bastante más de quince días.
        if ($indice % 5 === 0) {
            $this->orden($equipo, [
                'tipo' => 'correctivo',
                'estado' => $indice % 10 === 0 ? 'en_proceso' : 'programado',
                'prioridad' => 'Alta',
                'fecha_programada' => $hoy->copy()->subDays(mt_rand(18, 70)),
                'motivo' => $this->falla(),
                'descripcion' => 'Pendiente de repuesto importado.',
            ]);
        }

        // Preventivo vencido: programado para una fecha que ya pasó y todavía
        // sin ejecutar.
        if ($indice % 6 === 2) {
            $this->orden($equipo, [
                'tipo' => 'preventivo',
                'estado' => 'programado',
                'fecha_programada' => $hoy->copy()->subDays(mt_rand(3, 25)),
                'descripcion' => 'Rutina preventiva reprogramada por no disponibilidad del servicio.',
            ]);
        }
    }

    /**
     * Equipos que llevan más de medio año fuera de toda rutina. Existen en la
     * operación real —los que nadie reclama— y son el caso que persigue la
     * bandeja de «sin mantenimiento».
     */
    private function abandonado(int $indice): bool
    {
        return $indice % 11 === 7;
    }

    /** Garantías vigentes, unas cuantas a punto de vencer. */
    private function garantia(Equipo $equipo, CarbonInterface $hoy, int $indice): void
    {
        if ($equipo->garantia_vence !== null) {
            return;
        }

        $vence = match (true) {
            $indice % 9 === 3 => $hoy->copy()->addDays(mt_rand(5, 58)),
            $indice % 9 === 5 => $hoy->copy()->addMonths(mt_rand(8, 30)),
            default => null,
        };

        if ($vence !== null) {
            $equipo->fill(['garantia_vence' => $vence->toDateString()])->save();
        }
    }

    /**
     * Deja fuera de servicio los equipos con un correctivo de prioridad alta
     * todavía sin cerrar: es lo que significa el estado en la operación real.
     */
    private function marcarFueraDeServicio(): void
    {
        $parados = Mantenimiento::query()
            ->abiertos()
            ->where('tipo', 'correctivo')
            ->where('prioridad', 'Alta')
            ->distinct()
            ->orderBy('equipo_id')
            ->limit(6)
            ->pluck('equipo_id');

        Equipo::query()->whereIn('id', $parados)->update(['estado_operativo' => 'fuera_servicio']);
    }

    /**
     * Recalcula la fecha del último mantenimiento de cada equipo a partir de
     * sus órdenes ejecutadas. La columna es un denormalizado y tras sembrar hay
     * que dejarla coherente con lo sembrado.
     */
    private function refrescarUltimoMantenimiento(): void
    {
        $ultimas = Mantenimiento::query()
            ->where('estado', 'ejecutado')
            ->whereNotNull('fecha_ejecucion')
            ->selectRaw('equipo_id, MAX(fecha_ejecucion) as ultima')
            ->groupBy('equipo_id')
            ->pluck('ultima', 'equipo_id');

        foreach ($ultimas as $equipoId => $ultima) {
            Equipo::query()->whereKey($equipoId)->update(['ultimo_mantenimiento' => $ultima]);
        }
    }

    /**
     * Crea la orden si el equipo no tiene ya una del mismo tipo y fecha, para
     * que volver a sembrar no duplique el historial.
     *
     * @param  array<string, mixed>  $datos
     */
    private function orden(Equipo $equipo, array $datos): Mantenimiento
    {
        return Mantenimiento::firstOrCreate(
            [
                'equipo_id' => $equipo->id,
                'tipo' => $datos['tipo'],
                'fecha_programada' => $datos['fecha_programada'],
            ],
            array_merge($datos, [
                'empresa_id' => $equipo->empresa_id,
                'tecnico' => self::TECNICOS[mt_rand(0, count(self::TECNICOS) - 1)],
                'costo' => $datos['tipo'] === 'correctivo' ? mt_rand(180, 2400) * 1000 : null,
            ]),
        );
    }

    private function novedad(): string
    {
        return [
            'Cable de alimentación con el aislamiento agrietado.',
            'Batería que no sostiene carga por encima de veinte minutos.',
            'Alarma sonora con intensidad por debajo de lo especificado.',
            'Ruido en el extractor durante el arranque.',
            'Holgura en el soporte de la pieza de mano.',
        ][mt_rand(0, 4)];
    }

    private function falla(): string
    {
        return [
            'El equipo no enciende.',
            'Lectura inestable durante la medición.',
            'Fuga en el circuito neumático.',
            'Pantalla sin respuesta al tacto.',
            'Sobrecalentamiento tras veinte minutos de uso.',
        ][mt_rand(0, 4)];
    }
}
