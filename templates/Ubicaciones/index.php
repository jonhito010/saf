<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\ResultSetInterface|\Cake\ORM\ResultSet $ubicaciones
 */
?>

<?php $this->Paginator->setPaginated($ubicaciones); ?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Ubicaciones</h1>
    <p><?= $ubicaciones->count() ?> registros (página actual)</p>
  </div>
  <a class="btn btn-primary" href="<?= $this->Url->build(['controller' => 'Ubicaciones', 'action' => 'add']) ?>">
    <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
    Agregar ubicación
  </a>
</div>

<div class="panel">
  <div class="panel-header">
    <div class="panel-title">Listado</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Tipo</th>
          <th>ID ubicación</th>
          <th>RFC</th>
          <th>Nombre</th>
          <th>C.P.</th>
          <th>Estado</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ubicaciones as $u): ?>
        <tr>
          <td class="td-mono"><?= h($u->id) ?></td>
          <td><span class="tag"><?= h($u->tipo_ubicacion) ?></span></td>
          <td class="td-mono"><?= h($u->id_ubicacion) ?></td>
          <td class="td-mono"><?= h($u->rfc) ?></td>
          <td><?= h($u->nombre) ?></td>
          <td class="td-mono"><?= h($u->codigo_postal) ?></td>
          <td class="td-mono"><?= h($u->estado) ?></td>
          <td style="text-align:right">
            <a class="btn btn-ghost" style="font-size:12px;padding:5px 10px"
               href="<?= $this->Url->build(['controller' => 'Ubicaciones', 'action' => 'edit', $u->id]) ?>">
              Editar
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($ubicaciones->count() === 0): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--text3);padding:24px">No hay ubicaciones registradas.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div style="padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:10px;border-top:1px solid var(--border)">
    <div style="font-size:12px;color:var(--text3)">
      Página <?= (int)$this->Paginator->counter('{{page}}') ?> de <?= (int)$this->Paginator->counter('{{pages}}') ?>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
      <?= $this->Paginator->prev('← Anterior', ['class' => 'btn btn-ghost', 'escape' => false]) ?>
      <?= $this->Paginator->next('Siguiente →', ['class' => 'btn btn-ghost', 'escape' => false]) ?>
    </div>
  </div>
</div>

