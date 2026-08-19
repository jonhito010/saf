<?php
/**
 * @var \App\View\AppView $this
 * @var list<array<string, mixed>> $filas
 * @var array<string, mixed> $filtros
 * @var array<int, string> $operadoresList
 * @var array<string, string> $tiposDocumento
 * @var array<int, string> $empresasFiltro
 * @var int|null $filtroEmpresaId
 * @var bool $vistaConsolidada
 * @var string $alcanceLabel
 * @var array<string, string> $filtroPreserve
 */

$total = count($filas);
$queryExport = array_filter([
    'empresa_id' => $filtros['empresa_id'] ?? null,
    'operador_id' => $filtros['operador_id'] ?? null,
    'tipo_documento' => $filtros['tipo_documento'] ?? '',
    'estatus' => $filtros['estatus'] ?? '',
    'fecha_desde' => $filtros['fecha_desde'] ?? '',
    'fecha_hasta' => $filtros['fecha_hasta'] ?? '',
    'q' => $filtros['q'] ?? '',
], static fn($v) => $v !== null && $v !== '');

$badgeEstatus = static function (string $estatus): string {
    return match ($estatus) {
        'vencido' => '<span class="badge badge-red">Vencido</span>',
        'por_vencer' => '<span class="badge badge-amber">Por vencer</span>',
        'vigente' => '<span class="badge badge-green">Vigente</span>',
        default => '<span class="badge badge-gray">' . h($estatus) . '</span>',
    };
};
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Reportes</h1>
    <p>Documentos unificados de empresa, vehículo y operador · <?= $total ?> registro<?= $total === 1 ? '' : 's' ?></p>
  </div>
  <div class="page-header-actions">
    <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'exportarCsv', '?' => $queryExport]) ?>">Exportar CSV</a>
    <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'exportarXlsx', '?' => $queryExport]) ?>">Exportar XLSX</a>
    <a class="btn btn-primary" href="<?= $this->Url->build(['action' => 'exportarCompleto', '?' => $queryExport]) ?>">Exportar completo</a>
  </div>
</div>

<?= $this->element('filtro_empresa', compact('empresasFiltro', 'filtroEmpresaId', 'vistaConsolidada', 'alcanceLabel', 'filtroPreserve')) ?>

<div class="panel" style="margin-bottom:16px">
  <div class="panel-header"><div class="panel-title">Filtros</div></div>
  <div class="empresa-panel-body">
    <?= $this->Form->create(null, ['type' => 'get', 'url' => ['action' => 'index']]) ?>
    <?php if ($filtroEmpresaId !== null): ?>
      <input type="hidden" name="empresa_id" value="<?= (int)$filtroEmpresaId ?>">
    <?php endif; ?>
    <div class="empresa-form-grid">
      <?= $this->Form->control('operador_id', [
          'label' => 'Operador',
          'type' => 'select',
          'options' => ['' => 'Todos'] + $operadoresList,
          'value' => $filtros['operador_id'] ?? '',
          'empty' => false,
      ]) ?>
      <?= $this->Form->control('tipo_documento', [
          'label' => 'Tipo documento',
          'type' => 'select',
          'options' => ['' => 'Todos'] + $tiposDocumento,
          'value' => $filtros['tipo_documento'] ?? '',
          'empty' => false,
      ]) ?>
      <?= $this->Form->control('estatus', [
          'label' => 'Estatus',
          'type' => 'select',
          'options' => [
              '' => 'Todos',
              'activo' => 'Entidad activa',
              'vigente' => 'Vigente',
              'por_vencer' => 'Por vencer',
              'vencido' => 'Vencido',
          ],
          'value' => $filtros['estatus'] ?? '',
          'empty' => false,
      ]) ?>
      <?= $this->Form->control('fecha_desde', [
          'label' => 'Vence desde',
          'type' => 'date',
          'value' => $filtros['fecha_desde'] ?? '',
      ]) ?>
      <?= $this->Form->control('fecha_hasta', [
          'label' => 'Vence hasta',
          'type' => 'date',
          'value' => $filtros['fecha_hasta'] ?? '',
      ]) ?>
      <?= $this->Form->control('q', [
          'label' => 'Buscar',
          'value' => $filtros['q'] ?? '',
          'placeholder' => 'Empresa, sujeto, tipo…',
      ]) ?>
    </div>
    <div class="empresa-form-actions">
      <button type="submit" class="btn btn-primary">Filtrar</button>
      <a class="btn btn-ghost" href="<?= $this->Url->build(['action' => 'index']) ?>">Limpiar</a>
    </div>
    <?= $this->Form->end() ?>
  </div>
</div>

<div class="panel">
  <div class="panel-header">
    <div class="panel-title">Resultados</div>
    <div class="panel-action"><?= $total ?> fila<?= $total === 1 ? '' : 's' ?></div>
  </div>
  <div class="table-wrap table-wrap--stack">
    <table>
      <thead>
        <tr>
          <th>Empresa</th>
          <th>Sujeto</th>
          <th>Ámbito</th>
          <th>Tipo documento</th>
          <th>Vencimiento</th>
          <th>Días</th>
          <th>Estatus</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($filas === []): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--text3);padding:20px">No hay documentos con los filtros seleccionados.</td></tr>
        <?php else: ?>
          <?php foreach ($filas as $f): ?>
            <?php
              $fv = $f['fecha_vencimiento'] ?? null;
              $dias = $f['dias_restantes'] ?? null;
            ?>
            <tr>
              <td data-label="Empresa"><?= h($f['empresa'] ?? '') ?></td>
              <td data-label="Sujeto"><strong><?= h($f['sujeto'] ?? '') ?></strong></td>
              <td data-label="Ámbito"><?= h($f['ambito'] ?? '') ?></td>
              <td data-label="Tipo"><?= h($f['tipo_label'] ?? '') ?></td>
              <td class="td-mono" data-label="Vence"><?= $fv ? h(date('d/m/Y', strtotime((string)$fv))) : '—' ?></td>
              <td data-label="Días"><?= $dias !== null ? (($dias > 0) ? (int)$dias : 'Vencido') : '—' ?></td>
              <td data-label="Estatus"><?= $badgeEstatus((string)($f['estatus'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
