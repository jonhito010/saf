<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, mixed> $formData
 * @var array<int, string> $empresasOptions
 */

$empresaIds = array_map('intval', $formData['empresa_ids'] ?? []);
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Nueva cuenta</h1>
    <p>Crea una cuenta consultor titular (es_cuenta=1)</p>
  </div>
  <div class="page-header-actions">
    <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index']) ?>">Volver</a>
  </div>
</div>

<div class="panel">
  <div class="panel-header"><div class="panel-title">Datos de la cuenta</div></div>
  <div class="empresa-panel-body">
    <?= $this->Form->create(null, ['autocomplete' => 'off']) ?>
    <div class="empresa-form-grid">
      <?= $this->Form->control('nombre', [
          'label' => 'Nombre *',
          'required' => true,
          'value' => $formData['nombre'] ?? '',
      ]) ?>
      <?= $this->Form->control('apellidos', [
          'label' => 'Apellidos',
          'value' => $formData['apellidos'] ?? '',
      ]) ?>
      <?= $this->Form->control('email', [
          'label' => 'Correo *',
          'type' => 'email',
          'required' => true,
          'value' => $formData['email'] ?? '',
      ]) ?>
      <?= $this->Form->control('password', [
          'label' => 'Contraseña *',
          'type' => 'password',
          'required' => true,
          'autocomplete' => 'new-password',
      ]) ?>
      <?= $this->Form->control('password_confirm', [
          'label' => 'Confirmar contraseña *',
          'type' => 'password',
          'required' => true,
          'autocomplete' => 'new-password',
      ]) ?>
      <div class="field-span-2">
        <?= $this->Form->control('activo', [
            'label' => 'Cuenta activa',
            'type' => 'checkbox',
            'checked' => !empty($formData['activo']),
        ]) ?>
      </div>
    </div>

    <div class="usuarios-empresas-block">
      <div class="usuarios-empresas-title">Empresas asignadas *</div>
      <?php if ($empresasOptions === []): ?>
        <p style="color:var(--amber);margin-top:8px">No hay empresas disponibles.</p>
      <?php else: ?>
        <div class="usuarios-empresas-grid">
          <?php foreach ($empresasOptions as $empresaId => $nombreEmpresa): ?>
            <label class="usuarios-empresa-check">
              <input type="checkbox" name="empresa_ids[]" value="<?= (int)$empresaId ?>"
                <?= in_array((int)$empresaId, $empresaIds, true) ? 'checked' : '' ?>>
              <span><?= h($nombreEmpresa) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="empresa-form-actions">
      <button class="btn btn-primary" type="submit" <?= $empresasOptions === [] ? 'disabled' : '' ?>>Crear cuenta</button>
      <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index']) ?>">Cancelar</a>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>
