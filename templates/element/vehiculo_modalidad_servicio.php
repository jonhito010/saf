<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Vehiculo $vehiculo
 * @var array<string, string> $modalidadOptions
 * @var array<string, string> $tipoServicioOptions
 */
$modalidadActual = (string)($vehiculo->modalidad ?? '');
$mostrarTipoServicio = $modalidadActual === 'AUTOTRANSPORTE FEDERAL';
?>
<?= $this->Form->control('modalidad', [
    'label' => 'Modalidad',
    'options' => $modalidadOptions,
    'empty' => '-- Modalidad --',
    'required' => false,
    'id' => 'veh-modalidad',
]) ?>
<div id="veh-tipo-servicio-wrap" style="<?= $mostrarTipoServicio ? '' : 'display:none' ?>">
  <?= $this->Form->control('tipo_servicio', [
      'label' => 'Tipo de servicio',
      'options' => $tipoServicioOptions,
      'empty' => '-- Tipo de servicio --',
      'required' => false,
      'id' => 'veh-tipo-servicio',
  ]) ?>
</div>
