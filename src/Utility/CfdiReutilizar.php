<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Utility\Xml;

/**
 * Convierte un CFDI timbrado (XML) al formato que espera el formulario generar.
 */
class CfdiReutilizar
{
    /**
     * @return array<string, mixed>
     */
    public static function desdeXmlTimbrado(string $xmlContent): array
    {
        $xmlContent = trim($xmlContent);
        if ($xmlContent === '') {
            throw new \RuntimeException('El XML está vacío.');
        }

        $xmlArray = Xml::toArray(Xml::build($xmlContent));
        $comp = $xmlArray['Comprobante'] ?? null;
        if (!is_array($comp)) {
            throw new \RuntimeException('El archivo no contiene un comprobante CFDI válido.');
        }

        $receptor = self::node($comp, 'Receptor');
        $cpNode = self::cartaPorteNode($comp);

        $result = [
            'cfdi' => [
                'LugarExpedicion' => self::attr($comp, 'LugarExpedicion'),
                'FormaPago' => self::attr($comp, 'FormaPago'),
                'MetodoPago' => self::attr($comp, 'MetodoPago'),
                'TipoDeComprobante' => self::attr($comp, 'TipoDeComprobante') ?: 'I',
                'Exportacion' => self::attr($comp, 'Exportacion') ?: '01',
                'Moneda' => self::attr($comp, 'Moneda') ?: 'MXN',
                'TipoCambio' => self::attr($comp, 'TipoCambio') ?: '1',
                'CondicionesPago' => self::attr($comp, 'CondicionesPago'),
                'SubTotal' => self::attr($comp, 'SubTotal'),
                'Descuento' => self::attr($comp, 'Descuento') ?: '0',
                'Total' => self::attr($comp, 'Total'),
            ],
            'receptor' => [
                'Nombre' => self::attr($receptor, 'Nombre'),
                'Rfc' => self::attr($receptor, 'Rfc'),
                'RegimenFiscalReceptor' => self::attr($receptor, 'RegimenFiscalReceptor'),
                'UsoCFDI' => self::attr($receptor, 'UsoCFDI'),
                'DomicilioFiscalReceptor' => self::attr($receptor, 'DomicilioFiscalReceptor'),
                'Correo' => '',
                'direccionCompleta' => '',
            ],
            'conceptos' => self::conceptosDesdeXml($comp),
            'cartaPorte' => $cpNode !== null,
            'carta30' => $cpNode !== null ? self::carta30DesdeXml($cpNode) : [],
            'ubicaciones' => $cpNode !== null ? self::ubicacionesDesdeXml($cpNode) : [],
            'mercancias' => $cpNode !== null ? self::mercanciasDesdeXml($cpNode) : [],
            'figuraTransporte' => $cpNode !== null ? self::figuraTransporteDesdeXml($cpNode) : [],
        ];

        return $result;
    }

    /**
     * @param array<string, mixed> $comp
     * @return array<string, mixed>|null
     */
    private static function cartaPorteNode(array $comp): ?array
    {
        $complemento = self::node($comp, 'Complemento');
        if ($complemento === null) {
            return null;
        }
        foreach ($complemento as $key => $value) {
            if (is_string($key) && stripos($key, 'CartaPorte') !== false && is_array($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $cpNode
     * @return array<string, mixed>
     */
    private static function carta30DesdeXml(array $cpNode): array
    {
        $mercBlock = self::child($cpNode, 'Mercancias') ?? [];
        $auto = self::child($mercBlock, 'Autotransporte') ?? [];

        $carta30 = [
            'TranspInternac' => self::attr($cpNode, 'TranspInternac') ?: 'No',
            'EntradaSalidaMerc' => self::attr($cpNode, 'EntradaSalidaMerc'),
            'PaisOrigenDestino' => self::attr($cpNode, 'PaisOrigenDestino'),
            'ViaEntradaSalida' => self::attr($cpNode, 'ViaEntradaSalida'),
            'TotalDistRec' => self::attr($cpNode, 'TotalDistRec'),
            'RegistroISTMO30' => self::attr($cpNode, 'RegistroISTMO') ?: '',
            'UbicacionPoloOrigen' => self::attr($cpNode, 'UbicacionPoloOrigen'),
            'UbicacionPoloDestino' => self::attr($cpNode, 'UbicacionPoloDestino'),
            'mercancias' => [
                'PesoBrutoTotal' => self::attr($mercBlock, 'PesoBrutoTotal'),
                'UnidadPeso' => self::attr($mercBlock, 'UnidadPeso'),
                'PesoNetoTotal' => self::attr($mercBlock, 'PesoNetoTotal'),
                'NumTotalMercancias' => self::attr($mercBlock, 'NumTotalMercancias'),
                'CargoPorTasacion' => self::attr($mercBlock, 'CargoPorTasacion'),
                'LogisticaInversaRecoleccionDevolucion' => self::attr($mercBlock, 'LogisticaInversaRecoleccionDevolucion'),
            ],
            'autotransporte' => [
                'uso' => $auto !== [] ? 1 : 0,
                'PermSCT' => self::attr($auto, 'PermSCT'),
                'NumeroPermisoSCT' => self::attr($auto, 'NumPermisoSCT'),
                'IdentificacionVehicular' => self::identificacionVehicularDesdeXml($auto),
                'seguro' => self::segurosDesdeXml(self::child($auto, 'Seguros') ?? []),
                'Remolque' => self::remolquesDesdeXml($auto),
            ],
        ];

        return $carta30;
    }

    /**
     * @param array<string, mixed> $auto
     * @return array<string, string>
     */
    private static function identificacionVehicularDesdeXml(array $auto): array
    {
        $id = self::child($auto, 'IdentificacionVehicular') ?? [];

        return [
            'PlacaVM' => self::attr($id, 'PlacaVM'),
            'ConfigVehicular' => self::attr($id, 'ConfigVehicular'),
            'PesoBrutoVehicular' => self::attr($id, 'PesoBrutoVehicular'),
            'AnioModeloVM' => self::attr($id, 'AnioModeloVM'),
        ];
    }

    /**
     * @param array<string, mixed> $seguros
     * @return array<string, string>
     */
    private static function segurosDesdeXml(array $seguros): array
    {
        return [
            'AseguraRespCivil' => self::attr($seguros, 'AseguraRespCivil'),
            'PolizaRespCivil' => self::attr($seguros, 'PolizaRespCivil'),
            'AseguraMedAmbiente' => self::attr($seguros, 'AseguraMedAmbiente'),
            'PolizaMedAmbiente' => self::attr($seguros, 'PolizaMedAmbiente'),
            'AseguraCarga' => self::attr($seguros, 'AseguraCarga'),
            'PolizaCarga' => self::attr($seguros, 'PolizaCarga'),
            'PrimaSeguro' => self::attr($seguros, 'PrimaSeguro'),
        ];
    }

    /**
     * @param array<string, mixed> $auto
     * @return list<array{SubTipoRem: string, Placa: string}>
     */
    private static function remolquesDesdeXml(array $auto): array
    {
        $rem = self::child($auto, 'Remolques');
        if ($rem === null) {
            $single = self::child($auto, 'Remolque');
            if ($single !== null) {
                $rem = ['Remolque' => $single];
            }
        }
        if ($rem === null) {
            return [];
        }

        $items = self::listify(self::child($rem, 'Remolque') ?? $rem);
        $out = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $sub = self::attr($item, 'SubTipoRem');
            $placa = self::attr($item, 'Placa');
            if ($sub === '' && $placa === '') {
                continue;
            }
            $out[] = [
                'SubTipoRem' => $sub,
                'Placa' => $placa,
            ];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $cpNode
     * @return list<array<string, mixed>>
     */
    private static function ubicacionesDesdeXml(array $cpNode): array
    {
        $ubicBlock = self::child($cpNode, 'Ubicaciones');
        if ($ubicBlock === null) {
            return [];
        }
        $items = self::listify(self::child($ubicBlock, 'Ubicacion') ?? $ubicBlock);
        $out = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $tipo = self::attr($item, 'TipoUbicacion');
            $obj = [
                'TipoUbicacion' => $tipo,
                'RFCRemitenteDestinatario' => self::attr($item, 'RFCRemitenteDestinatario'),
                'NombreRemitenteDestinatario' => self::attr($item, 'NombreRemitenteDestinatario'),
                'IDUbicacion' => self::attr($item, 'IDUbicacion'),
                'FechaHoraSalidaLlegada' => self::attr($item, 'FechaHoraSalidaLlegada'),
            ];
            if ($tipo === 'Destino') {
                $obj['DistanciaRecorrida'] = self::attr($item, 'DistanciaRecorrida');
            }
            $dom = self::child($item, 'Domicilio');
            if ($dom !== null) {
                $obj['domicilio'] = [
                    'CodigoPostal' => self::attr($dom, 'CodigoPostal'),
                    'Calle' => self::attr($dom, 'Calle'),
                    'NumeroExterior' => self::attr($dom, 'NumeroExterior'),
                    'NumeroInterior' => self::attr($dom, 'NumeroInterior'),
                    'Colonia' => self::attr($dom, 'Colonia'),
                    'Municipio' => self::attr($dom, 'Municipio'),
                    'Estado' => self::attr($dom, 'Estado'),
                    'Pais' => self::attr($dom, 'Pais') ?: 'MEX',
                ];
            }
            $out[] = $obj;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $cpNode
     * @return list<array<string, mixed>>
     */
    private static function mercanciasDesdeXml(array $cpNode): array
    {
        $mercBlock = self::child($cpNode, 'Mercancias');
        if ($mercBlock === null) {
            return [];
        }
        $items = self::listify(self::child($mercBlock, 'Mercancia') ?? []);
        $out = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $out[] = [
                'BienesTransp' => self::attr($item, 'BienesTransp'),
                'Descripcion' => self::attr($item, 'Descripcion'),
                'ClaveSTCC' => self::attr($item, 'ClaveSTCC'),
                'Cantidad' => self::attr($item, 'Cantidad'),
                'ClaveUnidad' => self::attr($item, 'ClaveUnidad'),
                'Unidad' => self::attr($item, 'Unidad'),
                'Dimensiones' => self::attr($item, 'Dimensiones'),
                'PesoEnKg' => self::attr($item, 'PesoEnKg'),
                'MaterialPeligroso' => self::attr($item, 'MaterialPeligroso') ?: 'No',
                'CveMaterialPeligroso' => self::attr($item, 'CveMaterialPeligroso'),
                'Embalaje' => self::attr($item, 'Embalaje'),
                'DescripEmbalaje' => self::attr($item, 'DescripEmbalaje'),
                'SectorCOFEPRIS' => self::attr($item, 'SectorCOFEPRIS'),
                'ValorMercancia' => self::attr($item, 'ValorMercancia'),
                'Moneda' => self::attr($item, 'Moneda'),
                'FraccionArancelaria' => self::attr($item, 'FraccionArancelaria'),
                'UUIDComercioExt' => self::attr($item, 'UUIDComercioExt'),
                'TipoMateria' => self::attr($item, 'TipoMateria'),
                'DescripcionMateria' => self::attr($item, 'DescripcionMateria'),
            ];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $cpNode
     * @return list<array<string, mixed>>
     */
    private static function figuraTransporteDesdeXml(array $cpNode): array
    {
        $figBlock = self::child($cpNode, 'FiguraTransporte');
        if ($figBlock === null) {
            return [];
        }
        $items = self::listify(self::child($figBlock, 'TiposFigura') ?? $figBlock);
        $out = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $tipo = self::attr($item, 'TipoFigura');
            $out[] = [
                'TipoFigura' => self::mapTipoFigura($tipo),
                'RFCfigura' => self::attr($item, 'RFCFigura'),
                'NumLicencia' => self::attr($item, 'NumLicencia'),
                'NombreFigura' => self::attr($item, 'NombreFigura'),
                'NumRegIdTribFigura' => self::attr($item, 'NumRegIdTribFigura'),
                'ResidenciaFiscalFigura' => self::attr($item, 'ResidenciaFiscalFigura'),
                'PartesTransporte' => [],
                'Domicilio' => null,
            ];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $comp
     * @return list<array<string, mixed>>
     */
    private static function conceptosDesdeXml(array $comp): array
    {
        $conceptosNode = self::node($comp, 'Conceptos');
        if ($conceptosNode === null) {
            return [];
        }
        $items = self::listify(self::child($conceptosNode, 'Concepto') ?? $conceptosNode);
        $out = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $concepto = [
                'ClaveProdServ' => self::attr($item, 'ClaveProdServ'),
                'NoIdentificacion' => self::attr($item, 'NoIdentificacion'),
                'Cantidad' => self::attr($item, 'Cantidad'),
                'ClaveUnidad' => self::attr($item, 'ClaveUnidad'),
                'Unidad' => self::attr($item, 'Unidad'),
                'Descripcion' => self::attr($item, 'Descripcion'),
                'ValorUnitario' => self::attr($item, 'ValorUnitario'),
                'Importe' => self::attr($item, 'Importe'),
                'Descuento' => self::attr($item, 'Descuento') ?: '0',
                'ObjetoImp' => self::attr($item, 'ObjetoImp') ?: '02',
            ];
            $impNode = self::node($item, 'Impuestos');
            if ($impNode !== null) {
                $concepto['Impuestos'] = self::impuestosConceptoDesdeXml($impNode);
            }
            $out[] = $concepto;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $impNode
     * @return array<string, list<array<string, string>>>
     */
    private static function impuestosConceptoDesdeXml(array $impNode): array
    {
        $result = [];
        $trasladosNode = self::node($impNode, 'Traslados');
        if ($trasladosNode !== null) {
            $items = self::listify(self::child($trasladosNode, 'Traslado') ?? $trasladosNode);
            $result['Traslados'] = [];
            foreach ($items as $t) {
                if (!is_array($t)) {
                    continue;
                }
                $imp = self::attr($t, 'Impuesto');
                $tipo = self::attr($t, 'TipoFactor');
                $tasa = self::attr($t, 'TasaOCuota');
                $result['Traslados'][] = [
                    'Base' => self::attr($t, 'Base'),
                    'Impuesto' => $imp,
                    'TipoFactor' => $tipo,
                    'TasaOCuota' => $tasa,
                    'Importe' => self::attr($t, 'Importe'),
                    'extra' => self::extraImpuesto($imp, $tipo, $tasa),
                ];
            }
        }
        $retenidosNode = self::node($impNode, 'Retenciones');
        if ($retenidosNode !== null) {
            $items = self::listify(self::child($retenidosNode, 'Retencion') ?? $retenidosNode);
            $result['Retenidos'] = [];
            foreach ($items as $t) {
                if (!is_array($t)) {
                    continue;
                }
                $imp = self::attr($t, 'Impuesto');
                $tipo = self::attr($t, 'TipoFactor');
                $tasa = self::attr($t, 'TasaOCuota');
                $result['Retenidos'][] = [
                    'Base' => self::attr($t, 'Base'),
                    'Impuesto' => $imp,
                    'TipoFactor' => $tipo,
                    'TasaOCuota' => $tasa,
                    'Importe' => self::attr($t, 'Importe'),
                    'extra' => self::extraImpuesto($imp, $tipo, $tasa),
                ];
            }
        }

        return $result;
    }

    private static function extraImpuesto(string $imp, string $tipo, string $tasa): string
    {
        $labels = [
            '002-0.160000' => 'IVA_16%',
            '002-0.000000' => 'IVA_0%',
            '002-0.080000' => 'IVA_8%',
            '001-0.125000' => 'ISR_10%',
            '003-0.080000' => 'IEPS_8%',
        ];
        $key = $imp . '-' . $tasa;
        $label = $labels[$key] ?? ($imp . '_' . $tasa);

        return '1-' . $imp . '-' . $tipo . '-' . $tasa . '-' . $label;
    }

    private static function mapTipoFigura(string $code): string
    {
        $map = [
            '01' => '01-Operador',
            '02' => '02-Propietario',
            '03' => '03-Arrendatario',
            '04' => '04-Notificado',
            '05' => '05-Integrante de Coordinados',
        ];

        return $map[$code] ?? ($code !== '' ? $code . '-Figura' : '');
    }

    /**
     * @param array<string, mixed> $parent
     */
    private static function node(array $parent, string $localName): ?array
    {
        foreach ($parent as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            $name = self::localName($key);
            if (strcasecmp($name, $localName) === 0 && is_array($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $parent
     * @return array<string, mixed>|null
     */
    private static function child(array $parent, string $localName): ?array
    {
        foreach ($parent as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if (strcasecmp(self::localName($key), $localName) === 0 && is_array($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $node
     */
    private static function attr(array $node, string $name): string
    {
        $at = '@' . $name;
        if (isset($node[$at])) {
            return trim((string)$node[$at]);
        }
        if (isset($node[$name])) {
            return trim((string)$node[$name]);
        }

        return '';
    }

    private static function localName(string $key): string
    {
        $pos = strrpos($key, ':');

        return $pos !== false ? substr($key, $pos + 1) : $key;
    }

    /**
     * @return list<mixed>
     */
    private static function listify(mixed $node): array
    {
        if (!is_array($node)) {
            return [];
        }
        if ($node === []) {
            return [];
        }
        if (self::isList($node)) {
            return array_values($node);
        }

        return [$node];
    }

    /**
     * @param array<mixed> $arr
     */
    private static function isList(array $arr): bool
    {
        if ($arr === []) {
            return false;
        }

        return array_keys($arr) === range(0, count($arr) - 1);
    }
}
