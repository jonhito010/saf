<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Comprobante $comprobante
 * @var \Cake\Collection\CollectionInterface|string[] $users
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Comprobantes'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="comprobantes form content">
            <?= $this->Form->create($comprobante) ?>
            <fieldset>
                <legend><?= __('Add Comprobante') ?></legend>
                <?php
                    echo $this->Form->control('nombreEmisor');
                    echo $this->Form->control('rfcEmisor');
                    echo $this->Form->control('nombreReceptor');
                    echo $this->Form->control('rfcReceptor');
                    echo $this->Form->control('uuid');
                    echo $this->Form->control('serie');
                    echo $this->Form->control('folio');
                    echo $this->Form->control('fechaEmision', ['empty' => true]);
                    echo $this->Form->control('subtotal');
                    echo $this->Form->control('total');
                    echo $this->Form->control('descuento');
                    echo $this->Form->control('estatus');
                    echo $this->Form->control('xml');
                    echo $this->Form->control('pdf');
                    echo $this->Form->control('formaPago');
                    echo $this->Form->control('user_id', ['options' => $users, 'empty' => true]);
                    echo $this->Form->control('enviado');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
