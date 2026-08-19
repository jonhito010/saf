<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $mantenimientos
 */
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Mantenimientos</h1>
    <p><?= count($mantenimientos->toArray()) ?> registros</p>
  </div>
  <a class="btn btn-primary" href="<?= $this->Url->build(['action' => 'add']) ?>">+ Agregar</a>
</div>

<div class="panel">
  <div class="panel-header"><div class="panel-title">Listado</div></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Vehículo</th>
          <th>Fecha</th>
          <th>Tipo</th>
          <th>Descripción</th>
          <th>km</th>
          <th>Costo</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($mantenimientos as $m): ?>
        <tr>
          <td class="td-mono"><?= h($m->vehiculo->placas ?? '—') ?></td>
          <td class="td-mono"><?= $m->fecha ? h($m->fecha->format('d/m/Y')) : '—' ?></td>
          <td><?= h($m->tipo_servicio ?? '—') ?></td>
          <td><?= h($m->descripcion ?? '—') ?></td>
          <td class="td-mono"><?= $m->kilometraje ? number_format((float)$m->kilometraje) : '—' ?></td>
          <td class="td-mono"><?= $m->costo ? '$' . number_format((float)$m->costo, 2) : '—' ?></td>
          <td><a class="panel-action" style="font-size:12px" href="<?= $this->Url->build(['action' => 'edit', $m->id]) ?>">Editar →</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($mantenimientos->toArray())): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--text3);padding:20px">Sin registros.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

