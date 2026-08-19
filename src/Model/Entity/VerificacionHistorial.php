<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class VerificacionHistorial extends Entity
{
    protected array $_accessible = [
        'empresa_id' => true,
        'operador_id' => true,
        'vehiculo_id' => true,
        'usuario_id' => true,
        'tipo' => true,
        'resultado' => true,
        'detalle' => true,
        'fecha' => true,
        'created_at' => true,
    ];
}
