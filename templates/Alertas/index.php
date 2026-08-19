<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $alertas
 * @var array<int, string> $empresasMap
 */

use App\Controller\AlertasController;
use App\Service\AlertasReglasService;

$filas = $alertas->toArray();
$total = count($filas);

$grupos = [];
foreach ($filas as $a) {
    $grupoKey = AlertasController::etiquetaGrupoAlerta($a);
    if (!isset($grupos[$grupoKey])) {
        $grupos[$grupoKey] = [];
    }
    $grupos[$grupoKey][] = $a;
}
uksort($grupos, static fn(string $a, string $b): int => strcasecmp($a, $b));
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Alertas</h1>
    <p><?= $total ?> por vencer o vencidos · recordatorios configurables</p>
  </div>
  <?php if ($total > 0): ?>
  <div class="page-header-actions alerta-group-controls">
    <button type="button" class="btn btn-ghost btn-sm" id="alertas-expand-all">Expandir todo</button>
    <button type="button" class="btn btn-ghost btn-sm" id="alertas-collapse-all">Contraer todo</button>
  </div>
  <?php endif; ?>
</div>

<?php if ($total === 0): ?>
<div class="panel">
  <div class="panel-body" style="text-align:center;color:var(--text3);padding:28px">
    No hay alertas activas en tus empresas.
  </div>
</div>
<?php else: ?>
  <?php foreach ($grupos as $grupoLabel => $itemsGrupo):
      $grupoId = 'alerta-grupo-' . md5($grupoLabel);
      $countGrupo = count($itemsGrupo);
  ?>
  <section class="panel alerta-group" data-alerta-group>
    <header class="alerta-group-head">
      <button type="button" class="alerta-group-toggle" data-alerta-toggle="<?= h($grupoId) ?>" aria-expanded="true" aria-controls="<?= h($grupoId) ?>">
        <span class="alerta-group-chevron" aria-hidden="true">▾</span>
        <span class="alerta-group-title"><?= h($grupoLabel) ?></span>
        <span class="alerta-group-count">(<?= $countGrupo ?>)</span>
      </button>
    </header>
    <div class="alerta-group-body" id="<?= h($grupoId) ?>">
      <div class="table-wrap table-wrap--stack">
        <table>
          <thead>
            <tr>
              <th>Empresa</th>
              <th>Operador / Entidad</th>
              <th>Tipo documento</th>
              <th>Fecha vencimiento</th>
              <th>Días restantes</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($itemsGrupo as $a):
                $vencido = ($a->tipo_alerta ?? '') === 'documento_vencido';
                $dias = $a->dias_restantes ?? AlertasReglasService::diasRestantes($a->fecha_vencimiento ?? null);
                $docLabel = (string)($a->tipo_documento_label ?? '') !== ''
                    ? (string)$a->tipo_documento_label
                    : AlertasController::etiquetaTipoDocumento(
                        (string)($a->tipo_documento ?? ''),
                        (string)($a->entidad_tipo ?? ''),
                    );
                $entidadNombre = trim((string)($a->entidad_nombre ?? ''));
                if ($entidadNombre === '') {
                    $entidadNombre = match ((string)($a->entidad_tipo ?? '')) {
                        'vehiculo' => 'Vehículo #' . (int)$a->entidad_id,
                        'operador' => 'Operador #' . (int)$a->entidad_id,
                        'empresa' => $empresasMap[(int)$a->empresa_id] ?? 'Empresa',
                        default => ucfirst((string)($a->entidad_tipo ?? '')),
                    };
                }
                $empresaNombre = $empresasMap[(int)($a->empresa_id ?? 0)] ?? ('Empresa #' . (int)($a->empresa_id ?? 0));
                $url = AlertasController::urlAlerta($a);
                $urlIr = $this->Url->build(['controller' => 'Alertas', 'action' => 'ir', (int)$a->id]);
                $claseDias = AlertasController::claseDiasRestantes(
                    $dias !== null ? (int)$dias : null,
                    $vencido,
                );
                $textoDias = AlertasController::textoDiasRestantes(
                    $dias !== null ? (int)$dias : null,
                    $vencido,
                );
            ?>
            <tr class="alerta-row-link">
              <td data-label="Empresa"><?= h($empresaNombre) ?></td>
              <td data-label="Operador / Entidad">
                <a href="<?= h($urlIr) ?>"><?= h($entidadNombre) ?></a>
              </td>
              <td data-label="Tipo documento">
                <a href="<?= h($urlIr) ?>"><strong><?= h($docLabel) ?></strong></a>
              </td>
              <td class="td-mono" data-label="Fecha vencimiento">
                <a href="<?= h($urlIr) ?>">
                  <?= $a->fecha_vencimiento ? h($a->fecha_vencimiento->format('d/m/Y')) : '—' ?>
                </a>
              </td>
              <td data-label="Días restantes">
                <a href="<?= h($urlIr) ?>" class="alerta-dias <?= h($claseDias) ?>"><?= h($textoDias) ?></a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
  <?php endforeach; ?>
<?php endif; ?>

<script>
(function () {
  function setGrupo(id, abierto) {
    var body = document.getElementById(id);
    var btn = document.querySelector('[data-alerta-toggle="' + id + '"]');
    if (!body || !btn) return;
    body.hidden = !abierto;
    btn.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    var chev = btn.querySelector('.alerta-group-chevron');
    if (chev) chev.textContent = abierto ? '▾' : '▸';
  }

  document.querySelectorAll('[data-alerta-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-alerta-toggle');
      var body = document.getElementById(id);
      if (!body) return;
      setGrupo(id, body.hidden);
    });
  });

  var expandAll = document.getElementById('alertas-expand-all');
  var collapseAll = document.getElementById('alertas-collapse-all');
  if (expandAll) {
    expandAll.addEventListener('click', function () {
      document.querySelectorAll('[data-alerta-toggle]').forEach(function (btn) {
        setGrupo(btn.getAttribute('data-alerta-toggle'), true);
      });
    });
  }
  if (collapseAll) {
    collapseAll.addEventListener('click', function () {
      document.querySelectorAll('[data-alerta-toggle]').forEach(function (btn) {
        setGrupo(btn.getAttribute('data-alerta-toggle'), false);
      });
    });
  }
})();
</script>
