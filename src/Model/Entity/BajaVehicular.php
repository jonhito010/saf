<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class BajaVehicular extends Entity
{
    protected array $_accessible = ['*' => true];

    public function _getMotivoBajaLabel(): string
    {
        return [
            'siniestro' => 'Siniestro',
            'reemplazo' => 'Reemplazo',
            'venta'     => 'Venta',
            'robo'      => 'Robo',
            'otro'      => 'Otro',
        ][$this->motivo_baja] ?? $this->motivo_baja;
    }
}
