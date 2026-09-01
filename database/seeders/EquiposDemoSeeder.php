<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\Modelo;
use Illuminate\Database\Seeder;

/**
 * Inventario de demostración: clientes, áreas y equipos biomédicos
 * representativos de los que atiende INGSOLMEP en La Guajira.
 */
class EquiposDemoSeeder extends Seeder
{
    public function run(): void
    {
        $empresas = [];

        foreach ($this->empresas() as $datos) {
            $empresa = Empresa::firstOrCreate(['nombre' => $datos['nombre']], $datos);
            $empresas[$datos['nombre']] = $empresa;
        }

        foreach ($this->equipos() as $datos) {
            $marca = Marca::firstOrCreate(['nombre' => $datos['marca']]);
            $modelo = Modelo::firstOrCreate(['marca_id' => $marca->id, 'nombre' => $datos['modelo']]);

            $empresa = $datos['empresa'] ? $empresas[$datos['empresa']] : null;

            $area = ($empresa && $datos['area'])
                ? Area::firstOrCreate(['empresa_id' => $empresa->id, 'nombre' => $datos['area']])
                : null;

            Equipo::firstOrCreate(
                [
                    'descripcion' => $datos['descripcion'],
                    'modelo_id' => $modelo->id,
                    'numero_serie' => $datos['serie'],
                ],
                [
                    'empresa_id' => $empresa?->id,
                    'area_id' => $area?->id,
                    'marca_id' => $marca->id,
                    'registro_invima' => $datos['invima'],
                    'clasificacion_riesgo' => $datos['riesgo'],
                    'clasificacion_especialidad' => $datos['especialidad'],
                    'fabricante' => $datos['fabricante'],
                    'pais_origen' => $datos['pais'],
                    'tipo_adquisicion' => $datos['adquisicion'],
                    'prioridad' => $datos['prioridad'],
                    'observaciones_tecnicas' => $datos['tecnicas'],
                    'observaciones_generales' => $datos['generales'] ?? null,
                    'mantenimiento' => $datos['mantenimiento'] ?? null,
                    'activo' => $datos['activo'],
                    'suministro_electrico' => $datos['suministro'],
                    'voltaje' => $datos['voltaje'] ?? null,
                    'frecuencia' => $datos['frecuencia'] ?? null,
                    'potencia' => $datos['potencia'] ?? null,
                    'peso' => $datos['peso'] ?? null,
                    'tecnologia_predominante' => $datos['tecnologia'],
                    'subtareas' => $this->subtareas($datos['subtareas']),
                    'accesorios_estado' => $datos['accesorios'] ?? [],
                    'componentes' => $datos['componentes'] ?? null,
                    'observaciones_ot' => $datos['ot'] ?? null,
                ],
            );
        }
    }

    /**
     * Convierte una lista de claves marcadas en el mapa completo de subtareas.
     *
     * @param  list<string>  $marcadas
     * @return array<string, bool>
     */
    private function subtareas(array $marcadas): array
    {
        $mapa = array_fill_keys(array_keys(Equipo::SUBTAREAS), false);

        foreach ($marcadas as $clave) {
            $mapa[$clave] = true;
        }

        return $mapa;
    }

    /** @return list<array<string, mixed>> */
    private function empresas(): array
    {
        return [
            [
                'nombre' => 'I.P.S. SALUD INTEGRAL H&B',
                'nit' => '900.315.442-1',
                'ciudad' => 'Riohacha',
                'direccion' => 'Calle 15 # 7-42',
                'telefono' => '+57 605 728 4410',
            ],
            [
                'nombre' => 'CLÍNICA RIOHACHA S.A.S.',
                'nit' => '892.115.009-7',
                'ciudad' => 'Riohacha',
                'direccion' => 'Carrera 7 # 12-55',
                'telefono' => '+57 605 727 3300',
            ],
            [
                'nombre' => 'E.S.E. HOSPITAL NUESTRA SEÑORA DE LOS REMEDIOS',
                'nit' => '892.115.006-1',
                'ciudad' => 'Riohacha',
                'direccion' => 'Calle 12 # 3-05',
                'telefono' => '+57 605 727 2222',
            ],
            [
                'nombre' => 'IPS INDÍGENA ANAS WAYUU',
                'nit' => '839.000.457-3',
                'ciudad' => 'Maicao',
                'direccion' => 'Carrera 10 # 14-30',
                'telefono' => '+57 605 726 1188',
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function equipos(): array
    {
        $hb = 'I.P.S. SALUD INTEGRAL H&B';
        $clinica = 'CLÍNICA RIOHACHA S.A.S.';
        $hospital = 'E.S.E. HOSPITAL NUESTRA SEÑORA DE LOS REMEDIOS';
        $anas = 'IPS INDÍGENA ANAS WAYUU';

        return [
            [
                'descripcion' => 'BALANZA DIGITAL DE PIE', 'empresa' => $hb, 'area' => 'FISIOTERAPIA',
                'marca' => 'GMD', 'modelo' => 'GMD-BD 1522', 'serie' => '2018041600425',
                'invima' => '2016DM-0014521', 'riesgo' => 'I', 'especialidad' => 'Apoyo diagnóstico',
                'fabricante' => 'GMD', 'pais' => 'China', 'adquisicion' => 'Compra', 'prioridad' => 'Baja',
                'tecnicas' => 'Equipo electrónico. Capacidad 200 kg, división 100 g, plataforma en vidrio templado.',
                'mantenimiento' => 'NA', 'activo' => true, 'suministro' => 'dc', 'voltaje' => '3',
                'peso' => '2.4', 'tecnologia' => 'Electrónica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'revision_conectores'],
                'accesorios' => ['bateria' => 'B'],
                'componentes' => 'Plataforma de pesaje, display LCD, batería 9V.',
            ],
            [
                'descripcion' => 'CAMILLA', 'empresa' => $hb, 'area' => 'CONSULTORIO MEDICO #1',
                'marca' => 'DOMETAL', 'modelo' => 'NT', 'serie' => '14110242',
                'invima' => null, 'riesgo' => 'I', 'especialidad' => 'Consulta externa',
                'fabricante' => 'Dometal', 'pais' => 'Colombia', 'adquisicion' => 'Compra', 'prioridad' => 'Baja',
                'tecnicas' => 'Equipo mecánico. Estructura en acero inoxidable con espaldar reclinable.',
                'mantenimiento' => 'NA', 'activo' => true, 'suministro' => 'na',
                'peso' => '32', 'tecnologia' => 'Mecánica',
                'subtareas' => ['desarmado_limpieza', 'limpieza_ajuste_mecanicos'],
                'componentes' => 'Colchoneta, porta rollo, ruedas con freno.',
            ],
            [
                'descripcion' => 'ELECTROCARDIOGRAFO', 'empresa' => $hb, 'area' => 'CONSULTORIO MEDICO #1',
                'marca' => 'NIHON KOHDEN', 'modelo' => 'NV', 'serie' => 'NV',
                'invima' => '2014DM-0011870', 'riesgo' => 'IIB', 'especialidad' => 'Cardiología',
                'fabricante' => 'Nihon Kohden', 'pais' => 'Japón', 'adquisicion' => 'Compra', 'prioridad' => 'Alta',
                'tecnicas' => 'Equipo electrónico de 12 derivaciones con impresión térmica.',
                'mantenimiento' => 'Deshabilitado en diciembre 2025 después de actualización de inventario.',
                'activo' => false, 'suministro' => 'mixto', 'voltaje' => '110', 'frecuencia' => '60', 'potencia' => '40',
                'tecnologia' => 'Electrónica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'ajuste_sistema_electronico', 'limpieza_tarjetas', 'revision_conectores'],
                'accesorios' => ['cable_ac' => 'R', 'cable_ecg' => 'M', 'chupas_precordiales' => 'M', 'bateria' => 'R'],
                'componentes' => 'Cable paciente 10 derivaciones, chupas precordiales, electrodos de extremidad.',
            ],
            [
                'descripcion' => 'ELECTROCARDIOGRAFO', 'empresa' => $hb, 'area' => 'CONSULTORIO MEDICO #1',
                'marca' => 'EDAN', 'modelo' => 'SE-1', 'serie' => 'SE13A0307C4697',
                'invima' => '2013DM-0010455', 'riesgo' => 'IIB', 'especialidad' => 'Cardiología',
                'fabricante' => 'EDAN Instruments', 'pais' => 'China', 'adquisicion' => 'Compra', 'prioridad' => 'Alta',
                'tecnicas' => 'Equipo electrónico portátil de un canal, pantalla LCD y batería recargable.',
                'mantenimiento' => 'Deshabilitado en diciembre 2025 después de actualización de inventario.',
                'activo' => false, 'suministro' => 'mixto', 'voltaje' => '110', 'frecuencia' => '60',
                'tecnologia' => 'Electrónica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'ajuste_sistema_electronico', 'revision_conectores'],
                'accesorios' => ['cable_ac' => 'B', 'cable_ecg' => 'R', 'bateria' => 'M'],
            ],
            [
                'descripcion' => 'FONENDOSCOPIO', 'empresa' => $hb, 'area' => 'CONSULTORIO MEDICO #1',
                'marca' => 'GMD', 'modelo' => 'DOBLE CAMPANA', 'serie' => 'NT',
                'invima' => null, 'riesgo' => 'I', 'especialidad' => 'Consulta externa',
                'fabricante' => 'GMD', 'pais' => 'China', 'adquisicion' => 'Compra', 'prioridad' => 'Baja',
                'tecnicas' => 'Equipo acústico de doble campana, diafragma para adulto y pediátrico.',
                'mantenimiento' => 'Equipo sin información para su identificación.',
                'activo' => true, 'suministro' => 'na', 'tecnologia' => 'Acústica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza'],
            ],
            [
                'descripcion' => 'OXIMETRO PORTATIL', 'empresa' => $hb, 'area' => 'CONSULTORIO MEDICO #1',
                'marca' => 'AXON', 'modelo' => 'LK88', 'serie' => 'NA',
                'invima' => '2019DM-0018902', 'riesgo' => 'IIA', 'especialidad' => 'Apoyo diagnóstico',
                'fabricante' => 'Axon Medical', 'pais' => 'China', 'adquisicion' => 'Compra', 'prioridad' => 'Media',
                'tecnicas' => 'Equipo electrónico de dedo, mide SpO2 y frecuencia de pulso, pantalla OLED.',
                'activo' => true, 'suministro' => 'dc', 'voltaje' => '3', 'tecnologia' => 'Electrónica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'revision_alarma'],
                'accesorios' => ['sensor_spo2' => 'B', 'bateria' => 'B'],
            ],
            [
                'descripcion' => 'TENSIOMETRO ANALOGO', 'empresa' => $hb, 'area' => 'CONSULTORIO MEDICO #1',
                'marca' => 'ALPK2', 'modelo' => 'Artery 300', 'serie' => '1',
                'invima' => '2018DM-0018548', 'riesgo' => 'I', 'especialidad' => 'Consulta externa',
                'fabricante' => 'ALPK2', 'pais' => 'Japón', 'adquisicion' => 'Compra', 'prioridad' => 'Media',
                'tecnicas' => 'Equipo aneroide con brazalete adulto y perilla de insuflación.',
                'mantenimiento' => 'NA', 'activo' => true, 'suministro' => 'na', 'tecnologia' => 'Mecánica',
                'subtareas' => ['prueba_funcionamiento', 'prueba_fugas', 'limpieza_ajuste_mecanicos'],
                'accesorios' => ['brazalete' => 'B', 'manguera_nibp' => 'B'],
            ],
            [
                'descripcion' => 'CAMILLA', 'empresa' => $hb, 'area' => 'CONSULTORIO MEDICO #2',
                'marca' => 'NT', 'modelo' => 'NT', 'serie' => 'NT',
                'invima' => null, 'riesgo' => 'I', 'especialidad' => 'Consulta externa',
                'fabricante' => null, 'pais' => null, 'adquisicion' => 'Donación', 'prioridad' => 'Baja',
                'tecnicas' => 'Equipo mecánico sin placa de identificación visible.',
                'mantenimiento' => 'Equipo sin información para su identificación.',
                'activo' => true, 'suministro' => 'na', 'tecnologia' => 'Mecánica',
                'subtareas' => ['desarmado_limpieza', 'limpieza_ajuste_mecanicos'],
            ],
            [
                'descripcion' => 'EQUIPO DE ORGANOS', 'empresa' => $hb, 'area' => 'CONSULTORIO MEDICO #2',
                'marca' => 'WELCH ALLYN', 'modelo' => 'NT', 'serie' => '408282',
                'invima' => '2015DM-0012903', 'riesgo' => 'IIA', 'especialidad' => 'Consulta externa',
                'fabricante' => 'Welch Allyn', 'pais' => 'Estados Unidos', 'adquisicion' => 'Compra', 'prioridad' => 'Media',
                'tecnicas' => 'Equipo eléctrico. Otoscopio y oftalmoscopio con mango recargable.',
                'activo' => true, 'suministro' => 'mixto', 'voltaje' => '110', 'tecnologia' => 'Electrónica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'ajuste_sistema_electrico'],
                'accesorios' => ['bateria' => 'B', 'cable_ac' => 'B'],
            ],
            [
                'descripcion' => 'MONITOR DE SIGNOS VITALES', 'empresa' => $clinica, 'area' => 'URGENCIAS',
                'marca' => 'MINDRAY', 'modelo' => 'uMEC 12', 'serie' => 'CF-82340117',
                'invima' => '2020DM-0019745', 'riesgo' => 'IIB', 'especialidad' => 'Cuidado crítico',
                'fabricante' => 'Mindray', 'pais' => 'China', 'adquisicion' => 'Compra', 'prioridad' => 'Alta',
                'tecnicas' => 'Monitor multiparámetro: ECG, SpO2, NIBP, temperatura y respiración. Pantalla 12".',
                'activo' => true, 'suministro' => 'mixto', 'voltaje' => '110', 'frecuencia' => '60', 'potencia' => '60',
                'peso' => '4.6', 'tecnologia' => 'Electrónica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'revision_alarma', 'ajuste_sistema_electronico', 'limpieza_tarjetas', 'revision_conectores', 'cambio_accesorios'],
                'accesorios' => ['cable_ac' => 'B', 'cable_ecg' => 'B', 'sensor_spo2' => 'B', 'manguera_nibp' => 'B', 'brazalete' => 'R', 'sensor_temperatura' => 'B', 'bateria' => 'R'],
                'componentes' => 'Cable ECG 5 derivaciones, brazalete NIBP adulto, sensor SpO2 adulto, manual de usuario.',
                'ot' => 'Verificar exactitud de NIBP contra simulador y registrar desviación en el acta.',
            ],
            [
                'descripcion' => 'DESFIBRILADOR', 'empresa' => $clinica, 'area' => 'URGENCIAS',
                'marca' => 'ZOLL', 'modelo' => 'R Series', 'serie' => 'AA14D009876',
                'invima' => '2017DM-0016320', 'riesgo' => 'III', 'especialidad' => 'Cuidado crítico',
                'fabricante' => 'ZOLL Medical', 'pais' => 'Estados Unidos', 'adquisicion' => 'Comodato', 'prioridad' => 'Alta',
                'tecnicas' => 'Desfibrilador bifásico con monitor ECG, marcapaso externo y modo DEA.',
                'activo' => true, 'suministro' => 'mixto', 'voltaje' => '110', 'frecuencia' => '60', 'potencia' => '200',
                'peso' => '6.1', 'tecnologia' => 'Electrónica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'revision_alarma', 'ajuste_sistema_electronico', 'limpieza_tarjetas', 'revision_conectores'],
                'accesorios' => ['cable_ac' => 'B', 'cable_ecg' => 'B', 'pala_ekg' => 'B', 'bateria' => 'B'],
                'componentes' => 'Palas adulto/pediátrico, cable ECG 3 derivaciones, batería de litio, papel térmico.',
                'ot' => 'Ejecutar descarga de prueba a 200 J y verificar energía entregada con analizador.',
            ],
            [
                'descripcion' => 'BOMBA DE INFUSION', 'empresa' => $clinica, 'area' => 'UCI ADULTOS',
                'marca' => 'B. BRAUN', 'modelo' => 'Infusomat Space', 'serie' => '18034577',
                'invima' => '2016DM-0014008', 'riesgo' => 'IIB', 'especialidad' => 'Cuidado crítico',
                'fabricante' => 'B. Braun', 'pais' => 'Alemania', 'adquisicion' => 'Comodato', 'prioridad' => 'Alta',
                'tecnicas' => 'Bomba volumétrica peristáltica, rango 0.1–1200 mL/h, detección de aire y oclusión.',
                'activo' => true, 'suministro' => 'mixto', 'voltaje' => '110', 'frecuencia' => '60', 'potencia' => '25',
                'peso' => '1.4', 'tecnologia' => 'Electromecánica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'revision_alarma', 'ajuste_sistema_electronico', 'limpieza_ajuste_mecanicos', 'revision_panel_control'],
                'accesorios' => ['cable_ac' => 'B', 'bateria' => 'R'],
                'ot' => 'Verificar exactitud de flujo a 25 mL/h y 100 mL/h; tolerancia ±5 %.',
            ],
            [
                'descripcion' => 'VENTILADOR MECANICO', 'empresa' => $clinica, 'area' => 'UCI ADULTOS',
                'marca' => 'HAMILTON MEDICAL', 'modelo' => 'C1', 'serie' => 'HM2019C1044',
                'invima' => '2019DM-0018100', 'riesgo' => 'III', 'especialidad' => 'Cuidado crítico',
                'fabricante' => 'Hamilton Medical', 'pais' => 'Suiza', 'adquisicion' => 'Compra', 'prioridad' => 'Alta',
                'tecnicas' => 'Ventilador de turbina para paciente adulto y pediátrico, con compensación automática.',
                'activo' => true, 'suministro' => 'mixto', 'voltaje' => '110', 'frecuencia' => '60', 'potencia' => '180',
                'peso' => '9.5', 'tecnologia' => 'Electroneumática',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'prueba_fugas', 'revision_alarma', 'limpieza_filtros', 'revision_sistema_neumatico', 'ajuste_sistema_electronico'],
                'accesorios' => ['cable_ac' => 'B', 'manguera_oxigeno' => 'B', 'manguera_aire' => 'B', 'sensor_oxigeno' => 'R', 'bateria' => 'B'],
                'componentes' => 'Circuito paciente reusable, sensor de flujo, celda de oxígeno, filtro HEPA.',
                'ot' => 'Prueba de fugas del circuito y calibración de celda de O2 antes de entregar el equipo.',
            ],
            [
                'descripcion' => 'ELECTROBISTURI', 'empresa' => $clinica, 'area' => 'SALA DE CIRUGIA',
                'marca' => 'VALLEYLAB', 'modelo' => 'Force FX-8C', 'serie' => 'F5G22314A',
                'invima' => '2012DM-0009431', 'riesgo' => 'III', 'especialidad' => 'Cirugía',
                'fabricante' => 'Medtronic', 'pais' => 'Estados Unidos', 'adquisicion' => 'Compra', 'prioridad' => 'Alta',
                'tecnicas' => 'Unidad electroquirúrgica monopolar y bipolar, 300 W de corte máximo.',
                'activo' => true, 'suministro' => 'ac', 'voltaje' => '110', 'frecuencia' => '60', 'potencia' => '300',
                'peso' => '11.8', 'tecnologia' => 'Electrónica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'prueba_fugas', 'revision_alarma', 'ajuste_sistema_electronico', 'ajuste_pieza_mano', 'revision_conectores'],
                'accesorios' => ['cable_ac' => 'B', 'pieza_mano' => 'R', 'control' => 'B'],
                'componentes' => 'Lápiz monopolar, pinza bipolar, pedal doble, cable de placa neutra.',
                'ot' => 'Medir corriente de fuga y verificar alarma de placa neutra.',
            ],
            [
                'descripcion' => 'LAMPARA CIELITICA', 'empresa' => $clinica, 'area' => 'SALA DE CIRUGIA',
                'marca' => 'DR. MACH', 'modelo' => 'M3', 'serie' => 'MCH-2017-0455',
                'invima' => '2015DM-0013277', 'riesgo' => 'IIA', 'especialidad' => 'Cirugía',
                'fabricante' => 'Dr. Mach', 'pais' => 'Alemania', 'adquisicion' => 'Compra', 'prioridad' => 'Media',
                'tecnicas' => 'Lámpara quirúrgica LED de techo, 130.000 lux, brazo articulado balanceado.',
                'activo' => true, 'suministro' => 'ac', 'voltaje' => '110', 'frecuencia' => '60', 'potencia' => '90',
                'tecnologia' => 'Electromecánica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'ajuste_sistema_electrico', 'limpieza_ajuste_mecanicos', 'revision_panel_control'],
                'accesorios' => ['cable_ac' => 'B'],
            ],
            [
                'descripcion' => 'AUTOCLAVE', 'empresa' => $clinica, 'area' => 'ESTERILIZACION',
                'marca' => 'AUTOMAT', 'modelo' => '2400', 'serie' => 'AUT24-1188',
                'invima' => '2018DM-0018563', 'riesgo' => 'IIA', 'especialidad' => 'Equipo de esterilización',
                'fabricante' => 'Automat', 'pais' => 'Colombia', 'adquisicion' => 'Compra', 'prioridad' => 'Alta',
                'tecnicas' => 'Autoclave de vapor de 24 litros, control digital de ciclos y válvula de seguridad.',
                'activo' => true, 'suministro' => 'ac', 'voltaje' => '220', 'frecuencia' => '60', 'potencia' => '2000',
                'peso' => '38', 'tecnologia' => 'Electroneumática',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'prueba_fugas', 'revision_alarma', 'revision_sistema_neumatico', 'limpieza_filtros', 'revision_panel_control'],
                'accesorios' => ['cable_ac' => 'B', 'sensor_temperatura' => 'B'],
                'ot' => 'Registrar curva de temperatura y presión del ciclo de 121 °C.',
            ],
            [
                'descripcion' => 'INCUBADORA NEONATAL', 'empresa' => $hospital, 'area' => 'UCI NEONATAL',
                'marca' => 'DRAGER', 'modelo' => 'Isolette 8000', 'serie' => 'ARLM-0342',
                'invima' => '2016DM-0015012', 'riesgo' => 'III', 'especialidad' => 'Neonatología',
                'fabricante' => 'Dräger', 'pais' => 'Alemania', 'adquisicion' => 'Compra', 'prioridad' => 'Alta',
                'tecnicas' => 'Incubadora de cuidado intensivo con control servo de temperatura y humedad.',
                'activo' => true, 'suministro' => 'ac', 'voltaje' => '110', 'frecuencia' => '60', 'potencia' => '450',
                'temperatura' => '36.5', 'peso' => '75', 'tecnologia' => 'Electrónica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'revision_alarma', 'ajuste_sistema_electronico', 'limpieza_filtros', 'ajuste_extractores', 'revision_panel_control'],
                'accesorios' => ['cable_ac' => 'B', 'sensor_temperatura' => 'B', 'sensor_oxigeno' => 'R'],
                'ot' => 'Verificar estabilidad térmica y nivel de ruido interno dentro de la cúpula.',
            ],
            [
                'descripcion' => 'LAMPARA DE FOTOTERAPIA', 'empresa' => $hospital, 'area' => 'UCI NEONATAL',
                'marca' => 'OLIDEF', 'modelo' => 'BiliLED', 'serie' => 'OLD-2019-7781',
                'invima' => '2019DM-0018344', 'riesgo' => 'IIA', 'especialidad' => 'Neonatología',
                'fabricante' => 'Olidef', 'pais' => 'Brasil', 'adquisicion' => 'Compra', 'prioridad' => 'Media',
                'tecnicas' => 'Fototerapia LED azul de alta intensidad con contador de horas de uso.',
                'activo' => true, 'suministro' => 'ac', 'voltaje' => '110', 'frecuencia' => '60', 'potencia' => '60',
                'tecnologia' => 'Electrónica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'ajuste_sistema_electrico', 'revision_panel_control'],
                'accesorios' => ['cable_ac' => 'B'],
            ],
            [
                'descripcion' => 'ECOGRAFO', 'empresa' => $hospital, 'area' => 'IMAGENOLOGIA',
                'marca' => 'SAMSUNG', 'modelo' => 'HS40', 'serie' => 'HS40-2021-3390',
                'invima' => '2021DM-0020118', 'riesgo' => 'IIB', 'especialidad' => 'Imagenología',
                'fabricante' => 'Samsung Medison', 'pais' => 'Corea del Sur', 'adquisicion' => 'Leasing', 'prioridad' => 'Alta',
                'tecnicas' => 'Ecógrafo doppler color con tres puertos de transductor y monitor LED de 21,5".',
                'activo' => true, 'suministro' => 'ac', 'voltaje' => '110', 'frecuencia' => '60', 'potencia' => '400',
                'peso' => '68', 'tecnologia' => 'Electrónica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'limpieza_tarjetas', 'ajuste_extractores', 'revision_conectores', 'revision_panel_control'],
                'accesorios' => ['cable_ac' => 'B', 'transductor' => 'B'],
                'componentes' => 'Transductor convexo, transductor lineal, transductor endocavitario, gel conductor.',
            ],
            [
                'descripcion' => 'RAYOS X PORTATIL', 'empresa' => $hospital, 'area' => 'IMAGENOLOGIA',
                'marca' => 'SIEMENS', 'modelo' => 'Mobilett Mira', 'serie' => 'SMS-MIR-4471',
                'invima' => '2014DM-0011206', 'riesgo' => 'III', 'especialidad' => 'Imagenología',
                'fabricante' => 'Siemens Healthineers', 'pais' => 'Alemania', 'adquisicion' => 'Compra', 'prioridad' => 'Alta',
                'tecnicas' => 'Equipo de rayos X móvil digital, 32 kW, brazo telescópico motorizado.',
                'mantenimiento' => 'Requiere verificación anual de blindaje por parte del proveedor autorizado.',
                'activo' => true, 'suministro' => 'ac', 'voltaje' => '220', 'frecuencia' => '60', 'potencia' => '32000',
                'peso' => '295', 'tecnologia' => 'Electrónica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'ajuste_sistema_electronico', 'limpieza_tarjetas', 'revision_conectores', 'revision_panel_control'],
                'accesorios' => ['cable_ac' => 'B', 'control' => 'B', 'bateria' => 'R'],
            ],
            [
                'descripcion' => 'CENTRIFUGA', 'empresa' => $hospital, 'area' => 'LABORATORIO CLINICO',
                'marca' => 'HETTICH', 'modelo' => 'EBA 200', 'serie' => 'HET-EBA-9022',
                'invima' => '2017DM-0016788', 'riesgo' => 'I', 'especialidad' => 'Laboratorio clínico',
                'fabricante' => 'Andreas Hettich', 'pais' => 'Alemania', 'adquisicion' => 'Compra', 'prioridad' => 'Media',
                'tecnicas' => 'Centrífuga de mesa para 8 tubos, 6.000 rpm, tapa con bloqueo de seguridad.',
                'activo' => true, 'suministro' => 'ac', 'voltaje' => '110', 'frecuencia' => '60', 'potencia' => '85',
                'velocidad' => '6000', 'peso' => '7.2', 'tecnologia' => 'Electromecánica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'limpieza_ajuste_mecanicos', 'ajuste_sistema_electrico', 'revision_panel_control'],
                'accesorios' => ['cable_ac' => 'B'],
                'ot' => 'Verificar rpm con tacómetro y estado de las escobillas del motor.',
            ],
            [
                'descripcion' => 'MICROSCOPIO BINOCULAR', 'empresa' => $hospital, 'area' => 'LABORATORIO CLINICO',
                'marca' => 'OLYMPUS', 'modelo' => 'CX23', 'serie' => 'OLY-CX23-1140',
                'invima' => null, 'riesgo' => 'I', 'especialidad' => 'Laboratorio clínico',
                'fabricante' => 'Olympus', 'pais' => 'Japón', 'adquisicion' => 'Compra', 'prioridad' => 'Media',
                'tecnicas' => 'Microscopio óptico binocular con objetivos 4x, 10x, 40x y 100x, iluminación LED.',
                'activo' => true, 'suministro' => 'ac', 'voltaje' => '110', 'potencia' => '5',
                'peso' => '6.8', 'tecnologia' => 'Óptica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'limpieza_ajuste_mecanicos', 'ajuste_sistema_electrico'],
                'accesorios' => ['cable_ac' => 'B'],
            ],
            [
                'descripcion' => 'UNIDAD ODONTOLOGICA', 'empresa' => $anas, 'area' => 'ODONTOLOGIA',
                'marca' => 'GNATUS', 'modelo' => 'Syncrus G3', 'serie' => 'GN-SYN-5521',
                'invima' => '2018DM-0017455', 'riesgo' => 'IIA', 'especialidad' => 'Odontología',
                'fabricante' => 'Gnatus', 'pais' => 'Brasil', 'adquisicion' => 'Compra', 'prioridad' => 'Alta',
                'tecnicas' => 'Unidad odontológica completa con sillón eléctrico, jeringa triple y suctor.',
                'activo' => true, 'suministro' => 'ac', 'voltaje' => '110', 'frecuencia' => '60', 'potencia' => '600',
                'presion' => '80', 'peso' => '120', 'tecnologia' => 'Electroneumática',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'prueba_fugas', 'revision_sistema_neumatico', 'ajuste_pieza_mano', 'limpieza_ajuste_mecanicos', 'limpieza_filtros'],
                'accesorios' => ['cable_ac' => 'B', 'pieza_mano' => 'R', 'manguera_aire' => 'B'],
                'componentes' => 'Sillón, escupidera, jeringa triple, micromotor, pieza de alta velocidad, lámpara.',
            ],
            [
                'descripcion' => 'COMPRESOR ODONTOLOGICO', 'empresa' => $anas, 'area' => 'ODONTOLOGIA',
                'marca' => 'SCHULZ', 'modelo' => 'CSD 9/50', 'serie' => 'SCH-950-2210',
                'invima' => null, 'riesgo' => 'I', 'especialidad' => 'Odontología',
                'fabricante' => 'Schulz', 'pais' => 'Brasil', 'adquisicion' => 'Compra', 'prioridad' => 'Media',
                'tecnicas' => 'Compresor libre de aceite de 50 litros, 9 pies³/min, presión máxima 120 PSI.',
                'activo' => true, 'suministro' => 'ac', 'voltaje' => '220', 'frecuencia' => '60', 'potencia' => '1500',
                'presion' => '120', 'peso' => '48', 'tecnologia' => 'Electromecánica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'prueba_fugas', 'revision_sistema_neumatico', 'limpieza_filtros', 'limpieza_ajuste_mecanicos'],
                'accesorios' => ['cable_ac' => 'B', 'manguera_aire' => 'R'],
                'ot' => 'Drenar el tanque y verificar el ajuste del presostato.',
            ],
            [
                'descripcion' => 'ASPIRADOR DE SECRECIONES', 'empresa' => $anas, 'area' => 'CONSULTORIO MEDICO #1',
                'marca' => 'THOMAS', 'modelo' => 'VAC-3020', 'serie' => 'TH-3020-0891',
                'invima' => '2016DM-0014990', 'riesgo' => 'IIA', 'especialidad' => 'Consulta externa',
                'fabricante' => 'Thomas', 'pais' => 'Estados Unidos', 'adquisicion' => 'Donación', 'prioridad' => 'Media',
                'tecnicas' => 'Aspirador portátil con frasco de 1000 mL, vacío regulable hasta 600 mmHg.',
                'mantenimiento' => 'Pendiente cambio de filtro hidrofóbico en el próximo preventivo.',
                'activo' => true, 'suministro' => 'ac', 'voltaje' => '110', 'frecuencia' => '60', 'potencia' => '130',
                'presion' => '600', 'peso' => '5.5', 'tecnologia' => 'Electromecánica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'prueba_fugas', 'revision_sistema_neumatico', 'limpieza_filtros'],
                'accesorios' => ['cable_ac' => 'B', 'manguera_aire' => 'R'],
            ],
            [
                'descripcion' => 'TENSIOMETRO DIGITAL', 'empresa' => $anas, 'area' => 'CONSULTORIO MEDICO #1',
                'marca' => 'OMRON', 'modelo' => 'HEM-7120', 'serie' => 'OM-7120-4432',
                'invima' => '2019DM-0018770', 'riesgo' => 'IIA', 'especialidad' => 'Consulta externa',
                'fabricante' => 'Omron Healthcare', 'pais' => 'Japón', 'adquisicion' => 'Compra', 'prioridad' => 'Media',
                'tecnicas' => 'Tensiómetro digital de brazo, método oscilométrico, memoria de 30 lecturas.',
                'activo' => false, 'suministro' => 'dc', 'voltaje' => '6',
                'mantenimiento' => 'Fuera de servicio: brazalete con fuga confirmada en prueba de presión.',
                'tecnologia' => 'Electrónica',
                'subtareas' => ['prueba_funcionamiento', 'prueba_fugas', 'desarmado_limpieza'],
                'accesorios' => ['brazalete' => 'M', 'manguera_nibp' => 'M', 'bateria' => 'B'],
            ],
            [
                'descripcion' => 'DESFIBRILADOR EXTERNO AUTOMATICO', 'empresa' => null, 'area' => null,
                'marca' => 'PHILIPS', 'modelo' => 'HeartStart FRx', 'serie' => 'PH-FRX-2026-01',
                'invima' => '2020DM-0019330', 'riesgo' => 'III', 'especialidad' => 'Cuidado crítico',
                'fabricante' => 'Philips', 'pais' => 'Estados Unidos', 'adquisicion' => 'Compra', 'prioridad' => 'Alta',
                'tecnicas' => 'DEA con parches precableados, análisis automático de ritmo e indicador de estado.',
                'generales' => 'Equipo maestro en bodega, pendiente de asignación a cliente.',
                'activo' => true, 'suministro' => 'dc', 'voltaje' => '12', 'peso' => '1.6', 'tecnologia' => 'Electrónica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'revision_alarma', 'cambio_accesorios'],
                'accesorios' => ['bateria' => 'B'],
            ],
            [
                'descripcion' => 'BOMBA DE INFUSION', 'empresa' => null, 'area' => null,
                'marca' => 'MINDRAY', 'modelo' => 'BeneFusion VP1', 'serie' => 'MR-VP1-2026-07',
                'invima' => '2022DM-0021004', 'riesgo' => 'IIB', 'especialidad' => 'Cuidado crítico',
                'fabricante' => 'Mindray', 'pais' => 'China', 'adquisicion' => 'Compra', 'prioridad' => 'Media',
                'tecnicas' => 'Bomba volumétrica con pantalla táctil, biblioteca de fármacos y batería de 8 horas.',
                'generales' => 'Equipo maestro en bodega, pendiente de asignación a cliente.',
                'activo' => true, 'suministro' => 'mixto', 'voltaje' => '110', 'frecuencia' => '60', 'potencia' => '30',
                'peso' => '1.9', 'tecnologia' => 'Electromecánica',
                'subtareas' => ['prueba_funcionamiento', 'desarmado_limpieza', 'revision_alarma', 'revision_panel_control'],
                'accesorios' => ['cable_ac' => 'B', 'bateria' => 'B'],
            ],
        ];
    }
}
