<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $documentos
 */
use App\Model\Table\DocumentosOperadorTable;
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Documentos de operador</h1>
    <p><?= count($documentos->toArray()) ?> registros</p>
  </div>
  <a class="btn btn-primary" href="<?= $this->Url->build(['action' => 'add']) ?>">+ Agregar</a>
</div>

<div class="panel">
  <div class="panel-header"><div class="panel-title">Listado</div></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Operador</th>
          <th>Tipo</th>
          <th>Estatus</th>
          <th>Vencimiento</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($documentos as $d): ?>
        <tr>
          <td><?= h($d->operador->nombre_completo ?? '—') ?></td>
          <td><?= h(DocumentosOperadorTable::tipoLabel($d->tipo_documento)) ?></td>
          <td><?= h($d->estatus ?? '—') ?></td>
          <td class="td-mono"><?= $d->fecha_vencimiento ? h($d->fecha_vencimiento->format('d/m/Y')) : '—' ?></td>
          <td><a class="panel-action" style="font-size:12px" href="<?= $this->Url->build(['action' => 'edit', $d->id]) ?>">Editar →</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($documentos->toArray())): ?>
        <tr><td colspan="5" style="text-align:center;color:var(--text3);padding:20px">Sin registros.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

