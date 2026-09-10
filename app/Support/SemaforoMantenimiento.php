<?php

namespace App\Support;

use App\Models\Equipo;
use App\Models\Mantenimiento;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

/**
 * Cuánto le falta a un equipo para su próximo mantenimiento, resumido en lo
 * que cabe en una tarjeta: una fecha, los días que quedan y un nivel de aviso.
 *
 * La fecha sale de la orden abierta más próxima. Cuando no hay ninguna
 * programada se estima a partir del último mantenimiento y de la rutina
 * acordada con el cliente, porque un equipo sin orden no es un equipo sin
 * necesidad: es justo el que se puede quedar atrás sin que nadie lo note.
 */
final class SemaforoMantenimiento
{
    public const VERDE = 'verde';

    public const AMARILLO = 'amarillo';

    public const ROJO = 'rojo';

    /**
     * @param  CarbonInterface  $fecha  Día en el que toca el mantenimiento.
     * @param  int  $diasRestantes  Días que faltan; negativo si la fecha ya pasó.
     * @param  int  $porcentaje  Avance recorrido del intervalo, de 0 a 100.
     * @param  Mantenimiento|null  $orden  Orden que fija la fecha, si está programada.
     */
    private function __construct(
        public readonly CarbonInterface $fecha,
        public readonly int $diasRestantes,
        public readonly int $porcentaje,
        public readonly ?Mantenimiento $orden,
    ) {}

    /**
     * Construye el semáforo del equipo, o null cuando no hay con qué: sin orden
     * abierta y sin un mantenimiento anterior no existe fecha que anunciar.
     */
    public static function para(Equipo $equipo): ?self
    {
        $orden = $equipo->proximoMantenimiento;
        $ultimo = $equipo->ultimo_mantenimiento?->copy()->startOfDay();

        if ($orden instanceof Mantenimiento) {
            $fecha = $orden->fecha_programada->copy()->startOfDay();
            // El intervalo se cuenta desde la última intervención; si el equipo
            // no tiene ninguna —o es posterior a la fecha, que pasa cuando se
            // reprograma— se cuenta desde que se abrió la orden.
            $inicio = $ultimo !== null && $ultimo->lt($fecha)
                ? $ultimo
                : ($orden->created_at?->copy()->startOfDay() ?? $fecha->copy()->subDays(self::diasDeRutina()));
        } elseif ($ultimo !== null) {
            $inicio = $ultimo;
            $fecha = $ultimo->copy()->addDays(self::diasDeRutina());
        } else {
            return null;
        }

        return new self(
            fecha: $fecha,
            diasRestantes: (int) Date::today()->diffInDays($fecha, false),
            porcentaje: self::avance($inicio, $fecha),
            orden: $orden,
        );
    }

    /**
     * Nivel de aviso. Los cortes viven en configuración porque son el acuerdo
     * de servicio con el cliente, no una constante del código.
     */
    public function nivel(): string
    {
        return match (true) {
            $this->diasRestantes < (int) config('panel.umbrales.mantenimiento_critico', 3) => self::ROJO,
            $this->diasRestantes < (int) config('panel.umbrales.mantenimiento_proximo', 7) => self::AMARILLO,
            default => self::VERDE,
        };
    }

    /** La fecha ya pasó y el mantenimiento sigue sin ejecutarse. */
    public function vencido(): bool
    {
        return $this->diasRestantes < 0;
    }

    /** Hay una orden abierta que fija la fecha; si no, está estimada. */
    public function programado(): bool
    {
        return $this->orden instanceof Mantenimiento;
    }

    /** Cuánto falta, en la forma en que se dice en voz alta. */
    public function etiqueta(): string
    {
        $dias = abs($this->diasRestantes);
        $plural = $dias === 1 ? 'día' : 'días';

        return match (true) {
            $this->diasRestantes < 0 => "Vencido hace {$dias} {$plural}",
            $this->diasRestantes === 0 => 'Es hoy',
            $this->diasRestantes === 1 => 'Mañana',
            default => "En {$dias} {$plural}",
        };
    }

    /** De dónde sale la fecha, para no dar por programado lo que es estimado. */
    public function origen(): string
    {
        return $this->orden instanceof Mantenimiento
            ? 'Orden '.$this->orden->codigo().' · '.$this->orden->tipoEtiqueta()
            : 'Estimado según la rutina, sin orden programada';
    }

    /**
     * Porción recorrida del intervalo entre una intervención y la siguiente.
     * Un intervalo vencido se muestra lleno: ya no queda nada por recorrer.
     */
    private static function avance(CarbonInterface $inicio, CarbonInterface $fecha): int
    {
        $total = max(1, (int) $inicio->diffInDays($fecha, false));
        $transcurrido = (int) $inicio->diffInDays(Date::today(), false);

        return (int) round(min(max($transcurrido / $total, 0), 1) * 100);
    }

    /** Días entre mantenimientos de rutina acordados con el cliente. */
    private static function diasDeRutina(): int
    {
        return (int) config('panel.umbrales.sin_mantenimiento', 180);
    }
}
