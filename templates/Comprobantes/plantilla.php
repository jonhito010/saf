<?php
/* =============================================================================
 * Plantilla CFDI 4.0 — layout 100 % basado en <table> para compatibilidad
 * con Dompdf (sin flexbox, sin Bootstrap CDN, sin llamadas externas).
 * ============================================================================= */

// ---------------------------------------------------------------------------
// RFCs con reglas especiales de dirección en PDF
// ---------------------------------------------------------------------------
$rfcs = ['DIE190523UQA', 'CIS230222SE9', 'DERR760115396'];

// ---------------------------------------------------------------------------
// Helpers de inclusión
// ---------------------------------------------------------------------------
if (!function_exists('cfdi_cartaporte_node')) {
    /**
     * Nodo Carta Porte dentro del complemento (2.0 / 3.0 / 3.1).
     */
    function cfdi_cartaporte_node(?object $obj): ?object
    {
        if ($obj === null || !isset($obj->{'Complemento'})) {
            return null;
        }
        $comp = $obj->{'Complemento'};
        if (isset($comp->{'CartaPorte'})) {
            return $comp->{'CartaPorte'};
        }
        foreach ((array)$comp as $key => $value) {
            if (is_string($key) && stripos($key, 'CartaPorte') !== false && is_object($value)) {
                return $value;
            }
        }

        return null;
    }
}

if (!function_exists('cfdi_tpl_include')) {
    function cfdi_tpl_include(string $relative): void
    {
        $path = dirname(__FILE__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (!is_file($path)) {
            return;
        }
        if (isset($GLOBALS['_cfdi_pdf_scope']) && is_array($GLOBALS['_cfdi_pdf_scope'])) {
            extract($GLOBALS['_cfdi_pdf_scope'], EXTR_SKIP);
        }
        include $path;
    }
}

// Texto largo de sellos: una sola cadena; Dompdf ajusta al ancho completo de la celda.
if (!function_exists('cfdi_sello_impreso')) {
    function cfdi_sello_impreso(string $texto): string
    {
        $texto = preg_replace('/\s+/', '', trim($texto));
        if ($texto === '') {
            return '';
        }

        return h($texto);
    }
}

// ---------------------------------------------------------------------------
// Catálogo local de ClaveUnidad (sin curl)
// ---------------------------------------------------------------------------
if (!function_exists('cfdi_clave_unidad')) {
    function cfdi_clave_unidad(string $clave): string
    {
        $cat = [
            'ACT'=>'Actividad','BB'=>'Caja','DAY'=>'Día','E48'=>'Unidad de Servicio',
            'GRM'=>'Gramo','H87'=>'Pieza','HUR'=>'Hora','KGM'=>'Kilogramo',
            'KT'=>'Kit','LTR'=>'Litro','MON'=>'Mes','MTK'=>'Metro cuadrado',
            'MTQ'=>'Metro cúbico','MTR'=>'Metro','MWH'=>'Megavatio hora',
            'PR'=>'Par','SET'=>'Conjunto','TNE'=>'Tonelada','XBX'=>'Caja',
            'XKI'=>'Kit','XPK'=>'Paquete','XUN'=>'Unidad','YRD'=>'Yarda',
            'Z3'=>'Unidad equivalente',
        ];
        return $cat[$clave] ?? $clave;
    }
}

// ---------------------------------------------------------------------------
// Catálogos SAT como funciones (evitan switch gigantes en el HTML)
// ---------------------------------------------------------------------------
if (!function_exists('cfdi_regimen')) {
    function cfdi_regimen(string $c): string {
        $t = [
            '601'=>'601 - General de Ley Personas Morales',
            '603'=>'603 - Personas Morales con Fines no Lucrativos',
            '605'=>'605 - Sueldos y Salarios e Ingresos Asimilados a Salarios',
            '606'=>'606 - Arrendamiento','608'=>'608 - Demás ingresos',
            '609'=>'609 - Consolidación',
            '610'=>'610 - Residentes en el Extranjero sin Establecimiento Permanente en México',
            '611'=>'611 - Ingresos por Dividendos (socios y accionistas)',
            '612'=>'612 - Personas Físicas con Actividades Empresariales y Profesionales',
            '614'=>'614 - Ingresos por intereses','616'=>'616 - Sin obligaciones fiscales',
            '620'=>'620 - Sociedades Cooperativas de Producción que optan por diferir sus ingresos',
            '621'=>'621 - Incorporación Fiscal',
            '622'=>'622 - Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
            '623'=>'623 - Opcional para Grupos de Sociedades','624'=>'624 - Coordinados',
            '628'=>'628 - Hidrocarburos',
            '607'=>'607 - Régimen de Enajenación o Adquisición de Bienes',
            '629'=>'629 - De los Regímenes Fiscales Preferentes y de las Empresas Multinacionales',
            '630'=>'630 - Enajenación de acciones en bolsa de valores',
            '615'=>'615 - Régimen de los ingresos por obtención de premios',
            '625'=>'625 - Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas',
            '626'=>'626 - Régimen Simplificado de Confianza',
        ];
        return $t[$c] ?? $c;
    }
}
if (!function_exists('cfdi_uso')) {
    function cfdi_uso(string $c): string {
        $t = [
            'D01'=>'D01 - Honorarios médicos, dentales y gastos hospitalarios',
            'D02'=>'D02 - Gastos médicos por incapacidad o discapacidad',
            'D03'=>'D03 - Gastos funerales','D04'=>'D04 - Donativos',
            'D05'=>'D05 - Intereses reales efectivamente pagados por créditos hipotecarios',
            'D06'=>'D06 - Aportaciones voluntarias al SAR',
            'D07'=>'D07 - Primas por seguros de gastos médicos',
            'D08'=>'D08 - Gastos de transportación escolar obligatoria',
            'D09'=>'D09 - Depósitos en cuentas para el ahorro, primas que tengan como base planes de pensiones',
            'D10'=>'D10 - Pagos por servicios educativos (colegiaturas)',
            'G01'=>'G01 - Adquisición de mercancías',
            'G02'=>'G02 - Devoluciones, descuentos o bonificaciones',
            'G03'=>'G03 - Gastos en general','I01'=>'I01 - Construcciones',
            'I02'=>'I02 - Mobilario y equipo de oficina por inversiones',
            'I03'=>'I03 - Equipo de transporte',
            'I04'=>'I04 - Equipo de computo y accesorios',
            'I05'=>'I05 - Dados, troqueles, moldes, matrices y herramental',
            'I06'=>'I06 - Comunicaciones telefónicas','I07'=>'I07 - Comunicaciones satelitales',
            'I08'=>'I08 - Otra maquinaria y equipo','S01'=>'S01 - Sin efectos fiscales',
        ];
        return $t[$c] ?? $c;
    }
}
if (!function_exists('cfdi_metodo_pago')) {
    function cfdi_metodo_pago(string $c): string {
        $t = ['PUE'=>'PUE - Pago en una sola exhibición','PIP'=>'PIP - Pago inicial y parcialidades','PPD'=>'PPD - Pago en parcialidades o diferido'];
        return $t[$c] ?? $c;
    }
}
if (!function_exists('cfdi_forma_pago')) {
    function cfdi_forma_pago(string $c): string {
        $t = [
            '01'=>'01 - Efectivo','02'=>'02 - Cheque Nominativo',
            '03'=>'03 - Transferencia Electrónica de Fondos','04'=>'04 - Tarjeta de Crédito',
            '05'=>'05 - Monedero Electrónico','06'=>'06 - Dinero Electrónico',
            '08'=>'08 - Vales de Despensa','12'=>'12 - Dación en Pago',
            '13'=>'13 - Pago por Subrogación','14'=>'14 - Pago por Consignación',
            '15'=>'15 - Condonación','17'=>'17 - Compensación','23'=>'23 - Novación',
            '24'=>'24 - Confusión','25'=>'25 - Remisión de Deuda',
            '26'=>'26 - Prescripción o Caducidad','27'=>'27 - A satisfacción del Acreedor',
            '28'=>'28 - Tarjeta de Débito','29'=>'29 - Tarjeta de Servicio',
            '30'=>'30 - Aplicación de Anticipos','31'=>'31 - Intermediario Pagos','99'=>'99 - Por Definir',
        ];
        return $t[$c] ?? $c;
    }
}
if (!function_exists('cfdi_tipo_comprobante')) {
    function cfdi_tipo_comprobante(string $c): string {
        $t = ['I'=>'I - Ingreso','E'=>'E - Egreso','T'=>'T - Traslado','P'=>'P - Pago','N'=>'N - Nómina'];
        return $t[$c] ?? $c;
    }
}
if (!function_exists('cfdi_exportacion')) {
    function cfdi_exportacion(string $c): string {
        $t = [
            '01'=>'01 - No aplica','02'=>'02 - Definitiva con clave A1','03'=>'03 - Temporal',
            '04'=>'04 - Definitiva con clave distinta a A1 o cuando no existe enajenación en términos del CFF',
        ];
        return $t[$c] ?? $c;
    }
}
if (!function_exists('cfdi_moneda')) {
    function cfdi_moneda(string $c): string {
        $t = [
            'MXN'=>'MXN - Peso Mexicano','USD'=>'USD - Dólar Americano','EUR'=>'EUR - Euro',
            'GBP'=>'GBP - Libra Esterlina','CAD'=>'CAD - Dólar Canadiense',
            'JPY'=>'JPY - Yen','CHF'=>'CHF - Franco Suizo','ARS'=>'ARS - Peso Argentino',
            'COP'=>'COP - Peso Colombiano','PEN'=>'PEN - Nuevo Sol','UYU'=>'UYU - Peso Uruguayo',
            'GTQ'=>'GTQ - Quetzal','HNL'=>'HNL - Lempira','CRC'=>'CRC - Colón Costarricense',
            'BOB'=>'BOB - Boliviano','CLP'=>'CLP - Peso Chileno','DOP'=>'DOP - Peso Dominicano',
            'BRL'=>'BRL - Real Brasileño','PYG'=>'PYG - Guaraní','NIO'=>'NIO - Córdoba Oro',
            'AUD'=>'AUD - Dólar Australiano','NZD'=>'NZD - Dólar de Nueva Zelanda',
            'MXV'=>'MXV - México Unidad de Inversión (UDI)','XXX'=>'XXX - Sin moneda específica',
        ];
        return $t[$c] ?? $c;
    }
}
if (!function_exists('cfdi_impuesto')) {
    function cfdi_impuesto(string $c): string {
        return ['001'=>'001 - ISR','002'=>'002 - IVA','003'=>'003 - IEPS'][$c] ?? $c;
    }
}

// ---------------------------------------------------------------------------
// Vars de seguridad
// ---------------------------------------------------------------------------
if (!isset($datosParametro) || !is_array($datosParametro)) { $datosParametro = []; }
if (!isset($direccion)) { $direccion = null; }
if (!isset($mx))        { $mx = null; }
if (!isset($textQr))    { $textQr = ''; }
if (!isset($textQrCP))  { $textQrCP = ''; }
if (!isset($qrpng))     { $qrpng = ''; }
if (!isset($qrpngCP))   { $qrpngCP = ''; }
if (!isset($mostrarCartaPortePdf)) { $mostrarCartaPortePdf = true; }

$tieneCartaPorteEnXml = cfdi_cartaporte_node($obj) !== null;
$tieneQrCartaPorte = $mostrarCartaPortePdf && !empty($qrpngCP);

$color = [
    'intenso'            => '#1B3A8C',   // --navy  (barras de sección, thead conceptos)
    'borde'              => '#2BBFB3',   // --teal  (bordes de celdas y tablas)
    'transparente'       => '#E4F8F7',   // --teal-light (fondos de filas secundarias)
    'comprobante'        => '#1B3A8C',   // --navy  (etiquetas de texto)
    'letra'              => '#ffffff',   // texto sobre fondo oscuro
    'colorEncabezadoUUID'=> '#2BBFB3',   // --teal  (encabezado UUID)
];

// Moneda del comprobante
$monedaComp = (string)($obj->{'@attributes'}->{'Moneda'} ?? 'MXN');
$esEur      = ($monedaComp === 'EUR');

// Función inline para formatear importes según moneda
$fmtMonto = function($v) use ($esEur): string {
    if ($v === null || $v === '') return $esEur ? '0.00 &euro;' : '$ 0.00';
    return $esEur
        ? number_format((float)$v, 2, '.', ',') . ' &euro;'
        : '$ ' . number_format((float)$v, 2, '.', ',');
};

// UUID y estado del timbre
$uuid         = (string)($obj->{'Complemento'}->{'TimbreFiscalDigital'}->{'@attributes'}->{'UUID'} ?? '');
$timbrado     = ($uuid !== '');

// Conceptos (normalizar a array)
$conceptos = isset($obj->{'Conceptos'}->{'Concepto'}->{'@attributes'})
    ? [$obj->{'Conceptos'}->{'Concepto'}]
    : (array)($obj->{'Conceptos'}->{'Concepto'} ?? []);

// Estilos de celda de layout reutilizables
$tdL  = 'style="font-size:11px; color:#000; vertical-align:top; padding:2px 4px;"';
$tdLb = 'style="font-size:11px; color:#000; vertical-align:top; padding:2px 4px; font-weight:bold; text-align:right;"';
?>
<!DOCTYPE html>
<html lang="es-mx">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?= $timbrado ? h($uuid) : '*VISTA PREVIA*' ?></title>
<style type="text/css">
/* === Reset básico === */
* { box-sizing: border-box; margin: 0; padding: 0; }
@page { margin: 10mm 12mm 16mm 12mm; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #000; padding-bottom: 12mm; }
table { border-collapse: collapse; }
p { margin: 0; }

/* === Sección de encabezado === */
.cfdi-label  { font-size: 10px; font-weight: bold; color: <?= $color['comprobante'] ?>; }
.cfdi-uuid   { font-size: 8.5px; word-break: break-all; }
.cfdi-mini   { font-size: 9px; }
.cfdi-bold   { font-weight: bold; }
.cfdi-center { text-align: center; }
.cfdi-right  { text-align: right; }

/* === Barra de sección === */
.bar-naranja {
    background: <?= $color['intenso'] ?>;
    color: <?= $color['letra'] ?>;
    font-weight: bold;
    font-size: 11px;
    padding: 4px 8px;
}
.bar-borde {
    border: 1px solid <?= $color['borde'] ?>;
    background: <?= $color['transparente'] ?>;
    font-size: 11px;
    padding: 2px 6px;
}

/* === Tabla de conceptos === */
.tbl-conceptos { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 6px; }
.tbl-conceptos thead tr { background: <?= $color['intenso'] ?>; color: <?= $color['letra'] ?>; }
.tbl-conceptos th { padding: 3px 4px; text-align: center; }
.tbl-conceptos td { padding: 2px 3px; word-break: break-word; border-bottom: 1px solid <?= $color['borde'] ?>; }
.tbl-conceptos .imp-row { background: <?= $color['transparente'] ?>; border-top: 1px solid <?= $color['borde'] ?>; }

/* === Totales === */
.tbl-totales { width: 100%; }
.tbl-totales td { font-size: 11px; padding: 1px 4px; }
.tbl-totales .lbl { text-align: right; font-weight: bold; width: 55%; }
.tbl-totales .val { text-align: right; width: 45%; }
.total-final { font-size: 14px; font-weight: bold; border-top: 2px solid <?= $color['borde'] ?>; padding-top: 3px; }

/* === Sellos / verificación === */
.sello-lbl {
    font-size: 9px;
    font-weight: bold;
    color: <?= $color['comprobante'] ?>;
    padding: 4px 0 2px;
}
.sello-val {
    font-size: 6.5px;
    font-family: DejaVu Sans Mono, DejaVu Sans, monospace;
    line-height: 8px;
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-all;
    white-space: normal;
    width: 100%;
    padding: 0 0 4px;
    border-bottom: 1px solid #000;
    vertical-align: top;
    page-break-inside: auto;
}
.sello-val--ultimo { border-bottom: none; padding-bottom: 0; }
.parrafosat { font-size: 6.5px; word-break: break-all; white-space: pre-wrap; line-height: 8px; margin-bottom: 2px; }
.parrafosat.sin-borde { border-bottom: none; }
.qr-block { text-align: center; vertical-align: top; padding: 4px 6px; }
.qr-block--sellos { text-align: left; padding: 0 2px 0 0; }
.qr-block--sellos img { display: block; margin: 0 0 6px 0; }
.qr-sat {
    width: <?= \App\Utility\CfdiQrSat::TAMANO_IMPRESION_CSS ?>;
    height: <?= \App\Utility\CfdiQrSat::TAMANO_IMPRESION_CSS ?>;
    min-width: <?= \App\Utility\CfdiQrSat::TAMANO_IMPRESION_CSS ?>;
    min-height: <?= \App\Utility\CfdiQrSat::TAMANO_IMPRESION_CSS ?>;
    max-width: none;
}
.qr-block--sellos .qr-cp { margin-top: 8px; padding-top: 6px; border-top: 1px solid #ccc; }
.qr-title { font-size: 9px; font-weight: bold; margin-bottom: 4px; }
.qr-url { font-size: 6.5px; word-break: break-all; line-height: 8px; margin-top: 3px; color: #333; }
.verificacion { border-top: 1px solid #000; border-bottom: 1px solid #000; margin: 8px 0 8px -6px; padding: 2px 0; width: 100%; }
.verificacion-layout { width: 100%; border-collapse: collapse; table-layout: fixed; }
.verificacion-layout .col-qr { width: 16%; vertical-align: top; padding: 0 4px 0 0; }
.verificacion-layout .col-sellos { width: 84%; vertical-align: top; padding: 0; }
.cfdi-pie-fiscal { page-break-inside: avoid; }
.cfdi-page-break { page-break-before: always; }
.cfdi-cp-section { padding: 10px 12px; }
.cfdi-cp-verificacion { margin-top: 14px; padding-top: 10px; border-top: 1px solid #ccc; }
.sellos-cfdi { width: 100%; border-collapse: collapse; table-layout: fixed; }
.sellos-cfdi td { width: 100%; padding: 0; }

/* === Firmas === */
.firma-nombre { border-top: 2px solid #000; padding-top: 6px; text-align: center; margin-top: 80px; }
.firma-puesto { font-weight: bold; text-align: center; }

/* === Etiquetas === */
.etiq-nombre { font-weight: bold; padding: 2px 4px; border-radius: 4px 4px 0 0; }
.etiq-valor  { border: 1px solid <?= $color['borde'] ?>; padding: 4px; border-radius: 0 0 4px 4px; font-size: 11px; }

/* === Auxiliar === */
.layout { width: 100%; border-collapse: collapse; }
.layout td { vertical-align: top; }
.separator { height: 6px; }

/* === Pie de página fijo (Dompdf) === */
.cfdi-pdf-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    width: 100%;
    text-align: center;
    font-size: 10px;
    color: #000;
    padding: 5px 12mm 0;
    border-top: 1px solid #ccc;
    background: #fff;
}
</style>
</head>
<body>
<section class="cfdi-factura-section" style="padding: 10px 12px;">

<!-- =========================================================
     TÍTULO — FACTURA / CFDI
     ========================================================= -->
<p style="font-size:10px; text-align:center; margin-bottom:4px;">
    COMPROBANTE FISCAL DIGITAL A TRAVÉS DE INTERNET — VERSIÓN <?= h((string)($obj->{'@attributes'}->{'Version'} ?? '4.0')) ?>
</p>

<!-- =========================================================
     ENCABEZADO: LOGO | EMISOR | FOLIO FISCAL / UUID / SERIE
     ========================================================= -->
<table class="layout" style="margin-bottom:4px;">
<tr>
    <!-- LOGO -->
    <td style="width:18%; vertical-align:middle; text-align:center; padding-right:6px;">
        <?php if (!empty($logo)): ?>
            <img src="<?= $logo ?>" style="max-width:130px; height:auto;">
        <?php endif; ?>
    </td>

    <!-- DATOS EMISOR -->
    <td style="width:42%; vertical-align:top; padding-right:8px;">
        <p class="cfdi-bold" style="font-size:13px;"><?= h((string)$obj->{'Emisor'}->{'@attributes'}->{'Rfc'}) ?></p>
        <p class="cfdi-bold" style="font-size:12px; margin-bottom:2px;"><?= h((string)$obj->{'Emisor'}->{'@attributes'}->{'Nombre'}) ?></p>
        <p style="font-size:10px; font-weight:bold; margin-bottom:1px;">
            Régimen fiscal: <?= h(cfdi_regimen((string)($obj->{'Emisor'}->{'@attributes'}->{'RegimenFiscal'} ?? ''))) ?>
        </p>
        <?php if (!empty($datosParametro['emailEmisor'])): ?>
            <p style="font-size:10px;">Email: <?= h($datosParametro['emailEmisor']) ?></p>
        <?php endif; ?>
        <?php if (!empty($datosParametro['telefonoEmisor'])): ?>
            <p style="font-size:10px;">Teléfono: <?= h($datosParametro['telefonoEmisor']) ?></p>
        <?php endif; ?>
        <?php
        $partesDirEmisor = [];
        foreach (['calleEmisor','noExteriorEmisor','noInteriorEmisor','coloniaEmisor','codigoPostalEmisor','localidadEmisor','municipioEmisor','estadoEmisor','paisEmisor'] as $k) {
            if (!empty($datosParametro[$k])) $partesDirEmisor[] = $datosParametro[$k];
        }
        if ($partesDirEmisor): ?>
            <p style="font-size:10px;"><?= h(implode(', ', $partesDirEmisor)) ?></p>
        <?php endif; ?>
    </td>

    <!-- FOLIO FISCAL / UUID / SERIE / FECHA -->
    <td style="width:40%; vertical-align:top;">
        <!-- UUID -->
        <table width="100%" style="margin-bottom:4px;">
            <tr>
                <td class="bar-naranja cfdi-center">FOLIO FISCAL</td>
            </tr>
            <tr>
                <td style="border:1px solid <?= $color['borde'] ?>; padding:3px 6px; font-size:8.5px; word-break:break-all; text-align:center;">
                    <?= $timbrado ? h($uuid) : '<em>*** V I S T A &nbsp; P R E V I A ***</em>' ?>
                </td>
            </tr>
        </table>

        <!-- SERIE / FOLIO / FECHA -->
        <table width="100%">
            <tr>
                <td style="width:33%; border:1px solid <?= $color['borde'] ?>; background:<?= $color['transparente'] ?>; text-align:center; font-weight:bold; font-size:10px; padding:2px;">SERIE</td>
                <td style="width:33%; border:1px solid <?= $color['borde'] ?>; background:<?= $color['transparente'] ?>; text-align:center; font-weight:bold; font-size:10px; padding:2px;">FOLIO</td>
                <td style="width:34%; border:1px solid <?= $color['borde'] ?>; background:<?= $color['transparente'] ?>; text-align:center; font-weight:bold; font-size:10px; padding:2px;">FECHA EMISIÓN</td>
            </tr>
            <tr>
                <td style="text-align:center; font-size:10px; padding:2px; border:1px solid <?= $color['borde'] ?>;">
                    <?= h((string)($obj->{'@attributes'}->{'Serie'} ?? '')) ?>
                </td>
                <td style="text-align:center; font-size:10px; padding:2px; border:1px solid <?= $color['borde'] ?>;">
                    <?= h((string)($obj->{'@attributes'}->{'Folio'} ?? '')) ?>
                </td>
                <td style="text-align:center; font-size:10px; padding:2px; border:1px solid <?= $color['borde'] ?>;">
                    <?php
                    if (!empty($obj->{'@attributes'}->{'Fecha'})) {
                        $d = new DateTime($obj->{'@attributes'}->{'Fecha'});
                        echo h($d->format('Y-m-d')) . '<br>' . h($d->format('H:i:s'));
                    }
                    ?>
                </td>
            </tr>
        </table>

        <?php
        $fechaCert = (string)($obj->{'Complemento'}->{'TimbreFiscalDigital'}->{'@attributes'}->{'FechaTimbrado'} ?? '');
        $csdEmisor = (string)($obj->{'@attributes'}->{'NoCertificado'} ?? '');
        $csdSat    = (string)($obj->{'Complemento'}->{'TimbreFiscalDigital'}->{'@attributes'}->{'NoCertificadoSAT'} ?? '');
        $lugarExp  = (string)($obj->{'@attributes'}->{'LugarExpedicion'} ?? '');
        if ($fechaCert !== '' || $csdEmisor !== '' || $csdSat !== '' || $lugarExp !== ''):
        ?>
        <table width="100%" style="margin-top:4px; font-size:9px; border:1px solid <?= $color['borde'] ?>;">
            <?php if ($fechaCert !== ''): ?>
            <tr>
                <td style="padding:2px 4px; font-weight:bold; width:38%;">Certificación:</td>
                <td style="padding:2px 4px;"><?= h(str_replace('T', ' ', $fechaCert)) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($csdEmisor !== ''): ?>
            <tr>
                <td style="padding:2px 4px; font-weight:bold;">CSD emisor:</td>
                <td style="padding:2px 4px;"><?= h($csdEmisor) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($csdSat !== ''): ?>
            <tr>
                <td style="padding:2px 4px; font-weight:bold;">CSD SAT:</td>
                <td style="padding:2px 4px;"><?= h($csdSat) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($lugarExp !== ''): ?>
            <tr>
                <td style="padding:2px 4px; font-weight:bold;">Lugar exp.:</td>
                <td style="padding:2px 4px;"><?= h($lugarExp) ?></td>
            </tr>
            <?php endif; ?>
        </table>
        <?php endif; ?>
    </td>
</tr>
</table>

<!-- =========================================================
     DATOS FISCALES DEL CLIENTE
     ========================================================= -->
<table class="layout" style="margin-bottom:0;">
<tr><td class="bar-naranja">D A T O S &nbsp;&nbsp; F I S C A L E S &nbsp;&nbsp; D E L &nbsp;&nbsp; C L I E N T E</td></tr>
</table>
<table class="layout" style="margin-bottom:6px; border:1px solid <?= $color['borde'] ?>; border-top:none;">
<tr>
    <td style="vertical-align:top; padding:5px 8px;">
        <table width="100%" style="font-size:10px;">
            <tr>
                <td style="width:50%; padding:1px 0;">
                    <span style="font-weight:bold;">RFC</span>
                    <?= h((string)$obj->{'Receptor'}->{'@attributes'}->{'Rfc'}) ?>
                </td>
                <?php if (!empty($obj->{'Receptor'}->{'@attributes'}->{'DomicilioFiscalReceptor'})): ?>
                <td style="width:50%; padding:1px 0;">
                    <span style="font-weight:bold;">C.P. domicilio fiscal</span>
                    <?= h((string)$obj->{'Receptor'}->{'@attributes'}->{'DomicilioFiscalReceptor'}) ?>
                </td>
                <?php endif; ?>
            </tr>
            <tr>
                <td colspan="2" style="padding:2px 0;">
                    <span style="font-weight:bold;">Razón social</span>
                    <?= h((string)$obj->{'Receptor'}->{'@attributes'}->{'Nombre'}) ?>
                </td>
            </tr>
            <?php if (!empty($obj->{'Receptor'}->{'@attributes'}->{'RegimenFiscalReceptor'})): ?>
            <tr>
                <td colspan="2" style="padding:1px 0;">
                    <span style="font-weight:bold;">Régimen fiscal</span>
                    <?= h(cfdi_regimen((string)$obj->{'Receptor'}->{'@attributes'}->{'RegimenFiscalReceptor'})) ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td colspan="2" style="padding:1px 0;">
                    <span style="font-weight:bold;">Uso CFDI</span>
                    <?= h(cfdi_uso((string)($obj->{'Receptor'}->{'@attributes'}->{'UsoCFDI'} ?? ''))) ?>
                </td>
            </tr>
        </table>
        <?php
        // Dirección completa del receptor (si aplica)
        if (!empty($datosParametro['direccionReceptor']) && $datosParametro['direccionReceptor'] == 1):
            $rfcsCP = $rfcs;
            $partesDirRec = [];
            if (!empty($datosParametro['calleReceptor']))      $partesDirRec[] = $datosParametro['calleReceptor'];
            if (!empty($datosParametro['noExteriorReceptor'])) $partesDirRec[] = 'No. Ext. '.$datosParametro['noExteriorReceptor'];
            if (!empty($datosParametro['noInteriorReceptor'])) $partesDirRec[] = 'No. Int. '.$datosParametro['noInteriorReceptor'];
            if (!empty($datosParametro['coloniaReceptor']))    $partesDirRec[] = 'Col. '.$datosParametro['coloniaReceptor'];
            if (!in_array($obj->{'Emisor'}->{'@attributes'}->{'Rfc'}, $rfcsCP)) {
                if (!empty($datosParametro['calleReceptor']) && !empty($obj->{'Receptor'}->{'@attributes'}->{'DomicilioFiscalReceptor'})) {
                    $partesDirRec[] = 'C.P. '.(string)$obj->{'Receptor'}->{'@attributes'}->{'DomicilioFiscalReceptor'};
                }
            } elseif (!empty($datosParametro['codigoPostalReceptor'])) {
                $partesDirRec[] = 'C.P. '.$datosParametro['codigoPostalReceptor'];
            }
            if (!empty($datosParametro['municipioReceptor']))  $partesDirRec[] = 'Municipio: '.$datosParametro['municipioReceptor'];
            if (!empty($datosParametro['estadoReceptor']))     $partesDirRec[] = 'Estado: '.$datosParametro['estadoReceptor'];
            if (!empty($datosParametro['paisReceptor']))       $partesDirRec[] = 'País: '.$datosParametro['paisReceptor'];
            if ($partesDirRec): ?>
                <p style="font-size:10px; margin-top:2px;"><?= h(implode(', ', $partesDirRec)) ?></p>
        <?php endif; endif; ?>
        <?php if (!empty($obj->{'Receptor'}->{'@attributes'}->{'ResidenciaFiscal'})): ?>
            <p class="cfdi-bold" style="font-size:10px; margin-top:2px;">Residencia Fiscal: <?= h((string)$obj->{'Receptor'}->{'@attributes'}->{'ResidenciaFiscal'}) ?></p>
            <?php if (!empty($obj->{'Receptor'}->{'@attributes'}->{'NumRegIdTrib'})): ?>
                <p class="cfdi-bold" style="font-size:10px;">NumRegIdTrib: <?= h((string)$obj->{'Receptor'}->{'@attributes'}->{'NumRegIdTrib'}) ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </td>
</tr>
</table>

<!-- =========================================================
     DATOS DEL COMPROBANTE
     ========================================================= -->
<table class="layout" style="margin-bottom:0;">
<tr><td class="bar-naranja">D A T O S &nbsp;&nbsp; D E L &nbsp;&nbsp; C O M P R O B A N T E</td></tr>
</table>
<table class="layout" style="margin-bottom:6px; border:1px solid <?= $color['borde'] ?>; border-top:none;">
<tr>
    <td style="vertical-align:top; padding:5px 8px;">
        <table width="100%" style="font-size:10px;">
            <tr>
                <td style="width:50%; padding:2px 4px; vertical-align:top;">
                    <span style="font-weight:bold;">Tipo comprobante</span>
                    <?= h(cfdi_tipo_comprobante((string)($obj->{'@attributes'}->{'TipoDeComprobante'} ?? ''))) ?>
                </td>
                <td style="width:50%; padding:2px 4px; vertical-align:top;">
                    <span style="font-weight:bold;">Moneda</span>
                    <?= h(cfdi_moneda($monedaComp)) ?>
                </td>
            </tr>
            <?php if (!empty($obj->{'@attributes'}->{'MetodoPago'}) || !empty($obj->{'@attributes'}->{'FormaPago'})): ?>
            <tr>
                <?php if (!empty($obj->{'@attributes'}->{'MetodoPago'})): ?>
                <td style="padding:2px 4px; vertical-align:top;">
                    <span style="font-weight:bold;">Método de pago</span>
                    <?= h(cfdi_metodo_pago((string)$obj->{'@attributes'}->{'MetodoPago'})) ?>
                </td>
                <?php endif; ?>
                <?php if (!empty($obj->{'@attributes'}->{'FormaPago'})): ?>
                <td style="padding:2px 4px; vertical-align:top;">
                    <span style="font-weight:bold;">Forma de pago</span>
                    <?= h(cfdi_forma_pago((string)$obj->{'@attributes'}->{'FormaPago'})) ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endif; ?>
            <?php if (!empty($obj->{'@attributes'}->{'TipoCambio'})): ?>
            <tr>
                <td colspan="2" style="padding:2px 4px;">
                    <span style="font-weight:bold;">Tipo de cambio</span>
                    <?= h((string)$obj->{'@attributes'}->{'TipoCambio'}) ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <?php if (!empty($obj->{'@attributes'}->{'LugarExpedicion'})): ?>
                <td style="padding:2px 4px; vertical-align:top;">
                    <span style="font-weight:bold;">Lugar expedición</span>
                    <?= h((string)$obj->{'@attributes'}->{'LugarExpedicion'}) ?>
                </td>
                <?php endif; ?>
                <?php if (!empty($obj->{'@attributes'}->{'Fecha'})): ?>
                <td style="padding:2px 4px; vertical-align:top;">
                    <span style="font-weight:bold;">Fecha emisión</span>
                    <?php
                    $dEm = new DateTime($obj->{'@attributes'}->{'Fecha'});
                    echo h($dEm->format('Y-m-d H:i:s'));
                    ?>
                </td>
                <?php endif; ?>
            </tr>
            <tr>
                <?php if ($csdEmisor !== ''): ?>
                <td style="padding:2px 4px; vertical-align:top;">
                    <span style="font-weight:bold;">CSD emisor</span>
                    <?= h($csdEmisor) ?>
                </td>
                <?php endif; ?>
                <?php if ($csdSat !== ''): ?>
                <td style="padding:2px 4px; vertical-align:top;">
                    <span style="font-weight:bold;">CSD SAT</span>
                    <?= h($csdSat) ?>
                </td>
                <?php endif; ?>
            </tr>
            <tr>
                <?php if ($fechaCert !== ''): ?>
                <td style="padding:2px 4px; vertical-align:top;">
                    <span style="font-weight:bold;">Fecha certificación</span>
                    <?= h(str_replace('T', ' ', $fechaCert)) ?>
                </td>
                <?php endif; ?>
                <?php if (!empty($obj->{'Complemento'}->{'TimbreFiscalDigital'}->{'@attributes'}->{'RfcProvCertif'})): ?>
                <td style="padding:2px 4px; vertical-align:top;">
                    <span style="font-weight:bold;">RFC prov. cert.</span>
                    <?= h((string)$obj->{'Complemento'}->{'TimbreFiscalDigital'}->{'@attributes'}->{'RfcProvCertif'}) ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php if (!empty($obj->{'@attributes'}->{'Exportacion'})): ?>
            <tr>
                <td colspan="2" style="padding:2px 4px;">
                    <span style="font-weight:bold;">Exportación</span>
                    <?= h(cfdi_exportacion((string)$obj->{'@attributes'}->{'Exportacion'})) ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($datosParametro['json']['extra']['tipoComprobante'])): ?>
            <tr>
                <td colspan="2" style="padding:2px 4px;">
                    <span style="font-weight:bold;">Tipo Comprobante:</span>
                    <?= h((string)$datosParametro['json']['extra']['tipoComprobante']) ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </td>
</tr>
</table>

<!-- =========================================================
     TABLA DE CONCEPTOS
     ========================================================= -->
<table class="tbl-conceptos">
    <thead>
        <tr>
            <th style="width:6%;">CANT.</th>
            <th style="width:10%;">CLAVE UNIDAD</th>
            <th style="width:10%;">CLAVE P./S.</th>
            <th style="width:8%;">NO. IDENT.</th>
            <th style="width:38%; text-align:left;">DESCRIPCIÓN</th>
            <th style="width:14%;">PRECIO UNITARIO</th>
            <th style="width:14%;">IMPORTE</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($conceptos as $Concepto): ?>
        <tr style="font-weight:bold;">
            <td style="text-align:center;"><?= h((string)($Concepto->{'@attributes'}->{'Cantidad'} ?? '')) ?></td>
            <td style="text-align:center;"><?= h(cfdi_clave_unidad((string)($Concepto->{'@attributes'}->{'ClaveUnidad'} ?? ''))) ?></td>
            <td style="text-align:center;"><?= h((string)($Concepto->{'@attributes'}->{'ClaveProdServ'} ?? '')) ?></td>
            <td style="text-align:center;"><?= h((string)($Concepto->{'@attributes'}->{'NoIdentificacion'} ?? '')) ?></td>
            <td style="text-align:left; font-weight:normal;">
                <?php
                $desc = (string)($Concepto->{'@attributes'}->{'Descripcion'} ?? '');
                $desc = str_replace(["\r\n","\n","\r"], '<br>', $desc);
                echo $desc;
                ?>
            </td>
            <td style="text-align:center;">
                <?php
                $rpcEsp = ($obj->{'Emisor'}->{'@attributes'}->{'Rfc'} === 'RPD160620GJ8');
                $vu = $Concepto->{'@attributes'}->{'ValorUnitario'} ?? null;
                echo $rpcEsp ? '$ '.h((string)$vu) : $fmtMonto($vu);
                ?>
            </td>
            <td style="text-align:center;">
                <?php
                $imp = $Concepto->{'@attributes'}->{'Importe'} ?? null;
                echo $rpcEsp ? '$ '.h((string)$imp) : $fmtMonto($imp);
                ?>
            </td>
        </tr>

        <!-- Fila secundaria: ObjetoImp + Descuento -->
        <tr class="imp-row">
            <td colspan="2" style="font-size:9px; text-align:left; padding:1px 3px;">
                <span style="color:<?= $color['comprobante'] ?>; font-weight:bold;">OBJETO IMP: </span>
                <?php
                switch ($Concepto->{'@attributes'}->{'ObjetoImp'} ?? '') {
                    case '01': echo '01 - No objeto de impuesto.'; break;
                    case '02': echo '02 - Sí objeto de impuesto.'; break;
                    case '03': echo '03 - Sí objeto del impuesto y no obligado al desglose.'; break;
                }
                ?>
            </td>
            <td colspan="5" style="font-size:9px; text-align:left; padding:1px 3px;">
                <span style="color:<?= $color['comprobante'] ?>; font-weight:bold;">DESCUENTO: </span>
                <?= $fmtMonto($Concepto->{'@attributes'}->{'Descuento'} ?? '0') ?>
            </td>
        </tr>

        <?php if (!empty($Concepto->{'CuentaPredial'}->{'@attributes'}->{'Numero'})): ?>
        <tr class="imp-row">
            <td colspan="7" style="font-size:9px; padding:1px 3px;">
                <span style="color:<?= $color['comprobante'] ?>; font-weight:bold;">Cuenta Predial: </span>
                <?= h((string)$Concepto->{'CuentaPredial'}->{'@attributes'}->{'Numero'}) ?>
            </td>
        </tr>
        <?php endif; ?>

        <?php if (!empty($Concepto->{'InformacionAduanera'}->{'@attributes'}->{'NumeroPedimento'})): ?>
        <tr class="imp-row">
            <td colspan="7" style="font-size:9px; padding:1px 3px;">
                <span style="color:<?= $color['comprobante'] ?>; font-weight:bold;">Número Pedimento: </span>
                <?= h((string)$Concepto->{'InformacionAduanera'}->{'@attributes'}->{'NumeroPedimento'}) ?>
            </td>
        </tr>
        <?php endif; ?>

        <?php if (isset($Concepto->{'Impuestos'})): ?>
        <tr class="imp-row">
            <td colspan="7" style="padding:2px 3px;">
                <table width="100%" style="font-size:9px; border-collapse:collapse;">
                    <tr>
                        <td style="width:15%; font-weight:bold; color:<?= $color['comprobante'] ?>; vertical-align:top; padding:1px 3px;">
                            IMPUESTOS POR CONCEPTO
                        </td>
                        <?php
                        // Traslados
                        if (!empty($Concepto->{'Impuestos'}->{'Traslados'})):
                            $_tr = isset($Concepto->{'Impuestos'}->{'Traslados'}->{'Traslado'}->{'@attributes'})
                                ? [$Concepto->{'Impuestos'}->{'Traslados'}->{'Traslado'}->{'@attributes'}]
                                : array_map(fn($x) => $x->{'@attributes'}, (array)$Concepto->{'Impuestos'}->{'Traslados'}->{'Traslado'});
                        ?>
                        <td style="vertical-align:top; padding:1px 4px;">
                            <strong style="color:<?= $color['comprobante'] ?>;">Traslados</strong>
                            <?php foreach ($_tr as $t): ?>
                            <table width="100%" style="font-size:8.5px; margin-top:1px;">
                                <tr>
                                    <td><strong>Impuesto:</strong> <?= h(cfdi_impuesto((string)($t->{'Impuesto'} ?? ''))) ?></td>
                                    <td><strong>Tipo:</strong> Trasladado</td>
                                    <td><strong>Base:</strong> <?= $fmtMonto($t->{'Base'} ?? null) ?></td>
                                    <td><strong>Factor:</strong> <?= h((string)($t->{'TipoFactor'} ?? '')) ?></td>
                                    <td><strong>Tasa:</strong> <?= h((string)($t->{'TasaOCuota'} ?? '0')) ?></td>
                                    <td><strong>Importe:</strong> <?= $fmtMonto($t->{'Importe'} ?? null) ?></td>
                                </tr>
                            </table>
                            <?php endforeach; ?>
                        </td>
                        <?php endif; ?>

                        <?php
                        // Retenciones
                        if (!empty($Concepto->{'Impuestos'}->{'Retenciones'})):
                            $_ret = isset($Concepto->{'Impuestos'}->{'Retenciones'}->{'Retencion'}->{'@attributes'})
                                ? [$Concepto->{'Impuestos'}->{'Retenciones'}->{'Retencion'}->{'@attributes'}]
                                : array_map(fn($x) => $x->{'@attributes'}, (array)$Concepto->{'Impuestos'}->{'Retenciones'}->{'Retencion'});
                        ?>
                        <td style="vertical-align:top; padding:1px 4px;">
                            <strong style="color:<?= $color['comprobante'] ?>;">Retenciones</strong>
                            <?php foreach ($_ret as $r): ?>
                            <table width="100%" style="font-size:8.5px; margin-top:1px;">
                                <tr>
                                    <td><strong>Impuesto:</strong> <?= h(cfdi_impuesto((string)($r->{'Impuesto'} ?? ''))) ?></td>
                                    <td><strong>Tipo:</strong> Retención</td>
                                    <td><strong>Base:</strong> <?= $fmtMonto($r->{'Base'} ?? null) ?></td>
                                    <td><strong>Factor:</strong> <?= h((string)($r->{'TipoFactor'} ?? '')) ?></td>
                                    <td><strong>Tasa:</strong> <?= h((string)($r->{'TasaOCuota'} ?? '0')) ?></td>
                                    <td><strong>Importe:</strong> <?= $fmtMonto($r->{'Importe'} ?? null) ?></td>
                                </tr>
                            </table>
                            <?php endforeach; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                </table>
            </td>
        </tr>
        <?php endif; ?>

        <?php
        // Complementos por concepto
        $GLOBALS['_cfdi_pdf_scope'] = get_defined_vars();
        if (isset($Concepto->{'ComplementoConcepto'}->{'instEducativas'})) cfdi_tpl_include('Complemento/instEducativas.php');
        if (isset($Concepto->{'ComplementoConcepto'}->{'VentaVehiculos'})) cfdi_tpl_include('Complemento/ventaVehiculos.php');
        if (isset($Concepto->{'ACuentaTerceros'})) cfdi_tpl_include('Complemento/cuentaTerceros.php');
        ?>

    <?php endforeach; ?>
    </tbody>
</table>

<!-- =========================================================
     COMPLEMENTOS GLOBALES
     ========================================================= -->
<?php
$GLOBALS['_cfdi_pdf_scope'] = get_defined_vars();
if (isset($obj->{'InformacionGlobal'}))                            cfdi_tpl_include('Complemento/InformacionGlobal.php');
if (isset($obj->{'CfdiRelacionados'}))                             cfdi_tpl_include('Complemento/relacionados.php');
if (isset($obj->{'Complemento'}->{'Divisas'}))                     cfdi_tpl_include('Complemento/divisas.php');
elseif (isset($obj->{'Complemento'}->{'PFintegranteCoordinado'}))  cfdi_tpl_include('Complemento/coordinados.php');
elseif (isset($obj->{'Complemento'}->{'Aerolineas'}))              cfdi_tpl_include('Complemento/aerolineas.php');
elseif (isset($obj->{'Complemento'}->{'ComercioExterior'}))        cfdi_tpl_include('Complemento/comercioExterior.php');
elseif (isset($obj->{'Complemento'}->{'IEdu'}))                    cfdi_tpl_include('Complemento/instEducativas.php');
elseif (isset($obj->{'Complemento'}->{'Detalista'}))               cfdi_tpl_include('Complemento/detalista.php');
elseif (isset($obj->{'Complemento'}->{'leyendasFisc'}))            cfdi_tpl_include('Complemento/leyendasFiscales.php');
elseif (isset($obj->{'Complemento'}->{'NotariosPublicos'}))        cfdi_tpl_include('Complemento/notarios.php');
elseif (isset($obj->{'Complemento'}->{'VehiculoUsado'}))           cfdi_tpl_include('Complemento/vehiculoUsado.php');
if (isset($obj->{'Complemento'}->{'parcialesconstruccion'}))       cfdi_tpl_include('Complemento/parcialesConstruccion.php');
?>

<!-- =========================================================
     IMPORTE EN LETRAS  |  TOTALES
     ========================================================= -->
<div class="cfdi-pie-fiscal">
<table class="layout" style="margin-top:8px;">
<tr>
    <!-- Columna izquierda: importe en letras, condiciones, comentarios, etiquetas -->
    <td style="width:55%; vertical-align:top; padding-right:10px;">
        <p style="font-size:13px; font-weight:bold; word-break:break-word; margin-bottom:4px;">
            <?php
            $totalComp = (string)($obj->{'@attributes'}->{'Total'} ?? '0');
            if ($totalComp === '0' && $monedaComp === 'MXN')      echo 'CERO PESOS 00/100 MXN';
            elseif ($totalComp === '0' && $monedaComp === 'EUR')   echo 'CERO EUROS 00/100 EUR';
            elseif ($totalComp === '0' && $monedaComp === 'USD')   echo 'CERO DÓLARES 00/100 USD';
            else {
                $_nal = new \App\Utility\NumeroALetras();
                echo h($_nal->convertir((float)$totalComp, $monedaComp));
            }
            ?>
        </p>

        <?php if (!empty($obj->{'@attributes'}->{'CondicionesDePago'})): ?>
        <p style="font-size:11px; text-align:justify; margin-bottom:4px;">
            <?php cfdi_tpl_include('Complemento/condiciones.php'); ?>
        </p>
        <?php endif; ?>

        <?php if (!empty($datosParametro['json']['extra']['comentarios'])): ?>
        <p style="font-size:11px; text-align:justify; margin-bottom:4px;">
            <strong>Comentarios: </strong>
            <?php
            $enters   = ["\r\n","\n","\r"];
            $comentarios = str_replace($enters, '<br>', $datosParametro['json']['extra']['comentarios']);
            echo $comentarios;
            ?>
        </p>
        <?php endif; ?>

        <?php
        // Etiquetas extra (tipo badge)
        if (!empty($datosParametro['json']['extra'])):
            foreach ($datosParametro['json']['extra'] as $k => $v):
                if (!in_array($k, ['tipoComprobante','comentarios']) && $v !== ''):
        ?>
        <p style="font-size:11px; margin-bottom:2px; word-break:break-word;">
            <span style="background:<?= $color['intenso'] ?>; color:<?= $color['letra'] ?>; padding:1px 5px; border-radius:3px;">&nbsp;<?= h(strtoupper(str_replace('_', ' de ', $k))) ?></span>
            &nbsp;&nbsp;<?= h((string)$v) ?>
        </p>
        <?php endif; endforeach; endif; ?>

        <?php
        // Etiquetas del comprobante
        if (!empty($datosParametro['etiquetasAplica']) && $datosParametro['etiquetasAplica'] == 1 && !empty($datosParametro['json']['etiquetas'])):
            $lista_etiquetas = json_decode($datosParametro['json']['etiquetas'], true) ?? [];
            foreach ($lista_etiquetas as $etiqueta):
                if (!empty($etiqueta['nombre']) && !empty($etiqueta['valor'])):
        ?>
        <p style="font-size:11px; margin-bottom:2px; word-break:break-word;">
            <span style="background:<?= $color['intenso'] ?>; color:<?= $color['letra'] ?>; padding:1px 5px; border-radius:3px;">&nbsp;<?= h(strtoupper(str_replace('_', ' de ', $etiqueta['nombre']))) ?></span>
            &nbsp;&nbsp;<?= h((string)$etiqueta['valor']) ?>
        </p>
        <?php endif; endforeach; endif; ?>

        <?php if (!empty($datosParametro['Propina']) && $datosParametro['Propina'] != '0'): ?>
        <p style="font-size:11px; margin-top:4px;">
            <span style="background:<?= $color['intenso'] ?>; color:<?= $color['letra'] ?>; padding:1px 5px; border-radius:3px;">PROPINA</span>
            &nbsp;&nbsp;$ <?= number_format((float)$datosParametro['Propina'], 2, '.', '') ?>
        </p>
        <?php endif; ?>
    </td>

    <!-- Columna derecha: subtotal, descuento, impuestos, total -->
    <td style="width:45%; vertical-align:top;">
        <table class="tbl-totales">
            <!-- SUBTOTAL -->
            <?php if (!empty($obj->{'@attributes'}->{'SubTotal'})): ?>
            <tr>
                <td class="lbl">SUBTOTAL</td>
                <td class="val"><?= $fmtMonto($obj->{'@attributes'}->{'SubTotal'}) ?></td>
            </tr>
            <?php endif; ?>

            <!-- DESCUENTO -->
            <?php if (!empty($obj->{'@attributes'}->{'Descuento'})): ?>
            <tr>
                <td class="lbl">DESCUENTO</td>
                <td class="val"><?= $fmtMonto($obj->{'@attributes'}->{'Descuento'}) ?></td>
            </tr>
            <?php endif; ?>

            <!-- TRASLADOS GLOBALES -->
            <?php
            if (!empty($obj->{'Impuestos'}->{'Traslados'}->{'Traslado'})):
                $traslados = isset($obj->{'Impuestos'}->{'Traslados'}->{'Traslado'}->{'@attributes'})
                    ? [$obj->{'Impuestos'}->{'Traslados'}->{'Traslado'}]
                    : (array)$obj->{'Impuestos'}->{'Traslados'}->{'Traslado'};
                foreach ($traslados as $traslado):
                    $impCod  = (string)($traslado->{'@attributes'}->{'Impuesto'} ?? '');
                    $tasa    = (string)($traslado->{'@attributes'}->{'TasaOCuota'} ?? '');
                    $factor  = (string)($traslado->{'@attributes'}->{'TipoFactor'} ?? '');
                    $impNom  = match($impCod) { '001'=>'ISR', '002'=>'IVA', '003'=>'IEPS', default=>$impCod };
                    if ($impCod === '002' && $tasa === '0.000000') $etqImp = "$impNom TASA 0%";
                    elseif ($impCod === '002' && in_array($tasa, ['0.160000','0.1600'])) $etqImp = "$impNom TASA 16%";
                    elseif ($impCod === '002' && $tasa === '0.080000') $etqImp = "$impNom TASA 8%";
                    elseif ($factor === 'Exento') $etqImp = "$impNom EXENTO";
                    else $etqImp = "$impNom " . ($tasa !== '' ? 'TASA '.((float)$tasa * 100).'%' : '');
            ?>
            <tr>
                <td class="lbl"><?= h($etqImp) ?></td>
                <td class="val"><?= $fmtMonto($traslado->{'@attributes'}->{'Importe'} ?? null) ?></td>
            </tr>
            <?php endforeach; endif; ?>

            <!-- RETENCIONES GLOBALES -->
            <?php
            if (!empty($obj->{'Impuestos'}->{'Retenciones'}->{'Retencion'})):
                $retenciones = isset($obj->{'Impuestos'}->{'Retenciones'}->{'Retencion'}->{'@attributes'})
                    ? [$obj->{'Impuestos'}->{'Retenciones'}->{'Retencion'}]
                    : (array)$obj->{'Impuestos'}->{'Retenciones'}->{'Retencion'};
                foreach ($retenciones as $retencion):
                    $impCodR = (string)($retencion->{'@attributes'}->{'Impuesto'} ?? '');
                    $impNomR = match($impCodR) { '001'=>'ISR', '002'=>'IVA', '003'=>'IEPS', default=>$impCodR };
            ?>
            <tr>
                <td class="lbl">RET. <?= h($impNomR) ?></td>
                <td class="val"><?= $fmtMonto($retencion->{'@attributes'}->{'Importe'} ?? null) ?></td>
            </tr>
            <?php endforeach; endif; ?>

            <!-- IMPUESTOS LOCALES TRASLADOS -->
            <?php
            if (!empty($obj->{'Complemento'}->{'ImpuestosLocales'}->{'TrasladosLocales'})):
                $locT = isset($obj->{'Complemento'}->{'ImpuestosLocales'}->{'TrasladosLocales'}->{'@attributes'})
                    ? [$obj->{'Complemento'}->{'ImpuestosLocales'}->{'TrasladosLocales'}]
                    : (array)$obj->{'Complemento'}->{'ImpuestosLocales'}->{'TrasladosLocales'};
                foreach ($locT as $tl):
            ?>
            <tr>
                <td class="lbl"><?= h(utf8_encode((string)($tl->{'@attributes'}->{'ImpLocTrasladado'} ?? ''))) ?></td>
                <td class="val"><?= $fmtMonto($tl->{'@attributes'}->{'Importe'} ?? null) ?></td>
            </tr>
            <?php endforeach; endif; ?>

            <!-- IMPUESTOS LOCALES RETENCIONES -->
            <?php
            if (!empty($obj->{'Complemento'}->{'ImpuestosLocales'}->{'RetencionesLocales'})):
                $locR = isset($obj->{'Complemento'}->{'ImpuestosLocales'}->{'RetencionesLocales'}->{'@attributes'})
                    ? [$obj->{'Complemento'}->{'ImpuestosLocales'}->{'RetencionesLocales'}]
                    : (array)$obj->{'Complemento'}->{'ImpuestosLocales'}->{'RetencionesLocales'};
                foreach ($locR as $rl):
            ?>
            <tr>
                <td class="lbl"><?= h(utf8_decode((string)($rl->{'@attributes'}->{'ImpLocRetenido'} ?? ''))) ?></td>
                <td class="val"><?= $fmtMonto($rl->{'@attributes'}->{'Importe'} ?? null) ?></td>
            </tr>
            <?php endforeach; endif; ?>

            <!-- TOTAL -->
            <tr>
                <td class="lbl total-final">TOTAL</td>
                <td class="val total-final"><?= $fmtMonto($obj->{'@attributes'}->{'Total'} ?? null) ?></td>
            </tr>
        </table>
    </td>
</tr>
</table>

<!-- =========================================================
     FIRMAS
     ========================================================= -->
<?php if (!empty($datosParametro['firmas'])): ?>
<table class="layout" style="margin-top:60px;">
<tr>
<?php $cntF = 0; foreach ($datosParametro['firmas'] as $firma): if (empty($firma['nombre'])) continue; $cntF++; ?>
    <td style="width:33%; text-align:center; vertical-align:bottom; padding:4px;">
        <p class="firma-nombre"><?= h($firma['nombre']) ?></p>
        <p class="firma-puesto"><?= h($firma['puesto'] ?? '') ?></p>
    </td>
    <?php if ($cntF % 3 === 0): ?></tr><tr><?php endif; ?>
<?php endforeach; ?>
</tr>
</table>
<?php endif; ?>

<!-- =========================================================
     ETIQUETAS DEL COMPROBANTE (tabla)
     ========================================================= -->
<?php if (!empty($datosParametro['etiquetas'])): ?>
<table class="layout" style="margin-top:10px;">
<tr>
<?php $cntE = 0; foreach ($datosParametro['etiquetas'] as $etiq):
    if ($etiq['nombre'] === 'motivo_descuento' || empty($etiq['nombre']) || empty($etiq['valor'])) continue;
    $cntE++;
?>
    <td style="width:25%; vertical-align:top; padding:3px;">
        <p class="etiq-nombre" style="background:<?= $color['intenso'] ?>; color:<?= $color['letra'] ?>;"><?= h($etiq['nombre']) ?></p>
        <div class="etiq-valor"><?= h($etiq['valor']) ?></div>
    </td>
    <?php if ($cntE % 4 === 0): ?></tr><tr><?php endif; ?>
<?php endforeach; ?>
</tr>
</table>
<?php endif; ?>

<!-- =========================================================
     ADDENDAS
     ========================================================= -->
<?php
$GLOBALS['_cfdi_pdf_scope'] = get_defined_vars();
if (isset($obj->Addenda->Factura->{'@attributes'}->PMT))                                         cfdi_tpl_include('Addenda/pmt.php');
if (isset($obj->addenda->AddendaComercial->{'@attributes'}->VERITIV))                            cfdi_tpl_include('Addenda/veritiv.php');
if (isset($obj->Addenda->Datos->{'@attributes'}->fa))                                            cfdi_tpl_include('Addenda/marelli.php');
if (isset($obj->Addenda->LugarRecepcion))                                                        cfdi_tpl_include('Addenda/picaros.php');
if (isset($obj->Addenda->addenda->SINTENOVO))                                                    cfdi_tpl_include('Addenda/sintenovo.php');
if (isset($obj->Addenda->Factura->{'@attributes'}->tipoDocumento))                               cfdi_tpl_include('Addenda/serviciosaudi.php');
if (isset($obj->Addenda->AddendaFondoGarantia))                                                  cfdi_tpl_include('Addenda/fondogarantia.php');
if (!is_null($mx) && $mx === 'sintenovo')                                                        cfdi_tpl_include('Addenda/sintenovosimple.php');
if (isset($obj->Addenda->addenda->AddendaEmisor->{'@attributes'}->Interno))                      cfdi_tpl_include('Addenda/propina.php');
if (isset($obj->Addenda->ReferenciaReceptor->Siniestro))                                         cfdi_tpl_include('Addenda/imbursa.php');
if (isset($obj->addenda->AddendaBasware))                                                        cfdi_tpl_include('Addenda/basware.php');
if (isset($obj->addenda->requestForPayment->{'@attributes'}->orderIdentification))               cfdi_tpl_include('Addenda/lesaffre.php');
if (isset($obj->Addenda->requestForPayment->{'@attributes'}->documentStructureVersion) && $obj->Addenda->requestForPayment->{'@attributes'}->documentStructureVersion === 'AMC7.1') cfdi_tpl_include('Addenda/amc71.php');
if (isset($obj->Addenda->Identificacion->{'@attributes'}->WO_Contenedor))                        cfdi_tpl_include('Addenda/simple.php');
if (isset($obj->Addenda->REM))                                                                   cfdi_tpl_include('Addenda/rem.php');
if (isset($obj->Addenda->ordenCompra))                                                           cfdi_tpl_include('Addenda/ordenCompra.php');
if (isset($obj->addenda->Conceptos))                                                             cfdi_tpl_include('Addenda/cpt.php');
if (isset($obj->addenda->Concepto))                                                              cfdi_tpl_include('Addenda/conceptos.php');
if (isset($obj->Addenda->minotaria))                                                             cfdi_tpl_include('Addenda/minotaria.php');
if (isset($obj->Addenda->RequestCFD))                                                            cfdi_tpl_include('Addenda/pepsico.php');
if (isset($obj->Addenda->AP))                                                                    cfdi_tpl_include('Addenda/sanmina.php');
?>

</div>

<!-- =========================================================
     VERIFICACIÓN / SELLOS / QR (SAT CFDI 4.0 + Carta Porte 3.1)
     ========================================================= -->
<?php
$selloCfdi = (string)($obj->{'Complemento'}->{'TimbreFiscalDigital'}->{'@attributes'}->{'SelloCFD'} ?? '');
$selloSat = (string)($obj->{'Complemento'}->{'TimbreFiscalDigital'}->{'@attributes'}->{'SelloSAT'} ?? '');
$tfd = $obj->{'Complemento'}->{'TimbreFiscalDigital'}->{'@attributes'} ?? null;
$cadenaOriginalSat = '';
if ($tfd && !empty($tfd->{'UUID'}) && !empty($tfd->{'FechaTimbrado'}) && !empty($tfd->{'RfcProvCertif'}) && !empty($tfd->{'SelloCFD'}) && !empty($tfd->{'NoCertificadoSAT'})) {
    $cadenaOriginalSat = '||1.1|'.$tfd->{'UUID'}.'|'.$tfd->{'FechaTimbrado'}.'|'.$tfd->{'RfcProvCertif'}.'|'.$tfd->{'SelloCFD'}.'|'.$tfd->{'NoCertificadoSAT'}.'|';
}
?>
<div class="verificacion" style="margin-top:10px;">

<table class="verificacion-layout">
<colgroup>
    <col style="width:16%">
    <col style="width:84%">
</colgroup>
<tr>
    <td class="qr-block qr-block--sellos col-qr">
        <?php if (!empty($qrpng)): ?>
            <p class="qr-title" style="color:<?= $color['comprobante'] ?>; font-size:9px; margin-bottom:3px;">Código QR del CFDI</p>
            <img src="<?= $qrpng ?>" alt="QR CFDI" class="qr-sat">
        <?php else: ?>
            <p style="font-size:9px; color:#666;">QR no disponible</p>
        <?php endif; ?>
    </td>

    <td class="col-sellos">
        <table class="sellos-cfdi">
            <tr>
                <td class="sello-lbl">Sello digital del CFDI (emisor)</td>
            </tr>
            <tr>
                <td class="sello-val"><?= cfdi_sello_impreso($selloCfdi) ?></td>
            </tr>
            <tr>
                <td class="sello-lbl">Sello digital del SAT</td>
            </tr>
            <tr>
                <td class="sello-val"><?= cfdi_sello_impreso($selloSat) ?></td>
            </tr>
            <tr>
                <td class="sello-lbl">Cadena Original del Complemento de Certificación Digital del SAT</td>
            </tr>
            <tr>
                <td class="sello-val sello-val--ultimo"><?= cfdi_sello_impreso($cadenaOriginalSat) ?></td>
            </tr>
        </table>
    </td>
</tr>
</table>
</div>

<?php if (in_array($obj->{'Emisor'}->{'@attributes'}->{'Rfc'}, $rfcs)): ?>
<table class="layout" style="margin-top:4px;">
<tr>
    <td style="width:50%; text-align:center; font-size:11px;">
        <a href="https://www.factura-lo.com/" style="color:#000;">www.factura-lo.com</a>
    </td>
    <td style="width:50%; text-align:center; font-size:10px;">
        <?php
        $p2 = [];
        if (!empty($datosParametro['calleEmisor']))        $p2[] = $datosParametro['calleEmisor'];
        if (!empty($datosParametro['noExteriorEmisor']))   $p2[] = $datosParametro['noExteriorEmisor'];
        if (!empty($datosParametro['coloniaEmisor']))      $p2[] = $datosParametro['coloniaEmisor'];
        if (!empty($datosParametro['estadoEmisor']))       $p2[] = $datosParametro['estadoEmisor'];
        if (!empty($datosParametro['codigoPostalEmisor'])) $p2[] = 'C.P. '.$datosParametro['codigoPostalEmisor'];
        echo h(implode(', ', $p2));
        ?>
    </td>
</tr>
</table>
<?php endif; ?>

</section>

<?php if ($mostrarCartaPortePdf && $tieneCartaPorteEnXml):
    $_cpNode = cfdi_cartaporte_node($obj);
    $_cpAttrs = $_cpNode->{'@attributes'} ?? null;
    $_cpVersion = $_cpAttrs ? (string)($_cpAttrs->{'Version'} ?? '3.1') : '3.1';
    $_cpId = $_cpAttrs ? (string)($_cpAttrs->{'IdCCP'} ?? '') : '';
    $_cpSerie = (string)($obj->{'@attributes'}->{'Serie'} ?? '');
    $_cpFolio = (string)($obj->{'@attributes'}->{'Folio'} ?? '');
?>
<section class="cfdi-cp-section cfdi-page-break">

<p style="font-size:10px; text-align:center; margin-bottom:6px;">
    COMPLEMENTO CARTA PORTE <?= h($_cpVersion) ?> — COMPROBANTE DE TRASLADO
</p>

<table class="layout" style="margin-bottom:8px; border:1px solid <?= $color['borde'] ?>;">
<tr>
    <td style="width:18%; vertical-align:middle; text-align:center; padding:6px;">
        <?php if (!empty($logo)): ?>
            <img src="<?= $logo ?>" alt="Logo" style="max-width:90px; max-height:70px;">
        <?php endif; ?>
    </td>
    <td style="width:42%; padding:6px 8px; font-size:11px; vertical-align:top;">
        <p class="cfdi-bold" style="font-size:12px; margin-bottom:3px;"><?= h((string)$obj->{'Emisor'}->{'@attributes'}->{'Nombre'}) ?></p>
        <p style="font-size:10px;">RFC: <?= h((string)$obj->{'Emisor'}->{'@attributes'}->{'Rfc'}) ?></p>
        <?php if ($_cpSerie !== '' || $_cpFolio !== ''): ?>
        <p style="font-size:10px; margin-top:4px;">CFDI relacionado: <?= h(trim($_cpSerie . ' ' . $_cpFolio)) ?></p>
        <?php endif; ?>
        <?php if ($uuid !== ''): ?>
        <p style="font-size:8.5px; word-break:break-all; margin-top:2px;">UUID: <?= h($uuid) ?></p>
        <?php endif; ?>
    </td>
    <td style="width:40%; padding:6px 8px; font-size:10px; vertical-align:top; border-left:1px solid <?= $color['borde'] ?>;">
        <?php if ($_cpId !== ''): ?>
        <p class="cfdi-bold" style="font-size:9px; margin-bottom:3px;">IdCCP</p>
        <p style="font-size:8px; word-break:break-all; font-family:DejaVu Sans Mono,monospace;"><?= h($_cpId) ?></p>
        <?php endif; ?>
        <?php
        $fechaTimbCp = (string)($obj->{'Complemento'}->{'TimbreFiscalDigital'}->{'@attributes'}->{'FechaTimbrado'} ?? '');
        if ($fechaTimbCp !== ''):
            $_dTimb = new DateTime($fechaTimbCp);
        ?>
        <p style="margin-top:6px;"><strong>Fecha timbrado:</strong> <?= h($_dTimb->format('d/m/Y H:i:s')) ?></p>
        <?php endif; ?>
    </td>
</tr>
</table>

<?php
$GLOBALS['_cfdi_pdf_scope'] = get_defined_vars();
cfdi_tpl_include('Complemento/cartaPorte.php');
?>

<?php if ($tieneQrCartaPorte): ?>
<div class="cfdi-cp-verificacion">
<table class="verificacion-layout">
<tr>
    <td class="qr-block col-qr" style="width:22%; vertical-align:top;">
        <p class="qr-title" style="color:<?= $color['comprobante'] ?>; font-size:9px; margin-bottom:4px;">Código QR Carta Porte</p>
        <?php if (!empty($qrpngCP)): ?>
            <img src="<?= $qrpngCP ?>" alt="QR Carta Porte" class="qr-sat">
        <?php else: ?>
            <p style="font-size:9px; color:#666;">QR no disponible</p>
        <?php endif; ?>
    </td>
    <td style="width:78%; vertical-align:top; padding-left:10px; font-size:9px; color:#333;">
        <p style="margin-bottom:4px;">Verifique este complemento en el portal del SAT escaneando el código QR.</p>
        <?php if (!empty($textQrCP) && strpos($textQrCP, 'http') === 0): ?>
        <p style="word-break:break-all; font-size:7.5px; font-family:DejaVu Sans Mono,monospace;"><?= h($textQrCP) ?></p>
        <?php endif; ?>
    </td>
</tr>
</table>
</div>
<?php endif; ?>

<p style="text-align:center; font-size:10px; margin-top:14px; padding-top:8px; border-top:1px solid #ccc; color:#333;">
    Representación impresa del Complemento Carta Porte <?= h($_cpVersion) ?>
</p>

</section>
<?php endif; ?>

<footer class="cfdi-pdf-footer">
    <p><?= ($mostrarCartaPortePdf && $tieneCartaPorteEnXml)
        ? 'Documento con factura CFDI y complemento Carta Porte en páginas separadas'
        : 'Este documento es una representación impresa de un CFDI' ?></p>
</footer>
</body>
</html>
