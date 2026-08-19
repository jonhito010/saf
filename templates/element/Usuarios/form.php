<?php
/**
 * Formulario crear/editar usuario con asignación de empresas
 * @var \App\View\AppView $this
 * @var array<string, mixed> $formData
 * @var array<int, string> $empresasOptions
 * @var array<int, string> $rolesOptions
 * @var bool $isEdit
 */
$empresaIds = array_map('intval', $formData['empresa_ids'] ?? []);
?>

<div class="page-header">
  <div class="page-header-left">
    <h1><?= $isEdit ? 'Editar acceso' : 'Nuevo acceso' ?></h1>
    <p>Asigna la empresa (o empresas) que este usuario controlará en el sistema.</p>
  </div>
  <div class="page-header-actions">
    <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index']) ?>">Volver</a>
  </div>
</div>

<div class="panel">
  <div class="panel-header">
    <div class="panel-title">Datos de acceso</div>
  </div>
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
          'required' => false,
          'value' => $formData['apellidos'] ?? '',
      ]) ?>
      <?= $this->Form->control('email', [
          'label' => 'Correo *',
          'type' => 'email',
          'required' => true,
          'value' => $formData['email'] ?? '',
      ]) ?>
      <?= $this->Form->control('rol_id', [
          'label' => 'Rol *',
          'type' => 'select',
          'options' => $rolesOptions,
          'value' => $formData['rol_id'] ?? 2,
          'required' => true,
      ]) ?>
      <?= $this->Form->control('password', [
          'label' => $isEdit ? 'Nueva contraseña (opcional)' : 'Contraseña *',
          'type' => 'password',
          'required' => !$isEdit,
          'value' => '',
          'autocomplete' => 'new-password',
      ]) ?>
      <?= $this->Form->control('password_confirm', [
          'label' => $isEdit ? 'Confirmar nueva contraseña' : 'Confirmar contraseña *',
          'type' => 'password',
          'required' => !$isEdit,
          'value' => '',
          'autocomplete' => 'new-password',
      ]) ?>
      <div class="field-span-2">
        <?= $this->Form->control('activo', [
            'label' => 'Usuario activo',
            'type' => 'checkbox',
            'checked' => !empty($formData['activo']),
        ]) ?>
      </div>
    </div>

    <div class="usuarios-empresas-block">
      <div class="usuarios-empresas-title">Empresas a cargo *</div>
      <p class="usuarios-empresas-hint">
        El usuario solo podrá ver y administrar el cumplimiento de las empresas marcadas.
        <?php if ($empresasOptions === []): ?>
          Registra al menos una empresa antes de asignar usuarios.
        <?php endif; ?>
      </p>

      <?php if ($empresasOptions === []): ?>
        <p style="color:var(--amber);margin-top:8px">No hay empresas disponibles para asignar.</p>
      <?php else: ?>
        <div class="usuarios-empresas-grid">
          <?php foreach ($empresasOptions as $empresaId => $nombreEmpresa): ?>
            <label class="usuarios-empresa-check">
              <input
                type="checkbox"
                name="empresa_ids[]"
                value="<?= (int)$empresaId ?>"
                <?= in_array((int)$empresaId, $empresaIds, true) ? 'checked' : '' ?>
              >
              <span><?= h($nombreEmpresa) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="empresa-form-actions">
      <button class="btn btn-primary" type="submit" <?= $empresasOptions === [] ? 'disabled' : '' ?>>
        <?= $isEdit ? 'Guardar cambios' : 'Crear acceso' ?>
      </button>
      <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index']) ?>">Cancelar</a>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>
