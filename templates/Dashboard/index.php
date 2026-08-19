<?php
/**
 * @var \App\View\AppView $this
 * @var string $empresaNombre
 * @var int|null $empresaId
 * @var int $totalEmpresasCuenta
 * @var array<int, string> $empresasFiltro
 * @var int|null $filtroEmpresaId
 * @var bool $vistaConsolidada
 * @var string $alcanceLabel
 * @var list<array<string, mixed>> $empresasResumen
 * @var list<array<string, mixed>> $vencimientosPróximos
 * @var array{vigente_pct: int, por_vencer_pct: int, vencido_pct: int, total: int} $indicadoresGlobales
 * @var bool $panelGeneral
 * @var bool $puedeNotificar
 */

use Cake\I18n\DateTime;

$panelGeneral = !empty($panelGeneral);
$indicadoresGlobales = $indicadoresGlobales ?? [
    'vigente_pct' => 0,
    'por_vencer_pct' => 0,
    'vencido_pct' => 0,
    'total' => 0,
];

$badgeEstatus = function (string $estatus): string {
    return match ($estatus) {
        'vencido' => '<span class="badge badge-red">Vencido</span>',
        'por_vencer' => '<span class="badge badge-amber">Por vencer</span>',
        default => '<span class="badge badge-green">Vigente</span>',
    };
};

$pctClass = static function (int $pct): string {
    if ($pct >= 90) {
        return 'ok';
    }
    if ($pct >= 50) {
        return 'mid';
    }

    return 'low';
};

$empresasResumen = $empresasResumen ?? [];
$vencimientosPróximos = $vencimientosPróximos ?? [];
$puedeNotificar = !empty($puedeNotificar);
$mostrarTodas = $panelGeneral || empty($filtroEmpresaId);
$listado = $empresasResumen;
if (!$panelGeneral && !$mostrarTodas && $filtroEmpresaId) {
    $listado = array_values(array_filter(
        $empresasResumen,
        static fn(array $e): bool => (int)$e['id'] === (int)$filtroEmpresaId,
    ));
}

$mensajeNotificacion = 'Hola, le contactamos desde SAF respecto a {empresa}. Tiene documentos próximos a vencer o vencidos. Por favor revise su expediente a la brevedad.';
?>

<div class="dash-page<?= $panelGeneral ? ' dash-page--general' : '' ?>">
  <header class="dash-hero">
    <div class="dash-hero-main">
      <p class="dash-hero-kicker"><?= $panelGeneral
          ? 'SAF · Panel general administrador'
          : 'SAF · Sistema de cumplimiento normativo' ?></p>
      <h1 class="dash-hero-title"><?= $panelGeneral
          ? 'Cumplimiento total · Todas las empresas'
          : 'Panel de control' ?></h1>
      <p class="dash-hero-sub">
        <?php if ($panelGeneral || ((int)$totalEmpresasCuenta > 1 && $mostrarTodas)): ?>
          <strong><?= (int)$totalEmpresasCuenta ?> empresas</strong> · vista consolidada ·
        <?php elseif ($empresaNombre !== ''): ?>
          <strong><?= h($empresaNombre) ?></strong> ·
        <?php endif; ?>
        <?= (new DateTime())->i18nFormat('EEEE d \'de\' MMMM yyyy', null, 'es_MX') ?>
      </p>
      <?php if (!$panelGeneral): ?>
      <?= $this->element('filtro_empresa', [
          'empresasFiltro' => $empresasFiltro ?? [],
          'filtroEmpresaId' => $filtroEmpresaId ?? null,
          'vistaConsolidada' => $vistaConsolidada ?? false,
          'alcanceLabel' => $alcanceLabel ?? $empresaNombre,
      ]) ?>
      <?php endif; ?>
      <?php if ((int)($indicadoresGlobales['total'] ?? 0) > 0): ?>
      <div class="dash-global-pct" aria-label="Indicadores globales de documentos">
        <div class="dash-global-pct-item dash-global-pct-item--ok">
          <span class="dash-global-pct-val"><?= (int)$indicadoresGlobales['vigente_pct'] ?>%</span>
          <span class="dash-global-pct-lbl">Documentos vigentes</span>
        </div>
        <div class="dash-global-pct-item dash-global-pct-item--warn">
          <span class="dash-global-pct-val"><?= (int)$indicadoresGlobales['por_vencer_pct'] ?>%</span>
          <span class="dash-global-pct-lbl">Por vencer</span>
        </div>
        <div class="dash-global-pct-item dash-global-pct-item--bad">
          <span class="dash-global-pct-val"><?= (int)$indicadoresGlobales['vencido_pct'] ?>%</span>
          <span class="dash-global-pct-lbl">Vencidos</span>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <div class="dash-hero-actions">
      <?php if ($panelGeneral): ?>
        <a href="<?= $this->Url->build(['controller' => 'Dashboard', 'action' => 'index']) ?>" class="btn btn-ghost">Panel de control</a>
      <?php else: ?>
        <a href="<?= $this->Url->build(['controller' => 'Cumplimiento', 'action' => 'index']) ?>" class="btn btn-primary">Cumplimiento</a>
      <?php endif; ?>
      <?php if ($panelGeneral): ?>
        <a href="<?= $this->Url->build(['controller' => 'Cumplimiento', 'action' => 'index', '?' => ['empresa_id' => 'todas']]) ?>" class="btn btn-primary">Cumplimiento</a>
      <?php endif; ?>
    </div>
  </header>

  <?php if ($vencimientosPróximos !== []): ?>
  <section class="dash-venc-section panel" aria-labelledby="dash-venc-heading">
    <div class="dash-list-head">
      <div>
        <h2 id="dash-venc-heading" class="dash-list-title">Próximos vencimientos</h2>
        <p class="dash-list-desc">Documentos vencidos o por vencer que requieren atención inmediata.</p>
      </div>
      <span class="dash-modal-count"><?= count($vencimientosPróximos) ?> registro<?= count($vencimientosPróximos) === 1 ? '' : 's' ?></span>
    </div>
    <div class="table-wrap">
      <table class="saf-modal-table dash-venc-table">
        <thead>
          <tr>
            <th>Ámbito</th>
            <th>Referencia</th>
            <th>Documento</th>
            <th>Empresa</th>
            <th>Vence</th>
            <th>Días</th>
            <th>Estatus</th>
            <?php if ($puedeNotificar): ?><th>Notificar</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($vencimientosPróximos as $row):
              $tel = trim((string)($row['telefono'] ?? ''));
              $mail = trim((string)($row['email'] ?? ''));
              $empNombre = (string)($row['empresa_nombre'] ?? $row['ref'] ?? 'Empresa');
          ?>
          <tr>
            <td data-label="Ámbito"><span class="saf-modal-tag"><?= h($row['ambito']) ?></span></td>
            <td data-label="Referencia">
              <?php if (!empty($row['url'])): ?>
                <a class="saf-modal-link" href="<?= h($row['url']) ?>"><?= h($row['ref']) ?></a>
              <?php else: ?>
                <?= h($row['ref']) ?>
              <?php endif; ?>
            </td>
            <td data-label="Documento"><?= h($row['tipo_label'] ?? $row['documento']) ?></td>
            <td data-label="Empresa"><?= h($empNombre) ?></td>
            <td data-label="Vence" class="saf-modal-fecha<?= ($row['estatus'] ?? '') === 'por_vencer' ? ' saf-modal-fecha--warn' : '' ?>"><?= h($row['vence']) ?></td>
            <td data-label="Días">
              <span class="alerta-dias <?= h($row['dias_clase'] ?? 'alerta-dias--warn') ?>"><?= h($row['dias_texto'] ?? '—') ?></span>
            </td>
            <td data-label="Estatus"><?= $badgeEstatus((string)$row['estatus']) ?></td>
            <?php if ($puedeNotificar): ?>
            <td data-label="Notificar">
              <div class="dash-notify-actions">
                <button
                  type="button"
                  class="btn btn-sm dash-btn-wa"
                  data-dash-notify-wa
                  data-telefono="<?= h($tel) ?>"
                  data-empresa="<?= h($empNombre) ?>"
                  data-mensaje="<?= h($mensajeNotificacion) ?>"
                  <?= $tel === '' ? 'disabled title="Sin teléfono registrado"' : 'title="Notificar por WhatsApp"' ?>
                >WhatsApp</button>
                <button
                  type="button"
                  class="btn btn-sm dash-btn-mail"
                  data-dash-notify-mail
                  data-email="<?= h($mail) ?>"
                  data-empresa="<?= h($empNombre) ?>"
                  data-mensaje="<?= h($mensajeNotificacion) ?>"
                  <?= $mail === '' ? 'disabled title="Sin correo registrado"' : 'title="Notificar por correo"' ?>
                >Correo</button>
              </div>
            </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <?php endif; ?>

  <section class="dash-list-section" aria-labelledby="dash-list-heading">
    <div class="dash-list-head">
      <div>
        <h2 id="dash-list-heading" class="dash-list-title"><?= $panelGeneral ? 'Todas las empresas' : 'Empresas' ?></h2>
        <p class="dash-list-desc"><?= $panelGeneral
            ? 'Cumplimiento total consolidado. Haz clic en Vehículos, Operadores, etc. para abrirlos en otra pestaña (solo esa empresa).'
            : 'Haz clic en Vehículos, Operadores, etc. para abrirlos en otra pestaña (solo esa empresa). <strong>Ver detalle</strong> abre el resumen completo.' ?></p>
      </div>
      <?php if (!$panelGeneral && !$mostrarTodas): ?>
        <a href="<?= $this->Url->build(['action' => 'index', '?' => ['empresa_id' => 'todas']]) ?>" class="btn btn-ghost btn-sm">Ver todas</a>
      <?php endif; ?>
    </div>

    <?php if ($listado === []): ?>
      <div class="panel dash-empty-panel">
        <p>No hay empresas registradas en tu cuenta. <a href="<?= $this->Url->build(['controller' => 'Empresas', 'action' => 'index', '?' => ['nueva' => '1']]) ?>">Registrar empresa</a></p>
      </div>
    <?php else: ?>
      <div class="dash-emp-list">
        <?php foreach ($listado as $emp):
            $pct = (int)$emp['cumplimiento'];
            $pctCls = $pctClass($pct);
            $mp = $emp['mp'] ?? [];
            $pctMp = (int)($mp['pct'] ?? 0);
            $pctMpCls = $pctClass($pctMp);
            $vencidosLista = $emp['vencidosLista'] ?? [];
            $vencidosMp = $mp['vencidos'] ?? [];
            $activa = !empty($emp['esActiva']);
            $modalId = 'dash-modal-empresa-' . (int)$emp['id'];
            $eid = (int)$emp['id'];
            $telEmpresa = trim((string)($emp['telefono'] ?? ''));
            $emailEmpresa = trim((string)($emp['email'] ?? ''));
            $urlVeh = $this->Url->build(['controller' => 'Vehiculos', 'action' => 'index', '?' => ['empresa_id' => $eid]]);
            $urlOp = $this->Url->build(['controller' => 'Operadores', 'action' => 'index', '?' => ['empresa_id' => $eid]]);
            $urlCumpl = $this->Url->build(['controller' => 'Cumplimiento', 'action' => 'index', '?' => ['empresa_id' => $eid]]);
            $urlMp = $this->Url->build(['controller' => 'Cumplimiento', 'action' => 'materialesPeligrosos', '?' => ['empresa_id' => $eid]]);
            $urlAlertas = $this->Url->build(['controller' => 'Alertas', 'action' => 'index', '?' => ['empresa_id' => $eid]]);
            $urlEmpresa = $this->Url->build(['controller' => 'Empresas', 'action' => 'index', '?' => ['empresa_id' => $eid]]);
            $tieneVencimientos = $vencidosLista !== [] || ((int)$emp['docsPorVencer'] + (int)$emp['docsVencidos']) > 0;
        ?>
        <article class="dash-emp-row<?= $activa ? ' is-activa' : '' ?>">
          <header class="dash-emp-row-head">
            <div class="dash-emp-row-ident">
              <h3 class="dash-emp-row-name"><?= h($emp['nombre']) ?></h3>
              <div class="dash-emp-row-meta">
                <?php if ($emp['rfc'] !== ''): ?><span class="dash-emp-rfc"><?= h($emp['rfc']) ?></span><?php endif; ?>
                <?php if ($activa): ?><span class="dash-empresa-pill">Activa</span><?php endif; ?>
              </div>
            </div>
            <div class="dash-emp-row-actions">
              <a class="dash-emp-row-pct dash-emp-stat-link" href="<?= h($urlCumpl) ?>" target="_blank" rel="noopener noreferrer" title="Abrir cumplimiento de esta empresa">
                <span class="dash-emp-pct-val <?= h($pctCls) ?>"><?= $pct ?>%</span>
                <span class="dash-emp-pct-lbl">Cumplimiento</span>
              </a>
              <button type="button" class="btn btn-primary btn-sm" data-saf-modal-open="<?= h($modalId) ?>">
                Ver detalle
              </button>
            </div>
          </header>

          <div class="dash-emp-stats">
            <a class="dash-emp-stat dash-emp-stat-link" href="<?= h($urlVeh) ?>" target="_blank" rel="noopener noreferrer" title="Vehículos de esta empresa">
              <span class="dash-emp-stat-val"><?= (int)$emp['vehiculos'] ?></span>
              <span class="dash-emp-stat-lbl">Vehículos</span>
            </a>
            <a class="dash-emp-stat dash-emp-stat-link" href="<?= h($urlOp) ?>" target="_blank" rel="noopener noreferrer" title="Operadores de esta empresa">
              <span class="dash-emp-stat-val"><?= (int)$emp['operadores'] ?></span>
              <span class="dash-emp-stat-lbl">Operadores</span>
            </a>
            <a class="dash-emp-stat ok dash-emp-stat-link" href="<?= h($urlCumpl) ?>" target="_blank" rel="noopener noreferrer" title="Cumplimiento">
              <span class="dash-emp-stat-val"><?= (int)$emp['docsCorrectos'] ?></span>
              <span class="dash-emp-stat-lbl">Correctos</span>
            </a>
            <a class="dash-emp-stat warn dash-emp-stat-link" href="<?= h($urlCumpl) ?>" target="_blank" rel="noopener noreferrer" title="Documentos por vencer">
              <span class="dash-emp-stat-val"><?= (int)$emp['docsPorVencer'] ?></span>
              <span class="dash-emp-stat-lbl">Por vencer</span>
            </a>
            <a class="dash-emp-stat bad dash-emp-stat-link" href="<?= h($urlCumpl) ?>" target="_blank" rel="noopener noreferrer" title="Documentos vencidos">
              <span class="dash-emp-stat-val"><?= (int)$emp['docsVencidos'] ?></span>
              <span class="dash-emp-stat-lbl">Vencidos</span>
            </a>
            <a class="dash-emp-stat mp dash-emp-stat-link" href="<?= h($urlMp) ?>" target="_blank" rel="noopener noreferrer" title="Materiales peligrosos de esta empresa">
              <span class="dash-emp-stat-val <?= h($pctMpCls) ?>"><?= $pctMp ?>%</span>
              <span class="dash-emp-stat-lbl">Mat. peligrosos</span>
            </a>
          </div>

          <div class="dash-emp-bar"><span class="<?= h($pctCls) ?>" style="width:<?= max(2, $pct) ?>%"></span></div>

          <!-- Modal detalle empresa -->
          <div id="<?= h($modalId) ?>" class="saf-modal dash-emp-modal" role="dialog" aria-modal="true" aria-labelledby="<?= h($modalId) ?>-title" aria-hidden="true">
            <div class="saf-modal-backdrop" data-saf-modal-close tabindex="-1"></div>
            <div class="saf-modal-dialog saf-modal-dialog--wide">
              <div class="saf-modal-header">
                <div class="saf-modal-header-icon dash-modal-icon" aria-hidden="true">🏢</div>
                <div class="dash-modal-head-text">
                  <h2 id="<?= h($modalId) ?>-title" class="saf-modal-title"><?= h($emp['nombre']) ?></h2>
                  <p class="saf-modal-subtitle">
                    <?php if ($emp['rfc'] !== ''): ?>RFC <?= h($emp['rfc']) ?> · <?php endif; ?>
                    Cumplimiento <?= $pct ?>% · MP <?= $pctMp ?>%
                    <?php if ($activa): ?> · Empresa activa<?php endif; ?>
                  </p>
                </div>
                <button type="button" class="btn btn-ghost btn-icon dash-modal-close" data-saf-modal-close aria-label="Cerrar">×</button>
              </div>

              <div class="saf-modal-body dash-modal-body">
                <div class="dash-emp-stats dash-emp-stats--modal">
                  <a class="dash-emp-stat dash-emp-stat-link" href="<?= h($urlVeh) ?>" target="_blank" rel="noopener noreferrer">
                    <span class="dash-emp-stat-val"><?= (int)$emp['vehiculos'] ?></span>
                    <span class="dash-emp-stat-lbl">Vehículos</span>
                  </a>
                  <a class="dash-emp-stat dash-emp-stat-link" href="<?= h($urlOp) ?>" target="_blank" rel="noopener noreferrer">
                    <span class="dash-emp-stat-val"><?= (int)$emp['operadores'] ?></span>
                    <span class="dash-emp-stat-lbl">Operadores</span>
                  </a>
                  <a class="dash-emp-stat ok dash-emp-stat-link" href="<?= h($urlCumpl) ?>" target="_blank" rel="noopener noreferrer">
                    <span class="dash-emp-stat-val"><?= (int)$emp['docsCorrectos'] ?></span>
                    <span class="dash-emp-stat-lbl">Correctos</span>
                  </a>
                  <a class="dash-emp-stat warn dash-emp-stat-link" href="<?= h($urlCumpl) ?>" target="_blank" rel="noopener noreferrer">
                    <span class="dash-emp-stat-val"><?= (int)$emp['docsPorVencer'] ?></span>
                    <span class="dash-emp-stat-lbl">Por vencer</span>
                  </a>
                  <a class="dash-emp-stat bad dash-emp-stat-link" href="<?= h($urlCumpl) ?>" target="_blank" rel="noopener noreferrer">
                    <span class="dash-emp-stat-val"><?= (int)$emp['docsVencidos'] ?></span>
                    <span class="dash-emp-stat-lbl">Vencidos</span>
                  </a>
                  <a class="dash-emp-stat dash-emp-stat-link" href="<?= h($urlAlertas) ?>" target="_blank" rel="noopener noreferrer">
                    <span class="dash-emp-stat-val"><?= (int)$emp['alertas'] ?></span>
                    <span class="dash-emp-stat-lbl">Alertas</span>
                  </a>
                </div>

                <div class="dash-modal-section">
                  <div class="dash-emp-block-head">
                    <h4>Próximos vencimientos</h4>
                    <div class="dash-emp-block-head-right">
                      <span class="dash-modal-count"><?= count($vencidosLista) ?> registro<?= count($vencidosLista) === 1 ? '' : 's' ?></span>
                      <?php if ($puedeNotificar && $tieneVencimientos): ?>
                      <div class="dash-notify-actions">
                        <button
                          type="button"
                          class="btn btn-sm dash-btn-wa"
                          data-dash-notify-wa
                          data-telefono="<?= h($telEmpresa) ?>"
                          data-empresa="<?= h($emp['nombre']) ?>"
                          data-mensaje="<?= h($mensajeNotificacion) ?>"
                          <?= $telEmpresa === '' ? 'disabled title="Sin teléfono registrado"' : 'title="Notificar por WhatsApp"' ?>
                        >WhatsApp</button>
                        <button
                          type="button"
                          class="btn btn-sm dash-btn-mail"
                          data-dash-notify-mail
                          data-email="<?= h($emailEmpresa) ?>"
                          data-empresa="<?= h($emp['nombre']) ?>"
                          data-mensaje="<?= h($mensajeNotificacion) ?>"
                          <?= $emailEmpresa === '' ? 'disabled title="Sin correo registrado"' : 'title="Notificar por correo"' ?>
                        >Correo</button>
                      </div>
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php if ($vencidosLista === []): ?>
                    <p class="dash-emp-ok-msg">Sin vencimientos en expediente general.</p>
                  <?php else: ?>
                    <div class="table-wrap">
                      <table class="saf-modal-table">
                        <thead>
                          <tr><th>Ámbito</th><th>Referencia</th><th>Documento</th><th>Vence</th><th>Días</th><th>Estatus</th></tr>
                        </thead>
                        <tbody>
                          <?php foreach ($vencidosLista as $row): ?>
                          <tr>
                            <td data-label="Ámbito"><span class="saf-modal-tag"><?= h($row['ambito']) ?></span></td>
                            <td data-label="Referencia">
                              <?php if (!empty($row['url'])): ?>
                                <a class="saf-modal-link" href="<?= h($row['url']) ?>" target="_blank" rel="noopener noreferrer"><?= h($row['ref']) ?></a>
                              <?php else: ?>
                                <?= h($row['ref']) ?>
                              <?php endif; ?>
                            </td>
                            <td data-label="Documento"><?= h($row['tipo_label'] ?? $row['documento']) ?></td>
                            <td data-label="Vence" class="saf-modal-fecha<?= ($row['estatus'] ?? '') === 'por_vencer' ? ' saf-modal-fecha--warn' : '' ?>"><?= h($row['vence']) ?></td>
                            <td data-label="Días">
                              <span class="alerta-dias <?= h($row['dias_clase'] ?? 'alerta-dias--warn') ?>"><?= h($row['dias_texto'] ?? '—') ?></span>
                            </td>
                            <td data-label="Estatus"><?= $badgeEstatus((string)$row['estatus']) ?></td>
                          </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="dash-modal-section dash-modal-section-mp">
                  <div class="dash-emp-block-head">
                    <h4>Materiales peligrosos</h4>
                    <span class="dash-modal-count <?= h($pctMpCls) ?>"><?= $pctMp ?>% cumplimiento MP</span>
                  </div>
                  <div class="dash-emp-mp-kpis">
                    <div>
                      <strong><?= (int)($mp['vehiculosOk'] ?? 0) ?>/<?= (int)($mp['vehiculos'] ?? 0) ?></strong>
                      <span>Vehículos MP al día</span>
                    </div>
                    <div>
                      <strong><?= (int)($mp['operadoresOk'] ?? 0) ?>/<?= (int)($mp['operadores'] ?? 0) ?></strong>
                      <span>Operadores listos</span>
                    </div>
                    <div>
                      <strong><?= (int)($mp['adicionalesOk'] ?? 0) ?>/<?= (int)($mp['adicionales'] ?? 0) ?></strong>
                      <span>Docs. adicionales</span>
                    </div>
                    <div>
                      <strong class="<?= h($pctMpCls) ?>"><?= $pctMp ?>%</strong>
                      <span>Cumplimiento MP</span>
                    </div>
                  </div>
                  <?php if ($vencidosMp === []): ?>
                    <p class="dash-emp-ok-msg">Expediente MP sin vencimientos.</p>
                  <?php else: ?>
                    <div class="table-wrap">
                      <table class="saf-modal-table">
                        <thead>
                          <tr><th>Ámbito</th><th>Referencia</th><th>Documento</th><th>Vence</th><th>Días</th><th>Estatus</th></tr>
                        </thead>
                        <tbody>
                          <?php foreach ($vencidosMp as $row): ?>
                          <tr>
                            <td data-label="Ámbito"><span class="saf-modal-tag tag-mp"><?= h($row['ambito']) ?></span></td>
                            <td data-label="Referencia">
                              <?php if (!empty($row['url'])): ?>
                                <a class="saf-modal-link" href="<?= h($row['url']) ?>" target="_blank" rel="noopener noreferrer"><?= h($row['ref']) ?></a>
                              <?php else: ?>
                                <?= h($row['ref']) ?>
                              <?php endif; ?>
                            </td>
                            <td data-label="Documento"><?= h($row['tipo_label'] ?? $row['documento']) ?></td>
                            <td data-label="Vence" class="saf-modal-fecha<?= ($row['estatus'] ?? '') === 'por_vencer' ? ' saf-modal-fecha--warn' : '' ?>"><?= h($row['vence']) ?></td>
                            <td data-label="Días">
                              <span class="alerta-dias <?= h($row['dias_clase'] ?? 'alerta-dias--warn') ?>"><?= h($row['dias_texto'] ?? '—') ?></span>
                            </td>
                            <td data-label="Estatus"><?= $badgeEstatus((string)$row['estatus']) ?></td>
                          </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <div class="saf-modal-footer dash-modal-footer">
                <a class="btn btn-ghost" href="<?= h($urlEmpresa) ?>" target="_blank" rel="noopener noreferrer">Documentación</a>
                <a class="btn btn-ghost" href="<?= h($urlVeh) ?>" target="_blank" rel="noopener noreferrer">Vehículos</a>
                <a class="btn btn-ghost" href="<?= h($urlOp) ?>" target="_blank" rel="noopener noreferrer">Operadores</a>
                <a class="btn btn-ghost" href="<?= h($urlMp) ?>" target="_blank" rel="noopener noreferrer">MP</a>
                <a class="btn btn-primary" href="<?= h($urlCumpl) ?>" target="_blank" rel="noopener noreferrer">Cumplimiento</a>
                <button type="button" class="btn btn-ghost" data-saf-modal-close>Cerrar</button>
              </div>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <?php if ($puedeNotificar): ?>
  <div id="dash-modal-correo" class="saf-modal" role="dialog" aria-modal="true" aria-labelledby="dash-modal-correo-title" aria-hidden="true">
    <div class="saf-modal-backdrop" data-saf-modal-close tabindex="-1"></div>
    <div class="saf-modal-dialog">
      <div class="saf-modal-header">
        <div class="saf-modal-header-icon dash-modal-icon" aria-hidden="true">✉</div>
        <div class="dash-modal-head-text">
          <h2 id="dash-modal-correo-title" class="saf-modal-title">Notificar por correo</h2>
          <p class="saf-modal-subtitle" id="dash-mail-empresa-label">Empresa</p>
        </div>
        <button type="button" class="btn btn-ghost btn-icon dash-modal-close" data-saf-modal-close aria-label="Cerrar">×</button>
      </div>
      <div class="saf-modal-body dash-mail-modal-body">
        <p class="dash-mail-help">Se abrirá tu cliente de correo con el mensaje de aviso de vencimientos.</p>
        <div class="dash-mail-fields" id="dash-mail-fields">
          <div class="dash-mail-field">
            <label for="dash-mail-primary">Correo de la empresa</label>
            <input type="email" id="dash-mail-primary" class="dash-mail-input" readonly>
          </div>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" id="dash-mail-add">+ Agregar otro correo</button>
        <p class="dash-mail-error" id="dash-mail-error" hidden></p>
      </div>
      <div class="saf-modal-footer">
        <button type="button" class="btn btn-ghost" data-saf-modal-close>Cancelar</button>
        <button type="button" class="btn btn-primary" id="dash-mail-send">Abrir correo</button>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
