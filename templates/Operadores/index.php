<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\Paging\PaginatedInterface<\App\Model\Entity\Operador> $operadores
 * @var array $operadoresData
 * @var array $tiposReq
 * @var string $estatus
 * @var string $q
 * @var array<int, string> $empresasFiltro
 * @var int|null $filtroEmpresaId
 * @var bool $vistaConsolidada
 * @var string $alcanceLabel
 */

use App\Model\Entity\DocumentoOperador;
use App\Model\Table\DocumentosOperadorTable;

$this->Paginator->setPaginated($operadores);

$tiposIndice = DocumentosOperadorTable::tiposFormularioOperador();
$etiquetasCortas = [
    'ine' => 'INE',
    'licencia' => 'Licencia',
    'curso_capacitacion' => 'Capacitación',
    'comprobante_domicilio' => 'Domicilio',
];

$estatusLabel = fn(string $s): string => match ($s) {
    'vencido' => 'Doc. vencido',
    'por_vencer' => 'Por vencer',
    'faltante' => 'Incompleto',
    default => 'Expediente al día',
};

$estatusCardClass = fn(string $s): string => match ($s) {
    'vencido' => 'oper-card--crit',
    'por_vencer' => 'oper-card--warn',
    'faltante' => 'oper-card--muted',
    default => 'oper-card--ok',
};

$estatusBadge = fn(string $s): string => match ($s) {
    'vencido' => 'badge-red',
    'por_vencer' => 'badge-amber',
    'faltante' => 'badge-gray',
    default => 'badge-green',
};

$docPill = function (string $tipo, DocumentoOperador|string|null $doc, bool $fotoOk = false): array {
    if ($tipo === 'foto') {
        return [
            $fotoOk ? 'oper-pill--ok' : 'oper-pill--no',
            $fotoOk ? 'Cargada' : 'Pendiente',
            null,
        ];
    }
    if ($doc === null) {
        return ['oper-pill--no', 'Pendiente', null];
    }
    if (is_string($doc)) {
        return match ($doc) {
            'vencido' => ['oper-pill--bad', 'Vencido', null],
            'por_vencer' => ['oper-pill--warn', 'Por vencer', null],
            default => ['oper-pill--ok', 'Vigente', null],
        };
    }
    $est = (string)($doc->estatus ?? '');
    $fecha = $doc->fecha_vencimiento ? $doc->fecha_vencimiento->format('d/m/y') : null;
    $pillClass = match ($est) {
        'vencido' => 'oper-pill--bad',
        'por_vencer' => 'oper-pill--warn',
        default => 'oper-pill--ok',
    };
    $txt = match ($est) {
        'vencido' => 'Vencido',
        'por_vencer' => 'Por vencer',
        default => 'Vigente',
    };

    return [$pillClass, $txt, $fecha];
};

$operStats = ['completo' => 0, 'por_vencer' => 0, 'vencido' => 0, 'faltante' => 0];
foreach ($operadoresData as $row) {
    match ($row['estatus']) {
        'vencido' => $operStats['vencido']++,
        'por_vencer' => $operStats['por_vencer']++,
        'faltante' => $operStats['faltante']++,
        default => $operStats['completo']++,
    };
}

$avatarColors = ['', 'avatar-teal', '', 'avatar-teal', ''];
$urlIndex = ['controller' => 'Operadores', 'action' => 'index'];
?>

<div class="oper-page">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Operadores</h1>
      <p>
        <?= (int)$this->Paginator->counter('{{count}}') ?> en el listado · expediente documental por persona
        <?php if (!empty($alcanceLabel)): ?> · <?= h($alcanceLabel) ?><?php endif; ?>
      </p>
    </div>
    <a class="btn btn-primary" href="<?= $this->Url->build(['controller' => 'Operadores', 'action' => 'add']) ?>">
      <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
      Registrar operador
    </a>
  </div>

<?= $this->element('filtro_empresa', [
    'empresasFiltro' => $empresasFiltro ?? [],
    'filtroEmpresaId' => $filtroEmpresaId ?? null,
    'vistaConsolidada' => $vistaConsolidada ?? false,
    'alcanceLabel' => $alcanceLabel ?? '',
    'filtroPreserve' => [
        'q' => $q ?? '',
        'estatus' => $estatus ?? 'activo',
    ],
]) ?>

  <?php if (!empty($operadoresData)): ?>
  <div class="oper-kpi-grid">
    <div class="oper-kpi">
      <div class="oper-kpi-val"><?= (int)$this->Paginator->counter('{{count}}') ?></div>
      <div class="oper-kpi-label">En esta vista</div>
    </div>
    <div class="oper-kpi oper-kpi--ok">
      <div class="oper-kpi-val"><?= (int)$operStats['completo'] ?></div>
      <div class="oper-kpi-label">Expediente al día</div>
    </div>
    <div class="oper-kpi oper-kpi--warn">
      <div class="oper-kpi-val"><?= (int)$operStats['por_vencer'] ?></div>
      <div class="oper-kpi-label">Por vencer</div>
    </div>
    <div class="oper-kpi oper-kpi--crit">
      <div class="oper-kpi-val"><?= (int)$operStats['vencido'] ?></div>
      <div class="oper-kpi-label">Con vencidos</div>
    </div>
    <div class="oper-kpi oper-kpi--muted">
      <div class="oper-kpi-val"><?= (int)$operStats['faltante'] ?></div>
      <div class="oper-kpi-label">Incompletos</div>
    </div>
  </div>
  <?php endif; ?>

  <form class="oper-filters panel" method="get" action="<?= $this->Url->build($urlIndex) ?>">
    <?php if (!empty($filtroEmpresaId)): ?>
      <input type="hidden" name="empresa_id" value="<?= (int)$filtroEmpresaId ?>">
    <?php endif; ?>
    <div class="oper-filters-row">
      <div class="search-wrap oper-search">
        <svg class="search-icon" width="13" height="13" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
        <input type="search" name="q" value="<?= h($q ?? '') ?>" placeholder="Nombre, CURP, RFC, licencia, teléfono…" autocomplete="off">
      </div>
      <div class="oper-filter-tabs" role="group" aria-label="Estatus del operador">
        <?php
          $mk = fn(string $value) => $this->Url->build([
              'controller' => 'Operadores',
              'action' => 'index',
              '?' => array_filter([
                  'estatus' => $value,
                  'q' => $q ?? '',
                  'empresa_id' => $filtroEmpresaId ?? null,
              ], static fn($v) => $v !== null && $v !== ''),
          ]);
          $tabCls = fn(string $value) => 'oper-filter-tab' . (($estatus ?? 'activo') === $value ? ' oper-filter-tab--active' : '');
        ?>
        <a class="<?= $tabCls('activo') ?>" href="<?= $mk('activo') ?>">Activos</a>
        <a class="<?= $tabCls('inactivo') ?>" href="<?= $mk('inactivo') ?>">Inactivos</a>
        <a class="<?= $tabCls('suspendido') ?>" href="<?= $mk('suspendido') ?>">Suspendidos</a>
        <a class="<?= $tabCls('baja') ?>" href="<?= $mk('baja') ?>">Bajas</a>
        <a class="<?= $tabCls('todos') ?>" href="<?= $mk('todos') ?>">Todos</a>
      </div>
      <?php if (!empty($q)): ?>
        <a class="btn btn-ghost btn-sm" href="<?= $this->Url->build(['controller' => 'Operadores', 'action' => 'index', '?' => array_filter(['estatus' => $estatus ?? 'activo', 'empresa_id' => $filtroEmpresaId ?? null])]) ?>">Limpiar búsqueda</a>
      <?php endif; ?>
    </div>
  </form>

  <?php if (empty($operadoresData)): ?>
  <div class="panel oper-empty">
    <div class="oper-empty-icon" aria-hidden="true">👤</div>
    <h2>No hay operadores que coincidan</h2>
    <p>Registra un operador o ajusta la búsqueda y el filtro de estatus.</p>
    <a class="btn btn-primary" href="<?= $this->Url->build(['controller' => 'Operadores', 'action' => 'add']) ?>">Registrar operador</a>
  </div>
  <?php else:
    $porEmpresa = [];
    foreach ($operadoresData as $data) {
        $opRow = $data['operador'];
        $eid = (int)($opRow->empresa_id ?? 0);
        if (!isset($porEmpresa[$eid])) {
            $porEmpresa[$eid] = [
                'nombre' => (string)($opRow->empresa->nombre ?? ('Empresa #' . $eid)),
                'items' => [],
            ];
        }
        $porEmpresa[$eid]['items'][] = $data;
    }
    uasort(
        $porEmpresa,
        static fn(array $a, array $b): int => strcasecmp($a['nombre'], $b['nombre']),
    );
    $mostrarGrupo = count($porEmpresa) > 1 || !empty($vistaConsolidada);
    $cardIndex = 0;
  ?>

    <?php foreach ($porEmpresa as $grupo): ?>
      <?php if ($mostrarGrupo): ?>
      <div class="panel" style="margin-bottom:14px">
        <div class="panel-header">
          <div class="panel-title"><?= h($grupo['nombre']) ?></div>
          <div style="color:var(--text3);font-size:13px">
            <?= count($grupo['items']) ?> operador<?= count($grupo['items']) === 1 ? '' : 'es' ?>
          </div>
        </div>
        <div style="padding:14px 16px">
      <?php endif; ?>
          <div class="oper-grid">
            <?php foreach ($grupo['items'] as $data):
                $op = $data['operador'];
                $docs = $data['docsPorTipo'];
                $worst = $data['estatus'];
                $faltantes = $data['faltantes'];
                $editUrl = $this->Url->build(['controller' => 'Operadores', 'action' => 'edit', $op->id]);
                $i = $cardIndex++;

                $expTotal = 1 + count($tiposIndice);
                $expOk = !empty($op->foto_url) ? 1 : 0;
                foreach ($tiposIndice as $tipo) {
                    if (isset($docs[$tipo])) {
                        $expOk++;
                    }
                }
                $expPct = $expTotal > 0 ? (int)round(100 * $expOk / $expTotal) : 0;
                $fillClass = $expPct < 60 ? 'fill-red' : ($expPct < 100 ? 'fill-amber' : 'fill-teal');

                $docLic = $docs['licencia'] ?? null;
                $licenciaFecha = ($docLic instanceof DocumentoOperador && $docLic->fecha_vencimiento)
                    ? $docLic->fecha_vencimiento->format('d/m/Y')
                    : null;
                $licenciaEstatus = ($docLic instanceof DocumentoOperador) ? (string)($docLic->estatus ?? '') : null;
            ?>
            <article class="oper-card <?= $estatusCardClass($worst) ?>">
              <a href="<?= $editUrl ?>" class="oper-card-main">
                <div class="oper-card-head">
                  <div class="oper-card-identity">
                    <?php if (!empty($op->foto_url)): ?>
                      <div class="oper-avatar oper-avatar--foto">
                        <img src="<?= h($this->Url->build($op->foto_url)) ?>" alt="">
                      </div>
                    <?php else: ?>
                      <div class="oper-avatar <?= $avatarColors[$i % 5] ?>"><?= h($op->iniciales) ?></div>
                    <?php endif; ?>
                    <div class="oper-card-id-text">
                      <h2 class="oper-card-name"><?= h($op->nombre_completo) ?></h2>
                      <p class="oper-card-meta">
                        <?php if (!empty($op->curp)): ?>
                          <span class="oper-card-meta-item" title="CURP"><?= h($op->curp) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($op->rfc)): ?>
                          <span class="oper-card-meta-item" title="RFC"><?= h($op->rfc) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($op->telefono)): ?>
                          <?php
                            $telDigits = preg_replace('/\D/', '', (string)$op->telefono);
                            $telMostrar = strlen($telDigits) === 10
                              ? substr($telDigits, 0, 2) . ' ' . substr($telDigits, 2, 4) . ' ' . substr($telDigits, 6, 4)
                              : (string)$op->telefono;
                          ?>
                          <span class="oper-card-meta-item" title="Teléfono"><?= h($telMostrar) ?></span>
                        <?php endif; ?>
                        <?php if (empty($op->curp) && empty($op->rfc) && empty($op->telefono)): ?>
                          <span class="oper-card-meta-item oper-card-meta-item--muted">Sin CURP / RFC</span>
                        <?php endif; ?>
                      </p>
                    </div>
                  </div>
                  <span class="badge <?= $estatusBadge($worst) ?>"><?= h($estatusLabel($worst)) ?></span>
                </div>

                <?php if (($op->estatus ?? 'activo') !== 'activo'): ?>
                  <span class="oper-card-inactivo">Operador <?= h(match ($op->estatus ?? '') {
                      'inactivo' => 'inactivo',
                      'suspendido' => 'suspendido',
                      'baja' => 'de baja',
                      default => (string)($op->estatus ?? 'inactivo'),
                  }) ?></span>
                <?php endif; ?>

                <?php if (!empty($op->num_licencia) || $docLic instanceof DocumentoOperador): ?>
                <div class="oper-card-licencia<?= $licenciaEstatus === 'vencido' ? ' oper-card-licencia--bad' : ($licenciaEstatus === 'por_vencer' ? ' oper-card-licencia--warn' : '') ?>">
                  <div class="oper-card-licencia-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 9h4M7 13h10"/></svg>
                  </div>
                  <div class="oper-card-licencia-body">
                    <span class="oper-card-licencia-title">Licencia federal</span>
                    <?php if (!empty($op->num_licencia)): ?>
                      <span class="oper-card-licencia-num"><?= h($op->num_licencia) ?></span>
                    <?php endif; ?>
                    <?php if ($licenciaFecha): ?>
                      <span class="oper-card-licencia-vence">Vence <strong><?= h($licenciaFecha) ?></strong></span>
                    <?php elseif ($docLic instanceof DocumentoOperador): ?>
                      <span class="oper-card-licencia-vence oper-card-licencia-vence--empty">Sin fecha de vencimiento</span>
                    <?php else: ?>
                      <span class="oper-card-licencia-vence oper-card-licencia-vence--empty">Sin licencia cargada</span>
                    <?php endif; ?>
                  </div>
                  <?php if ($licenciaEstatus): ?>
                    <span class="oper-card-licencia-est <?= 'oper-card-licencia-est--' . h($licenciaEstatus) ?>"><?= h(match ($licenciaEstatus) {
                        'vencido' => 'Vencida',
                        'por_vencer' => 'Por vencer',
                        default => 'Vigente',
                    }) ?></span>
                  <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="oper-card-expediente">
                  <div class="oper-card-exp-top">
                    <span>Expediente</span>
                    <span class="oper-card-exp-pct"><?= $expPct ?>%</span>
                  </div>
                  <div class="cumpl-bar"><div class="cumpl-fill <?= $fillClass ?>" style="width:<?= $expPct ?>%"></div></div>
                  <div class="oper-pills" aria-label="Documentos del expediente">
                    <?php
                      [$pillCls, $pillTxt, $pillFecha] = $docPill('foto', null, !empty($op->foto_url));
                    ?>
                    <span class="oper-pill <?= h($pillCls) ?>" title="Foto del operador">
                      <span class="oper-pill-dot"></span>
                      <span class="oper-pill-label">Foto</span>
                      <span class="oper-pill-sub"><?= h($pillTxt) ?></span>
                    </span>
                    <?php foreach ($tiposIndice as $tipo):
                        [$pillCls, $pillTxt, $pillFecha] = $docPill($tipo, $docs[$tipo] ?? null);
                        $title = DocumentosOperadorTable::tipoLabel($tipo);
                        if ($pillFecha) {
                            $title .= ' · vence ' . $pillFecha;
                        }
                    ?>
                    <span class="oper-pill <?= h($pillCls) ?>" title="<?= h($title) ?>">
                      <span class="oper-pill-dot"></span>
                      <span class="oper-pill-label"><?= h($etiquetasCortas[$tipo] ?? $tipo) ?></span>
                      <span class="oper-pill-sub"><?= $pillFecha ? h($pillFecha) : h($pillTxt) ?></span>
                    </span>
                    <?php endforeach; ?>
                  </div>
                </div>

                <div class="oper-card-link">Editar expediente →</div>
              </a>

              <footer class="oper-card-foot">
                <?php if ($worst === 'faltante' && $faltantes > 0): ?>
                  <span class="oper-card-foot-hint"><?= (int)$faltantes ?> documento<?= $faltantes > 1 ? 's' : '' ?> pendiente<?= $faltantes > 1 ? 's' : '' ?></span>
                <?php elseif ($worst === 'vencido'): ?>
                  <span class="oper-card-foot-hint oper-card-foot-hint--bad">Revisar vencimientos</span>
                <?php else: ?>
                  <span class="oper-card-foot-hint"></span>
                <?php endif; ?>
                <?php if (in_array($op->estatus ?? 'activo', ['activo', 'suspendido'], true)): ?>
                  <?= $this->Form->postLink(
                      'Dar de baja',
                      ['controller' => 'Operadores', 'action' => 'delete', $op->id],
                      [
                          'confirm' => '¿Dar de baja (inactivar) este operador?',
                          'class' => 'oper-card-baja',
                      ]
                  ) ?>
                <?php endif; ?>
              </footer>
            </article>
            <?php endforeach; ?>
          </div>
      <?php if ($mostrarGrupo): ?>
        </div>
      </div>
      <?php endif; ?>
    <?php endforeach; ?>

    <div class="oper-grid" style="margin-top:4px">
      <a href="<?= $this->Url->build(['controller' => 'Operadores', 'action' => 'add']) ?>" class="oper-card oper-card--add">
        <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
        <span>Registrar operador</span>
      </a>
    </div>

  <?php endif; ?>

  <?php if ($this->Paginator->hasPage(2)): ?>
    <nav class="oper-pagination" aria-label="Paginación">
      <?= $this->Paginator->prev('← Anterior', ['class' => 'btn btn-ghost btn-sm']) ?>
      <span><?= $this->Paginator->counter('Página {{page}} de {{pages}}') ?></span>
      <?= $this->Paginator->next('Siguiente →', ['class' => 'btn btn-ghost btn-sm']) ?>
    </nav>
  <?php endif; ?>

  <div class="oper-index-legend">
    <span class="oper-index-legend-title">Leyenda</span>
    <span class="oper-pill oper-pill--ok oper-pill--sample"><span class="oper-pill-dot"></span> Vigente</span>
    <span class="oper-pill oper-pill--warn oper-pill--sample"><span class="oper-pill-dot"></span> Por vencer</span>
    <span class="oper-pill oper-pill--bad oper-pill--sample"><span class="oper-pill-dot"></span> Vencido</span>
    <span class="oper-pill oper-pill--no oper-pill--sample"><span class="oper-pill-dot"></span> Pendiente</span>
    <span>La fecha en cada píldora es el vencimiento del documento.</span>
  </div>
</div>
