<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class AuditoriaCambio extends Entity
{
    protected array $_accessible = [
        'empresa_id' => true,
        'usuario_id' => true,
        'entidad_tipo' => true,
        'entidad_id' => true,
        'campo' => true,
        'valor_anterior' => true,
        'valor_nuevo' => true,
        'ip' => true,
        'created_at' => true,
    ];
}
