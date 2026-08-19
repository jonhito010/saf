<?php
/**
 * @var \App\View\AppView $this
 * @var array $secciones
 * @var array<string, \App\Model\Entity\DocumentoEmpresa> $docsAdicPorTipo
 * @var array $vehiculosResumen
 * @var array $operadoresResumen
 * @var int $comprobantes6m
 * @var list<string> $reqVehiculo
 * @var list<string> $reqOperador
 * @var array<int, string> $empresasFiltro
 * @var int|null $filtroEmpresaId
 * @var bool $vistaConsolidada
 * @var string $alcanceLabel
 */

use App\Model\Table\DocumentosEmpresaTable;
use App\Model\Table\DocumentosOperadorTable;
use App\Model\Table\DocumentosVehiculoTable;
use App\Utility\CumplimientoMaterialesPeligrosos;

$estadoAdic = function (array $tipos) use ($docsAdicPorTipo): string {
    $tiene = false;
    foreach ($tipos as $t) {
        if (isset($docsAdicPorTipo[$t])) {
            $tiene = true;
            if (($docsAdicPorTipo[$t]->estatus ?? '') === 'vencido') {
                return 'bad';
            }
            if (($docsAdicPorTipo[$t]->estatus ?? '') === 'por_vencer') {
                return 'warn';
            }
        }
    }
    return $tiene ? 'ok' : 'pending';
};
?>

<div class="veh-page mp-cumpl-page">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Materiales peligrosos</h1>
      <p>
        Requisitos SICT para transporte de materiales, remanentes y residuos peligrosos
        <?php if (!empty($alcanceLabel)): ?> · <?= h($alcanceLabel) ?><?php endif; ?>
      </p>
    </div>
    <div class="veh-header-actions">
      <a class="btn btn-ghost" href="<?= $this->Url->build(['controller' => 'Cumplimiento', 'action' => 'index']) ?>">Cumplimiento general</a>
      <a class="btn btn-primary" href="<?= $this->Url->build(['controller' => 'DocumentosAdicionales', 'action' => 'add', '?' => ['origen' => 'cumplimiento-mp']]) ?>">+ Documento adicional</a>
    </div>
  </div>

<?= $this->element('filtro_empresa', [
    'empresasFiltro' => $empresasFiltro ?? [],
    'filtroEmpresaId' => $filtroEmpresaId ?? null,
    'vistaConsolidada' => $vistaConsolidada ?? false,
    'alcanceLabel' => $alcanceLabel ?? '',
]) ?>

  <div class="mp-cumpl-intro panel">
    <p>Empresas de transporte de materiales, remanentes y residuos peligrosos (o transporte privado de MP) deben cumplir los puntos siguientes. Cada requisito indica dónde registrarlo en el sistema.</p>
  </div>

  <?php foreach ($secciones as $bloque): ?>
  <section class="mp-cumpl-section">
    <h2 class="mp-cumpl-section-title"><?= h($bloque['label']) ?></h2>
    <div class="mp-cumpl-grid">
      <?php foreach ($bloque['items'] as $req):
          $sec = $req['seccion'];
          $est = 'pending';
          if ($sec === CumplimientoMaterialesPeligrosos::SECCION_ADICIONAL && $req['tipos_documento'] !== []) {
              $est = $estadoAdic($req['tipos_documento']);
          } elseif ($sec === CumplimientoMaterialesPeligrosos::SECCION_CARTA_PORTE) {
              $est = $comprobantes6m > 0 ? 'ok' : 'pending';
          }
          $url = $req['enlace'] ? $this->Url->build($req['enlace']) : null;
          if ($sec === CumplimientoMaterialesPeligrosos::SECCION_ADICIONAL && count($req['tipos_documento']) === 1) {
              $url = $this->Url->build([
                  'controller' => 'DocumentosAdicionales',
                  'action' => 'add',
                  '?' => ['tipo' => $req['tipos_documento'][0], 'origen' => 'cumplimiento-mp'],
              ]);
          }
      ?>
      <article class="mp-cumpl-card mp-cumpl-card--<?= h($est) ?>">
        <div class="mp-cumpl-card-head">
          <span class="mp-cumpl-dot"></span>
          <h3><?= h($req['titulo']) ?></h3>
        </div>
        <p class="mp-cumpl-desc"><?= h($req['descripcion']) ?></p>
        <p class="mp-cumpl-ret"><strong>Retención:</strong> <?= h($req['retencion']) ?></p>
        <?php if ($sec === CumplimientoMaterialesPeligrosos::SECCION_CARTA_PORTE): ?>
          <p class="mp-cumpl-stat">CFDI emitidos (6 meses): <strong><?= (int)$comprobantes6m ?></strong></p>
        <?php endif; ?>
        <?php if ($url): ?>
          <a href="<?= h($url) ?>" class="mp-cumpl-link">Ir a sección →</a>
        <?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endforeach; ?>

  <?php if (!empty($vehiculosResumen)): ?>
  <section class="panel mp-cumpl-panel">
    <div class="panel-header">
      <div class="panel-title">Vehículos de material peligroso</div>
      <a class="btn btn-ghost btn-sm" href="<?= $this->Url->build(['controller' => 'Vehiculos', 'action' => 'index', '?' => ['tipo_carga' => 'material_peligroso']]) ?>">Ver vehículos</a>
    </div>
    <div class="mp-cumpl-units">
      <?php foreach ($vehiculosResumen as $row):
          $v = $row['vehiculo'];
      ?>
      <a href="<?= $this->Url->build(['controller' => 'Vehiculos', 'action' => 'view', $v->id, '?' => ['tab' => 'mp']]) ?>" class="mp-cumpl-unit">
        <span class="mp-cumpl-unit-plate"><?= h(strtoupper($v->placas)) ?></span>
        <span class="mp-cumpl-unit-pct"><?= (int)$row['pct'] ?>% docs MP</span>
        <span class="mp-cumpl-unit-hint">
          <?php foreach ($reqVehiculo as $tipo):
              $doc = $row['docs'][$tipo] ?? null;
              $ok = $doc && in_array($doc->estatus, ['vigente', 'por_vencer'], true);
          ?>
            <span class="<?= $ok ? 'is-ok' : 'is-no' ?>" title="<?= h(DocumentosVehiculoTable::tipoLabel($tipo)) ?>">●</span>
          <?php endforeach; ?>
        </span>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="panel mp-cumpl-panel">
    <div class="panel-header">
      <div class="panel-title">Operadores — licencia y capacitación</div>
      <a class="btn btn-ghost btn-sm" href="<?= $this->Url->build(['controller' => 'Operadores', 'action' => 'index']) ?>">Ver operadores</a>
    </div>
    <?php if (empty($operadoresResumen)): ?>
      <p class="mp-cumpl-empty">No hay operadores activos registrados.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Operador</th>
            <th>Licencia</th>
            <th>Capacitación</th>
            <th>INE</th>
            <th>Cumplimiento MP</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($operadoresResumen as $row):
              $op = $row['operador'];
              $celda = function (string $tipo) use ($row): string {
                  $doc = $row['docs'][$tipo] ?? null;
                  if (!$doc) {
                      return '<span class="badge badge-gray">Pendiente</span>';
                  }
                  return match ($doc->estatus ?? '') {
                      'vencido' => '<span class="badge badge-red">Vencido</span>',
                      'por_vencer' => '<span class="badge badge-amber">Por vencer</span>',
                      default => '<span class="badge badge-green">Vigente</span>',
                  };
              };
          ?>
          <tr>
            <td><?= h($op->nombre_completo) ?></td>
            <td><?= $celda('licencia') ?></td>
            <td><?= $celda('curso_capacitacion') ?></td>
            <td><?= $celda('ine') ?></td>
            <td class="td-mono"><?= (int)$row['pct'] ?>%</td>
            <td><a href="<?= $this->Url->build(['controller' => 'Operadores', 'action' => 'edit', $op->id]) ?>">Editar</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>
</div>
