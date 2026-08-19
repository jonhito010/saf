<?php
declare(strict_types=1);

namespace App\Utility;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Encoding\EncodingInterface;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;

/**
 * URLs de verificación QR conforme al SAT (CFDI 4.0 y Complemento Carta Porte 3.1).
 *
 * @see https://verificacfdi.facturaelectronica.sat.gob.mx/
 * @see Anexo técnico Complemento Carta Porte 3.1 — sección código QR
 */
class CfdiQrSat
{
    /** Lado mínimo del cuadrado impreso (Anexo 20 CFDI 4.0, sección D). */
    public const TAMANO_MINIMO_CM = 2.75;

    /** Valor CSS para el PDF (Dompdf respeta cm en impresión). */
    public const TAMANO_IMPRESION_CSS = '2.75cm';

    /** Resolución raster para nitidez al imprimir ~2.75 cm. */
    public const DPI_GENERACION = 300;

    public const URL_CFDI = 'https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx';

    public const URL_CARTA_PORTE = 'https://verificacfdi.facturaelectronica.sat.gob.mx/verificaccp/default.aspx';

    /**
     * URL del QR del comprobante (timbre fiscal digital).
     */
    public static function urlCfdi(object $obj, bool $esVistaPrevia = false): string
    {
        if ($esVistaPrevia) {
            return 'Representación de un QR para ilustración — Vista previa (sin timbre fiscal).';
        }

        $uuid = (string)($obj->{'Complemento'}->{'TimbreFiscalDigital'}->{'@attributes'}->{'UUID'} ?? '');
        if ($uuid === '') {
            return 'CFDI sin timbre fiscal digital.';
        }

        $re = (string)($obj->{'Emisor'}->{'@attributes'}->{'Rfc'} ?? '');
        $rr = (string)($obj->{'Receptor'}->{'@attributes'}->{'Rfc'} ?? '');
        $total = (float)($obj->{'@attributes'}->{'Total'} ?? 0);
        $sello = (string)($obj->{'@attributes'}->{'Sello'} ?? '');
        $fe = substr($sello, -8);

        $query = http_build_query([
            'id' => $uuid,
            're' => $re,
            'rr' => $rr,
            'tt' => self::formatearTotalQr($total),
            'fe' => $fe,
        ], '', '&', PHP_QUERY_RFC3986);

        return self::URL_CFDI . '?' . $query;
    }

    /**
     * URL del QR del complemento Carta Porte 3.1 (adicional al QR del CFDI).
     * Formato SAT: ?IdCCP=...&FechaOrig=...&FechaTimb=...
     */
    public static function urlCartaPorte31(object $obj, bool $esVistaPrevia = false): ?string
    {
        if (!isset($obj->{'Complemento'}->{'CartaPorte'})) {
            return null;
        }

        $cp = $obj->{'Complemento'}->{'CartaPorte'};
        $version = (string)($cp->{'@attributes'}->{'Version'} ?? '');
        if ($version !== '3.1') {
            return null;
        }

        if ($esVistaPrevia) {
            return 'Representación de un QR para ilustración — Vista previa Carta Porte 3.1.';
        }

        $idCcp = (string)($cp->{'@attributes'}->{'IdCCP'} ?? '');
        $fechaOrig = self::fechaOrigenCartaPorte($cp);
        $fechaTimb = (string)($obj->{'Complemento'}->{'TimbreFiscalDigital'}->{'@attributes'}->{'FechaTimbrado'} ?? '');

        if ($idCcp === '' || $fechaOrig === '' || $fechaTimb === '') {
            return null;
        }

        return self::URL_CARTA_PORTE
            . '?IdCCP=' . rawurlencode($idCcp)
            . '&FechaOrig=' . rawurlencode($fechaOrig)
            . '&FechaTimb=' . rawurlencode($fechaTimb);
    }

    /**
     * Total con 10 enteros + 6 decimales (formato SAT para parámetro tt).
     */
    public static function formatearTotalQr(float $total): string
    {
        $fmt = number_format($total, 6, '.', '');
        [$entero, $decimal] = array_pad(explode('.', $fmt, 2), 2, '000000');
        $entero = str_pad($entero, 10, '0', STR_PAD_LEFT);
        $decimal = str_pad(substr($decimal, 0, 6), 6, '0', STR_PAD_RIGHT);

        return $entero . '.' . $decimal;
    }

    private static function fechaOrigenCartaPorte(object $cartaPorte): string
    {
        if (!isset($cartaPorte->{'Ubicaciones'}->{'Ubicacion'})) {
            return '';
        }

        $ubicaciones = $cartaPorte->{'Ubicaciones'}->{'Ubicacion'};
        if (!is_array($ubicaciones)) {
            $ubicaciones = [$ubicaciones];
        }

        foreach ($ubicaciones as $ubicacion) {
            $attrs = $ubicacion->{'@attributes'} ?? null;
            if ($attrs === null) {
                continue;
            }
            $tipo = (string)($attrs->{'TipoUbicacion'} ?? '');
            $idUb = (string)($attrs->{'IDUbicacion'} ?? '');
            if ($tipo === 'Origen' || str_starts_with($idUb, 'OR')) {
                return (string)($attrs->{'FechaHoraSalidaLlegada'} ?? '');
            }
        }

        $first = $ubicaciones[0];
        $attrs = $first->{'@attributes'} ?? null;

        return $attrs ? (string)($attrs->{'FechaHoraSalidaLlegada'} ?? '') : '';
    }

    /**
     * Píxeles del PNG/SVG del QR (~300 DPI sobre 2.75 cm).
     */
    public static function pixelesGeneracion(): int
    {
        return (int) round((self::TAMANO_MINIMO_CM / 2.54) * self::DPI_GENERACION);
    }

    /**
     * QR conforme SAT: ISO/IEC 18004, corrección de error mínima M (Medium).
     */
    public static function crearQrCode(string $data, ?EncodingInterface $encoding = null): QrCode
    {
        return new QrCode(
            data: $data,
            encoding: $encoding ?? new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: self::pixelesGeneracion(),
            margin: 4,
        );
    }
}
