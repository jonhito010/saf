<?php
/**
 * Usuarios — listado y empresas asignadas
 * @var \App\View\AppView $this
 * @var list<array<string, mixed>> $usuarios
 * @var bool $esSuper
 * @var array<int, string> $roles
 */

$authUser = $this->request->getSession()->read('Auth.User') ?: ($_SESSION['Auth']['User'] ?? null);
$usuarioSesionId = is_array($authUser) ? (int)($authUser['id'] ?? 0) : 0;
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Accesos</h1>
    <p>Crea cuentas y asigna qué empresa controlará cada usuario.</p>
  </div>
  <div class="page-header-actions">
    <a class="btn btn-primary" href="<?= $this->Url->build(['action' => 'add']) ?>">Nuevo acceso</a>
  </div>
</div>

<div class="panel">
  <div class="panel-header">
    <div class="panel-title">Listado</div>
    <div class="panel-action"><?= count($usuarios) ?> usuario<?= count($usuarios) === 1 ? '' : 's' ?></div>
  </div>

  <div class="table-wrap table-wrap--stack">
    <table>
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Correo</th>
          <th>Rol</th>
          <th>Empresas a cargo</th>
          <th>Estatus</th>
          <th class="actions">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($usuarios === []): ?>
          <tr>
            <td colspan="6" style="color:var(--text3)">Aún no hay usuarios. Crea el primero y asígnale una empresa.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($usuarios as $u): ?>
            <?php
              $nombreCompleto = trim($u['nombre'] . ' ' . $u['apellidos']);
              $empresasTxt = $u['empresas'] !== []
                  ? implode(', ', array_values($u['empresas']))
                  : (($u['rol_id'] === 1) ? 'Todas (superadmin)' : 'Sin empresa');
            ?>
            <tr>
              <td data-label="Nombre"><?= h($nombreCompleto !== '' ? $nombreCompleto : '—') ?></td>
              <td data-label="Correo"><?= h($u['email']) ?></td>
              <td data-label="Rol"><?= h($u['rol_nombre']) ?></td>
              <td data-label="Empresas"><?= h($empresasTxt) ?></td>
              <td data-label="Estatus">
                <?php if ($u['activo']): ?>
                  <span class="badge badge-green">Activo</span>
                <?php else: ?>
                  <span class="badge badge-gray">Inactivo</span>
                <?php endif; ?>
              </td>
              <td data-label="Acciones" style="white-space:nowrap">
                <a class="btn btn-ghost" style="font-size:11px;padding:5px 10px"
                   href="<?= $this->Url->build(['action' => 'edit', $u['id']]) ?>">Editar</a>
                <?php if ((int)$u['id'] !== $usuarioSesionId && ($esSuper || (int)$u['rol_id'] !== 1)): ?>
                  <?php if ($u['activo']): ?>
                    <?= $this->Form->postLink(
                        'Suspender',
                        ['action' => 'suspender', $u['id']],
                        ['class' => 'btn btn-ghost', 'style' => 'font-size:11px;padding:5px 10px', 'confirm' => '¿Suspender este acceso?']
                    ) ?>
                  <?php else: ?>
                    <?= $this->Form->postLink(
                        'Activar',
                        ['action' => 'activar', $u['id']],
                        ['class' => 'btn btn-ghost', 'style' => 'font-size:11px;padding:5px 10px']
                    ) ?>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
