<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, array{label: string, items: list<\App\Model\Entity\TipoDocumento>}> $porAmbito
 * @var bool $puedeImportar
 */
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Tipos de documento</h1>
    <p>Catálogo fijo del sistema — solo lectura. Use importación CSV para actualizar registros.</p>
  </div>
</div>

<?php if ($puedeImportar): ?>
<div class="panel" style="margin-bottom:16px">
  <div class="panel-header"><div class="panel-title">Importar catálogo (CSV)</div></div>
  <div class="empresa-panel-body">
    <?= $this->Form->create(null, [
        'url' => ['action' => 'importar'],
        'type' => 'file',
        'class' => 'empresa-form-grid',
    ]) ?>
    <p style="grid-column:1/-1;color:var(--text2);font-size:13px;margin:0 0 8px">
      Columnas requeridas: <code>codigo,nombre,ambito,categoria,orden</code>.
      Los registros existentes se actualizan por código y ámbito.
    </p>
    <?= $this->Form->control('archivo_csv', [
        'label' => 'Archivo CSV',
        'type' => 'file',
        'accept' => '.csv,text/csv',
        'required' => true,
    ]) ?>
    <div class="empresa-form-actions" style="grid-column:1/-1">
      <button type="submit" class="btn btn-primary">Importar CSV</button>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>
<?php endif; ?>

<?php foreach ($porAmbito as $grupo): ?>
<div class="panel" style="margin-bottom:16px">
  <div class="panel-header">
    <div class="panel-title"><?= h($grupo['label']) ?></div>
    <div class="panel-action"><?= count($grupo['items']) ?> tipo<?= count($grupo['items']) === 1 ? '' : 's' ?></div>
  </div>
  <?php if ($grupo['items'] === []): ?>
    <div style="padding:16px;color:var(--text3)">Sin tipos registrados para este ámbito.</div>
  <?php else: ?>
  <div class="table-wrap table-wrap--stack">
    <table>
      <thead>
        <tr>
          <th>Orden</th>
          <th>Código</th>
          <th>Nombre</th>
          <th>Categoría</th>
          <th>Activo</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($grupo['items'] as $tipo): ?>
          <tr>
            <td data-label="Orden"><?= (int)($tipo->orden ?? 0) ?></td>
            <td data-label="Código"><code><?= h($tipo->codigo) ?></code></td>
            <td data-label="Nombre"><?= h($tipo->nombre) ?></td>
            <td data-label="Categoría"><?= h($tipo->categoria ?? '—') ?></td>
            <td data-label="Activo"><?= (int)($tipo->activo ?? 0) === 1 ? 'Sí' : 'No' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>
