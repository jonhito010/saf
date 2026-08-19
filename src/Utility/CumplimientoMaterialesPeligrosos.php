<?php
declare(strict_types=1);

namespace App\Utility;

/**
 * Requisitos para empresas de transporte de materiales, remanentes
 * y residuos peligrosos (y transporte privado de MP).
 */
final class CumplimientoMaterialesPeligrosos
{
    public const SECCION_VEHICULO = 'vehiculo';
    public const SECCION_OPERADOR = 'operador';
    public const SECCION_ADICIONAL = 'adicional';
    public const SECCION_CARTA_PORTE = 'carta_porte';

    /**
     * @return list<array{
     *   id: string,
     *   titulo: string,
     *   descripcion: string,
     *   seccion: string,
     *   retencion: string,
     *   tipos_documento: list<string>,
     *   enlace: array<string, mixed>|null
     * }>
     */
    public static function requisitos(): array
    {
        return [
            [
                'id' => 'poliza_ambiental_unidad',
                'titulo' => 'Póliza de seguro ambiental por vehículo',
                'descripcion' => 'Póliza vigente para cubrir responsabilidad por daños al medio ambiente y comprobante de pago.',
                'seccion' => self::SECCION_VEHICULO,
                'retencion' => 'Vigente + comprobante de pago',
                'tipos_documento' => ['poliza_responsabilidad_ambiental', 'comprobante_pago_poliza_ambiental'],
                'enlace' => ['controller' => 'Vehiculos', 'action' => 'index', '?' => ['tipo_carga' => 'material_peligroso']],
            ],
            [
                'id' => 'conductores_documentacion',
                'titulo' => 'Relación de operadores y documentación',
                'descripcion' => 'Relación escrita de operadores; licencia federal y constancia de capacitación vigente por cada uno.',
                'seccion' => self::SECCION_OPERADOR,
                'retencion' => 'Licencia y capacitación vigentes',
                'tipos_documento' => ['licencia', 'curso_capacitacion'],
                'enlace' => ['controller' => 'Operadores', 'action' => 'index'],
            ],
            [
                'id' => 'reporte_trimestral_sict',
                'titulo' => 'Reporte trimestral SICT',
                'descripcion' => 'Información de producto, peso, origen, destino, ruta frecuente y características del producto transportado.',
                'seccion' => self::SECCION_ADICIONAL,
                'retencion' => 'Por trimestre',
                'tipos_documento' => ['reporte_trimestral_sict'],
                'enlace' => ['controller' => 'DocumentosAdicionales', 'action' => 'index'],
            ],
            [
                'id' => 'carta_porte_emitidas',
                'titulo' => 'Carta de Porte / CFDI de traslado',
                'descripcion' => 'Carta de Porte pre-impresa y digital (CFDI), consecutivos e informes de servicios de los 6 meses anteriores.',
                'seccion' => self::SECCION_CARTA_PORTE,
                'retencion' => 'Últimos 6 meses',
                'tipos_documento' => [],
                'enlace' => ['controller' => 'Comprobantes', 'action' => 'index'],
            ],
            [
                'id' => 'documentos_embarque',
                'titulo' => 'Documentos de embarque',
                'descripcion' => 'Embarque de materiales y/o residuos peligrosos transportados.',
                'seccion' => self::SECCION_ADICIONAL,
                'retencion' => '6 meses anteriores',
                'tipos_documento' => ['documentos_embarque_mp'],
                'enlace' => ['controller' => 'DocumentosAdicionales', 'action' => 'index'],
            ],
            [
                'id' => 'bitacora_inspeccion_ocular',
                'titulo' => 'Bitácora de inspección ocular diaria',
                'descripcion' => 'Inspección ocular diaria de los vehículos.',
                'seccion' => self::SECCION_VEHICULO,
                'retencion' => '6 meses anteriores',
                'tipos_documento' => ['bitacora_inspeccion_ocular'],
                'enlace' => ['controller' => 'Vehiculos', 'action' => 'index', '?' => ['tipo_carga' => 'material_peligroso']],
            ],
            [
                'id' => 'control_mantenimiento',
                'titulo' => 'Control de mantenimiento',
                'descripcion' => 'Mantenimiento preventivo y correctivo de los vehículos.',
                'seccion' => self::SECCION_VEHICULO,
                'retencion' => 'Histórico del vehículo',
                'tipos_documento' => ['control_mantenimiento'],
                'enlace' => ['controller' => 'Vehiculos', 'action' => 'index', '?' => ['tipo_carga' => 'material_peligroso']],
            ],
            [
                'id' => 'hoja_emergencia',
                'titulo' => 'Hoja de información de emergencia (HIE)',
                'descripcion' => 'Hoja de emergencia en transportación de los materiales/residuos que transporta.',
                'seccion' => self::SECCION_ADICIONAL,
                'retencion' => 'Por material transportado',
                'tipos_documento' => ['hoja_emergencia_transportacion'],
                'enlace' => ['controller' => 'DocumentosAdicionales', 'action' => 'index'],
            ],
            [
                'id' => 'manifiesto_residuos',
                'titulo' => 'Manifiesto entrega, transporte y recepción',
                'descripcion' => 'Manifiesto de residuos peligrosos, en su caso.',
                'seccion' => self::SECCION_ADICIONAL,
                'retencion' => 'Por servicio / en su caso',
                'tipos_documento' => ['manifiesto_entrega_transporte_residuos'],
                'enlace' => ['controller' => 'DocumentosAdicionales', 'action' => 'index'],
            ],
            [
                'id' => 'limpieza_remanentes',
                'titulo' => 'Limpieza y control de remanentes',
                'descripcion' => 'Documento que acredita la limpieza y control de remanentes.',
                'seccion' => self::SECCION_ADICIONAL,
                'retencion' => 'Según operación',
                'tipos_documento' => ['documento_limpieza_remanentes'],
                'enlace' => ['controller' => 'DocumentosAdicionales', 'action' => 'index'],
            ],
            [
                'id' => 'libro_registro_bodega',
                'titulo' => 'Libro de registro de mercancía en bodega',
                'descripcion' => 'Recepción, despacho, devoluciones y servicios; inventario selectivo vs. carta de porte.',
                'seccion' => self::SECCION_ADICIONAL,
                'retencion' => 'Registro continuo',
                'tipos_documento' => ['libro_registro_mercancia_bodega'],
                'enlace' => ['controller' => 'DocumentosAdicionales', 'action' => 'index'],
            ],
            [
                'id' => 'manifiesto_derrames',
                'titulo' => 'Manifiesto por derrames (accidente)',
                'descripcion' => 'Manifiesto para derrames de residuos peligrosos por accidente, en su caso.',
                'seccion' => self::SECCION_ADICIONAL,
                'retencion' => 'En su caso',
                'tipos_documento' => ['manifiesto_derrames_accidente'],
                'enlace' => ['controller' => 'DocumentosAdicionales', 'action' => 'index'],
            ],
            [
                'id' => 'bitacora_residuos',
                'titulo' => 'Bitácora de control de residuos',
                'descripcion' => 'Bitácora para el control de residuos, en su caso.',
                'seccion' => self::SECCION_ADICIONAL,
                'retencion' => 'En su caso',
                'tipos_documento' => ['bitacora_control_residuos'],
                'enlace' => ['controller' => 'DocumentosAdicionales', 'action' => 'index'],
            ],
        ];
    }

    /** @return array<string, string> */
    public static function etiquetasSeccion(): array
    {
        return [
            self::SECCION_VEHICULO => 'Por vehículo',
            self::SECCION_OPERADOR => 'Operadores',
            self::SECCION_ADICIONAL => 'Documentos adicionales',
            self::SECCION_CARTA_PORTE => 'Carta Porte / CFDI',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function requisitosPorSeccion(): array
    {
        $out = [];
        foreach (self::etiquetasSeccion() as $key => $label) {
            $out[] = [
                'seccion' => $key,
                'label' => $label,
                'items' => array_values(array_filter(
                    self::requisitos(),
                    static fn(array $r): bool => $r['seccion'] === $key,
                )),
            ];
        }

        return $out;
    }
}
