<?php
/**
 * Clientes — listado
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Cliente> $clientes
 */
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Clientes</h1>
    <p>Catálogo de receptores para CFDI.</p>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <a class="btn btn-primary" href="<?= $this->Url->build(['action' => 'add']) ?>">Nuevo cliente</a>
  </div>
</div>

<div class="panel">
  <div class="panel-header">
    <div class="panel-title">Listado</div>
    <div class="panel-action"><?= count($clientes) ?> registro<?= count($clientes) === 1 ? '' : 's' ?></div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Nombre</th>
          <th>RFC</th>
          <th>Régimen</th>
          <th>Uso CFDI</th>
          <th>Correo</th>
          <th class="actions">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($clientes)): ?>
          <tr>
            <td colspan="6" style="color:var(--text3)">Sin registros.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($clientes as $c): ?>
            <tr>
              <td><?= h($c->nombre ?? '—') ?></td>
              <td class="td-mono"><?= h($c->rfc ?? '—') ?></td>
              <td class="td-mono"><?= h($c->regimenFiscal ?? '—') ?></td>
              <td class="td-mono"><?= h($c->usoCFDI ?? '—') ?></td>
              <td><?= h($c->correo ?? '—') ?></td>
              <td style="white-space:nowrap">
                <a class="btn btn-ghost" style="font-size:11px;padding:5px 10px" href="<?= $this->Url->build(['action' => 'view', $c->id]) ?>">Ver</a>
                <a class="btn btn-ghost" style="font-size:11px;padding:5px 10px" href="<?= $this->Url->build(['action' => 'edit', $c->id]) ?>">Editar</a>
                <?= $this->Form->postLink(
                    'Eliminar',
                    ['action' => 'delete', $c->id],
                    [
                        'confirm' => '¿Eliminar este cliente? (se marcará como eliminado)',
                        'class' => 'btn btn-ghost',
                        'style' => 'font-size:11px;padding:5px 10px;border-color:rgba(176,30,30,.35);color:var(--red)',
                    ]
                ) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

