<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SistemaLog extends Entity
{
    protected array $_accessible = [
        'usuario_id' => true,
        'empresa_id' => true,
        'accion' => true,
        'modulo' => true,
        'descripcion' => true,
        'detalle_json' => true,
        'ip' => true,
        'user_agent' => true,
        'nivel' => true,
        'created_at' => true,
    ];
}
