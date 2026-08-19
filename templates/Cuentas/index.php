<?php
/**
 * @var \App\View\AppView $this
 * @var list<array<string, mixed>> $cuentas
 */
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Cuentas</h1>
    <p>Cuentas titulares y consultores del sistema</p>
  </div>
  <div class="page-header-actions">
    <a class="btn btn-primary" href="<?= $this->Url->build(['action' => 'crear']) ?>">Nueva cuenta</a>
  </div>
</div>

<div class="panel">
  <div class="panel-header">
    <div class="panel-title">Listado</div>
    <div class="panel-action"><?= count($cuentas) ?> cuenta<?= count($cuentas) === 1 ? '' : 's' ?></div>
  </div>
  <div class="table-wrap table-wrap--stack">
    <table>
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Correo</th>
          <th>Rol</th>
          <th>Empresas</th>
          <th>Estatus</th>
          <th class="actions">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($cuentas === []): ?>
          <tr><td colspan="6" style="color:var(--text3)">No hay cuentas registradas.</td></tr>
        <?php else: ?>
          <?php foreach ($cuentas as $c): ?>
            <?php
              $nombreCompleto = trim($c['nombre'] . ' ' . $c['apellidos']);
              $empresasTxt = $c['empresas'] !== []
                  ? implode(', ', array_values($c['empresas']))
                  : (($c['rol_id'] === 1) ? 'Todas (superadmin)' : 'Sin empresa');
            ?>
            <tr>
              <td data-label="Nombre">
                <?= h($nombreCompleto !== '' ? $nombreCompleto : '—') ?>
                <?php if (!empty($c['es_cuenta'])): ?>
                  <span class="badge badge-navy" style="margin-left:4px">Cuenta</span>
                <?php endif; ?>
              </td>
              <td data-label="Correo"><?= h($c['email']) ?></td>
              <td data-label="Rol"><?= h($c['rol_nombre']) ?></td>
              <td data-label="Empresas"><?= h($empresasTxt) ?></td>
              <td data-label="Estatus">
                <?php if ($c['activo']): ?>
                  <span class="badge badge-green">Activa</span>
                <?php else: ?>
                  <span class="badge badge-gray">Suspendida</span>
                <?php endif; ?>
              </td>
              <td data-label="Acciones" style="white-space:nowrap">
                <?php if ($c['activo']): ?>
                  <?= $this->Form->postLink(
                      'Suspender',
                      ['action' => 'suspender', $c['id']],
                      ['class' => 'btn btn-ghost', 'style' => 'font-size:11px;padding:5px 10px', 'confirm' => '¿Suspender esta cuenta?']
                  ) ?>
                <?php else: ?>
                  <?= $this->Form->postLink(
                      'Activar',
                      ['action' => 'activar', $c['id']],
                      ['class' => 'btn btn-ghost', 'style' => 'font-size:11px;padding:5px 10px']
                  ) ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
