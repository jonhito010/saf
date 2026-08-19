<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Empresa $empresa
 * @var iterable $documentos
 * @var bool $sinDocumentosCorporativos
 * @var bool $modoFormulario
 * @var list<array<string, mixed>> $empresasDocumentacion
 * @var array<int, string> $empresasFiltro
 * @var int|null $filtroEmpresaId
 * @var bool $vistaConsolidada
 * @var string $alcanceLabel
 */

use App\Model\Table\DocumentosEmpresaTable;
use App\Service\AlertasReglasService;
use Cake\I18n\Date;

$modoFormulario = $modoFormulario ?? false;
$empresasDocumentacion = $empresasDocumentacion ?? [];

$estatusBadge = function (string $estatus): string {
    return match ($estatus) {
        'vencido'    => '<span class="badge badge-red">vencido</span>',
        'por_vencer' => '<span class="badge badge-amber">por vencer</span>',
        default      => '<span class="badge badge-green">vigente</span>',
    };
};

/** Texto de días restantes / atraso según fecha de vencimiento. */
$diasRestantesTxt = static function (mixed $fecha, ?string $estatus = null): ?string {
    if ($fecha === null || $fecha === '') {
        return null;
    }
    try {
        $vence = $fecha instanceof \DateTimeInterface
            ? new Date($fecha->format('Y-m-d'))
            : new Date((string)$fecha);
    } catch (\Throwable) {
        return null;
    }

    $hoy = Date::today();
    if ($vence < $hoy || $estatus === 'vencido') {
        $atraso = (int)$vence->diff($hoy)->days;
        if ($atraso <= 0) {
            return 'Vencido';
        }

        return $atraso === 1 ? 'Vencido hace 1 día' : "Vencido hace {$atraso} días";
    }

    $dias = AlertasReglasService::diasRestantes($vence);
    if ($dias === null) {
        return null;
    }
    if ($dias === 0) {
        return 'Vence hoy';
    }

    return $dias === 1 ? '1 día restante' : "{$dias} días restantes";
};

$docCardClass = function (string $estatus): string {
    return match ($estatus) {
        'vencido'    => 'doc-card vencido',
        'por_vencer' => 'doc-card por_vencer',
        default      => 'doc-card uploaded',
    };
};

$docIcons = [
    'acta_constitutiva' => '📄',
    'acta_asamblea'     => '📋',
    'cedula_fiscal'     => '🪪',
    'permiso_sict'      => '🏛️',
    'otro'              => '📎',
];

$pctClass = static function (int $pct): string {
    if ($pct >= 90) {
        return 'ok';
    }
    if ($pct >= 50) {
        return 'mid';
    }

    return 'low';
};

if (!$modoFormulario):
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Documentación empresa</h1>
    <p>
      Expediente documental por empresa
      <?php if (!empty($alcanceLabel)): ?> · <?= h($alcanceLabel) ?><?php endif; ?>
    </p>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index', '?' => ['nueva' => '1']]) ?>">+ Nueva empresa</a>
    <a class="btn btn-ghost" href="<?= $this->Url->build(['controller' => 'Cumplimiento', 'action' => 'index']) ?>">Ver cumplimiento</a>
  </div>
</div>

<?= $this->element('filtro_empresa', [
    'empresasFiltro' => $empresasFiltro ?? [],
    'filtroEmpresaId' => $filtroEmpresaId ?? null,
    'vistaConsolidada' => $vistaConsolidada ?? false,
    'alcanceLabel' => $alcanceLabel ?? '',
    'filtroPreserve' => ['q' => $q ?? ''],
]) ?>

<form class="oper-filters panel" method="get" action="<?= $this->Url->build(['action' => 'index']) ?>" style="margin-bottom:16px">
  <?php if (!empty($filtroEmpresaId)): ?>
    <input type="hidden" name="empresa_id" value="<?= (int)$filtroEmpresaId ?>">
  <?php endif; ?>
  <div class="oper-filters-row">
    <div class="search-wrap oper-search">
      <svg class="search-icon" width="13" height="13" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
      <input type="search" name="q" value="<?= h($q ?? '') ?>" placeholder="Buscar por nombre, RFC, correo o teléfono…" autocomplete="off">
    </div>
    <?php if (!empty($q)): ?>
      <a class="btn btn-ghost btn-sm" href="<?= $this->Url->build(['action' => 'index', '?' => array_filter(['empresa_id' => $filtroEmpresaId ?? null])]) ?>">Limpiar búsqueda</a>
    <?php endif; ?>
  </div>
</form>

<?php if ($empresasDocumentacion === []): ?>
  <div class="panel dash-empty-panel">
    <p>No hay empresas en tu alcance. <a href="<?= $this->Url->build(['action' => 'index', '?' => ['nueva' => '1']]) ?>">Registrar empresa</a></p>
  </div>
<?php else: ?>
  <div class="emp-doc-list">
    <?php foreach ($empresasDocumentacion as $bloque):
        /** @var \App\Model\Entity\Empresa $emp */
        $emp = $bloque['empresa'];
        $docs = $bloque['documentos'];
        $eid = (int)$emp->id;
        $urlAgregar = $this->Url->build([
            'controller' => 'DocumentosEmpresa',
            'action' => 'add',
            '?' => ['empresa_id' => $eid, 'return' => 'empresa', 'origen' => 'empresa'],
        ]);
        $urlGestionar = $this->Url->build(['action' => 'index', '?' => ['gestionar' => $eid]]);
        $pct = (int)$bloque['cumplimientoPct'];
        $pctCls = $pctClass($pct);
        $esActiva = (int)($empresaIdActiva ?? 0) === $eid;
    ?>
    <article class="dash-emp-row emp-doc-block<?= $esActiva ? ' is-activa' : '' ?>" id="empresa-<?= $eid ?>">
      <header class="dash-emp-row-head">
        <div class="dash-emp-row-ident">
          <h3 class="dash-emp-row-name"><?= h($emp->nombre) ?></h3>
          <div class="dash-emp-row-meta">
            <?php if (!empty($emp->rfc)): ?><span class="dash-emp-rfc"><?= h($emp->rfc) ?></span><?php endif; ?>
            <?php
              $eClass = match ($emp->estatus ?? 'activa') {
                  'suspendida' => 'badge-amber', 'baja' => 'badge-red', default => 'badge-green'
              };
            ?>
            <span class="badge <?= $eClass ?>"><?= h($emp->estatus ?? 'activa') ?></span>
            <?php if ($esActiva): ?><span class="dash-empresa-pill">Activa</span><?php endif; ?>
          </div>
        </div>
        <div class="dash-emp-row-actions" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
          <div class="dash-emp-row-pct">
            <span class="dash-emp-pct-val <?= h($pctCls) ?>"><?= $pct ?>%</span>
            <span class="dash-emp-pct-lbl">Cumplimiento<?= !empty($bloque['cumplimientoRequeridos']) ? ' · ' . (int)$bloque['cumplimientoOk'] . '/' . (int)$bloque['cumplimientoRequeridos'] : '' ?></span>
          </div>
          <a class="btn btn-primary btn-sm" href="<?= h($urlAgregar) ?>">+ Documento</a>
          <a class="btn btn-ghost btn-sm" href="<?= h($urlGestionar) ?>">Datos / CSD</a>
        </div>
      </header>

      <div class="dash-emp-stats" style="grid-template-columns:repeat(5,minmax(0,1fr))">
        <div class="dash-emp-stat">
          <span class="dash-emp-stat-val"><?= (int)$bloque['docTotal'] ?></span>
          <span class="dash-emp-stat-lbl">Documentos</span>
        </div>
        <div class="dash-emp-stat ok">
          <span class="dash-emp-stat-val"><?= (int)$bloque['docVigente'] ?></span>
          <span class="dash-emp-stat-lbl">Vigentes</span>
        </div>
        <div class="dash-emp-stat<?= (int)$bloque['docPorVencer'] > 0 ? ' warn' : '' ?>">
          <span class="dash-emp-stat-val"><?= (int)$bloque['docPorVencer'] ?></span>
          <span class="dash-emp-stat-lbl">Por vencer</span>
        </div>
        <div class="dash-emp-stat<?= (int)$bloque['docVencido'] > 0 ? ' bad' : '' ?>">
          <span class="dash-emp-stat-val"><?= (int)$bloque['docVencido'] ?></span>
          <span class="dash-emp-stat-lbl">Vencidos</span>
        </div>
        <div class="dash-emp-stat">
          <span class="dash-emp-stat-val" style="font-size:14px"><?= h((string)$bloque['permisoSictTxt']) ?></span>
          <span class="dash-emp-stat-lbl">Vence permiso SICT</span>
        </div>
      </div>

      <div class="emp-doc-block-body">
        <?php if (!empty($bloque['sinDocumentos'])): ?>
          <p class="empresa-doc-hint" style="padding:4px 0 12px;margin:0">
            Sin archivos cargados.
            <a href="<?= h($urlAgregar) ?>">Agregar permiso SICT, acta o constancia fiscal</a>
          </p>
        <?php else: ?>
          <div class="doc-grid" style="padding-bottom:8px">
            <?php foreach ($docs as $doc): ?>
            <div class="<?= $docCardClass($doc->estatus) ?>">
              <div class="doc-card-icon"><?= $docIcons[$doc->tipo_documento] ?? '📎' ?></div>
              <div class="doc-card-name"><?= h(DocumentosEmpresaTable::tipoLabel($doc->tipo_documento)) ?></div>
              <div class="doc-card-meta">
                <?php if ($doc->fecha_vencimiento):
                    $diasTxt = $diasRestantesTxt($doc->fecha_vencimiento, (string)$doc->estatus);
                ?>
                  Vence <?= h($doc->fecha_vencimiento->format('d/m/Y')) ?>
                  <?php if ($diasTxt !== null): ?>
                    <span class="doc-card-dias"> · <?= h($diasTxt) ?></span>
                  <?php endif; ?>
                <?php elseif ($doc->created_at): ?>
                  Subido <?= h($doc->created_at->format('d/m/Y')) ?>
                <?php else: ?>
                  Permanente
                <?php endif; ?>
              </div>
              <div class="doc-card-badge"><?= $estatusBadge($doc->estatus) ?></div>
              <div class="doc-card-actions">
                <a href="<?= $this->Url->build(['controller' => 'DocumentosEmpresa', 'action' => 'edit', $doc->id, '?' => ['return' => 'empresa', 'origen' => 'empresa']]) ?>"
                   class="btn btn-ghost btn-compact">Editar</a>
                <?php if (!empty($doc->archivo_url)): ?>
                <a href="<?= h($doc->archivo_url) ?>" target="_blank" rel="noopener"
                   class="btn btn-ghost btn-compact">Ver archivo</a>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
            <a class="doc-card doc-card-add" href="<?= h($urlAgregar) ?>">
              <div class="doc-card-icon">➕</div>
              <div class="doc-card-name doc-card-add-label">Nuevo documento</div>
              <div class="doc-card-meta">Clic para cargar</div>
            </a>
          </div>
        <?php endif; ?>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php
  return;
endif;

$tieneId      = (int)($empresa->id ?? 0) > 0;
$empresaNueva = $empresa->isNew();
$urlAgregarDoc = $tieneId
    ? $this->Url->build(['controller' => 'DocumentosEmpresa', 'action' => 'add',
        '?' => ['empresa_id' => $empresa->id, 'return' => 'empresa', 'origen' => 'empresa']])
    : null;

// Cert status
$certVigente = false;
$certDias    = null;
if (!$empresaNueva && !empty($empresa->fecha_fin_cert)) {
    $hoy       = new \DateTime('today');
    $finCert   = new \DateTime($empresa->fecha_fin_cert instanceof \DateTimeInterface
        ? $empresa->fecha_fin_cert->format('Y-m-d')
        : (string)$empresa->fecha_fin_cert);
    $certDias  = (int)$hoy->diff($finCert)->days * ($finCert >= $hoy ? 1 : -1);
    $certVigente = $finCert >= $hoy;
}

$docTotal = $docVigente = $docPorVencer = $docVencido = 0;
foreach ($documentos as $doc) {
    $docTotal++;
    match ($doc->estatus ?? '') {
        'vencido'    => $docVencido++,
        'por_vencer' => $docPorVencer++,
        default      => $docVigente++,
    };
}
$cumplimientoDetalle = $cumplimientoDetalle ?? null;
$cumplimientoPct = is_array($cumplimientoDetalle)
    ? (int)$cumplimientoDetalle['porcentaje']
    : 0;
$cumplimientoOk = is_array($cumplimientoDetalle) ? (int)$cumplimientoDetalle['ok'] : 0;
$cumplimientoReq = is_array($cumplimientoDetalle) ? (int)$cumplimientoDetalle['total'] : 0;
$cumplimientoFalt = is_array($cumplimientoDetalle) ? (int)$cumplimientoDetalle['faltantes'] : 0;

$permisoSictTxt = '—';
if (!$empresaNueva && !empty($empresa->vencimiento_permiso_sict)) {
    $vSict = $empresa->vencimiento_permiso_sict;
    $permisoSictTxt = $vSict instanceof \DateTimeInterface
        ? $vSict->format('d/m/Y')
        : (string)$vSict;
}
?>

<div class="page-header">
  <div class="page-header-left">
    <h1><?= $empresaNueva ? 'Registrar empresa' : 'Documentación empresa' ?></h1>
    <p><?= $empresaNueva
        ? 'Primero guarda los datos básicos; después podrás cargar el expediente documental.'
        : 'Datos, permiso SICT, CSD y expediente de ' . h((string)$empresa->nombre) . '.' ?></p>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index']) ?>">← Todas las empresas</a>
    <?php if (!$empresaNueva): ?>
      <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index', '?' => ['nueva' => '1']]) ?>">+ Nueva empresa</a>
    <?php endif; ?>
    <?php if ($urlAgregarDoc): ?>
    <a class="btn btn-primary" href="<?= $urlAgregarDoc ?>">+ Agregar documento</a>
    <a class="btn btn-ghost" href="<?= $this->Url->build(['controller' => 'Cumplimiento', 'action' => 'index', '?' => ['empresa_id' => $empresa->id]]) ?>">Ver cumplimiento</a>
    <?php endif; ?>
  </div>
</div>

<?php if (!$empresaNueva): ?>
<!-- ── HERO CARD ─────────────────────────────────────────── -->
<div class="emp-hero">
  <div class="emp-hero-avatar">
    <?= strtoupper(substr($empresa->nombre ?? 'E', 0, 2)) ?>
  </div>
  <div class="emp-hero-info">
    <div class="emp-hero-name"><?= h($empresa->nombre) ?></div>
    <div class="emp-hero-sub">
      <span class="tag"><?= h($empresa->rfc) ?></span>
      <?php if ($empresa->regimen_fiscal): ?>
        <span class="tag">Régimen <?= h($empresa->regimen_fiscal) ?></span>
      <?php endif; ?>
      <?php
        $eClass = match($empresa->estatus ?? 'activa') {
            'suspendida' => 'badge-amber', 'baja' => 'badge-red', default => 'badge-green'
        };
      ?>
      <span class="badge <?= $eClass ?>"><?= h($empresa->estatus) ?></span>
    </div>
  </div>
  <div class="emp-hero-stats">
    <div class="emp-stat">
      <div class="emp-stat-val"><?= $docTotal ?></div>
      <div class="emp-stat-label">Documentos</div>
    </div>
    <div class="emp-stat">
      <div class="emp-stat-val" style="color:var(--green)"><?= $docVigente ?></div>
      <div class="emp-stat-label">Vigentes</div>
    </div>
    <div class="emp-stat">
      <div class="emp-stat-val" style="color:<?= $docPorVencer > 0 ? '#C87000' : 'var(--text3)' ?>"><?= $docPorVencer ?></div>
      <div class="emp-stat-label">Por vencer</div>
    </div>
    <div class="emp-stat">
      <div class="emp-stat-val" style="color:<?= $docVencido > 0 ? 'var(--red)' : 'var(--text3)' ?>"><?= $docVencido ?></div>
      <div class="emp-stat-label">Vencidos</div>
    </div>
    <div class="emp-stat">
      <div class="emp-stat-val" style="color:var(--teal)"><?= $cumplimientoPct ?>%</div>
      <div class="emp-stat-label">Cumplimiento<?= $cumplimientoReq > 0 ? ' · ' . $cumplimientoOk . '/' . $cumplimientoReq : '' ?></div>
    </div>
    <div class="emp-stat">
      <div class="emp-stat-val" style="font-size:14px"><?= h($permisoSictTxt) ?></div>
      <div class="emp-stat-label">Vence permiso SICT</div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="empresa-page">

  <?php if ($tieneId): ?>
  <!-- ── DOCUMENTACIÓN (prioridad) ───────────────────────── -->
  <div class="panel emp-panel-doc-primary">
    <div class="panel-header">
      <div class="panel-title">Expediente documental</div>
      <?php if ($urlAgregarDoc): ?>
      <a class="btn btn-primary" style="font-size:11.5px;padding:5px 11px" href="<?= $urlAgregarDoc ?>">+ Agregar documento</a>
      <?php endif; ?>
    </div>
    <?php if ($sinDocumentosCorporativos): ?>
    <div class="empresa-doc-hint" style="padding:20px">
      Sin archivos cargados. Empieza con el <strong>permiso SICT</strong>, acta constitutiva y constancia fiscal.
    </div>
    <?php else: ?>
    <div class="doc-grid">
      <?php foreach ($documentos as $doc): ?>
      <div class="<?= $docCardClass($doc->estatus) ?>">
        <div class="doc-card-icon"><?= $docIcons[$doc->tipo_documento] ?? '📎' ?></div>
        <div class="doc-card-name"><?= h(DocumentosEmpresaTable::tipoLabel($doc->tipo_documento)) ?></div>
        <div class="doc-card-meta">
          <?php if ($doc->fecha_vencimiento):
              $diasTxt = $diasRestantesTxt($doc->fecha_vencimiento, (string)$doc->estatus);
          ?>
            Vence <?= h($doc->fecha_vencimiento->format('d/m/Y')) ?>
            <?php if ($diasTxt !== null): ?>
              <span class="doc-card-dias"> · <?= h($diasTxt) ?></span>
            <?php endif; ?>
          <?php elseif ($doc->created_at): ?>
            Subido <?= h($doc->created_at->format('d/m/Y')) ?>
          <?php else: ?>
            Permanente
          <?php endif; ?>
        </div>
        <div class="doc-card-badge"><?= $estatusBadge($doc->estatus) ?></div>
        <div class="doc-card-actions">
          <a href="<?= $this->Url->build(['controller' => 'DocumentosEmpresa', 'action' => 'edit', $doc->id, '?' => ['return' => 'empresa', 'origen' => 'empresa']]) ?>"
             class="btn btn-ghost btn-compact">Editar</a>
          <?php if (!empty($doc->archivo_url)): ?>
          <a href="<?= h($doc->archivo_url) ?>" target="_blank" rel="noopener"
             class="btn btn-ghost btn-compact">Ver archivo</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if ($urlAgregarDoc): ?>
      <a class="doc-card doc-card-add" href="<?= $urlAgregarDoc ?>">
        <div class="doc-card-icon">➕</div>
        <div class="doc-card-name doc-card-add-label">Nuevo documento</div>
        <div class="doc-card-meta">Clic para cargar</div>
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="panel emp-panel-doc-primary" style="margin-bottom:16px">
    <div class="panel-header"><div class="panel-title">Resumen de cumplimiento</div></div>
    <div class="empresa-panel-body">
      <?php if ($sinDocumentosCorporativos): ?>
        <div class="empresa-empty-hint">Registra documentos para ver el estado de cumplimiento.</div>
      <?php else: ?>
      <div class="cumpl-list">
        <?php foreach ($documentos as $doc):
            $pct       = $doc->estatus === 'vencido' ? 30 : ($doc->estatus === 'por_vencer' ? 75 : 100);
            $fillClass = $doc->estatus === 'vencido' ? 'fill-red' : ($doc->estatus === 'por_vencer' ? 'fill-amber' : 'fill-green');
            $pctColor  = $doc->estatus === 'vencido' ? 'color:var(--red)' : ($doc->estatus === 'por_vencer' ? 'color:#C87000' : 'color:var(--green)');
            $diasTxt = $doc->fecha_vencimiento
                ? $diasRestantesTxt($doc->fecha_vencimiento, (string)$doc->estatus)
                : null;
            if ($diasTxt !== null) {
                $cumplTxt = $diasTxt;
            } elseif ($doc->estatus === 'vigente') {
                $cumplTxt = '✓ vigente';
            } else {
                $cumplTxt = h((string)$doc->estatus);
            }
        ?>
        <div>
          <div class="cumpl-top">
            <span class="cumpl-name"><?= h(DocumentosEmpresaTable::tipoLabel($doc->tipo_documento)) ?></span>
            <span class="cumpl-pct" style="<?= $pctColor ?>"><?= h($cumplTxt) ?></span>
          </div>
          <div class="cumpl-bar"><div class="cumpl-fill <?= $fillClass ?>" style="width:<?= $pct ?>%"></div></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Datos (secundario, colapsable si ya existe empresa) ── -->
  <?php if ($tieneId): ?>
  <details class="emp-fold">
    <summary class="emp-fold-summary">
      <span>Datos de la empresa y permiso SICT</span>
      <span class="tag">Editar cuando lo necesites</span>
    </summary>
    <div class="emp-fold-body">
  <?php endif; ?>

  <div class="panel<?= $tieneId ? ' panel-secondary' : '' ?>">
    <div class="panel-header">
      <div class="panel-title"><?= $empresaNueva ? 'Datos de la empresa (primer paso)' : 'Datos generales y permiso SICT' ?></div>
    </div>

    <?= $this->Form->create($empresa, [
        'id' => 'empresa-form',
        'url' => ['action' => 'index'],
        'enctype' => 'multipart/form-data',
    ]) ?>

    <!-- Tabs -->
    <div class="emp-tabs">
      <?php if ($tieneId): ?>
      <button type="button" class="emp-tab active" data-tab="sict">Permiso SICT</button>
      <button type="button" class="emp-tab" data-tab="generales">Identificación</button>
      <button type="button" class="emp-tab" data-tab="domicilio">Domicilio</button>
      <button type="button" class="emp-tab" data-tab="cfdi">Extra: Carta Porte / CFDI</button>
      <?php else: ?>
      <button type="button" class="emp-tab active" data-tab="generales">Identificación</button>
      <button type="button" class="emp-tab" data-tab="domicilio">Domicilio</button>
      <button type="button" class="emp-tab" data-tab="sict">Permiso SICT</button>
      <button type="button" class="emp-tab" data-tab="cfdi">Extra: Carta Porte / CFDI</button>
      <?php endif; ?>
    </div>

    <div class="empresa-panel-body">

      <!-- TAB: Permiso SICT -->
      <div class="emp-tab-content<?= $tieneId ? ' active' : '' ?>" id="tab-sict">
        <div class="empresa-form-grid">
          <?= $this->Form->control('numero_permiso_sict',     ['label' => 'Número de permiso',    'required' => false]) ?>
          <div></div>
          <?= $this->Form->control('fecha_permiso_sict',      ['label' => 'Fecha de permiso',     'empty' => true, 'required' => false]) ?>
          <?= $this->Form->control('vencimiento_permiso_sict',['label' => 'Fecha de vencimiento', 'empty' => true, 'required' => false]) ?>
        </div>
        <?php if ($tieneId && $urlAgregarDoc): ?>
        <p style="font-size:12px;color:var(--text3);margin:12px 0 0">
          También puedes adjuntar el archivo del permiso en
          <a href="<?= $urlAgregarDoc ?>">Expediente documental → Agregar</a>.
        </p>
        <?php endif; ?>
      </div>

      <!-- TAB: Identificación -->
      <div class="emp-tab-content<?= $tieneId ? '' : ' active' ?>" id="tab-generales">
        <div class="empresa-form-grid">
          <?= $this->Form->control('nombre', [
              'label'     => 'Razón social',
              'maxlength' => 255,
          ]) ?>
          <?= $this->Form->control('rfc', [
              'label'     => 'RFC',
              'maxlength' => 13,
              'pattern'   => '^[A-Za-z&Ññ]{3,4}[0-9]{6}[A-Za-z0-9]{3}$',
              'title'     => '3-4 letras + 6 dígitos + 3 alfanuméricos. Ej: AAA010101AAA',
              'oninput'   => 'this.value=this.value.toUpperCase()',
          ]) ?>
          <?= $this->Form->control('telefono', [
              'label'       => 'Teléfono',
              'required'    => false,
              'type'        => 'tel',
              'placeholder' => '+52 81 1234-5678',
              'pattern'     => '^[\+]?[\d\s\(\)\-]{7,20}$',
              'title'       => 'Ej: +52 81 1234-5678',
          ]) ?>
          <?= $this->Form->control('email', [
              'label'       => 'Correo electrónico',
              'required'    => false,
              'type'        => 'email',
              'placeholder' => 'contacto@empresa.com',
              'maxlength'   => 150,
          ]) ?>
          <?= $this->Form->control('estatus', [
              'label'   => 'Estatus',
              'options' => ['activa' => 'Activa', 'suspendida' => 'Suspendida', 'baja' => 'Baja'],
          ]) ?>
          <div class="field-span-2">
            <?= $this->Form->control('transporte_materiales_peligrosos', [
                'label' => 'Transporta materiales / residuos peligrosos (activa requisitos MP en el % de cumplimiento)',
                'type' => 'checkbox',
                'checked' => !empty($empresa->transporte_materiales_peligrosos),
            ]) ?>
          </div>
        </div>
      </div>

      <!-- TAB: Domicilio -->
      <div class="emp-tab-content" id="tab-domicilio">
        <div class="empresa-form-grid">
          <div class="field-span-2">
            <?= $this->Form->control('domicilio', ['label' => 'Calle y número', 'required' => false]) ?>
          </div>
          <?= $this->Form->control('colonia',  ['label' => 'Colonia',   'required' => false, 'maxlength' => 100]) ?>
          <?= $this->Form->control('cp', [
              'label'       => 'C.P.',
              'required'    => false,
              'type'        => 'text',
              'inputmode'   => 'numeric',
              'pattern'     => '[0-9]{5}',
              'maxlength'   => 5,
              'placeholder' => '00000',
              'title'       => '5 dígitos. Ej: 64500',
              'oninput'     => 'this.value=this.value.replace(/\D/g,"")',
          ]) ?>
          <?= $this->Form->control('municipio', ['label' => 'Municipio', 'required' => false, 'maxlength' => 100]) ?>
          <?php
          // Catálogo SAT c_Estado (México) — guardamos el NOMBRE en `estado` para no cambiar BD
          $estadosSat = [
              ''   => '-- Selecciona estado (SAT) --',
              'Aguascalientes' => '01 - Aguascalientes',
              'Baja California' => '02 - Baja California',
              'Baja California Sur' => '03 - Baja California Sur',
              'Campeche' => '04 - Campeche',
              'Coahuila de Zaragoza' => '05 - Coahuila de Zaragoza',
              'Colima' => '06 - Colima',
              'Chiapas' => '07 - Chiapas',
              'Chihuahua' => '08 - Chihuahua',
              'Ciudad de México' => '09 - Ciudad de México',
              'Durango' => '10 - Durango',
              'Guanajuato' => '11 - Guanajuato',
              'Guerrero' => '12 - Guerrero',
              'Hidalgo' => '13 - Hidalgo',
              'Jalisco' => '14 - Jalisco',
              'México' => '15 - México',
              'Michoacán de Ocampo' => '16 - Michoacán de Ocampo',
              'Morelos' => '17 - Morelos',
              'Nayarit' => '18 - Nayarit',
              'Nuevo León' => '19 - Nuevo León',
              'Oaxaca' => '20 - Oaxaca',
              'Puebla' => '21 - Puebla',
              'Querétaro' => '22 - Querétaro',
              'Quintana Roo' => '23 - Quintana Roo',
              'San Luis Potosí' => '24 - San Luis Potosí',
              'Sinaloa' => '25 - Sinaloa',
              'Sonora' => '26 - Sonora',
              'Tabasco' => '27 - Tabasco',
              'Tamaulipas' => '28 - Tamaulipas',
              'Tlaxcala' => '29 - Tlaxcala',
              'Veracruz de Ignacio de la Llave' => '30 - Veracruz de Ignacio de la Llave',
              'Yucatán' => '31 - Yucatán',
              'Zacatecas' => '32 - Zacatecas',
          ];
          ?>
          <?= $this->Form->control('estado', [
              'label' => 'Estado',
              'type' => 'select',
              'required' => false,
              'options' => $estadosSat,
          ]) ?>
        </div>
      </div>

      <!-- TAB: Facturación CFDI -->
      <div class="emp-tab-content" id="tab-cfdi">
        <div class="emp-cfdi-section-title">Configuración de emisión</div>
        <div class="empresa-form-grid" style="margin-bottom:20px">
          <?= $this->Form->control('regimen_fiscal', [
              'label'   => 'Régimen fiscal',
              'type'    => 'select',
              'empty'   => '-- Selecciona régimen --',
              'required' => false,
              'options' => [
                  '601' => '601 - General de Ley Personas Morales',
                  '603' => '603 - Personas Morales con Fines no Lucrativos',
                  '605' => '605 - Sueldos y Salarios e Ingresos Asimilados a Salarios',
                  '606' => '606 - Arrendamiento',
                  '607' => '607 - Régimen de Enajenación o Adquisición de Bienes',
                  '608' => '608 - Demás ingresos',
                  '609' => '609 - Consolidación',
                  '610' => '610 - Residentes en el Extranjero sin Establecimiento Permanente en México',
                  '611' => '611 - Ingresos por Dividendos (socios y accionistas)',
                  '612' => '612 - Personas Físicas con Actividades Empresariales y Profesionales',
                  '614' => '614 - Ingresos por intereses',
                  '615' => '615 - Régimen de los ingresos por obtención de premios',
                  '616' => '616 - Sin obligaciones fiscales',
                  '620' => '620 - Sociedades Cooperativas de Producción que optan por diferir sus ingresos',
                  '621' => '621 - Incorporación Fiscal',
                  '622' => '622 - Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
                  '623' => '623 - Opcional para Grupos de Sociedades',
                  '624' => '624 - Coordinados',
                  '625' => '625 - Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas',
                  '626' => '626 - Régimen Simplificado de Confianza',
                  '628' => '628 - Hidrocarburos',
                  '629' => '629 - De los Regímenes Fiscales Preferentes y de las Empresas Multinacionales',
                  '630' => '630 - Enajenación de acciones en bolsa de valores',
              ],
          ]) ?>
          <?= $this->Form->control('lugar_expedicion',  ['label' => 'Lugar de expedición (C.P.)', 'required' => false, 'placeholder' => 'Ej. 42501']) ?>
          <?= $this->Form->control('serie_cfdi',        ['label' => 'Serie',          'required' => false, 'placeholder' => 'Ej. A']) ?>
          <?= $this->Form->control('folio_cfdi',        ['label' => 'Folio actual',   'required' => false]) ?>
          <?= $this->Form->control('folio_inicial',     ['label' => 'Folio inicial',  'required' => false]) ?>
          <?= $this->Form->control('folios_disponibles',['label' => 'Folios disponibles', 'required' => false]) ?>
        </div>
      </div>

      <div class="empresa-form-actions">
        <button class="btn btn-primary" type="submit">
          <?= $empresaNueva ? 'Guardar empresa' : 'Guardar cambios' ?>
        </button>
        <?php if (!$empresaNueva): ?>
        <span style="font-size:11.5px;color:var(--text3)">Los cambios aplican a todas las secciones.</span>
        <?php endif; ?>
      </div>
    </div>
    <?= $this->Form->end() ?>
  </div>

  <?php if ($tieneId): ?>
    </div>
  </details>

  <details class="emp-fold emp-fold-extra">
    <summary class="emp-fold-summary">
      <span>Extra: timbrado y Carta Porte (CFDI)</span>
      <span class="tag">Opcional</span>
    </summary>
    <div class="emp-fold-body">
  <?php endif; ?>

  <!-- ── CSD (solo para timbrado / Carta Porte) ───────────── -->
  <div class="panel panel-secondary">
    <div class="panel-header">
      <div class="panel-title">Certificado de sello digital (CSD)</div>
      <span class="tag" style="font-size:10px">Solo facturación</span>
    </div>

    <?php if (!$tieneId): ?>
      <div class="empresa-empty-hint">
        Primero guarda los <strong>datos de la empresa</strong> para poder cargar el CSD.
      </div>
    <?php else: ?>
      <div class="empresa-panel-body">

        <?php if (!empty($empresa->numero_certificado)): ?>
          <div class="csd-info-grid">
            <div class="csd-info-item">
              <div class="data-label">No. Serie</div>
              <div class="data-value td-mono"><?= h($empresa->numero_certificado) ?></div>
            </div>
            <div class="csd-info-item">
              <div class="data-label">Vigencia inicio</div>
              <div class="data-value"><?= h($empresa->fecha_inicio_cert) ?></div>
            </div>
            <div class="csd-info-item">
              <div class="data-label">Vigencia fin</div>
              <div class="data-value"><?= h($empresa->fecha_fin_cert) ?></div>
            </div>
            <div class="csd-info-item">
              <div class="data-label">Archivos</div>
              <div class="data-value" style="font-size:11.5px;color:var(--text2)">
                <?= !empty($empresa->pem_cer) ? '✓ .cer.pem&nbsp;&nbsp;' : '✗ .cer.pem&nbsp;&nbsp;' ?>
                <?= !empty($empresa->pem_key) ? '✓ .key.pem' : '✗ .key.pem' ?>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <div class="emp-cfdi-section-title" style="margin-top:6px">
          <?= !empty($empresa->numero_certificado) ? 'Reemplazar CSD' : 'Cargar CSD' ?>
        </div>

        <?= $this->Form->create(null, [
            'url' => ['action' => 'subir'],
            'type' => 'file',
            'class' => 'csd-upload-form',
        ]) ?>
          <div class="empresa-form-grid">
            <?= $this->Form->control('certificado', [
                'type' => 'file',
                'label' => 'Archivo .cer',
                'required' => true,
                'accept' => '.cer',
            ]) ?>
            <?= $this->Form->control('archivo_key', [
                'type' => 'file',
                'label' => 'Archivo .key',
                'required' => true,
                'accept' => '.key',
            ]) ?>
            <?= $this->Form->control('contrasena', [
                'type' => 'password',
                'label' => 'Contraseña de la llave privada',
                'required' => true,
                'autocomplete' => 'off',
                'placeholder' => 'Contraseña del archivo .key',
            ]) ?>
          </div>
          <div style="margin-top:14px">
            <button type="submit" class="btn btn-teal">Validar y cargar CSD</button>
          </div>
        <?= $this->Form->end() ?>

      </div>
    <?php endif; ?>
  </div>

  <?php if ($tieneId): ?>
  <div class="panel panel-extra-cp">
    <div class="panel-header">
      <div class="panel-title">Extra: Carta Porte y facturación CFDI</div>
      <span class="tag" style="font-size:10px">Opcional</span>
    </div>
    <div class="empresa-panel-body">
      <p style="font-size:12.5px;color:var(--text3);margin:0 0 14px;line-height:1.45">
        Emisión de complementos de transporte y CFDI. No sustituye el control de documentos de empresa y vehículos.
      </p>
      <?php if ($certDias !== null): ?>
      <div class="emp-cert-banner <?= !$certVigente ? 'emp-cert-expired' : ($certDias <= 30 ? 'emp-cert-warn' : 'emp-cert-ok') ?>">
        <div class="emp-cert-icon"><?= !$certVigente ? '⚠️' : ($certDias <= 30 ? '⏳' : '🔒') ?></div>
        <div>
          <div class="emp-cert-title">
            <?= !$certVigente ? 'Certificado vencido' : ($certDias <= 30 ? 'Certificado por vencer' : 'Certificado vigente') ?>
          </div>
          <div class="emp-cert-sub">
            <?= $certVigente ? "Vence en $certDias días" : 'Actualiza tus sellos digitales' ?>
            — <?= h($empresa->numero_certificado ?? 'sin número') ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
      <div class="facturacion-links" style="margin-top:<?= $certDias !== null ? '14px' : '0' ?>">
        <a class="btn btn-ghost" href="<?= $this->Url->build(['controller' => 'Comprobantes', 'action' => 'generar']) ?>">
          Generar Carta Porte
        </a>
        <a class="btn btn-ghost" href="<?= $this->Url->build(['controller' => 'Comprobantes', 'action' => 'index']) ?>">
          Comprobantes emitidos
        </a>
        <a class="btn btn-ghost" href="<?= $this->Url->build(['controller' => 'Clientes', 'action' => 'index']) ?>">
          Clientes
        </a>
        <a class="btn btn-ghost" href="<?= $this->Url->build(['controller' => 'Servicios', 'action' => 'index']) ?>">
          Productos y servicios
        </a>
      </div>
    </div>
  </div>
    </div>
  </details>
  <?php endif; ?>

</div><!-- /empresa-page -->

<style>
.emp-panel-doc-primary{border-color:rgba(27,58,140,.22);box-shadow:0 0 0 1px rgba(43,191,179,.08)}
.emp-panel-doc-primary>.panel-header{background:linear-gradient(180deg,rgba(43,191,179,.06),transparent)}
.panel-secondary{opacity:.98}
.panel-secondary>.panel-header .panel-title{color:var(--text2);font-size:13px}
.emp-fold{border:1px solid var(--border);border-radius:var(--radius);margin-bottom:14px;background:var(--surface);overflow:hidden}
.emp-fold-extra{border-style:dashed;background:var(--bg)}
.emp-fold-summary{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 16px;cursor:pointer;font-size:13px;font-weight:600;color:var(--text2);list-style:none;user-select:none}
.emp-fold-summary::-webkit-details-marker{display:none}
.emp-fold[open] .emp-fold-summary{border-bottom:1px solid var(--border);color:var(--navy)}
.emp-fold-body{padding:0 0 4px}
.emp-fold-body .panel{border:none;border-radius:0;box-shadow:none;margin:0}
.emp-fold-body .panel + .panel{border-top:1px solid var(--border)}
.panel-extra-cp{border-style:dashed;background:var(--bg);margin-bottom:0}
.panel-extra-cp .panel-header{background:transparent}
/* ── HERO ── */
.emp-hero{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px 24px;margin-bottom:20px;display:flex;align-items:center;gap:18px;box-shadow:var(--shadow)}
.emp-hero-avatar{width:52px;height:52px;border-radius:12px;background:var(--navy);color:#fff;font-family:'Syne',sans-serif;font-weight:700;font-size:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0;letter-spacing:-1px}
.emp-hero-info{flex:1;min-width:0}
.emp-hero-name{font-family:'Syne',sans-serif;font-weight:700;font-size:18px;letter-spacing:-.4px;margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.emp-hero-sub{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
.emp-hero-stats{display:flex;gap:0;border-left:1px solid var(--border);padding-left:20px;flex-shrink:0}
.emp-stat{padding:0 18px;border-right:1px solid var(--border);text-align:center}
.emp-stat:last-child{border-right:none;padding-right:0}
.emp-stat-val{font-family:'Syne',sans-serif;font-weight:700;font-size:20px;letter-spacing:-.5px;line-height:1.1;margin-bottom:3px;color:var(--text)}
.emp-stat-label{font-size:10px;color:var(--text3);font-family:'DM Mono',monospace;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap}

/* ── TABS ── */
.emp-tabs{display:flex;border-bottom:1px solid var(--border);padding:0 20px;gap:0;background:var(--bg)}
.emp-tab{background:none;border:none;border-bottom:2px solid transparent;padding:11px 16px;font-size:12.5px;font-weight:500;color:var(--text3);cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .15s;white-space:nowrap;margin-bottom:-1px}
.emp-tab:hover{color:var(--text)}
.emp-tab.active{color:var(--navy);border-bottom-color:var(--navy);font-weight:600}
.emp-tab-content{display:none}
.emp-tab-content.active{display:block}

/* ── CFDI sections ── */
.emp-cfdi-section-title{font-size:10px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.7px;font-family:'DM Mono',monospace;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid var(--surface2)}
.emp-cfdi-section-title + .empresa-form-grid{margin-bottom:24px}

/* ── CERT BANNER ── */
.emp-cert-banner{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:8px;border:1px solid}
.emp-cert-ok{background:var(--green-bg);border-color:rgba(21,122,82,.2);color:var(--green)}
.emp-cert-warn{background:var(--amber-bg);border-color:rgba(138,96,0,.2);color:var(--amber)}
.emp-cert-expired{background:var(--red-bg);border-color:rgba(176,30,30,.2);color:var(--red)}
.emp-cert-icon{font-size:20px;flex-shrink:0}
.emp-cert-title{font-weight:600;font-size:13px;margin-bottom:2px}
.emp-cert-sub{font-size:11.5px;opacity:.85;font-family:'DM Mono',monospace}
.emp-cert-file-ok{font-size:10px;font-weight:600;color:var(--green);margin-left:6px;font-family:'DM Sans',sans-serif;text-transform:none;letter-spacing:0}
.emp-cert-file-current{font-family:'DM Mono',monospace;font-size:11px;color:var(--text2);background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:5px 9px;margin-bottom:6px}
.emp-cert-file-hint{font-size:10.5px;color:var(--text3);margin-top:5px}
.csd-info-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:14px 16px;margin-bottom:4px}
.csd-info-item{}
.csd-upload-form{margin-top:4px}
@media(max-width:900px){.csd-info-grid{grid-template-columns:repeat(2,1fr)}}
input[type="file"]{width:100%;padding:7px 10px;border:1.5px dashed var(--border2);border-radius:8px;background:var(--bg);color:var(--text2);font-size:12.5px;font-family:'DM Sans',sans-serif;cursor:pointer}
input[type="file"]:hover{border-color:var(--teal);background:var(--teal-light)}
</style>

<script>
/* ── TABS ── */
document.querySelectorAll('.emp-tab').forEach(function(tab) {
  tab.addEventListener('click', function() {
    document.querySelectorAll('.emp-tab').forEach(function(t) { t.classList.remove('active'); });
    document.querySelectorAll('.emp-tab-content').forEach(function(c) { c.classList.remove('active'); });
    tab.classList.add('active');
    document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
  });
});

/* ── VALIDACIÓN JS ── */
(function () {

  // Reglas: [selector, función validadora, mensaje de error]
  var rules = [
    {
      sel: '#nombre',
      ok: function(v) { return v.trim().length >= 2; },
      msg: 'La razón social es obligatoria (mínimo 2 caracteres).'
    },
    {
      sel: '#rfc',
      ok: function(v) {
        return v === '' || /^[A-Za-z&Ññ]{3,4}[0-9]{6}[A-Za-z0-9]{3}$/i.test(v.trim());
      },
      msg: 'RFC inválido. Formato: 3-4 letras + 6 dígitos + 3 alfanuméricos. Ej: AAA010101AAA'
    },
    {
      sel: '#telefono',
      ok: function(v) {
        return v === '' || /^[\+]?[\d\s\(\)\-]{7,20}$/.test(v.trim());
      },
      msg: 'Teléfono inválido. Ej: +52 81 1234-5678'
    },
    {
      sel: '#email',
      ok: function(v) {
        return v === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim());
      },
      msg: 'Correo electrónico inválido.'
    },
    {
      sel: '#cp',
      ok: function(v) { return v === '' || /^[0-9]{5}$/.test(v.trim()); },
      msg: 'El C.P. debe tener exactamente 5 dígitos.'
    },
    {
      sel: '#lugar-expedicion, #lugar_expedicion',
      ok: function(v) { return v === '' || /^[0-9]{5}$/.test(v.trim()); },
      msg: 'El lugar de expedición debe ser un C.P. de 5 dígitos.'
    },
  ];

  function getInput(sel) {
    return document.querySelector(sel);
  }

  function getWrap(input) {
    return input.closest('.input') || input.parentElement;
  }

  function showError(input, msg) {
    var wrap = getWrap(input);
    wrap.classList.add('error');
    var existing = wrap.querySelector('.js-error');
    if (!existing) {
      existing = document.createElement('div');
      existing.className = 'error-message js-error';
      wrap.appendChild(existing);
    }
    existing.textContent = msg;
  }

  function clearError(input) {
    var wrap = getWrap(input);
    wrap.classList.remove('error');
    var existing = wrap.querySelector('.js-error');
    if (existing) existing.remove();
  }

  function validate(rule) {
    // Soporta selector múltiple separado por coma
    var sels = rule.sel.split(',');
    var input = null;
    for (var i = 0; i < sels.length; i++) {
      input = getInput(sels[i].trim());
      if (input) break;
    }
    if (!input) return true;
    var ok = rule.ok(input.value);
    if (!ok) showError(input, rule.msg);
    else      clearError(input);
    return ok;
  }

  // Validación en tiempo real al salir del campo (blur)
  rules.forEach(function(rule) {
    var sels = rule.sel.split(',');
    sels.forEach(function(sel) {
      var input = getInput(sel.trim());
      if (!input) return;
      input.addEventListener('blur', function() { validate(rule); });
      input.addEventListener('input', function() {
        if (getWrap(input).classList.contains('error')) validate(rule);
      });
    });
  });

  // Validación completa al intentar guardar
  var form = document.getElementById('empresa-form');
  if (form) {
    form.addEventListener('submit', function(e) {
      var allOk = true;
      var firstBad = null;

      rules.forEach(function(rule) {
        var ok = validate(rule);
        if (!ok) {
          allOk = false;
          if (!firstBad) {
            var sels = rule.sel.split(',');
            for (var i = 0; i < sels.length; i++) {
              var inp = getInput(sels[i].trim());
              if (inp) { firstBad = inp; break; }
            }
          }
        }
      });

      if (!allOk) {
        e.preventDefault();
        // Activar el tab donde está el primer error
        if (firstBad) {
          var tabContent = firstBad.closest('.emp-tab-content');
          if (tabContent) {
            var tabId = tabContent.id.replace('tab-', '');
            document.querySelectorAll('.emp-tab').forEach(function(t) {
              t.classList.toggle('active', t.dataset.tab === tabId);
            });
            document.querySelectorAll('.emp-tab-content').forEach(function(c) {
              c.classList.toggle('active', c.id === tabContent.id);
            });
          }
          firstBad.focus();
          firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }
    });
  }

})();
</script>
