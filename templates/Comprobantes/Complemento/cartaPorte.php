<?php
/**
 * Detalle del complemento Carta Porte (2.0 / 3.0 / 3.1) para representación impresa.
 *
 * @var object $obj
 * @var array<string, string> $color
 */

if (!function_exists('cp_attrs')) {
    function cp_attrs(?object $node): array
    {
        if ($node === null) {
            return [];
        }
        $attrs = $node->{'@attributes'} ?? null;
        if ($attrs === null) {
            return [];
        }

        return (array)$attrs;
    }
}

if (!function_exists('cp_items')) {
    /** @return list<object> */
    function cp_items(mixed $node): array
    {
        if ($node === null) {
            return [];
        }
        if (is_array($node)) {
            return array_values($node);
        }
        if (isset($node->{'@attributes'})) {
            return [$node];
        }

        return array_values((array)$node);
    }
}

if (!function_exists('cp_estado')) {
    function cp_estado(string $clave): string
    {
        static $map = [
            '01' => 'Aguascalientes', '02' => 'Baja California', '03' => 'Baja California Sur',
            '04' => 'Campeche', '05' => 'Coahuila', '06' => 'Colima', '07' => 'Chiapas',
            '08' => 'Chihuahua', '09' => 'Ciudad de México', '10' => 'Durango',
            '11' => 'Guanajuato', '12' => 'Guerrero', '13' => 'Hidalgo', '14' => 'Jalisco',
            '15' => 'Estado de México', '16' => 'Michoacán', '17' => 'Morelos', '18' => 'Nayarit',
            '19' => 'Nuevo León', '20' => 'Oaxaca', '21' => 'Puebla', '22' => 'Querétaro',
            '23' => 'Quintana Roo', '24' => 'San Luis Potosí', '25' => 'Sinaloa', '26' => 'Sonora',
            '27' => 'Tabasco', '28' => 'Tamaulipas', '29' => 'Tlaxcala', '30' => 'Veracruz',
            '31' => 'Yucatán', '32' => 'Zacatecas',
            'AGU' => 'Aguascalientes', 'BCN' => 'Baja California', 'BCS' => 'Baja California Sur',
            'CAM' => 'Campeche', 'COA' => 'Coahuila', 'COL' => 'Colima', 'CHP' => 'Chiapas',
            'CHH' => 'Chihuahua', 'CMX' => 'Ciudad de México', 'DUR' => 'Durango',
            'GUA' => 'Guanajuato', 'GRO' => 'Guerrero', 'HID' => 'Hidalgo', 'JAL' => 'Jalisco',
            'MEX' => 'Estado de México', 'MIC' => 'Michoacán', 'MOR' => 'Morelos', 'NAY' => 'Nayarit',
            'NLE' => 'Nuevo León', 'OAX' => 'Oaxaca', 'PUE' => 'Puebla', 'QUE' => 'Querétaro',
            'ROO' => 'Quintana Roo', 'SLP' => 'San Luis Potosí', 'SIN' => 'Sinaloa', 'SON' => 'Sonora',
            'TAB' => 'Tabasco', 'TAM' => 'Tamaulipas', 'TLA' => 'Tlaxcala', 'VER' => 'Veracruz',
            'YUC' => 'Yucatán', 'ZAC' => 'Zacatecas',
        ];

        return $map[$clave] ?? $clave;
    }
}

if (!function_exists('cp_domicilio')) {
    function cp_domicilio(?object $dom): string
    {
        $a = cp_attrs($dom);
        if ($a === []) {
            return '—';
        }
        $parts = [];
        foreach (['Calle', 'NumeroExterior', 'NumeroInterior'] as $k) {
            if (!empty($a[$k])) {
                $parts[] = (string)$a[$k];
            }
        }
        if (!empty($a['Colonia'])) {
            $parts[] = 'Col. ' . $a['Colonia'];
        }
        if (!empty($a['Municipio'])) {
            $parts[] = 'Mun. ' . $a['Municipio'];
        }
        if (!empty($a['Estado'])) {
            $parts[] = cp_estado((string)$a['Estado']);
        }
        if (!empty($a['CodigoPostal'])) {
            $parts[] = 'C.P. ' . $a['CodigoPostal'];
        }
        if (!empty($a['Pais']) && (string)$a['Pais'] !== 'MEX') {
            $parts[] = (string)$a['Pais'];
        }

        return $parts !== [] ? implode(', ', $parts) : '—';
    }
}

$cp = cfdi_cartaporte_node($obj);
if ($cp === null) {
    return;
}

$root = cp_attrs($cp);
$version = (string)($root['Version'] ?? '');
?>

<table class="layout" style="width:100%; margin-top:4px; border:1px solid <?= $color['borde'] ?>;">
<tr>
    <td style="width:50%; padding:6px 8px; font-size:10px; vertical-align:top;">
        <strong>IdCCP:</strong> <?= h((string)($root['IdCCP'] ?? '—')) ?><br>
        <strong>Transporte internacional:</strong> <?= h((string)($root['TranspInternac'] ?? '—')) ?><br>
        <?php if (!empty($root['EntradaSalidaMerc'])): ?>
        <strong>Entrada/Salida mercancía:</strong> <?= h((string)$root['EntradaSalidaMerc']) ?><br>
        <?php endif; ?>
        <?php if (!empty($root['TotalDistRec'])): ?>
        <strong>Distancia recorrida total:</strong> <?= h((string)$root['TotalDistRec']) ?> km<br>
        <?php endif; ?>
        <?php if (!empty($root['PaisOrigenDestino'])): ?>
        <strong>País origen/destino:</strong> <?= h((string)$root['PaisOrigenDestino']) ?><br>
        <?php endif; ?>
    </td>
    <td style="width:50%; padding:6px 8px; font-size:10px; vertical-align:top;">
        <?php if (!empty($root['ViaEntradaSalida'])): ?>
        <strong>Vía entrada/salida:</strong> <?= h((string)$root['ViaEntradaSalida']) ?><br>
        <?php endif; ?>
        <?php if (!empty($root['RegistroISTMO'])): ?>
        <strong>Registro ISTMO:</strong> <?= h((string)$root['RegistroISTMO']) ?><br>
        <?php endif; ?>
        <?php if (!empty($root['UbicacionPoloOrigen'])): ?>
        <strong>Polo origen:</strong> <?= h((string)$root['UbicacionPoloOrigen']) ?><br>
        <?php endif; ?>
        <?php if (!empty($root['UbicacionPoloDestino'])): ?>
        <strong>Polo destino:</strong> <?= h((string)$root['UbicacionPoloDestino']) ?><br>
        <?php endif; ?>
    </td>
</tr>
</table>

<?php
$ubicaciones = cp_items($cp->{'Ubicaciones'}->{'Ubicacion'} ?? null);
if ($ubicaciones !== []):
?>
<table class="tbl-conceptos" style="margin-top:6px;">
<thead>
<tr>
    <th>Tipo</th>
    <th>ID</th>
    <th>RFC</th>
    <th>Nombre</th>
    <th>Fecha salida/llegada</th>
    <th>Distancia</th>
    <th>Domicilio</th>
</tr>
</thead>
<tbody>
<?php foreach ($ubicaciones as $ub):
    $ua = cp_attrs($ub);
?>
<tr>
    <td><?= h((string)($ua['TipoUbicacion'] ?? '')) ?></td>
    <td><?= h((string)($ua['IDUbicacion'] ?? '')) ?></td>
    <td><?= h((string)($ua['RFCRemitenteDestinatario'] ?? '')) ?></td>
    <td><?= h((string)($ua['NombreRemitenteDestinatario'] ?? '')) ?></td>
    <td><?= h((string)($ua['FechaHoraSalidaLlegada'] ?? '')) ?></td>
    <td><?= h((string)($ua['DistanciaRecorrida'] ?? '')) ?></td>
    <td><?= h(cp_domicilio($ub->Domicilio ?? null)) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<?php
$mercBlock = $cp->{'Mercancias'} ?? null;
if ($mercBlock !== null):
    $ma = cp_attrs($mercBlock);
    $mercancias = cp_items($mercBlock->{'Mercancia'} ?? null);
?>
<table class="layout" style="width:100%; margin-top:8px; border:1px solid <?= $color['borde'] ?>;">
<tr><td colspan="2" class="bar-borde" style="font-weight:bold;">MERCANCÍAS</td></tr>
<tr>
    <td colspan="2" style="padding:6px 8px; font-size:10px;">
        <?php if (!empty($ma['PesoBrutoTotal'])): ?><strong>Peso bruto total:</strong> <?= h((string)$ma['PesoBrutoTotal']) ?> <?= h((string)($ma['UnidadPeso'] ?? '')) ?> &nbsp;<?php endif; ?>
        <?php if (!empty($ma['PesoNetoTotal'])): ?><strong>Peso neto:</strong> <?= h((string)$ma['PesoNetoTotal']) ?> &nbsp;<?php endif; ?>
        <?php if (!empty($ma['NumTotalMercancias'])): ?><strong>No. mercancías:</strong> <?= h((string)$ma['NumTotalMercancias']) ?><?php endif; ?>
    </td>
</tr>
</table>

<?php if ($mercancias !== []): ?>
<table class="tbl-conceptos" style="margin-top:4px;">
<thead>
<tr>
    <th>Clave</th>
    <th>Descripción</th>
    <th>Cantidad</th>
    <th>Unidad</th>
    <th>Peso (kg)</th>
    <th>Valor mercancía</th>
</tr>
</thead>
<tbody>
<?php foreach ($mercancias as $mer):
    $m = cp_attrs($mer);
?>
<tr>
    <td><?= h((string)($m['BienesTransp'] ?? '')) ?></td>
    <td><?= h((string)($m['Descripcion'] ?? '')) ?></td>
    <td style="text-align:right;"><?= h((string)($m['Cantidad'] ?? '')) ?></td>
    <td><?= h((string)($m['ClaveUnidad'] ?? '')) ?></td>
    <td style="text-align:right;"><?= h((string)($m['PesoEnKg'] ?? '')) ?></td>
    <td style="text-align:right;"><?= h((string)($m['ValorMercancia'] ?? '')) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<?php
$auto = $mercBlock->{'Autotransporte'} ?? null;
if ($auto !== null):
    $aa = cp_attrs($auto);
    $idVeh = cp_attrs($auto->{'IdentificacionVehicular'} ?? null);
    $seguros = cp_attrs($auto->{'Seguros'} ?? null);
    $remolques = cp_items($auto->{'Remolques'}->{'Remolque'} ?? null);
?>
<table class="layout" style="width:100%; margin-top:8px; border:1px solid <?= $color['borde'] ?>;">
<tr><td colspan="2" class="bar-borde" style="font-weight:bold;">AUTOTRANSPORTE</td></tr>
<tr>
    <td style="width:50%; padding:6px 8px; font-size:10px; vertical-align:top;">
        <strong>Permiso SCT:</strong> <?= h((string)($aa['PermSCT'] ?? '')) ?><br>
        <strong>No. permiso SCT:</strong> <?= h((string)($aa['NumPermisoSCT'] ?? '')) ?><br>
        <?php if ($idVeh !== []): ?>
        <strong>Config. vehicular:</strong> <?= h((string)($idVeh['ConfigVehicular'] ?? '')) ?><br>
        <strong>Placas:</strong> <?= h((string)($idVeh['PlacaVM'] ?? '')) ?><br>
        <strong>Año modelo:</strong> <?= h((string)($idVeh['AnioModeloVM'] ?? '')) ?><br>
        <strong>Peso bruto vehicular:</strong> <?= h((string)($idVeh['PesoBrutoVehicular'] ?? '')) ?><br>
        <?php endif; ?>
    </td>
    <td style="width:50%; padding:6px 8px; font-size:10px; vertical-align:top;">
        <?php if ($seguros !== []): ?>
        <strong>Seguro resp. civil:</strong> <?= h((string)($seguros['AseguraRespCivil'] ?? '')) ?><br>
        <strong>Póliza resp. civil:</strong> <?= h((string)($seguros['PolizaRespCivil'] ?? '')) ?><br>
        <?php if (!empty($seguros['AseguraMedAmbiente'])): ?>
        <strong>Seguro medio ambiente:</strong> <?= h((string)$seguros['AseguraMedAmbiente']) ?><br>
        <?php endif; ?>
        <?php if (!empty($seguros['AseguraCarga'])): ?>
        <strong>Seguro carga:</strong> <?= h((string)$seguros['AseguraCarga']) ?><br>
        <?php endif; ?>
        <?php endif; ?>
    </td>
</tr>
<?php if ($remolques !== []): ?>
<tr>
    <td colspan="2" style="padding:6px 8px; font-size:10px;">
        <strong>Remolques:</strong>
        <?php foreach ($remolques as $rem):
            $ra = cp_attrs($rem);
        ?>
        <?= h((string)($ra['SubTipoRem'] ?? '')) ?> / <?= h((string)($ra['Placa'] ?? '')) ?><?= $rem !== $remolques[array_key_last($remolques)] ? ' · ' : '' ?>
        <?php endforeach; ?>
    </td>
</tr>
<?php endif; ?>
</table>
<?php endif; ?>
<?php endif; ?>

<?php
$figuras = cp_items($cp->{'FiguraTransporte'}->{'TiposFigura'} ?? null);
if ($figuras !== []):
?>
<table class="tbl-conceptos" style="margin-top:8px;">
<thead>
<tr>
    <th>Tipo figura</th>
    <th>RFC</th>
    <th>Nombre</th>
    <th>Licencia</th>
    <th>No. identificación</th>
</tr>
</thead>
<tbody>
<?php foreach ($figuras as $fig):
    $fa = cp_attrs($fig);
?>
<tr>
    <td><?= h((string)($fa['TipoFigura'] ?? '')) ?></td>
    <td><?= h((string)($fa['RFCFigura'] ?? '')) ?></td>
    <td><?= h((string)($fa['NombreFigura'] ?? '')) ?></td>
    <td><?= h((string)($fa['NumLicencia'] ?? '')) ?></td>
    <td><?= h((string)($fa['NumRegIdTribFigura'] ?? '')) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
