<?php
/**
 * @var \App\View\AppView $this
 * @var iterable $verificaciones
 * @var string $q
 * @var string $resultado
 * @var string $fechaDesde
 * @var string $fechaHasta
 */
$this->Paginator->setPaginated($verificaciones);
?>
<div class="page-header">
  <div class="page-header-left">
    <h1>Historial de verificaciones</h1>
    <p>Registro permanente de verificaciones (fecha, usuario, resultado, empresa y operador).</p>
  </div>
  <div class="page-header-actions">
    <a class="btn btn-primary" href="<?= $this->Url->build(['action' => 'add']) ?>">Nueva verificación</a>
  </div>
</div>

<div class="panel" style="margin-bottom:14px">
  <div class="panel-header"><div class="panel-title">Filtros</div></div>
  <div style="padding:14px">
    <?= $this->Form->create(null, ['type' => 'get', 'class' => 'form-grid']) ?>
      <?= $this->element('filtro_empresa', [
          'empresasFiltro' => $empresasFiltro ?? [],
          'filtroEmpresaId' => $filtroEmpresaId ?? null,
          'vistaConsolidada' => $vistaConsolidada ?? false,
          'alcanceLabel' => $alcanceLabel ?? '',
      ]) ?>
      <div class="form-row" style="display:flex;flex-wrap:wrap;gap:10px;margin-top:10px">
        <input type="search" name="q" value="<?= h($q) ?>" placeholder="Buscar…" class="form-control" style="min-width:180px">
        <select name="resultado" class="form-control">
          <option value="">Todos los resultados</option>
          <?php foreach (['aprobado', 'rechazado', 'condicional', 'pendiente'] as $r): ?>
            <option value="<?= h($r) ?>" <?= $resultado === $r ? 'selected' : '' ?>><?= h(ucfirst($r)) ?></option>
          <?php endforeach; ?>
        </select>
        <input type="date" name="fecha_desde" value="<?= h($fechaDesde) ?>" class="form-control" title="Desde">
        <input type="date" name="fecha_hasta" value="<?= h($fechaHasta) ?>" class="form-control" title="Hasta">
        <button type="submit" class="btn btn-primary">Filtrar</button>
      </div>
    <?= $this->Form->end() ?>
  </div>
</div>

<div class="panel">
  <div class="panel-header"><div class="panel-title">Registros</div></div>
  <div class="table-wrap table-wrap--stack">
    <table>
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Empresa</th>
          <th>Operador</th>
          <th>Tipo</th>
          <th>Resultado</th>
          <th>Usuario</th>
          <th>Detalle</th>
        </tr>
      </thead>
      <tbody>
        <?php
          $empresasMap = $empresasFiltro ?? [];
          $hay = false;
          foreach ($verificaciones as $v):
            $hay = true;
        ?>
          <tr>
            <td data-label="Fecha" class="td-mono"><?= $v->fecha ? h($v->fecha->format('d/m/Y H:i')) : '—' ?></td>
            <td data-label="Empresa"><?= h($empresasMap[(int)$v->empresa_id] ?? ('#' . $v->empresa_id)) ?></td>
            <td data-label="Operador">
              <?php if (!empty($v->operador_id)): ?>
                <a href="<?= $this->Url->build(['controller' => 'Operadores', 'action' => 'edit', $v->operador_id]) ?>">#<?= (int)$v->operador_id ?></a>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td data-label="Tipo"><?= h($v->tipo) ?></td>
            <td data-label="Resultado"><span class="badge badge-navy"><?= h($v->resultado) ?></span></td>
            <td data-label="Usuario">#<?= (int)$v->usuario_id ?></td>
            <td data-label="Detalle"><?= h($v->detalle ?: '—') ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$hay): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--text3);padding:20px">Sin verificaciones registradas.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="paginator" style="padding:12px 16px">
  <ul class="pagination" style="display:flex;gap:8px;list-style:none;margin:0;padding:0;flex-wrap:wrap">
    <?= $this->Paginator->first('«') ?>
    <?= $this->Paginator->prev('‹') ?>
    <?= $this->Paginator->numbers() ?>
    <?= $this->Paginator->next('›') ?>
    <?= $this->Paginator->last('»') ?>
  </ul>
</div>
