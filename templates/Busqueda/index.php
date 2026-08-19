<?php
/**
 * @var \App\View\AppView $this
 * @var string $termino
 * @var int $total
 * @var array<int, array{
 *   empresa_id: int,
 *   nombre: string,
 *   coincide_empresa: bool,
 *   empresa: ?\App\Model\Entity\Empresa,
 *   vehiculos: list<\App\Model\Entity\Vehiculo>,
 *   operadores: list<\App\Model\Entity\Operador>,
 *   documentos: list<array{
 *     ambito: string,
 *     id: int,
 *     empresa_id: int,
 *     tipo: string,
 *     tipo_label: string,
 *     nombre_archivo: string,
 *     ruta_archivo: string,
 *     estatus: string,
 *     contexto: string,
 *     edit_controller: string,
 *     edit_id: int
 *   }>
 * }> $porEmpresa
 */

$ambitoLabels = [
    'empresa' => 'Empresa',
    'adicional' => 'Adicional',
    'vehiculo' => 'Vehículo',
    'operador' => 'Operador',
];
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Búsqueda</h1>
    <p><?= $termino !== '' ? h((string)$total) . ' coincidencias para «' . h($termino) . '»' : 'Escribe al menos 2 caracteres en la barra superior' ?></p>
  </div>
</div>

<?php if ($termino !== '' && mb_strlen($termino) < 2): ?>
  <div class="panel"><div style="padding:16px 18px;color:var(--text3)">Ingresa al menos 2 caracteres para buscar.</div></div>
<?php elseif ($termino !== '' && $total === 0): ?>
  <div class="panel"><div style="padding:16px 18px;color:var(--text3)">No se encontraron coincidencias en tus empresas.</div></div>
<?php else: ?>

  <?php foreach ($porEmpresa as $grupo): ?>
    <?php
    $conteoGrupo = ($grupo['coincide_empresa'] ? 1 : 0)
        + count($grupo['vehiculos'])
        + count($grupo['operadores'])
        + count($grupo['documentos']);
    ?>
  <div class="panel" style="margin-bottom:14px">
    <div class="panel-header">
      <div class="panel-title"><?= h($grupo['nombre']) ?></div>
      <div style="color:var(--text3);font-size:13px"><?= h((string)$conteoGrupo) ?> coincidencia<?= $conteoGrupo === 1 ? '' : 's' ?></div>
    </div>

    <?php if ($grupo['coincide_empresa'] && $grupo['empresa'] !== null): ?>
    <div class="table-wrap" style="border-bottom:1px solid var(--border, #e5e7eb)">
      <table>
        <thead><tr><th colspan="2">Empresa</th></tr></thead>
        <tbody>
          <tr>
            <td>
              <a href="<?= $this->Url->build(['controller' => 'Empresas', 'action' => 'index']) ?>">
                <?= h($grupo['empresa']->nombre ?? $grupo['nombre']) ?>
              </a>
            </td>
            <td class="td-mono"><?= h($grupo['empresa']->rfc ?? '—') ?></td>
          </tr>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <?php if ($grupo['vehiculos'] !== []): ?>
    <div class="table-wrap" style="border-bottom:1px solid var(--border, #e5e7eb)">
      <table>
        <thead><tr><th>Vehículo</th><th>Marca / modelo</th><th>VIN / serie</th></tr></thead>
        <tbody>
          <?php foreach ($grupo['vehiculos'] as $v): ?>
          <tr>
            <td><a href="<?= $this->Url->build(['controller' => 'Vehiculos', 'action' => 'view', $v->id]) ?>"><?= h($v->placas ?? 'Sin placas') ?></a></td>
            <td><?= h(trim(($v->marca ?? '') . ' ' . ($v->modelo ?? ''))) ?></td>
            <td class="td-mono"><?= h($v->numero_serie ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <?php if ($grupo['operadores'] !== []): ?>
    <div class="table-wrap" style="border-bottom:1px solid var(--border, #e5e7eb)">
      <table>
        <thead><tr><th>Operador</th><th>Licencia</th></tr></thead>
        <tbody>
          <?php foreach ($grupo['operadores'] as $o): ?>
          <tr>
            <td><a href="<?= $this->Url->build(['controller' => 'Operadores', 'action' => 'edit', $o->id]) ?>"><?= h(trim(($o->nombre ?? '') . ' ' . ($o->apellido_paterno ?? '') . ' ' . ($o->apellido_materno ?? ''))) ?></a></td>
            <td class="td-mono"><?= h($o->num_licencia ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <?php if ($grupo['documentos'] !== []): ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Documento</th>
            <th>Archivo</th>
            <th>Ámbito</th>
            <th>Contexto</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($grupo['documentos'] as $d): ?>
          <tr>
            <td>
              <a href="<?= $this->Url->build(['controller' => $d['edit_controller'], 'action' => 'edit', $d['edit_id']]) ?>">
                <?= h($d['tipo_label']) ?>
              </a>
            </td>
            <td><?= h($d['nombre_archivo'] !== '' ? $d['nombre_archivo'] : '—') ?></td>
            <td><?= h($ambitoLabels[$d['ambito']] ?? $d['ambito']) ?></td>
            <td><?= h($d['contexto']) ?></td>
            <td>
              <?php
              $ruta = trim($d['ruta_archivo']);
              if ($ruta !== '' && $ruta !== '—'):
              ?>
                <a class="btn btn-ghost btn-sm" href="<?= h($this->Url->build(['controller' => 'Archivos', 'action' => 'ver', '?' => ['r' => $ruta]])) ?>" target="_blank" rel="noopener">Ver</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>

<?php endif; ?>
