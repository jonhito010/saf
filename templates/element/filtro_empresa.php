<?php
/**
 * Selector de alcance multiempresa (todas / una).
 *
 * @var \App\View\AppView $this
 * @var array<int, string> $empresasFiltro
 * @var int|null $filtroEmpresaId
 * @var bool $vistaConsolidada
 * @var string $alcanceLabel
 * @var array<string, mixed> $filtroPreserve Query params a conservar (q, estatus, etc.)
 */
$empresasFiltro = $empresasFiltro ?? [];
$filtroEmpresaId = $filtroEmpresaId ?? null;
$vistaConsolidada = $vistaConsolidada ?? false;
$alcanceLabel = $alcanceLabel ?? '';
$filtroPreserve = $filtroPreserve ?? [];

if (count($empresasFiltro) < 2) {
    return;
}

// action="" = misma URL del navegador (conserva /saf/ y no pierde la sesión).
// No usar getRequestTarget(): tras rewrite Apache puede quedar en "/" y sacar al usuario del sistema.
?>
<form class="alcance-empresa" method="get" action="">
  <?php foreach ($filtroPreserve as $key => $val): ?>
    <?php if ($val === null || $val === '' || $key === 'empresa_id') {
        continue;
    } ?>
    <input type="hidden" name="<?= h((string)$key) ?>" value="<?= h((string)$val) ?>">
  <?php endforeach; ?>
  <label class="alcance-empresa-label" for="filtro-empresa-id">Empresa</label>
  <select name="empresa_id" id="filtro-empresa-id" class="alcance-empresa-select" onchange="this.form.submit()" aria-label="Filtrar por empresa">
    <option value="todas"<?= $filtroEmpresaId === null ? ' selected' : '' ?>>Todas las empresas</option>
    <?php foreach ($empresasFiltro as $id => $nombre): ?>
      <option value="<?= (int)$id ?>"<?= $filtroEmpresaId === (int)$id ? ' selected' : '' ?>><?= h($nombre) ?></option>
    <?php endforeach; ?>
  </select>
  <?php if ($alcanceLabel !== ''): ?>
    <span class="alcance-empresa-hint"><?= $vistaConsolidada ? 'Vista consolidada' : 'Filtrado' ?>: <?= h($alcanceLabel) ?></span>
  <?php endif; ?>
</form>
