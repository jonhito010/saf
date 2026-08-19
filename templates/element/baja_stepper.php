<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\BajaVehicular $baja
 * @var array $pasos
 */

$estatusTramiteMap = [
    'iniciado'         => 1,
    'pendiente_placas' => 2,
    'placas_devueltas' => 3,
    'tramite_baja'     => 4,
    'completado'       => 5,
];
$pasoNum = $estatusTramiteMap[$baja->estatus_tramite] ?? 1;
?>
<div class="stepper" style="margin-bottom:20px">
  <?php foreach ($pasos as $num => $paso):
      $isDone   = $num < $pasoNum;
      $isActive = $num === $pasoNum;
      $class    = $isDone ? 'step done' : ($isActive ? 'step active' : 'step');
  ?>
  <div class="<?= $class ?>">
    <div class="step-circle"><?= $isDone ? '✓' : $num ?></div>
    <div class="step-label"><?= nl2br(h($paso['label'])) ?></div>
  </div>
  <?php if ($num < count($pasos)): ?>
  <div class="step-connector" style="<?= $isDone ? 'background:var(--teal)' : '' ?>"></div>
  <?php endif; ?>
  <?php endforeach; ?>
</div>
