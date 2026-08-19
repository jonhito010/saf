<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, mixed> $form
 * @var array<int, string> $empresas
 * @var array<int, string> $operadoresOpts
 * @var array<int, string> $vehiculosOpts
 */
?>
<div class="page-header">
  <div class="page-header-left">
    <h1>Nueva verificación</h1>
    <p>El registro queda permanente en el historial.</p>
  </div>
  <div class="page-header-actions">
    <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index']) ?>">← Volver</a>
  </div>
</div>

<div class="panel">
  <div class="panel-header"><div class="panel-title">Datos</div></div>
  <div style="padding:16px">
    <?= $this->Form->create(null) ?>
      <div class="form-group">
        <label>Empresa <span class="req">*</span></label>
        <?= $this->Form->select('empresa_id', $empresas, ['value' => $form['empresa_id'], 'required' => true, 'class' => 'form-control']) ?>
      </div>
      <div class="form-group">
        <label>Operador</label>
        <?= $this->Form->select('operador_id', ['' => '— Opcional —'] + $operadoresOpts, ['value' => $form['operador_id'], 'class' => 'form-control']) ?>
      </div>
      <div class="form-group">
        <label>Vehículo</label>
        <?= $this->Form->select('vehiculo_id', ['' => '— Opcional —'] + $vehiculosOpts, ['value' => $form['vehiculo_id'], 'class' => 'form-control']) ?>
      </div>
      <div class="form-group">
        <label>Tipo <span class="req">*</span></label>
        <input type="text" name="tipo" value="<?= h($form['tipo']) ?>" required class="form-control" maxlength="60">
      </div>
      <div class="form-group">
        <label>Resultado <span class="req">*</span></label>
        <select name="resultado" required class="form-control">
          <?php foreach (['aprobado', 'rechazado', 'condicional', 'pendiente'] as $r): ?>
            <option value="<?= h($r) ?>" <?= ($form['resultado'] ?? '') === $r ? 'selected' : '' ?>><?= h(ucfirst($r)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Fecha <span class="req">*</span></label>
        <input type="datetime-local" name="fecha" value="<?= h($form['fecha']) ?>" required class="form-control">
      </div>
      <div class="form-group">
        <label>Detalle</label>
        <textarea name="detalle" class="form-control" rows="3"><?= h($form['detalle']) ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary">Guardar en historial</button>
    <?= $this->Form->end() ?>
  </div>
</div>
