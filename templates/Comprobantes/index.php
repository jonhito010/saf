<?php
/**
 * Facturación — comprobantes emitidos (CFDI)
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Comprobante> $comprobantes
 * @var array{total:int,timbradas:int,canceladas:int,conCartaPorte:int} $stats
 * @var string|null $filtroDesde
 * @var string|null $filtroHasta
 * @var bool $filtroActivo
 * @var array{min:?string,max:?string,minFmt:?string,maxFmt:?string} $fechasDisponibles
 * @var int $totalEnSistema
 * @var int $totalFiltrado
 */
$this->Paginator->setPaginated($comprobantes);

$formasPago = [
    '01' => 'Efectivo', '02' => 'Cheque nominativo', '03' => 'Transferencia electrónica de fondos',
    '04' => 'Tarjeta de crédito', '05' => 'Monedero electrónico', '06' => 'Dinero electrónico',
    '08' => 'Vales de despensa', '12' => 'Dación en pago', '13' => 'Pago por subrogación',
    '14' => 'Pago por consignación', '15' => 'Condonación', '17' => 'Compensación', '23' => 'Novación',
    '24' => 'Confusión', '25' => 'Remisión de deuda', '26' => 'Prescripción o caducidad',
    '27' => 'A satisfacción del acreedor', '28' => 'Tarjeta de débito', '29' => 'Tarjeta de servicios',
    '30' => 'Aplicación de anticipo', '31' => 'Intermediario pagos', '99' => 'Por definir',
];

$fmtFiltro = static function (?string $iso): ?string {
    if ($iso === null || $iso === '') {
        return null;
    }
    $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $iso);

    return $dt ? $dt->format('d/m/Y') : $iso;
};

$filtroDesdeFmt = $fmtFiltro($filtroDesde ?? null);
$filtroHastaFmt = $fmtFiltro($filtroHasta ?? null);
$filasPagina = is_countable($comprobantes) ? count($comprobantes) : iterator_count($comprobantes);
$hayEnSistema = (int)($totalEnSistema ?? 0) > 0;
$hayResultados = (int)($totalFiltrado ?? 0) > 0;
?>
<link rel="stylesheet" href="<?= $this->Url->build('/css/comprobantes-index.css') ?>">

<div class="fac-page">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Comprobantes emitidos</h1>
      <p>CFDI timbrados e importados — filtra por rango de fecha de emisión.</p>
    </div>
    <div class="fac-header-actions">
      <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index']) ?>" title="Quitar filtros y recargar">
        <i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Actualizar
      </a>
      <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'importarXml']) ?>">
        <i class="fa-solid fa-file-import" aria-hidden="true"></i> Importar XML
      </a>
      <a class="btn btn-primary" href="<?= $this->Url->build(['action' => 'generar']) ?>">
        <i class="fa-solid fa-circle-plus" aria-hidden="true"></i> Generar Carta Porte
      </a>
    </div>
  </div>

  <?php if ($hayEnSistema && !empty($fechasDisponibles['minFmt']) && !empty($fechasDisponibles['maxFmt'])) : ?>
    <div class="fac-rango-disponible">
      <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
      <span>Comprobantes disponibles del <strong><?= h($fechasDisponibles['minFmt']) ?></strong> al <strong><?= h($fechasDisponibles['maxFmt']) ?></strong></span>
    </div>
  <?php endif; ?>

  <?php if (!$hayEnSistema) : ?>
    <div class="panel">
      <div class="panel-header">
        <div class="panel-title">Sin comprobantes</div>
      </div>
      <div class="fac-empty">
        <div class="fac-empty-icon" aria-hidden="true"><i class="fa-solid fa-file-invoice"></i></div>
        <div class="fac-empty-title">Aún no hay comprobantes emitidos</div>
        <p class="fac-empty-text">Cuando timbres un CFDI o importes un XML aparecerá aquí con acceso al PDF y al XML.</p>
        <div class="fac-empty-actions">
          <a class="btn btn-primary" href="<?= $this->Url->build(['action' => 'generar']) ?>">Generar Carta Porte</a>
          <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'importarXml']) ?>">Importar XML</a>
        </div>
      </div>
    </div>
  <?php else : ?>

    <form class="fac-filtros" method="get" action="<?= $this->Url->build(['action' => 'index']) ?>">
      <div class="fac-filtros__row">
        <div class="fac-filtros__group">
          <label class="fac-filtros__label" for="facDesde">Desde</label>
          <input type="date" id="facDesde" name="desde" class="fac-filtros__input"
                 value="<?= h($filtroDesde ?? '') ?>"
                 min="<?= h($fechasDisponibles['min'] ?? '') ?>"
                 max="<?= h($fechasDisponibles['max'] ?? '') ?>">
        </div>
        <div class="fac-filtros__group">
          <label class="fac-filtros__label" for="facHasta">Hasta</label>
          <input type="date" id="facHasta" name="hasta" class="fac-filtros__input"
                 value="<?= h($filtroHasta ?? '') ?>"
                 min="<?= h($fechasDisponibles['min'] ?? '') ?>"
                 max="<?= h($fechasDisponibles['max'] ?? '') ?>">
        </div>
        <div class="fac-filtros__actions">
          <button type="submit" class="btn btn-primary btn-compact">
            <i class="fa-solid fa-filter" aria-hidden="true"></i> Filtrar
          </button>
          <?php if (!empty($filtroActivo)) : ?>
            <a class="btn btn-ghost btn-compact" href="<?= $this->Url->build(['action' => 'index']) ?>">Limpiar</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="fac-filtros__presets">
        <?php
        $hoy = date('Y-m-d');
        $inicioMes = date('Y-m-01');
        $hace30 = date('Y-m-d', strtotime('-30 days'));
        $minDisp = $fechasDisponibles['min'] ?? '';
        $presetMes = ['desde' => $inicioMes, 'hasta' => $hoy];
        $preset30 = ['desde' => $hace30, 'hasta' => $hoy];
        ?>
        <span class="fac-filtros__presets-label">Atajos:</span>
        <a class="fac-preset" href="<?= $this->Url->build(['action' => 'index', '?' => $presetMes]) ?>">Este mes</a>
        <a class="fac-preset" href="<?= $this->Url->build(['action' => 'index', '?' => $preset30]) ?>">Últimos 30 días</a>
        <?php if ($minDisp !== '') : ?>
          <a class="fac-preset" href="<?= $this->Url->build(['action' => 'index', '?' => ['desde' => $minDisp, 'hasta' => $fechasDisponibles['max'] ?? $hoy]]) ?>">Todo el historial</a>
        <?php endif; ?>
      </div>
    </form>

    <?php if (!empty($filtroActivo)) : ?>
      <div class="fac-rango-activo">
        <i class="fa-solid fa-eye" aria-hidden="true"></i>
        <span>
          Mostrando
          <?php if ($filtroDesdeFmt && $filtroHastaFmt) : ?>
            del <strong><?= h($filtroDesdeFmt) ?></strong> al <strong><?= h($filtroHastaFmt) ?></strong>
          <?php elseif ($filtroDesdeFmt) : ?>
            desde el <strong><?= h($filtroDesdeFmt) ?></strong>
          <?php elseif ($filtroHastaFmt) : ?>
            hasta el <strong><?= h($filtroHastaFmt) ?></strong>
          <?php endif; ?>
          — <strong><?= (int)$totalFiltrado ?></strong> comprobante<?= (int)$totalFiltrado === 1 ? '' : 's' ?>
        </span>
      </div>
    <?php endif; ?>

    <div class="fac-stats">
      <div class="fac-stat">
        <span class="fac-stat__value"><?= (int)$stats['total'] ?></span>
        <span class="fac-stat__label"><?= !empty($filtroActivo) ? 'En rango' : 'Total' ?></span>
      </div>
      <div class="fac-stat fac-stat--green">
        <span class="fac-stat__value"><?= (int)$stats['timbradas'] ?></span>
        <span class="fac-stat__label">Timbradas</span>
      </div>
      <div class="fac-stat fac-stat--teal">
        <span class="fac-stat__value"><?= (int)$stats['conCartaPorte'] ?></span>
        <span class="fac-stat__label">Carta Porte</span>
      </div>
      <div class="fac-stat fac-stat--muted">
        <span class="fac-stat__value"><?= (int)$stats['canceladas'] ?></span>
        <span class="fac-stat__label">Canceladas</span>
      </div>
    </div>

    <?php if (!$hayResultados) : ?>
      <div class="panel">
        <div class="fac-empty fac-empty--filter">
          <div class="fac-empty-icon" aria-hidden="true"><i class="fa-solid fa-calendar-xmark"></i></div>
          <div class="fac-empty-title">Sin comprobantes en este rango</div>
          <p class="fac-empty-text">No hay comprobantes con fecha de emisión en el período seleccionado. Prueba ampliar el rango o limpiar el filtro.</p>
          <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index']) ?>">Ver todos los comprobantes</a>
        </div>
      </div>
    <?php else : ?>

    <div class="panel fac-panel">
      <div class="fac-toolbar">
        <div class="fac-toolbar__left">
          <span class="fac-toolbar__title">Listado</span>
          <span class="tag"><?= (int)$totalFiltrado ?> resultado<?= (int)$totalFiltrado === 1 ? '' : 's' ?></span>
        </div>
        <div class="fac-search-wrap">
          <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
          <input type="search" id="facSearch" placeholder="Buscar en esta página…" autocomplete="off" aria-label="Filtrar en página actual">
        </div>
      </div>

      <div class="fac-legend" aria-hidden="true">
        <span class="fac-legend__item"><i class="fa-solid fa-eye fac-legend__ico fac-legend__ico--view"></i> Ver PDF</span>
        <span class="fac-legend__item"><i class="fa-solid fa-clone fac-legend__ico fac-legend__ico--reuse"></i> Reutilizar</span>
        <span class="fac-legend__item"><i class="fa-solid fa-file-code fac-legend__ico fac-legend__ico--dl"></i> XML</span>
        <span class="fac-legend__item"><i class="fa-solid fa-file-pdf fac-legend__ico fac-legend__ico--pdf"></i> PDF</span>
      </div>

      <div class="table-wrap table-wrap--stack fac-table-wrap">
        <table id="facTable" class="fac-table">
          <colgroup>
            <col class="fac-col fac-col--id">
            <col class="fac-col fac-col--receptor">
            <col class="fac-col fac-col--fecha">
            <col class="fac-col fac-col--total">
            <col class="fac-col fac-col--estatus">
            <col class="fac-col fac-col--cp">
            <col class="fac-col fac-col--pago">
            <col class="fac-col fac-col--acciones">
          </colgroup>
          <thead>
            <tr>
              <th>Serie / folio</th>
              <th>Receptor</th>
              <th><?= $this->Paginator->sort('fechaEmision', 'Emisión') ?></th>
              <th class="fac-th-num">Total</th>
              <th class="fac-th-center">Estatus</th>
              <th class="fac-th-center" title="Carta Porte">CP</th>
              <th class="fac-th-center">Pago</th>
              <th class="fac-th-center fac-th-actions">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($comprobantes as $comprobante) :
                $fechaDate = '';
                $fechaTime = '';
                if (!empty($comprobante->fechaEmision)) {
                    $fe = $comprobante->fechaEmision;
                    if ($fe instanceof \DateTimeInterface) {
                        $fechaDate = $fe->format('d/m/Y');
                        $fechaTime = $fe->format('H:i');
                    } else {
                        $rawFe = (string)$fe;
                        $ts = strtotime($rawFe);
                        if ($ts !== false) {
                            $fechaDate = date('d/m/Y', $ts);
                            $fechaTime = date('H:i', $ts);
                        } else {
                            $fechaDate = substr($rawFe, 0, 10);
                            if (strlen($rawFe) > 10) {
                                $fechaTime = substr($rawFe, 11, 5);
                            }
                        }
                    }
                }
                $formaCod = (string)($comprobante->formaPago ?? '');
                $formaTxt = $formasPago[$formaCod] ?? 'Por definir';
                $estatus = (int)($comprobante->estatus ?? 0);
                $timbrada = $estatus === 1;
                $cancelada = $estatus === 2;
                $llevaCp = (int)($comprobante->llevaCartaPorte ?? 0) === 1;
                $uuidFull = (string)($comprobante->uuid ?? '');
                $serie = trim((string)($comprobante->serie ?? ''));
                $folio = trim((string)($comprobante->folio ?? ''));
                $serieFolio = $serie !== '' && $folio !== ''
                    ? $serie . '-' . $folio
                    : ($serie !== '' ? $serie : ($folio !== '' ? $folio : ''));
                $nomRec = trim((string)($comprobante->nombreReceptor ?? ''));
                $rfcRec = trim((string)($comprobante->rfcReceptor ?? ''));
                $tieneXml = trim((string)($comprobante->xml ?? '')) !== '';
                ?>
            <tr>
              <td data-label="Serie / folio">
                <?php if ($serieFolio !== '') : ?>
                  <span class="fac-serie"><?= h($serieFolio) ?></span>
                <?php else : ?>
                  <span class="fac-cell-muted">—</span>
                <?php endif; ?>
                <?php if ($uuidFull !== '') : ?>
                  <span class="fac-uuid" title="<?= h($uuidFull) ?>"><?= h($uuidFull) ?></span>
                <?php endif; ?>
              </td>
              <td data-label="Receptor">
                <div class="fac-cell-main" title="<?= h($nomRec) ?>"><?= $nomRec !== '' ? h($nomRec) : '—' ?></div>
                <?php if ($rfcRec !== '') : ?>
                  <div class="fac-cell-sub"><?= h($rfcRec) ?></div>
                <?php endif; ?>
              </td>
              <td data-label="Emisión" class="fac-date-cell">
                <?php if ($fechaDate !== '') : ?>
                  <span class="fac-date"><?= h($fechaDate) ?></span>
                  <?php if ($fechaTime !== '') : ?>
                    <span class="fac-time"><?= h($fechaTime) ?></span>
                  <?php endif; ?>
                <?php else : ?>
                  <span class="fac-cell-muted">—</span>
                <?php endif; ?>
              </td>
              <td data-label="Total" class="fac-money fac-money--total">$<?= h(number_format((float)($comprobante->total ?? 0), 2, '.', ',')) ?></td>
              <td data-label="Estatus" class="fac-td-center">
                <?php if ($cancelada) : ?>
                  <span class="badge badge-red">Cancelada</span>
                <?php elseif ($timbrada) : ?>
                  <span class="badge badge-green">Timbrado</span>
                <?php else : ?>
                  <span class="badge badge-amber">Pendiente</span>
                <?php endif; ?>
              </td>
              <td data-label="CP" class="fac-td-center">
                <?php if ($llevaCp) : ?>
                  <span class="fac-cp fac-cp--yes" title="Incluye Carta Porte"><i class="fa-solid fa-truck" aria-hidden="true"></i></span>
                <?php else : ?>
                  <span class="fac-cp fac-cp--no" aria-hidden="true">·</span>
                <?php endif; ?>
              </td>
              <td data-label="Pago" class="fac-td-center">
                <span class="fac-pago__code" title="<?= h($formaTxt) ?>"><?= h($formaCod !== '' ? $formaCod : '—') ?></span>
              </td>
              <td data-label="Acciones" class="fac-actions-cell">
                <div class="fac-actions">
                  <a href="<?= $this->Url->build(['action' => 'vistaPdf', $comprobante->id]) ?>" target="_blank" rel="noopener noreferrer" class="fac-icon-btn fac-icon-btn--view" title="Ver PDF" aria-label="Ver PDF">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                  </a>
                  <?php if ($tieneXml) : ?>
                    <a href="<?= $this->Url->build(['action' => 'generar', '?' => ['desde' => $comprobante->id]]) ?>" class="fac-icon-btn fac-icon-btn--reuse" title="Reutilizar comprobante y complemento" aria-label="Reutilizar comprobante">
                      <i class="fa-solid fa-clone" aria-hidden="true"></i>
                    </a>
                    <a href="<?= $this->Url->build(['action' => 'xml', $comprobante->id]) ?>" class="fac-icon-btn fac-icon-btn--download" title="Descargar XML" aria-label="Descargar XML" download>
                      <i class="fa-solid fa-file-code" aria-hidden="true"></i>
                    </a>
                  <?php endif; ?>
                  <a href="<?= $this->Url->build(['action' => 'pdf', $comprobante->id]) ?>" class="fac-icon-btn fac-icon-btn--pdf" title="Descargar PDF" aria-label="Descargar PDF" download>
                    <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="fac-panel-footer">
        <span class="fac-foot" id="facFootInfo"><?= (int)$filasPagina ?> fila<?= $filasPagina === 1 ? '' : 's' ?> en esta página</span>
        <div class="fac-paginator fac-paginator--inline">
          <ul>
            <?= $this->Paginator->prev('‹') ?>
            <?= $this->Paginator->numbers(['modulus' => 3]) ?>
            <?= $this->Paginator->next('›') ?>
          </ul>
          <span class="fac-paginator-counter"><?= $this->Paginator->counter('{{page}} / {{pages}}') ?></span>
        </div>
      </div>
    </div>

    <?php endif; ?>
  <?php endif; ?>
</div>

<script>
(function () {
  var search = document.getElementById('facSearch');
  var table = document.getElementById('facTable');
  if (!search || !table) return;
  var rows = Array.from(table.querySelectorAll('tbody tr'));
  var foot = document.getElementById('facFootInfo');
  var total = rows.length;

  function apply() {
    var q = search.value.trim().toLowerCase();
    var n = 0;
    rows.forEach(function (r) {
      var show = !q || r.textContent.toLowerCase().indexOf(q) !== -1;
      r.style.display = show ? '' : 'none';
      if (show) n++;
    });
    if (foot) {
      foot.textContent = n + ' de ' + total + ' en esta página' + (q ? ' · filtro activo' : '');
    }
  }

  search.addEventListener('input', apply);
})();
</script>
