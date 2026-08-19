<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Vehiculo $vehiculo
 * @var iterable $docsOrdenados
 * @var iterable $docsOrdenadosMp
 * @var array<string, int> $docResumen
 * @var array<string, int> $docResumenMp
 * @var string $peorEstatus
 * @var string $peorEstatusMp
 * @var list<string> $docsRequeridos
 * @var list<string> $docsRequeridosMp
 * @var list<string> $tiposMp
 * @var array<string, array{label: string, descripcion: string, retencion: string, icon: string}> $catalogoMp
 * @var bool $esMaterialPeligroso
 * @var string $tabInicial
 */

use App\Model\Table\DocumentosVehiculoTable;
use App\Model\Table\VehiculosTable;

$estatusBadge = function (?string $estatus): string {
    if (!$estatus) {
        return '<span class="badge badge-gray">—</span>';
    }
    return match ($estatus) {
        'vencido' => '<span class="badge badge-red">vencido</span>',
        'por_vencer' => '<span class="badge badge-amber">por vencer</span>',
        default => '<span class="badge badge-green">vigente</span>',
    };
};

$docCardClass = function (?string $estatus): string {
    return match ($estatus ?? '') {
        'vencido' => 'doc-card vencido',
        'por_vencer' => 'doc-card por_vencer',
        default => 'doc-card uploaded',
    };
};

$docIcons = [
    'tarjeta_circulacion' => '📄',
    'alta_vehicular' => '📋',
    'poliza_seguro' => '🛡️',
    'inspeccion_fisico_mecanica' => '🔧',
    'verificacion_emisiones' => '💨',
    'permiso_sct_sict' => '🏛️',
    'poliza_responsabilidad_ambiental' => '🌿',
    'comprobante_pago_poliza_ambiental' => '💳',
    'bitacora_inspeccion_ocular' => '👁️',
    'control_mantenimiento' => '🔩',
    'otro' => '📎',
];

$origenDocs = 'vehiculo:' . (int)$vehiculo->id . ':docs';
$origenMp = 'vehiculo:' . (int)$vehiculo->id . ':mp';

$urlAddDocMp = fn(string $tipo) => $this->Url->build([
    'controller' => 'DocumentosVehiculo',
    'action' => 'add',
    '?' => ['vehiculo_id' => $vehiculo->id, 'tipo' => $tipo, 'origen' => $origenMp],
]);

$docsPorTipo = [];
foreach ($vehiculo->documentos_vehiculo as $doc) {
    $docsPorTipo[$doc->tipo_documento] = $doc;
}

$urlAddDoc = $this->Url->build([
    'controller' => 'DocumentosVehiculo',
    'action' => 'add',
    '?' => ['vehiculo_id' => $vehiculo->id, 'origen' => $origenDocs],
]);

$urlEditDoc = fn($doc, string $tabOrigen) => $this->Url->build([
    'controller' => 'DocumentosVehiculo',
    'action' => 'edit',
    $doc->id,
    '?' => ['origen' => 'vehiculo:' . (int)$vehiculo->id . ':' . $tabOrigen],
]);

$estadoGeneral = match ($peorEstatus) {
    'vencido' => ['badge-red', 'Documentación con vencidos'],
    'por_vencer' => ['badge-amber', 'Documentos por vencer'],
    default => ['badge-green', 'Documentación vigente'],
};

$estadoMp = match ($peorEstatusMp) {
    'vencido' => ['badge-red', 'MP con vencidos'],
    'por_vencer' => ['badge-amber', 'MP por vencer'],
    default => $docResumenMp['total'] > 0 ? ['badge-green', 'MP al día'] : ['badge-gray', 'MP pendiente'],
};

$mpOk = 0;
foreach ($docsRequeridosMp as $tipoMpReq) {
    $d = $docsPorTipo[$tipoMpReq] ?? null;
    if ($d && in_array($d->estatus, ['vigente', 'por_vencer'], true)) {
        $mpOk++;
    }
}
$mpPct = count($docsRequeridosMp) > 0 ? (int)round(100 * $mpOk / count($docsRequeridosMp)) : 0;
$mpFill = $mpPct < 60 ? 'fill-red' : ($mpPct < 100 ? 'fill-amber' : 'fill-teal');

$tabActiva = in_array($tabInicial ?? 'docs', ['docs', 'mp', 'mant', 'hist'], true) ? $tabInicial : 'docs';
?>

<div class="veh-page">
  <div class="veh-toolbar">
    <a href="<?= $this->Url->build(['controller' => 'Vehiculos', 'action' => 'index']) ?>" class="btn btn-ghost btn-sm">← Vehículos</a>
    <a href="<?= $this->Url->build(['controller' => 'Vehiculos', 'action' => 'edit', $vehiculo->id]) ?>" class="btn btn-ghost btn-sm">Editar datos</a>
    <a href="<?= $urlAddDoc ?>" class="btn btn-primary btn-sm">+ Documento general</a>
    <button type="button" class="btn btn-primary btn-sm veh-btn-mp" data-veh-tab="mp">Materiales peligrosos</button>
  </div>

  <div class="veh-hero">
    <div class="veh-hero-plate"><?= h(strtoupper($vehiculo->placas)) ?></div>
    <div class="veh-hero-main">
      <h2 class="veh-hero-title"><?= h($vehiculo->nombre_completo) ?></h2>
      <p class="veh-hero-sub">
        VIN <?= h($vehiculo->numero_serie) ?>
        <?php if ($vehiculo->numero_motor): ?> · Motor <?= h($vehiculo->numero_motor) ?><?php endif; ?>
      </p>
      <div class="veh-hero-tags">
        <span class="badge <?= $estadoGeneral[0] ?>"><?= h($estadoGeneral[1]) ?></span>
        <span class="tag"><?= h($vehiculo->tipo_carga_label) ?></span>
        <?php if ($vehiculo->modalidad): ?>
          <span class="tag"><?= h(VehiculosTable::modalidadOptions()[$vehiculo->modalidad] ?? $vehiculo->modalidad) ?></span>
        <?php endif; ?>
        <?php if ($vehiculo->tipo_servicio): ?>
          <span class="tag"><?= h(VehiculosTable::tipoServicioOptions()[$vehiculo->tipo_servicio] ?? $vehiculo->tipo_servicio) ?></span>
        <?php endif; ?>
        <?php if ($vehiculo->color): ?><span class="tag"><?= h($vehiculo->color) ?></span><?php endif; ?>
      </div>
    </div>
    <div class="veh-hero-stats">
      <div class="veh-stat">
        <div class="veh-stat-val"><?= (int)$docResumen['total'] ?></div>
        <div class="veh-stat-label">Documentos</div>
      </div>
      <div class="veh-stat">
        <div class="veh-stat-val" style="color:var(--green)"><?= (int)$docResumen['vigente'] ?></div>
        <div class="veh-stat-label">Vigentes</div>
      </div>
      <div class="veh-stat">
        <div class="veh-stat-val" style="color:<?= $docResumen['porVencer'] > 0 ? '#C87000' : 'var(--text3)' ?>"><?= (int)$docResumen['porVencer'] ?></div>
        <div class="veh-stat-label">Por vencer</div>
      </div>
      <div class="veh-stat">
        <div class="veh-stat-val" style="color:<?= $docResumen['vencido'] > 0 ? 'var(--red)' : 'var(--text3)' ?>"><?= (int)$docResumen['vencido'] ?></div>
        <div class="veh-stat-label">Vencidos</div>
      </div>
      <div class="veh-stat">
        <div class="veh-stat-val" style="color:var(--teal)"><?= (int)$docResumen['pct'] ?>%</div>
        <div class="veh-stat-label">Cumplimiento</div>
      </div>
      <div class="veh-stat veh-stat--mp">
        <div class="veh-stat-val" style="color:<?= $mpPct < 100 ? '#C87000' : 'var(--green)' ?>"><?= $mpPct ?>%</div>
        <div class="veh-stat-label">MP vehículo</div>
      </div>
    </div>
  </div>

  <div class="veh-detail-grid">
    <div class="veh-detail-main">
      <div class="tabs-bar veh-tabs">
        <button type="button" class="tab-btn<?= $tabActiva === 'docs' ? ' active' : '' ?>" data-veh-tab="docs">Expediente documental</button>
        <button type="button" class="tab-btn tab-btn--mp<?= $tabActiva === 'mp' ? ' active' : '' ?>" data-veh-tab="mp">
          ⚠ Materiales peligrosos
          <span class="veh-tab-badge<?= $mpPct >= 100 ? ' veh-tab-badge--ok' : '' ?>"><?= $mpOk ?>/<?= count($docsRequeridosMp) ?></span>
        </button>
        <button type="button" class="tab-btn<?= $tabActiva === 'mant' ? ' active' : '' ?>" data-veh-tab="mant">Mantenimiento</button>
        <button type="button" class="tab-btn<?= $tabActiva === 'hist' ? ' active' : '' ?>" data-veh-tab="hist">Historial</button>
      </div>

      <div class="tab-content<?= $tabActiva === 'docs' ? ' active' : '' ?>" id="veh-tab-docs">
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title">Documentos del vehículo</div>
            <a class="btn btn-primary btn-sm" href="<?= $urlAddDoc ?>">+ Agregar</a>
          </div>
          <?php if (empty($docsOrdenados) || (is_countable($docsOrdenados) && count($docsOrdenados) === 0)): ?>
          <div class="veh-empty veh-empty--compact">
            <p>Sin documentos cargados. Registra seguro, verificación e inspección.</p>
            <a class="btn btn-primary btn-sm" href="<?= $urlAddDoc ?>">Cargar primer documento</a>
          </div>
          <?php else: ?>
          <div class="doc-grid veh-doc-grid">
            <?php foreach ($docsOrdenados as $doc):
                $archivoUrl = $doc->archivo_url ?? null;
                $archivoHref = $archivoUrl ? $this->Url->build($archivoUrl) : null;
            ?>
            <div class="<?= $docCardClass($doc->estatus) ?>">
              <div class="doc-card-icon"><?= $docIcons[$doc->tipo_documento] ?? '📎' ?></div>
              <div class="doc-card-name"><?= h(DocumentosVehiculoTable::tipoLabel((string)$doc->tipo_documento)) ?></div>
              <div class="doc-card-meta">
                <?php if ($doc->fecha_vencimiento): ?>
                  <?= $doc->estatus === 'vencido' ? 'Venció' : 'Vence' ?> <?= h($doc->fecha_vencimiento->format('d/m/Y')) ?>
                <?php else: ?>
                  Sin fecha de vencimiento
                <?php endif; ?>
              </div>
              <div class="doc-card-badge"><?= $estatusBadge($doc->estatus) ?></div>
              <div class="doc-card-actions">
                <a href="<?= h($urlEditDoc($doc, 'docs')) ?>" class="btn btn-ghost btn-compact">Editar</a>
                <?php if ($archivoHref): ?>
                <a href="<?= h($archivoHref) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-compact">Ver archivo</a>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
            <a class="doc-card doc-card-add" href="<?= $urlAddDoc ?>">
              <div class="doc-card-icon">➕</div>
              <div class="doc-card-name doc-card-add-label">Nuevo documento</div>
              <div class="doc-card-meta">PDF, imagen o Word</div>
            </a>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="tab-content veh-tab-mp-wrap<?= $tabActiva === 'mp' ? ' active' : '' ?>" id="veh-tab-mp">
        <div class="veh-mp-banner">
          <div class="veh-mp-banner-icon" aria-hidden="true">⚠</div>
          <div class="veh-mp-banner-text">
            <h2 class="veh-mp-banner-title">Expediente — materiales peligrosos</h2>
            <p class="veh-mp-banner-desc">Documentación por vehículo exigida para transporte de materiales, remanentes y residuos peligrosos (SICT). Solo se listan los archivos de este rubro.</p>
          </div>
          <div class="veh-mp-banner-actions">
            <span class="badge <?= h($estadoMp[0]) ?>"><?= h($estadoMp[1]) ?></span>
            <?php if ($esMaterialPeligroso): ?>
              <span class="tag tag--mp">Tipo de carga: MP</span>
            <?php endif; ?>
            <a class="btn btn-ghost btn-sm" href="<?= $this->Url->build(['controller' => 'Cumplimiento', 'action' => 'materialesPeligrosos']) ?>">Guía completa</a>
          </div>
        </div>

        <div class="veh-mp-kpis">
          <div class="veh-mp-kpi">
            <span class="veh-mp-kpi-val"><?= $mpOk ?>/<?= count($docsRequeridosMp) ?></span>
            <span class="veh-mp-kpi-label">Documentos cargados</span>
          </div>
          <div class="veh-mp-kpi veh-mp-kpi--pct">
            <span class="veh-mp-kpi-val"><?= $mpPct ?>%</span>
            <span class="veh-mp-kpi-label">Cumplimiento MP</span>
            <div class="cumpl-bar cumpl-bar--sm"><div class="cumpl-fill <?= $fillClass ?>" style="width:<?= $mpPct ?>%"></div></div>
          </div>
          <div class="veh-mp-kpi">
            <span class="veh-mp-kpi-val" style="color:var(--green)"><?= (int)$docResumenMp['vigente'] ?></span>
            <span class="veh-mp-kpi-label">Vigentes</span>
          </div>
          <div class="veh-mp-kpi">
            <span class="veh-mp-kpi-val" style="color:<?= $docResumenMp['vencido'] > 0 ? 'var(--red)' : 'var(--text3)' ?>"><?= (int)$docResumenMp['vencido'] ?></span>
            <span class="veh-mp-kpi-label">Vencidos</span>
          </div>
        </div>

        <div class="panel veh-mp-panel">
          <div class="veh-mp-panel-toolbar">
            <span class="veh-mp-panel-title">Documentos requeridos por vehículo</span>
            <a class="btn btn-primary btn-sm" href="<?= $urlAddDocMp($docsRequeridosMp[0] ?? 'poliza_responsabilidad_ambiental') ?>">+ Cargar documento MP</a>
          </div>

          <div class="veh-mp-list">
            <?php foreach ($docsRequeridosMp as $i => $tipoMp):
                $meta = $catalogoMp[$tipoMp] ?? [
                    'label' => DocumentosVehiculoTable::tipoLabel($tipoMp),
                    'descripcion' => '',
                    'retencion' => '—',
                    'icon' => $docIcons[$tipoMp] ?? '📎',
                ];
                $doc = $docsPorTipo[$tipoMp] ?? null;
                $archivoHref = ($doc && ($doc->archivo_url ?? null)) ? $this->Url->build($doc->archivo_url) : null;
                $rowClass = 'veh-mp-row';
                if (!$doc) {
                    $rowClass .= ' veh-mp-row--missing';
                } elseif ($doc->estatus === 'vencido') {
                    $rowClass .= ' veh-mp-row--bad';
                } elseif ($doc->estatus === 'por_vencer') {
                    $rowClass .= ' veh-mp-row--warn';
                } else {
                    $rowClass .= ' veh-mp-row--ok';
                }
            ?>
            <article class="<?= $rowClass ?>">
              <div class="veh-mp-row-num"><?= $i + 1 ?></div>
              <div class="veh-mp-row-icon" aria-hidden="true"><?= h($meta['icon']) ?></div>
              <div class="veh-mp-row-body">
                <h3 class="veh-mp-row-title"><?= h($meta['label']) ?></h3>
                <p class="veh-mp-row-desc"><?= h($meta['descripcion']) ?></p>
                <div class="veh-mp-row-meta">
                  <span class="veh-mp-row-ret"><strong>Retención:</strong> <?= h($meta['retencion']) ?></span>
                  <?php if ($doc && $doc->nombre_archivo): ?>
                    <span class="veh-mp-row-file"><?= h($doc->nombre_archivo) ?></span>
                  <?php endif; ?>
                  <?php if ($doc && $doc->fecha_vencimiento): ?>
                    <span class="veh-mp-row-vence td-mono"><?= $doc->estatus === 'vencido' ? 'Venció' : 'Vence' ?> <?= h($doc->fecha_vencimiento->format('d/m/Y')) ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="veh-mp-row-status">
                <?= $doc ? $estatusBadge($doc->estatus) : '<span class="badge badge-gray">Sin cargar</span>' ?>
              </div>
              <div class="veh-mp-row-actions">
                <?php if ($doc): ?>
                  <a href="<?= h($urlEditDoc($doc, 'mp')) ?>" class="btn btn-ghost btn-sm">Editar</a>
                  <?php if ($archivoHref): ?>
                    <a href="<?= h($archivoHref) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">Ver archivo</a>
                  <?php endif; ?>
                <?php else: ?>
                  <a href="<?= $urlAddDocMp($tipoMp) ?>" class="btn btn-primary btn-sm">Subir</a>
                <?php endif; ?>
              </div>
            </article>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="veh-mp-note panel">
          <p><strong>Nota:</strong> La bitácora de inspección ocular y el control de mantenimiento deben conservarse al menos de los <strong>6 meses anteriores</strong> a la fecha de revisión. El expediente general (seguro, verificación, etc.) está en la pestaña <button type="button" class="veh-mp-inline-tab" data-veh-tab="docs">Expediente documental</button>.</p>
        </div>
      </div>

      <div class="tab-content<?= $tabActiva === 'mant' ? ' active' : '' ?>" id="veh-tab-mant">
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title">Bitácora de mantenimiento</div>
            <a class="btn btn-primary btn-sm" href="<?= $this->Url->build(['controller' => 'Mantenimientos', 'action' => 'addForVehiculo', $vehiculo->id]) ?>">+ Agregar</a>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr><th>Fecha</th><th>Tipo</th><th>Descripción</th><th>Km</th><th>Taller</th><th>Costo</th></tr>
              </thead>
              <tbody>
                <?php foreach ($vehiculo->mantenimientos as $m): ?>
                <tr>
                  <td class="td-mono"><?= $m->fecha ? h($m->fecha->format('d/m/Y')) : '—' ?></td>
                  <td><span class="tag"><?= h(ucfirst((string)$m->tipo)) ?></span></td>
                  <td><?= h($m->descripcion) ?></td>
                  <td class="td-mono"><?= $m->kilometraje ? number_format((int)$m->kilometraje) : '—' ?></td>
                  <td><?= h($m->taller ?? '—') ?></td>
                  <td class="td-mono"><?= $m->costo ? '$' . number_format((float)$m->costo, 2) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($vehiculo->mantenimientos) || count($vehiculo->mantenimientos) === 0): ?>
                <tr><td colspan="6" class="veh-table-empty">Sin registros de mantenimiento.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="tab-content<?= $tabActiva === 'hist' ? ' active' : '' ?>" id="veh-tab-hist">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Historial documental</div></div>
          <div class="veh-timeline">
            <?php foreach ($docsOrdenados as $doc): ?>
            <div class="veh-timeline-item">
              <div class="veh-timeline-date td-mono">
                <?= $doc->created_at ? h($doc->created_at->format('d/m/Y')) : '—' ?>
              </div>
              <div class="veh-timeline-body">
                <strong><?= h(DocumentosVehiculoTable::tipoLabel((string)$doc->tipo_documento)) ?></strong>
                <?= $estatusBadge($doc->estatus) ?>
                <?php if ($doc->fecha_vencimiento): ?>
                  <span class="veh-timeline-meta">Vence <?= h($doc->fecha_vencimiento->format('d/m/Y')) ?></span>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($docsOrdenados) || (is_countable($docsOrdenados) && count($docsOrdenados) === 0)): ?>
            <p class="veh-table-empty" style="padding:16px 20px">Sin movimientos registrados.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <aside class="veh-detail-aside">
      <div class="panel">
        <div class="panel-header"><div class="panel-title">Ficha técnica</div></div>
        <div class="veh-spec-list">
          <div class="data-row"><div class="data-label">Placas</div><div class="data-value td-mono"><?= h($vehiculo->placas) ?></div></div>
          <div class="data-row"><div class="data-label">Año / color</div><div class="data-value"><?= h((string)$vehiculo->anio) ?> · <?= h($vehiculo->color ?? '—') ?></div></div>
          <div class="data-row"><div class="data-label">Peso bruto</div><div class="data-value"><?= $vehiculo->peso_bruto_vehicular ? h((string)$vehiculo->peso_bruto_vehicular) . ' kg' : '—' ?></div></div>
          <div class="data-row"><div class="data-label">Ejes</div><div class="data-value"><?= $vehiculo->num_ejes ? h((string)$vehiculo->num_ejes) : '—' ?></div></div>
          <div class="data-row"><div class="data-label">Estatus del vehículo</div><div class="data-value"><span class="badge badge-green"><?= h($vehiculo->estatus) ?></span></div></div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header"><div class="panel-title">Checklist normativo</div></div>
        <ul class="veh-checklist">
          <?php foreach ($docsRequeridos as $tipoReq):
              $docReq = $docsPorTipo[$tipoReq] ?? null;
              $ok = $docReq && in_array($docReq->estatus, ['vigente', 'por_vencer'], true);
          ?>
          <li class="veh-checklist-item<?= $ok ? ' veh-checklist-item--ok' : ' veh-checklist-item--miss' ?>">
            <span class="veh-check-icon" aria-hidden="true"><?= $ok ? '✓' : '!' ?></span>
            <span class="veh-check-label"><?= h(DocumentosVehiculoTable::tipoLabel($tipoReq)) ?></span>
            <?= $docReq ? $estatusBadge($docReq->estatus) : '<span class="badge badge-gray">faltante</span>' ?>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="panel veh-mp-aside">
        <div class="panel-header"><div class="panel-title">Resumen MP</div></div>
        <div class="veh-mp-aside-body">
          <div class="veh-mp-aside-pct">
            <div class="veh-mp-aside-pct-val"><?= $mpPct ?>%</div>
            <div class="veh-mp-aside-pct-label">cumplimiento</div>
          </div>
          <div class="cumpl-bar"><div class="cumpl-fill <?= $fillClass ?>" style="width:<?= $mpPct ?>%"></div></div>
          <ul class="veh-mp-aside-list">
            <?php foreach ($docsRequeridosMp as $tipoMp):
                $docA = $docsPorTipo[$tipoMp] ?? null;
                $okA = $docA && in_array($docA->estatus, ['vigente', 'por_vencer'], true);
                $metaA = $catalogoMp[$tipoMp] ?? ['label' => $tipoMp, 'icon' => '•'];
            ?>
            <li class="veh-mp-aside-item<?= $okA ? ' is-ok' : ' is-miss' ?>">
              <span><?= h($metaA['icon'] ?? '•') ?></span>
              <span class="veh-mp-aside-item-label"><?= h($metaA['label'] ?? DocumentosVehiculoTable::tipoLabel($tipoMp)) ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
          <button type="button" class="btn btn-primary btn-sm veh-mp-tab-link" data-veh-tab="mp">Abrir expediente MP</button>
        </div>
      </div>
    </aside>
  </div>
</div>

<script>
(function () {
  function activarTab(id, btn) {
    document.querySelectorAll('.veh-tabs .tab-btn').forEach(function (b) { b.classList.remove('active'); });
    document.querySelectorAll('.veh-detail-main .tab-content').forEach(function (p) { p.classList.remove('active'); });
    if (btn) btn.classList.add('active');
    var panel = document.getElementById('veh-tab-' + id);
    if (panel) panel.classList.add('active');
  }
  document.querySelectorAll('[data-veh-tab]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      activarTab(btn.getAttribute('data-veh-tab'), btn);
      if (btn.getAttribute('data-veh-tab') === 'mp') {
        var mp = document.getElementById('veh-tab-mp');
        if (mp) mp.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
})();
</script>
