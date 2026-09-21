<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Mantenimiento;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Cuentas de demostración de los tres perfiles.
 *
 * Crea un usuario técnico por cada nombre que ya venía escrito a mano en las
 * órdenes de demostración y los enlaza con ellas, y un usuario de institución
 * por cada IPS del inventario. Así, al entrar con cualquiera de ellos, la
 * pantalla tiene contenido de verdad que enseñar.
 */
class UsuariosDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Contraseña común de las cuentas de demostración. */
    private const CLAVE = 'ingsolmep123';

    public function run(): void
    {
        $this->crearTecnicos();
        $this->crearInstituciones();
    }

    /**
     * Un técnico por cada nombre que aparece como responsable en las órdenes ya
     * sembradas, y el vínculo entre ambos.
     */
    private function crearTecnicos(): void
    {
        $nombres = Mantenimiento::query()
            ->whereNotNull('tecnico')
            ->where('tecnico', '<>', '')
            ->distinct()
            ->orderBy('tecnico')
            ->pluck('tecnico');

        foreach ($nombres as $nombre) {
            $tecnico = User::updateOrCreate(
                ['username' => $this->usuarioDesde($nombre)],
                [
                    'name' => $nombre,
                    'rol' => 'tecnico',
                    'email' => $this->correoDesde($nombre, 'tecnicos'),
                    'email_verified_at' => now(),
                    'activo' => true,
                    'password' => self::CLAVE,
                ],
            );

            // Las órdenes existentes pasan a tener responsable de verdad, no
            // sólo un nombre escrito.
            Mantenimiento::query()
                ->where('tecnico', $nombre)
                ->whereNull('tecnico_id')
                ->update(['tecnico_id' => $tecnico->id]);
        }
    }

    /** Una cuenta de consulta por cada IPS registrada. */
    private function crearInstituciones(): void
    {
        foreach (Empresa::orderBy('id')->get() as $empresa) {
            User::updateOrCreate(
                ['username' => $this->usuarioDesde($empresa->nombre)],
                [
                    'name' => $empresa->nombre,
                    'rol' => 'institucion',
                    'empresa_id' => $empresa->id,
                    // El correo va al dominio de pruebas a propósito: el de la
                    // empresa puede repetirse entre sedes y aquí es único.
                    'email' => $this->correoDesde($empresa->nombre, 'ips'),
                    'email_verified_at' => now(),
                    'activo' => (bool) $empresa->activo,
                    'password' => self::CLAVE,
                ],
            );
        }
    }

    private function usuarioDesde(string $nombre): string
    {
        return Str::limit(Str::slug($nombre, '.'), 40, '');
    }

    private function correoDesde(string $nombre, string $dominio): string
    {
        return Str::limit(Str::slug($nombre), 40, '').'@'.$dominio.'.ingsolmep.test';
    }
}
